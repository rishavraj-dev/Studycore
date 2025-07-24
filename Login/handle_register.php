<?php
// Start the session to handle user state and messages
session_start();

// Include the database connection file
require_once '../config/db_connect.php';

// Check if the form was submitted using the POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // If not, redirect to the registration page
    header('Location: register.php');
    exit();
}

// ===============================================
// 1. RECEIVE AND SANITIZE FORM DATA
// ===============================================
$name = trim($_POST['name']);
$email = trim($_POST['email']);
$phone_code = $_POST['phone_code'];
$phone_number = trim($_POST['phone_number']);
// Only store phone if the number part is provided
$full_phone_number = !empty($phone_number) ? $phone_number : null;
$phone_country_code_to_save = !empty($phone_number) ? $phone_code : null;

$dob_string = $_POST['dob'];
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];
$security_question_id = $_POST['security_question_id'];
$security_answer = trim($_POST['security_answer']);

// ===============================================
// 2. SERVER-SIDE VALIDATION
// ===============================================
$errors = [];

// Check for empty required fields
if (empty($name) || empty($email) || empty($dob_string) || empty($password) || empty($security_question_id) || empty($security_answer)) {
    $errors[] = "Please fill in all required fields.";
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email format.";
}

// Check if passwords match
if ($password !== $confirm_password) {
    $errors[] = "Passwords do not match.";
}

// Check password length (optional but recommended)
if (strlen($password) < 8) {
    $errors[] = "Password must be at least 8 characters long.";
}

// Validate age (must be at least 12)
try {
    $dob = new DateTime($dob_string);
    $today = new DateTime();
    $age = $today->diff($dob)->y;
    if ($age < 12) {
        $errors[] = "You must be at least 12 years old to register.";
    }
} catch (Exception $e) {
    $errors[] = "Invalid date of birth format.";
}

// If there are any validation errors, redirect back to the form
if (!empty($errors)) {
    // Join all errors into a single string
    $error_message = urlencode(implode(' ', $errors));
    header("Location: register.php?error=$error_message");
    exit();
}

// ===============================================
// 3. CHECK FOR EXISTING USER AND GENERATE USERNAME
// ===============================================
try {
    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        header('Location: register.php?error=' . urlencode('An account with this email already exists.'));
        exit();
    }
    $stmt->close();

    // Generate a unique username
    $username_found = false;
    do {
        $generated_username = 'user_' . substr(bin2hex(random_bytes(4)), 0, 8); // e.g., user_a1b2c3d4
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $generated_username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows == 0) {
            $username_found = true; // Username is unique
        }
        $stmt->close();
    } while (!$username_found);

} catch (Exception $e) {
    header('Location: register.php?error=' . urlencode('A database error occurred. Please try again.'));
    exit();
}

// ===============================================
// 4. HASH PASSWORDS & PREPARE FOR DATABASE
// ===============================================
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$hashed_security_answer = password_hash($security_answer, PASSWORD_DEFAULT);


// ===============================================
// 5. DATABASE TRANSACTION: CREATE USER AND SUBSCRIPTION
// ===============================================
// Start a transaction
$conn->begin_transaction();

try {
    // Step A: Insert into `users` table
    $stmt_user = $conn->prepare("INSERT INTO users (username, name, email, password, phone_country_code, phone_number, dob, security_question_id, security_answer) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt_user->bind_param("sssssssis", $generated_username, $name, $email, $hashed_password, $phone_country_code_to_save, $full_phone_number, $dob_string, $security_question_id, $hashed_security_answer);
    $stmt_user->execute();
    
    // Get the ID of the user we just created
    $new_user_id = $conn->insert_id;
    $stmt_user->close();

    // Step B: Get Free Trial plan details
    $stmt_plan = $conn->prepare("SELECT plan_id, duration_days FROM plans WHERE plan_name = 'Free Trial' LIMIT 1");
    $stmt_plan->execute();
    $result_plan = $stmt_plan->get_result();
    $plan = $result_plan->fetch_assoc();
    $stmt_plan->close();

    if (!$plan) {
        throw new Exception("Free Trial plan not found in database.");
    }
    
    $plan_id = $plan['plan_id'];
    $duration_days = $plan['duration_days'];

    // Step C: Calculate subscription start and end dates
    $start_date = new DateTime();
    $end_date = clone $start_date;
    $end_date->add(new DateInterval("P{$duration_days}D")); // P15D = Period of 15 Days
    
    $start_date_str = $start_date->format('Y-m-d H:i:s');
    $end_date_str = $end_date->format('Y-m-d H:i:s');

    // Step D: Insert into `user_subscriptions` table
    $stmt_sub = $conn->prepare("INSERT INTO user_subscriptions (user_id, plan_id, start_date, end_date, status) VALUES (?, ?, ?, ?, 'active')");
    $stmt_sub->bind_param("iiss", $new_user_id, $plan_id, $start_date_str, $end_date_str);
    $stmt_sub->execute();
    $stmt_sub->close();

    // If everything was successful, commit the transaction
    $conn->commit();

    // Redirect to login page with a success message
    header('Location: login.php?success=' . urlencode('Registration successful! Please log in.'));
    exit();

} catch (Exception $e) {
    // If any step failed, roll back the transaction
    $conn->rollback();
    
    // Log the detailed error for debugging
    error_log("Registration failed: " . $e->getMessage());

    // Redirect back to registration with a generic error
    header('Location: register.php?error=' . urlencode('An error occurred during registration. Please try again.'));
    exit();
}

?>