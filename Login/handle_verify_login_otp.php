<?php
session_start();
// This script will always return a JSON response for the JavaScript
header('Content-Type: application/json');

require_once '../config/db_connect.php';

// Get data from the JavaScript fetch() request
$username_or_email = $_POST['username'] ?? '';
$otp_submitted = $_POST['otp'] ?? '';

// --- 1. Basic Validation ---
if (empty($username_or_email) || empty($otp_submitted)) {
    echo json_encode(['status' => 'error', 'message' => 'OTP is required.']);
    exit();
}

try {
    // --- 2. Get User Details Again ---
    // We need the user's ID to check the OTP
    $stmt_user = $conn->prepare("SELECT id, username, user_type, is_email_verified FROM users WHERE username = ? OR email = ?");
    $stmt_user->bind_param("ss", $username_or_email, $username_or_email);
    $stmt_user->execute();
    $user_result = $stmt_user->get_result();
    if ($user_result->num_rows !== 1) {
        throw new Exception("User not found during OTP check.");
    }
    $user = $user_result->fetch_assoc();
    $stmt_user->close();

    // --- 3. Verify the OTP against the `login_2fa_codes` table ---
    $stmt_otp = $conn->prepare("SELECT otp_hash FROM login_2fa_codes WHERE user_id = ? AND expires_at > NOW()");
    $stmt_otp->bind_param("i", $user['id']);
    $stmt_otp->execute();
    $result_otp = $stmt_otp->get_result();

    if ($result_otp->num_rows !== 1 || !password_verify($otp_submitted, $result_otp->fetch_assoc()['otp_hash'])) {
        echo json_encode(['status' => 'error', 'message' => 'The OTP you entered is invalid or has expired. Please try logging in again.']);
        exit();
    }

    // --- 4. OTP IS CORRECT: Complete the login process ---
    // Start a database transaction for the final session setup
    $conn->begin_transaction();
    
    // Clean up the used OTP
    $conn->prepare("DELETE FROM login_2fa_codes WHERE user_id = ?")->bind_param("i", $user['id'])->execute();

    // This is the same session management logic from your stable handle_login.php
    $stmt_sub = $conn->prepare("SELECT p.max_devices FROM user_subscriptions us JOIN plans p ON us.plan_id = p.plan_id WHERE us.user_id = ? AND us.status = 'active' AND us.end_date > NOW() LIMIT 1");
    $stmt_sub->bind_param("i", $user['id']);
    $stmt_sub->execute();
    $sub_result = $stmt_sub->get_result();
    $max_devices = ($sub_result->num_rows > 0) ? (int)$sub_result->fetch_assoc()['max_devices'] : 1;
    $stmt_sub->close();

    $stmt_count = $conn->prepare("SELECT session_id FROM active_sessions WHERE user_id = ? ORDER BY last_seen ASC");
    $stmt_count->bind_param("i", $user['id']);
    $stmt_count->execute();
    $active_sessions_result = $stmt_count->get_result();
    if ($active_sessions_result->num_rows >= $max_devices) {
        $oldest_session = $active_sessions_result->fetch_assoc();
        $conn->prepare("DELETE FROM active_sessions WHERE session_id = ?")->bind_param("i", $oldest_session['session_id'])->execute();
    }
    $stmt_count->close();
    
    $session_token = bin2hex(random_bytes(32));
    $stmt_insert_session = $conn->prepare("INSERT INTO active_sessions (user_id, session_token, ip_address, user_agent) VALUES (?, ?, ?, ?)");
    $stmt_insert_session->bind_param("isss", $user['id'], $session_token, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
    $stmt_insert_session->execute();
    $stmt_insert_session->close();

    $conn->commit();
    // --- End Transaction ---

    // Set all necessary session variables
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_type'] = $user['user_type'];
    $_SESSION['is_email_verified'] = $user['is_email_verified'];
    $_SESSION['session_token'] = $session_token;

    // Determine the correct redirect URL based on user role
    $redirect_url = '../Student/home.php'; // Default redirect
    switch ($user['user_type']) {
        case 'master_admin': $redirect_url = '../MasterAdmin/dashboard.php'; break;
        case 'admin': $redirect_url = '../Admin/dashboard.php'; break;
        case 'educator': $redirect_url = '../Educator/dashboard.php'; break;
        case 'support': $redirect_url = '../Support/dashboard.php'; break;
    }

    // Tell the front-end JavaScript to redirect the user
    echo json_encode(['status' => 'success', 'redirect_url' => $redirect_url]);
    exit();

} catch (Exception $e) {
    if ($conn->in_transaction) { $conn->rollback(); }
    error_log("Login 2FA Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'A system error occurred.']);
    exit();
}
?>