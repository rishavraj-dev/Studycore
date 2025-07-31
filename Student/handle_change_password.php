<?php
session_start();
require_once '../config/db_connect.php';

// --- 1. Security & Validation ---
if (!isset($_SESSION['user_id'])) {
    header('Location: ../Login/login.php');
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profile_settings.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// --- 2. Process Form Data ---
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_new_password = $_POST['confirm_new_password'] ?? '';

// Server-side validation
if (empty($current_password) || empty($new_password) || empty($confirm_new_password)) {
    header('Location: profile_settings.php?err=' . urlencode('All password fields are required.'));
    exit();
}
if ($new_password !== $confirm_new_password) {
    header('Location: profile_settings.php?err=' . urlencode('The new passwords do not match.'));
    exit();
}
if (strlen($new_password) < 8) {
    header('Location: profile_settings.php?err=' . urlencode('The new password must be at least 8 characters long.'));
    exit();
}

try {
    // --- 3. Verify the Current Password ---
    // Fetch the user's current hashed password from the database
    $stmt_fetch = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt_fetch->bind_param("i", $user_id);
    $stmt_fetch->execute();
    $result = $stmt_fetch->get_result();
    
    if ($result->num_rows !== 1) {
        // This is a failsafe, should not happen if user is logged in
        throw new Exception("User not found.");
    }
    
    $user = $result->fetch_assoc();
    $current_hashed_password = $user['password'];
    $stmt_fetch->close();

    // Use password_verify() to check if the submitted current password is correct
    if (!password_verify($current_password, $current_hashed_password)) {
        header('Location: profile_settings.php?err=' . urlencode('The current password you entered is incorrect.'));
        exit();
    }

    // --- 4. Update to the New Password ---
    // Hash the new password for secure storage
    $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Prepare and execute the UPDATE statement
    $stmt_update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt_update->bind_param("si", $new_hashed_password, $user_id);
    
    if ($stmt_update->execute()) {
        // Success
        header('Location: profile_settings.php?msg=' . urlencode('Your password has been changed successfully.'));
    } else {
        throw new Exception("Database update failed.");
    }
    $stmt_update->close();

} catch (Exception $e) {
    error_log("Change password error: " . $e->getMessage());
    header('Location: profile_settings.php?err=' . urlencode('A system error occurred. Please try again.'));
    exit();
}
?>