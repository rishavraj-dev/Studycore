<?php
session_start();
require_once '../config/db_connect.php';

header('Content-Type: application/json');

$email = $_POST['email'] ?? '';
$otp_submitted = $_POST['otp'] ?? '';

if (empty($email) || empty($otp_submitted)) {
    echo json_encode(['status' => 'error', 'message' => 'Email and OTP are required.']);
    exit();
}

try {
    // 1. Fetch the stored OTP hash for the given email
    $stmt = $conn->prepare("SELECT otp_hash FROM otp_codes WHERE email = ? AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $otp_hash_db = $row['otp_hash'];

        // 2. Securely verify the submitted OTP against the stored hash
        if (password_verify($otp_submitted, $otp_hash_db)) {
            // --- SUCCESS ---
            // Set a secure flag in the session to prove verification
            $_SESSION['email_is_verified'] = true;
            $_SESSION['verified_email'] = $email;

            // Clean up the used OTP
            $stmt_delete = $conn->prepare("DELETE FROM otp_codes WHERE email = ?");
            $stmt_delete->bind_param("s", $email);
            $stmt_delete->execute();
            $stmt_delete->close();
            
            echo json_encode(['status' => 'success', 'message' => 'Email verified successfully!']);
        } else {
            // OTP is incorrect
            echo json_encode(['status' => 'error', 'message' => 'Invalid OTP. Please try again.']);
        }
    } else {
        // No valid OTP found (either expired or never existed)
        echo json_encode(['status' => 'error', 'message' => 'OTP is invalid or has expired. Please request a new one.']);
    }
    $stmt->close();

} catch (Exception $e) {
    error_log("Confirm OTP Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'A system error occurred.']);
}
?>