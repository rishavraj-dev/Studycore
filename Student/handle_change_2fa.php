<?php
session_start();
require_once '../config/db_connect.php';

// Security: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../Login/login.php');
    exit();
}
// Ensure it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: my_profile.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? ''; // 'enable' or 'disable'

// Determine the new value for the database
if ($action === 'enable') {
    $new_status = 1;
    $message = 'Two-Factor Authentication has been enabled.';
} elseif ($action === 'disable') {
    $new_status = 0;
    $message = 'Two-Factor Authentication has been disabled.';
} else {
    // Invalid action
    header('Location: my_profile.php?err=' . urlencode('Invalid action.'));
    exit();
}

try {
    // Update the user's 2FA status in the database
    $stmt = $conn->prepare("UPDATE users SET is_2fa_enabled = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_status, $user_id);
    
    if ($stmt->execute()) {
        // Success
        header('Location: my_profile.php?msg=' . urlencode($message));
    } else {
        throw new Exception("Database update failed.");
    }
    $stmt->close();

} catch (Exception $e) {
    error_log("2FA change error: " . $e->getMessage());
    header('Location: my_profile.php?err=' . urlencode('A system error occurred.'));
    exit();
}
?>