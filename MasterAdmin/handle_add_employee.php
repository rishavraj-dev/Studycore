<?php
session_start();
// Import PHPMailer and connect to DB
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../PHPMailer/Exception.php';
require '../PHPMailer/PHPMailer.php';
require '../PHPMailer/SMTP.php';
require_once '../config/db_connect.php';

// --- 1. Security & Validation ---
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'master_admin'])) {
    header('Location: ../Login/login.php?error=' . urlencode('Access Denied.'));
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: employee_management.php');
    exit();
}

$full_name = trim($_POST['full_name']);
$email = trim($_POST['email']);
$dob = $_POST['dob'];
$role = $_POST['role'];
$admin_id = $_SESSION['user_id'];

// Full server-side validation
if (empty($full_name) || empty($email) || empty($dob) || empty($role)) {
    header('Location: employee_management.php?error=' . urlencode('All fields are required.'));
    exit();
}
// ... Add other validation as needed ...

try {
    // Check if email already exists in users or pending invitations
    $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ? UNION SELECT id FROM employees WHERE email = ? AND is_registered = 0");
    $stmt_check->bind_param("ss", $email, $email);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        header('Location: employee_management.php?error=' . urlencode('This email is already in use or has a pending invitation.'));
        exit();
    }
    $stmt_check->close();

    // --- 2. Generate Credentials & Set Timer ---
    $registration_token = bin2hex(random_bytes(32));
    $name_parts = explode(' ', $full_name);
    $first_name = ucfirst(strtolower(preg_replace("/[^A-Za-z]/", '', $name_parts[0])));
    $date_obj = new DateTime($dob);
    $dob_formatted = $date_obj->format('dmy');
    $temp_password_plaintext = "{$first_name}@{$dob_formatted}";
    $hashed_temp_password = password_hash($temp_password_plaintext, PASSWORD_DEFAULT);
    
    // Timer is started immediately
    $invitation_sent_at = date('Y-m-d H:i:s');
    $token_expires_at = date('Y-m-d H:i:s', time() + (6 * 24 * 60 * 60)); // 6 days from now

    // --- 3. Save Invitation to Database ---
    $stmt_insert = $conn->prepare("INSERT INTO employees (full_name, email, dob, role_assigned, registration_token, temp_password, invitation_sent_at, token_expires_at, created_by_admin_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt_insert->bind_param("ssssssssi", $full_name, $email, $dob, $role, $registration_token, $hashed_temp_password, $invitation_sent_at, $token_expires_at, $admin_id);
    $stmt_insert->execute();
    $stmt_insert->close();

    // --- 4. Automatically Send Email ---
    $mail = new PHPMailer(true);
    // (Your full Brevo SMTP configuration)
    $mail->isSMTP();
    $mail->Host       = 'smtp-relay.brevo.com';
    $mail->SMTPAuth   = true;
      $mail->Username   = 'use your ';      // Your unique SMTP Login from Brevo
    $mail->Password   = 'use your ';
   
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;
    $mail->setFrom('your-verified-sender@gmail.com', 'StudyCore HR');
    $mail->addAddress($email, $full_name);
    $mail->isHTML(true);
    $mail->Subject = 'Your Invitation to Join StudyCore';
    $registration_link = "https://{$_SERVER['HTTP_HOST']}/Employee/verify_details.php?token={$registration_token}";
    $mail->Body = "Hello {$full_name},<br><br>You have been invited to join the StudyCore platform. To set up your account, please follow the steps below:<br><br>" .
                  "1. Go to this unique registration link: <a href='{$registration_link}'>Complete Your Registration</a><br><br>" .
                  "2. You will need to verify your account with a temporary password. Your password is your First Name (capitalized) followed by '@' and your date of birth in DDMMYY format. (Example: <b>{$first_name}@{$dob_formatted}</b>)<br><br>" .
                  "This link will expire in 6 days.<br><br>Thank you,<br>The StudyCore Team";
    $mail->send();
    
    header('Location: employee_management.php?message=' . urlencode('Invitation has been successfully created and sent to ' . $email));
    exit();

} catch (Exception $e) {
    error_log("Automated Add Employee Error: " . ($e->getMessage()));
    header('Location: employee_management.php?error=' . urlencode('A system error occurred. The invitation could not be created or sent.'));
    exit();
}
?>