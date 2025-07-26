<?php
require_once '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

$token = $_POST['token'];
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];

// Validation
if (empty($token) || empty($password)) {
    header('Location: login.php?error=' . urlencode('Invalid request.'));
    exit();
}
if ($password !== $confirm_password) {
    header('Location: reset_password.php?token=' . urlencode($token) . '&error=' . urlencode('Passwords do not match.'));
    exit();
}
if (strlen($password) < 8) {
    header('Location: reset_password.php?token=' . urlencode($token) . '&error=' . urlencode('Password must be at least 8 characters long.'));
    exit();
}

try {
    // Re-validate the token and get the associated email
    $hashed_token = hash('sha256', $token);
    $stmt_val = $conn->prepare("SELECT email FROM password_resets WHERE token = ? AND created_at > NOW() - INTERVAL 1 HOUR");
    $stmt_val->bind_param("s", $hashed_token);
    $stmt_val->execute();
    $result = $stmt_val->get_result();

    if ($result->num_rows !== 1) {
        header('Location: login.php?error=' . urlencode('Invalid or expired reset token.'));
        exit();
    }
    $row = $result->fetch_assoc();
    $email = $row['email'];
    $stmt_val->close();

    // --- Start Transaction ---
    $conn->begin_transaction();

    // 1. Hash the new password
    $new_hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // 2. Update the user's password in the `users` table
    $stmt_update = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt_update->bind_param("ss", $new_hashed_password, $email);
    $stmt_update->execute();

    // 3. Delete the used token from the `password_resets` table
    $stmt_delete = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
    $stmt_delete->bind_param("s", $email);
    $stmt_delete->execute();

    // Commit the transaction
    $conn->commit();
    
    header('Location: login.php?success=' . urlencode('Your password has been reset successfully! You can now log in.'));
    exit();

} catch (Exception $e) {
    $conn->rollback();
    error_log("Reset password error: " . $e->getMessage());
    header('Location: login.php?error=' . urlencode('A critical error occurred. Please try again.'));
    exit();
}
?>