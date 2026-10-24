<?php
// Start the session at the very top
session_start();
// This script will always return a JSON response for the JavaScript
header('Content-Type: application/json');

// Import PHPMailer for sending the OTP email
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../PHPMailer/Exception.php';
require '../PHPMailer/PHPMailer.php';
require '../PHPMailer/SMTP.php';
require_once '../config/db_connect.php';

// Get data from the JavaScript fetch() request
$username_or_email = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// --- 1. Basic Validation ---
if (empty($username_or_email) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Username and password are required.']);
    exit();
}

try {
    // --- 2. Authenticate Password & Fetch All Necessary User Data ---
    $stmt = $conn->prepare(
        "SELECT id, username, email, password, user_type, is_2fa_enabled, is_email_verified 
         FROM users WHERE username = ? OR email = ? LIMIT 1"
    );
    $stmt->bind_param("ss", $username_or_email, $username_or_email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Check if user exists and if the submitted password is correct
    if ($result->num_rows !== 1 || !password_verify($password, ($user = $result->fetch_assoc())['password'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid username or password.']);
        exit();
    }
    $stmt->close();
    
    // --- 3. Check if 2FA is Required ---
    if ($user['is_2fa_enabled'] == 1) {
        // --- 2FA IS REQUIRED ---
        $otp = random_int(100000, 999999);
        $otp_hash = password_hash((string)$otp, PASSWORD_DEFAULT);
        $expires_at = date('Y-m-d H:i:s', time() + (10 * 60)); // OTP is valid for 10 minutes
        
        // **CORRECTED**: Use the NEW, DEDICATED table `login_2fa_codes`
        $conn->prepare("DELETE FROM login_2fa_codes WHERE user_id = ?")->bind_param("i", $user['id'])->execute();
        $stmt_insert = $conn->prepare("INSERT INTO login_2fa_codes (user_id, otp_hash, expires_at) VALUES (?, ?, ?)");
        $stmt_insert->bind_param("iss", $user['id'], $otp_hash, $expires_at);
        $stmt_insert->execute();
        
        // Send OTP email via PHPMailer (your Brevo config)
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp-relay.brevo.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = '93267f002@smtp-brevo.com';
            $mail->Password   = 'xsmtpsib-34cf4a5968eb1a523d5d209bc576049ac138b83f5dfdc72d95c9d5c44101968c-PSFkY9ETnCartJAK';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            
            $mail->setFrom('aiuser.first@gmail.com', 'StudyCore Security'); // Use your verified sender
            $mail->addAddress($user['email']);
            $mail->isHTML(true);
            $mail->Subject = 'Your StudyCore Login Verification Code';
            $mail->Body    = "Your login verification code is: <b>{$otp}</b>. It is valid for 10 minutes.";
            $mail->send();
        } catch (Exception $e) {
            error_log("2FA OTP Send Error: " . $mail->ErrorInfo);
            echo json_encode(['status' => 'error', 'message' => 'Could not send verification code. Please try again later.']);
            exit();
        }
        
        // Tell the front-end JavaScript to switch to the OTP view
        echo json_encode(['status' => 'otp_required', 'message' => 'A verification code has been sent to your email.']);
        exit();

    } else {
        // --- 2FA IS NOT REQUIRED (e.g., Student) ---
        // This is the direct login path, which remains the same as your stable version.
        $conn->begin_transaction();
        
        $stmt_sub = $conn->prepare("SELECT p.max_devices FROM user_subscriptions us JOIN plans p ON us.plan_id = p.plan_id WHERE us.user_id = ? AND us.status = 'active' AND us.end_date > NOW() LIMIT 1");
        $stmt_sub->bind_param("i", $user['id']);
        $stmt_sub->execute();
        $sub_result = $stmt_sub->get_result();
        $max_devices = ($sub_result->num_rows > 0) ? (int)$sub_result->fetch_assoc()['max_devices'] : 1;
        $stmt_sub->close();
        
        $stmt_count = $conn->prepare("SELECT session_id FROM active_sessions WHERE user_id = ? ORDER BY last_seen ASC");
        $stmt_count->bind_param("i", $user['id']);
        $stmt_count->execute();
        $active_sessions_result = $stmt_count->get_result();
        if ($active_sessions_result->num_rows >= $max_devices) {
            $oldest_session = $active_sessions_result->fetch_assoc();
            $conn->prepare("DELETE FROM active_sessions WHERE session_id = ?")->bind_param("i", $oldest_session['session_id'])->execute();
        }
        $stmt_count->close();
        
        $session_token = bin2hex(random_bytes(32));
        $stmt_insert_session = $conn->prepare("INSERT INTO active_sessions (user_id, session_token, ip_address, user_agent) VALUES (?, ?, ?, ?)");
        $stmt_insert_session->bind_param("isss", $user['id'], $session_token, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
        $stmt_insert_session->execute();
        $stmt_insert_session->close();

        $conn->commit();

        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_type'] = $user['user_type'];
        $_SESSION['is_email_verified'] = $user['is_email_verified'];
        $_SESSION['session_token'] = $session_token;
        
        $redirect_url = '../Student/home.php'; // Default redirect
        switch ($user['user_type']) {
            case 'master_admin':
                $redirect_url = '../MasterAdmin/dashboard.php';
                break;
            case 'admin':
                $redirect_url = '../Admin/dashboard.php';
                break;
            case 'educator':
                $redirect_url = '../Educator/dashboard.php';
                break;
            case 'support':
                $redirect_url = '../Support/dashboard.php';
                break;
            case 'student':
            default:
                $redirect_url = '../Student/home.php';
                break;
        }

        echo json_encode(['status' => 'success', 'redirect_url' => $redirect_url]);
        exit();
    }
} catch (Exception $e) {
    if ($conn->in_transaction) { $conn->rollback(); }
    error_log("Login Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'A system error occurred. Please try again.']);
    exit();
}
?>