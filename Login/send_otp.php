<?php
// Start session to manage the resend timer
session_start();

// Import PHPMailer and connect to DB
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../PHPMailer/Exception.php';
require '../PHPMailer/PHPMailer.php';
require '../PHPMailer/SMTP.php';
require_once '../config/db_connect.php';

// Set the response header to JSON
header('Content-Type: application/json');

// --- 1. Abuse Prevention & Validation ---
$resend_limit = 90; // 90 seconds
if (isset($_SESSION['otp_sent_at']) && (time() - $_SESSION['otp_sent_at'] < $resend_limit)) {
    echo json_encode(['status' => 'error', 'message' => 'Please wait before requesting another OTP.']);
    exit();
}

$email = $_POST['email'] ?? '';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email format.']);
    exit();
}

try {
    // --- 2. Check if Email is Already Registered ---
    $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'This email is already registered.']);
        exit();
    }
    $stmt_check->close();

    // --- 3. Generate and Store OTP ---
    $otp = random_int(100000, 999999); // Generate a 6-digit OTP
    $otp_hash = password_hash((string)$otp, PASSWORD_DEFAULT);
    $expires_at = date('Y-m-d H:i:s', time() + (15 * 60)); // OTP expires in 15 minutes

    // Delete any old OTPs for this email before inserting a new one
    $stmt_delete = $conn->prepare("DELETE FROM otp_codes WHERE email = ?");
    $stmt_delete->bind_param("s", $email);
    $stmt_delete->execute();
    $stmt_delete->close();

    // Insert the new OTP
    $stmt_insert = $conn->prepare("INSERT INTO otp_codes (email, otp_hash, expires_at) VALUES (?, ?, ?)");
    $stmt_insert->bind_param("sss", $email, $otp_hash, $expires_at);
    $stmt_insert->execute();
    $stmt_insert->close();
    
    // --- 4. Send OTP Email via PHPMailer ---
    $mail = new PHPMailer(true);
    // (Your full Brevo SMTP configuration goes here...)
    $mail->isSMTP();
    $mail->Host       = 'smtp-relay.brevo.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'use your Brevo SMTP username here';
    $mail->Password   = 'use your Brevo SMTP password here';
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;
    $mail->setFrom('aiuser.first@gmail.com', 'StudyCore Verification');
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = 'Your StudyCore Verification Code';
    $mail->Body    = "Your verification code is: <b>{$otp}</b>. This code will expire in 15 minutes.";
    $mail->AltBody = "Your verification code is: {$otp}. This code will expire in 15 minutes.";
    
    $mail->send();

    // --- 5. Set Timer and Return Success ---
    $_SESSION['otp_sent_at'] = time(); // Start the 90-second resend timer
    echo json_encode(['status' => 'success', 'message' => 'An OTP has been sent to your email.']);
    
} catch (Exception $e) {
    error_log("Send OTP Error: " . ($e->getMessage()));
    // For PHPMailer errors, use $mail->ErrorInfo
    // error_log("PHPMailer Error: " . ($mail->ErrorInfo ?? $e->getMessage()));
    echo json_encode(['status' => 'error', 'message' => 'Could not send OTP. Please try again later.']);
}
?>