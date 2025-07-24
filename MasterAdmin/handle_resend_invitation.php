<?php
session_start();
require_once '../config/db_connect.php';

// =================================================================
//  SECURITY CHECKS
// =================================================================
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'master_admin'])) {
    header('Location: ../Login/login.php');
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: employee_management.php');
    exit();
}

if (!isset($_POST['employee_id'])) {
    header('Location: employee_management.php?error=' . urlencode('Invalid request.'));
    exit();
}

$employee_id = $_POST['employee_id'];

// =================================================================
//  REGENERATE CREDENTIALS AND RESET TIMER
// =================================================================
try {
    // 1. Generate a NEW unique registration token
    $new_registration_token = bin2hex(random_bytes(32));

    // 2. Generate a NEW temporary password
    $new_temp_password_plaintext = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()'), 0, 8);
    
    // 3. Hash the new temporary password for secure storage
    $new_hashed_temp_password = password_hash($new_temp_password_plaintext, PASSWORD_DEFAULT);
    
    // 4. Prepare the update query to reset everything
    $stmt = $conn->prepare("
        UPDATE employees 
        SET 
            registration_token = ?,
            temp_password = ?,
            invitation_sent_at = NULL,
            token_expires_at = NULL
        WHERE 
            id = ? AND is_registered = 0
    ");
    $stmt->bind_param("ssi", $new_registration_token, $new_hashed_temp_password, $employee_id);

    if ($stmt->execute()) {
        header('Location: employee_management.php?message=' . urlencode('Invitation has been reset. You can now prepare and send the new invitation.'));
    } else {
        throw new Exception("Database update failed during resend.");
    }
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Resend invitation failed: " . $e->getMessage());
    header('Location: employee_management.php?error=' . urlencode('A system error occurred while resetting the invitation.'));
    exit();
}
?>