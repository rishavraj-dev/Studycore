<?php
session_start();
// Import PHPMailer and connect to DB
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../PHPMailer/Exception.php';
require '../PHPMailer/PHPMailer.php';
require '../PHPMailer/SMTP.php';
require_once '../config/db_connect.php';

// --- Security & Validation ---
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'master_admin'])) {
    header('Location: ../Login/login.php'); exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['employee_id'])) {
    header('Location: employee_management.php'); exit();
}
$employee_id = $_POST['employee_id'];

try {
    // Fetch the existing invitation details
    $stmt_fetch = $conn->prepare("SELECT full_name, email, dob FROM employees WHERE id = ? AND is_registered = 0");
    $stmt_fetch->bind_param("i", $employee_id);
    $stmt_fetch->execute();
    $result = $stmt_fetch->get_result();
    if ($result->num_rows !== 1) {
        throw new Exception("Invitation not found or has already been completed.");
    }
    $employee = $result->fetch_assoc();
    $stmt_fetch->close();

    // --- 1. Regenerate Credentials & Reset Timer ---
    $new_token = bin2hex(random_bytes(32));
    $name_parts = explode(' ', $employee['full_name']);
    $first_name = ucfirst(strtolower(preg_replace("/[^A-Za-z]/", '', $name_parts[0])));
    $date_obj = new DateTime($employee['dob']);
    $dob_formatted = $date_obj->format('dmy');
    $new_temp_pass = "{$first_name}@{$dob_formatted}";
    $new_hashed_pass = password_hash($new_temp_pass, PASSWORD_DEFAULT);
    
    // Reset the timer
    $new_sent_at = date('Y-m-d H:i:s');
    $new_expires_at = date('Y-m-d H:i:s', time() + (6 * 24 * 60 * 60)); // 6 days from now

    // --- 2. Update the Database Record ---
    $stmt_update = $conn->prepare("UPDATE employees SET registration_token = ?, temp_password = ?, invitation_sent_at = ?, token_expires_at = ? WHERE id = ?");
    $stmt_update->bind_param("ssssi", $new_token, $new_hashed_pass, $new_sent_at, $new_expires_at, $employee_id);
    $stmt_update->execute();
    $stmt_update->close();

    // --- 3. Automatically Resend Email ---
    $mail = new PHPMailer(true);
    
    // Your full Brevo SMTP configuration
    $mail->isSMTP();
    $mail->Host       = 'smtp-relay.brevo.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'YOUR_BREVO_SMTP_LOGIN';      // <-- PASTE YOUR CORRECT SMTP LOGIN HERE
    $mail->Password   = 'YOUR_BREVO_SMTP_KEY';        // <-- PASTE YOUR CORRECT SMTP KEY HERE
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;
    
    // Use your verified sender email here
    $mail->setFrom('your-verified-sender@gmail.com', 'StudyCore HR');
    $mail->addAddress($employee['email'], $employee['full_name']);
    $mail->isHTML(true);
    $mail->Subject = 'Resent Invitation to Join StudyCore';
    $registration_link = "https://{$_SERVER['HTTP_HOST']}/Employee/verify_details.php?token={$new_token}";
    $mail->Body = "Hello {$employee['full_name']},<br><br>Here is a new link to join the StudyCore platform. Please note that any previous invitation links are now invalid.<br><br>" .
                  "1. New Link: <a href='{$registration_link}'>Complete Your Registration</a><br><br>" .
                  "2. Your temporary password instruction remains the same: First Name (capitalized)@DDMMYY. (Example: <b>{$first_name}@{$dob_formatted}</b>)<br><br>" .
                  "This new link will expire in 6 days.<br><br>Thank you,<br>The StudyCore Team";
    
    $mail->send();
    
    header('Location: employee_management.php?message=' . urlencode('A new invitation has been successfully resent to ' . $employee['email']));
    exit();

} catch (Exception $e) {
    error_log("Automated Resend Error: " . ($e->getMessage()));
    header('Location: employee_management.php?error=' . urlencode('A system error occurred. The invitation could not be resent.'));
    exit();
}
?>