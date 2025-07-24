<?php
// Start a secure session
session_start();

// Include the database connection file
require_once '../config/db_connect.php';

// Redirect if not a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

// ===============================================
// 1. AUTHENTICATE USER
// ===============================================
$username_or_email = $_POST['username'];
$password = $_POST['password'];

if (empty($username_or_email) || empty($password)) {
    header('Location: login.php?error=' . urlencode('Please enter username and password.'));
    exit();
}

try {
    // **MODIFIED**: Fetch the user_type column as well
    $stmt = $conn->prepare("SELECT id, username, password, user_type FROM users WHERE username = ? OR email = ? LIMIT 1");
    $stmt->bind_param("ss", $username_or_email, $username_or_email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        // Verify the password
        if (!password_verify($password, $user['password'])) {
            header('Location: login.php?error=' . urlencode('Invalid username or password.'));
            exit();
        }
    } else {
        header('Location: login.php?error=' . urlencode('Invalid username or password.'));
        exit();
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Login auth failed: " . $e->getMessage());
    header('Location: login.php?error=' . urlencode('A database error occurred.'));
    exit();
}

// At this point, the user is authenticated.

// ===============================================
// 2. MANAGE SESSIONS BASED ON SUBSCRIPTION
// ===============================================
$conn->begin_transaction();
try {
    // This section remains the same as before...
    $stmt_sub = $conn->prepare("
        SELECT p.max_devices
        FROM user_subscriptions us
        JOIN plans p ON us.plan_id = p.plan_id
        WHERE us.user_id = ? AND us.status = 'active' AND us.end_date > NOW()
        ORDER BY us.end_date DESC
        LIMIT 1
    ");
    $stmt_sub->bind_param("i", $user['id']);
    $stmt_sub->execute();
    $sub_result = $stmt_sub->get_result();
    
    // --- FIX: Allow all users to log in, even without subscription ---
    if ($sub_result->num_rows === 0) {
        $max_devices = 2; // Default device limit for users without subscription
    } else {
        $subscription = $sub_result->fetch_assoc();
        $max_devices = (int)$subscription['max_devices'];
    }
    $stmt_sub->close();

    // ... The rest of the session management logic remains the same ...
    $stmt_count = $conn->prepare("SELECT session_id, last_seen FROM active_sessions WHERE user_id = ? ORDER BY last_seen ASC");
    $stmt_count->bind_param("i", $user['id']);
    $stmt_count->execute();
    $active_sessions_result = $stmt_count->get_result();
    $current_device_count = $active_sessions_result->num_rows;
    $stmt_count->close();

    if ($current_device_count >= $max_devices) {
        $oldest_session = $active_sessions_result->fetch_assoc();
        $stmt_delete = $conn->prepare("DELETE FROM active_sessions WHERE session_id = ?");
        $stmt_delete->bind_param("i", $oldest_session['session_id']);
        $stmt_delete->execute();
        $stmt_delete->close();
    }

    $session_token = bin2hex(random_bytes(32));
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    $stmt_insert = $conn->prepare("INSERT INTO active_sessions (user_id, session_token, ip_address, user_agent) VALUES (?, ?, ?, ?)");
    $stmt_insert->bind_param("isss", $user['id'], $session_token, $ip_address, $user_agent);
    $stmt_insert->execute();
    $stmt_insert->close();
    
    $conn->commit();

    // ===============================================
    // 3. SET PHP SESSION AND REDIRECT BASED ON ROLE
    // ===============================================
    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['session_token'] = $session_token;
    // **MODIFIED**: Store the user's role in the session
    $_SESSION['user_type'] = $user['user_type'];

    // **MODIFIED**: Redirect based on the user's role
    switch ($user['user_type']) {
        case 'master_admin':
            header('Location: ../MasterAdmin/dashboard.php');
            break;
        case 'admin':
            header('Location: ../Admin/dashboard.php');
            break;
        case 'educator':
            header('Location: ../Educator/dashboard.php'); // For future use
            break;
        case 'support':
            header('Location: ../Support/dashboard.php'); // For future use
            break;
        case 'student':
        default:
            header('Location: ../Student/home.php');
            break;
    }
    exit();

} catch (Exception $e) {
    $conn->rollback();
    error_log("Session management/login failed: " . $e->getMessage());
    header('Location: login.php?error=' . urlencode('A system error occurred. Please try again.'));
    exit();
}
?>