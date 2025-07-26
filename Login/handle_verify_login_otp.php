<?php
session_start();
header('Content-Type: application/json');

require_once '../config/db_connect.php';

$username_or_email = $_POST['username'] ?? '';
$otp_submitted = $_POST['otp'] ?? '';

if (empty($username_or_email) || empty($otp_submitted)) {
    echo json_encode(['status' => 'error', 'message' => 'OTP is required.']);
    exit();
}

try {
    // 1. Get user details again
    $stmt_user = $conn->prepare("SELECT id, username, user_type FROM users WHERE username = ? OR email = ?");
    $stmt_user->bind_param("ss", $username_or_email, $username_or_email);
    $stmt_user->execute();
    $user = $stmt_user->get_result()->fetch_assoc();
    if (!$user) {
        throw new Exception("User not found during OTP check.");
    }

    // 2. Verify the OTP
    $stmt_otp = $conn->prepare("SELECT otp_hash FROM otp_codes WHERE user_id = ? AND expires_at > NOW()");
    $stmt_otp->bind_param("i", $user['id']);
    $stmt_otp->execute();
    $result_otp = $stmt_otp->get_result();

    if ($result_otp->num_rows !== 1 || !password_verify($otp_submitted, $result_otp->fetch_assoc()['otp_hash'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid or expired OTP. Please try again.']);
        exit();
    }

    // --- OTP IS CORRECT: COMPLETE THE LOGIN ---
    $conn->prepare("DELETE FROM otp_codes WHERE user_id = ?")->bind_param("i", $user['id'])->execute();

    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_type'] = $user['user_type'];
    // Full session/device management would go here

    // Determine the correct redirect URL
    $redirect_url = '../Student/home.php'; // Default
    switch ($user['user_type']) {
        case 'master_admin': $redirect_url = '../MasterAdmin/dashboard.php'; break;
        case 'admin': $redirect_url = '../Admin/dashboard.php'; break;
        case 'educator': $redirect_url = '../Educator/dashboard.php'; break;
        case 'support': $redirect_url = '../Support/dashboard.php'; break;
    }

    echo json_encode(['status' => 'success', 'redirect_url' => $redirect_url]);
    exit();

} catch (Exception $e) {
    error_log("Login 2FA Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'A system error occurred.']);
    exit();
}
?>