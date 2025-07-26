<?php
// Start the session at the very top to access session variables
session_start();

// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load the PHPMailer files
require '../PHPMailer/Exception.php';
require '../PHPMailer/PHPMailer.php';
require '../PHPMailer/SMTP.php';

// Load your database connection
require_once '../config/db_connect.php';

// Check if the form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

// =================================================================
//  1. ABUSE PREVENTION: 5-MINUTE TIMER (SERVER-SIDE CHECK)
// =================================================================
$time_limit = 300; // 300 seconds = 5 minutes
if (isset($_SESSION['password_reset_sent_at']) && (time() - $_SESSION['password_reset_sent_at'] < $time_limit)) {
    $time_left = $time_limit - (time() - $_SESSION['password_reset_sent_at']);
    $minutes = floor($time_left / 60);
    $seconds = $time_left % 60;
    
    // Redirect back with an error message showing the time remaining
    $error_message = sprintf("Please wait %d minutes and %02d seconds before trying again.", $minutes, $seconds);
    header('Location: login.php?status=reset_failed&msg=' . urlencode($error_message));
    exit();
}

// =================================================================
//  2. VALIDATE EMAIL AND CHECK IF USER EXISTS
// =================================================================
$email = $_POST['email'];
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: login.php?status=reset_failed&msg=' . urlencode('Invalid email format.'));
    exit();
}

try {
    // Check if a user with this email exists in the database
    $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows === 1) {
        // User exists, so we proceed to generate a token and send the email.
        $token = bin2hex(random_bytes(32));
        $hashed_token = hash('sha256', $token);

        // Store the hashed token in the database
        $stmt_insert = $conn->prepare("INSERT INTO password_resets (email, token) VALUES (?, ?)");
        $stmt_insert->bind_param("ss", $email, $hashed_token);
        $stmt_insert->execute();

        // =================================================================
        //  3. SEND THE EMAIL USING PHPMailer
        // =================================================================
        $mail = new PHPMailer(true);
        try {
            // --- Server Settings (Fill with your Brevo credentials) ---
            $mail->isSMTP();
            $mail->Host       = 'smtp-relay.brevo.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'use your ';      // Your unique SMTP Login from Brevo
            $mail->Password   = 'use your ';        // Your unique SMTP Key from Brevo
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // --- Recipients ---
            $mail->setFrom('XXXXXX@gmail.com', 'StudyCore Support'); // Must be your verified sender email
            $mail->addAddress($email);

            // --- Content ---
            $reset_link = "https://{$_SERVER['HTTP_HOST']}/Login/reset_password.php?token={$token}";
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request for StudyCore';
            
            // The new, professional email body
            $mail->Body    = "<div style=\"font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;\">" .
                           "<h2 style=\"color: #1e40af; text-align: center;\">StudyCore Password Reset</h2>" .
                           "<p>Hello,</p>" .
                           "<p>We received a request to reset the password for your StudyCore account. To proceed, please click the button below.</p>" .
                           "<div style=\"text-align: center; margin: 30px 0;\">" .
                           "<a href=\"{$reset_link}\" style=\"background-color: #2563eb; color: #ffffff; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px;\">Reset Your Password</a>" .
                           "</div>" .
                           "<p>This password reset link is only valid for the next <strong>1 hour</strong>.</p>" .
                           "<p>If you did not request a password reset, you can safely ignore this email. Your account is secure.</p>" .
                           "<hr style=\"border: none; border-top: 1px solid #eee; margin: 20px 0;\">" .
                           "<p style=\"font-size: 0.9em; color: #777; text-align: center;\">" .
                           "If the button above does not work, please copy and paste the following link into your web browser:<br>" .
                           "<a href=\"{$reset_link}\" style=\"color: #3b82f6; word-break: break-all;\">{$reset_link}</a>" .
                           "</p></div>";

            // The plain text version for older email clients
            $mail->AltBody = "Hello,\n\nWe received a request to reset the password for your StudyCore account.\n\nTo proceed, please copy and paste the following link into your web browser:\n{$reset_link}\n\nThis link is only valid for the next 1 hour.\n\nThank you,\nThe StudyCore Team";

            $mail->send();

            // **SUCCESS**: If mail sends, set the session timestamp to start the timer
            $_SESSION['password_reset_sent_at'] = time();

        } catch (Exception $e) {
            // Email failed to send, log the detailed error for debugging.
            error_log("PHPMailer Error: {$mail->ErrorInfo}");
            // We still proceed to the success message to not reveal info to attackers.
        }
    }
    
    // **SECURITY**: ALWAYS redirect to the same success page, whether the user existed or not.
    // We pass 'reset_sent' to trigger the client-side timer on the login page.
    header('Location: login.php?status=reset_sent');
    exit();

} catch (Exception $e) {
    // This will catch any database errors
    error_log("Forgot password database error: " . $e->getMessage());
    header('Location: login.php?status=reset_failed&msg=' . urlencode('A system error occurred. Please try again.'));
    exit();
}
?>