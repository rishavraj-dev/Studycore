<?php
session_start();
require_once '../config/db_connect.php';

// =================================================================
//  1. SECURITY CHECKS
// =================================================================

// Ensure the form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php');
    exit();
}

// **CRITICAL**: Check for the session proof of OTP verification.
// The email from the form MUST match the email we verified and stored in the session.
if (!isset($_SESSION['email_is_verified']) || $_SESSION['email_is_verified'] !== true || !isset($_SESSION['verified_email']) || $_SESSION['verified_email'] !== $_POST['email']) {
    // If check fails, redirect back with a security error.
    header('Location: register.php?error=' . urlencode('Email verification is required or your session has expired. Please verify your email again.'));
    exit();
}

// =================================================================
//  2. PROCESS AND VALIDATE FORM DATA
// =================================================================
$name = trim($_POST['name']);
$email = trim($_POST['email']); // We know this email is verified
$phone_code = $_POST['phone_code'] ?? null;
$phone_number = trim($_POST['phone_number'] ?? '');
$dob_string = $_POST['dob'];
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];
$security_question_id = $_POST['security_question_id'];
$security_answer = trim($_POST['security_answer']);

// Perform full server-side validation again as a final security measure
$errors = [];
if (empty($name) || empty($email) || empty($dob_string) || empty($password) || empty($security_question_id) || empty($security_answer)) {
    $errors[] = "All required fields were not submitted.";
}
if ($password !== $confirm_password) {
    $errors[] = "Passwords do not match.";
}
// Add any other final validation checks here.
if (!empty($errors)) {
    header('Location: register.php?error=' . urlencode(implode(' ', $errors)));
    exit();
}

// =================================================================
//  3. CREATE THE FINAL, VERIFIED USER
// =================================================================
try {
    // Generate a unique username
    $username_base = strtolower(preg_replace("/[^A-Za-z]/", '', explode(' ', $name)[0]));
    $username = $username_base . substr(bin2hex(random_bytes(3)), 0, 4);
    
    // Hash the permanent password and security answer
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $hashed_security_answer = password_hash($security_answer, PASSWORD_DEFAULT);
    
    $full_phone_number = !empty($phone_number) ? $phone_number : null;
    $phone_country_code_to_save = !empty($phone_number) ? $phone_code : null;
    
    // Start a transaction for the final user creation and subscription
    $conn->begin_transaction();

    // Insert the final, verified user into the database, setting is_email_verified to 1
    $stmt_insert = $conn->prepare(
        "INSERT INTO users (username, name, email, password, phone_country_code, phone_number, dob, security_question_id, security_answer, is_email_verified) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)"
    );
    $stmt_insert->bind_param("sssssssis", $username, $name, $email, $hashed_password, $phone_country_code_to_save, $full_phone_number, $dob_string, $security_question_id, $hashed_security_answer);
    $stmt_insert->execute();
    $new_user_id = $conn->insert_id;
    $stmt_insert->close();

    // --- Assign the Free Trial Subscription ---
    $stmt_plan = $conn->prepare("SELECT plan_id, duration_days FROM plans WHERE plan_name = 'Free Trial' LIMIT 1");
    $stmt_plan->execute();
    $plan_result = $stmt_plan->get_result()->fetch_assoc();
    $stmt_plan->close();
    
    if ($plan_result) {
        $start_date = date('Y-m-d H:i:s');
        $end_date = date('Y-m-d H:i:s', strtotime("+{$plan_result['duration_days']} days"));
        $stmt_sub = $conn->prepare("INSERT INTO user_subscriptions (user_id, plan_id, start_date, end_date, status) VALUES (?, ?, ?, ?, 'active')");
        $stmt_sub->bind_param("iiss", $new_user_id, $plan_result['plan_id'], $start_date, $end_date);
        $stmt_sub->execute();
        $stmt_sub->close();
    }
    
    // Commit the transaction
    $conn->commit();

    // SUCCESS! Clean up the session variables used during registration.
    unset($_SESSION['email_is_verified']);
    unset($_SESSION['verified_email']);
    unset($_SESSION['otp_sent_at']);

    // Redirect to login with a success message
    header('Location: login.php?success=' . urlencode('Registration successful! You can now log in.'));
    exit();

} catch (Exception $e) {
    $conn->rollback(); // Roll back any changes if something failed
    error_log("Final Registration Error: " . $e->getMessage());
    header('Location: register.php?error=' . urlencode('A critical error occurred while finalizing your account. Please try again.'));
    exit();
}
?>