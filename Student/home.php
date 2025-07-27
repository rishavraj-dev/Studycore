<?php
session_start();
require_once '../config/db_connect.php';

// --- Security Guard ---
// 1. Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../Login/login.php?error=' . urlencode('You must be logged in to access the dashboard.'));
    exit();
}

// 2. Validate the session token against the database for security
// This prevents session hijacking.
try {
    $stmt_session = $conn->prepare("SELECT session_id FROM active_sessions WHERE user_id = ? AND session_token = ?");
    $stmt_session->bind_param("is", $_SESSION['user_id'], $_SESSION['session_token']);
    $stmt_session->execute();
    if ($stmt_session->get_result()->num_rows === 0) {
        session_unset(); session_destroy();
        header('Location: ../Login/login.php?error=' . urlencode('Your session is invalid. Please log in again.'));
        exit();
    }
    $stmt_session->close();
} catch (Exception $e) { /* Handle DB error */ }


// --- Fetch User Data for Display ---
$user_id = $_SESSION['user_id'];
$full_name = 'User'; // Default name
$first_initial = 'U'; // Default initial

try {
    $stmt_user = $conn->prepare("SELECT name FROM users WHERE id = ?");
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $result = $stmt_user->get_result();
    if ($user_row = $result->fetch_assoc()) {
        $full_name = htmlspecialchars($user_row['name']);
        $first_initial = strtoupper(substr($full_name, 0, 1));
    }
    $stmt_user->close();
} catch (Exception $e) { /* Handle DB error */ }

// Check for unverified email status
$is_email_verified = $_SESSION['is_email_verified'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - StudyCore</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Link to the new dashboard stylesheet -->
    <link rel="stylesheet" href="../CSS/student_style.css">
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <a href="#" class="header-logo">StudyCore</a>
            
            <div class="profile-section">
                <!-- This is the button that opens the dropdown -->
                <button id="profileButton" class="profile-button">
                    <div class="profile-avatar"><?php echo $first_initial; ?></div>
                    <span class="profile-name"><?php echo $full_name; ?></span>
                </button>

                <!-- This is the hidden dropdown menu -->
                <div id="profileDropdown" class="profile-dropdown">
                    <a href="dashboard.php">Dashboard</a>
                    <a href="manage_plan.php">Manage Plan</a>
                    <a href="profile_settings.php">Profile Settings</a>
                    <div class="dropdown-divider"></div>
                    <a href="../Login/logout.php">Logout</a>
                </div>
            </div>
        </header>

        <main class="main-content">
            <?php if ($is_email_verified == 0): ?>
                <div style="background-color: #fef3c7; color: #b45309; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; font-weight: 500;">
                    Your email is not verified. Please check your inbox for a verification link or <a href="#">resend the link</a> to access all features.
                </div>
            <?php endif; ?>
            
            <div class="welcome-card">
                <h1>Welcome back, <?php echo explode(' ', $full_name)[0]; ?>!</h1>
                <p>This is your student dashboard. From here you can access your courses, view your progress, and manage your account.</p>
                <!-- All future dashboard content like course cards, progress bars, etc., will go here. -->
            </div>
        </main>
    </div>

    <!-- Link to the new dashboard JavaScript -->
    <script src="../JS/student_dashboard.js"></script>
</body>
</html>