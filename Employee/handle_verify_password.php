<?php
session_start();
require_once '../config/db_connect.php';

// Check if form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../Login/login.php'); // Redirect to main login if accessed directly
    exit();
}

$token = $_POST['token'] ?? '';
$temp_password_submitted = $_POST['temp_password'] ?? '';

if (empty($token) || empty($temp_password_submitted)) {
    header('Location: verify_password.php?token=' . urlencode($token) . '&error=' . urlencode('Please enter the temporary password.'));
    exit();
}

try {
    // Fetch the hashed temporary password from the database using the token
    $stmt = $conn->prepare("SELECT temp_password FROM employees WHERE registration_token = ? AND is_registered = 0 AND token_expires_at > NOW() LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $employee = $result->fetch_assoc();
        $hashed_temp_password_db = $employee['temp_password'];

        // Use password_verify to check if the submitted password matches the hash
        if (password_verify($temp_password_submitted, $hashed_temp_password_db)) {
            // SUCCESS! Password is correct.
            // Set a session variable to grant access to the final registration step.
            $_SESSION['registration_step_2_complete'] = true;
            $_SESSION['registration_token'] = $token;

            // Redirect to the final registration page
            header('Location: complete_registration.php');
            exit();
        } else {
            // Password does not match
            header('Location: verify_password.php?token=' . urlencode($token) . '&error=' . urlencode('Invalid temporary password. Please try again.'));
            exit();
        }
    } else {
        // Token is invalid, expired, or used
        die("Error: This registration link is no longer valid.");
    }

} catch (Exception $e) {
    error_log("Temp password verification failed: " . $e->getMessage());
    header('Location: verify_password.php?token=' . urlencode($token) . '&error=' . urlencode('A system error occurred.'));
    exit();
}