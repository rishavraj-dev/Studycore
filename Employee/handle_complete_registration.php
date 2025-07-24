<?php
session_start();
require_once '../config/db_connect.php';

// =================================================================
//  SECURITY & SESSION VALIDATION
// =================================================================
if (!isset($_SESSION['registration_step_2_complete']) || !$_SESSION['registration_step_2_complete']) {
    die("Access Denied. Please complete the previous steps first.");
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../Login/login.php');
    exit();
}
$token = $_SESSION['registration_token'] ?? '';
if (empty($token)) {
    die("Your registration session has expired. Please start the registration process again.");
}

// =================================================================
//  FORM DATA & SERVER-SIDE VALIDATION
// =================================================================
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];
$security_question_id = $_POST['security_question_id'];
$security_answer = trim($_POST['security_answer']);

// Validation logic...
$errors = [];
if (empty($password) || empty($security_question_id) || empty($security_answer)) { $errors[] = "Please fill in all required fields."; }
if ($password !== $confirm_password) { $errors[] = "Passwords do not match."; }
if (strlen($password) < 8) { $errors[] = "Password must be at least 8 characters long."; }
if (!empty($errors)) {
    header('Location: complete_registration.php?error=' . urlencode(implode(' ', $errors)));
    exit();
}

// =================================================================
//  DATABASE PROCESSING
// =================================================================
try {
    // 1. Fetch employee's details from the `employees` table.
    $stmt_fetch = $conn->prepare("SELECT full_name, email, dob, role_assigned FROM employees WHERE registration_token = ? AND is_registered = 0 AND token_expires_at > NOW()");
    $stmt_fetch->bind_param("s", $token);
    $stmt_fetch->execute();
    $result = $stmt_fetch->get_result();
    if ($result->num_rows !== 1) {
        throw new Exception("The registration token is invalid or expired.");
    }
    $employee_data = $result->fetch_assoc();
    $stmt_fetch->close();

    // 2. **CRITICAL FIX**: Generate a unique username BEFORE the transaction.
    $username_found = false;
    $generated_username = '';
    do {
        // Generate a username like 'johna1b2'
        $name_parts = explode(' ', $employee_data['full_name']);
        $base_name = strtolower(preg_replace("/[^A-Za-z]/", '', $name_parts[0]));
        $random_suffix = substr(bin2hex(random_bytes(3)), 0, 4);
        $generated_username = $base_name . $random_suffix;

        $stmt_check_username = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt_check_username->bind_param("s", $generated_username);
        $stmt_check_username->execute();
        if ($stmt_check_username->get_result()->num_rows === 0) {
            $username_found = true; // This username is unique and we can use it.
        }
        $stmt_check_username->close();
    } while (!$username_found);
    
    // --- Start the main transaction ---
    $conn->begin_transaction();

    // 3. Hash the new permanent password and security answer
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $hashed_security_answer = password_hash($security_answer, PASSWORD_DEFAULT);
    
    // 4. **CRITICAL FIX**: Insert the final user record, NOW INCLUDING the generated username.
    $stmt_insert = $conn->prepare("INSERT INTO users (username, name, email, password, dob, security_question_id, security_answer, user_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt_insert->bind_param("sssssiss", $generated_username, $employee_data['full_name'], $employee_data['email'], $hashed_password, $employee_data['dob'], $security_question_id, $hashed_security_answer, $employee_data['role_assigned']);
    $stmt_insert->execute();
    if ($stmt_insert->affected_rows !== 1) {
        throw new Exception("Failed to create the new user record.");
    }
    $new_user_id = $conn->insert_id;
    $stmt_insert->close();

    // 5. Generate the permanent emp_id
    $year = date('y');
    $role_code = strtoupper(substr($employee_data['role_assigned'], 0, 3));
    $emp_id = $year . $role_code . $new_user_id;

    // 6. Update the new user record with their permanent emp_id
    $stmt_update_user = $conn->prepare("UPDATE users SET emp_id = ? WHERE id = ?");
    $stmt_update_user->bind_param("si", $emp_id, $new_user_id);
    $stmt_update_user->execute();
    if ($stmt_update_user->affected_rows !== 1) {
        throw new Exception("Failed to assign the Employee ID.");
    }
    $stmt_update_user->close();

    // 7. Mark the invitation as completed
    $stmt_update_emp = $conn->prepare("UPDATE employees SET is_registered = 1, temp_password = NULL WHERE registration_token = ?");
    $stmt_update_emp->bind_param("s", $token);
    $stmt_update_emp->execute();
    if ($stmt_update_emp->affected_rows !== 1) {
        throw new Exception("Failed to deactivate the registration invitation.");
    }
    $stmt_update_emp->close();

    // If all steps succeeded, commit the changes to the database
    $conn->commit();

    // Clean up session and redirect
    unset($_SESSION['registration_step_2_complete'], $_SESSION['registration_token']);
    header('Location: ../Login/login.php?success=' . urlencode('Registration complete! You can now log in with your new password.'));
    exit();

} catch (Exception $e) {
    $conn->rollback();
    error_log("CRITICAL ERROR in handle_complete_registration.php: " . $e->getMessage() . " for token: " . $token);
    header('Location: complete_registration.php?error=' . urlencode('A critical error occurred. Please contact an administrator.'));
    exit();
}
?>