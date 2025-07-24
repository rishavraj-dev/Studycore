<?php
session_start();
require_once '../config/db_connect.php';

// =================================================================
//  SECURITY CHECKS (Allows Admin & Master Admin)
// =================================================================
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'master_admin'])) {
    header('Location: ../Login/login.php?error=' . urlencode('Access Denied.'));
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: employee_management.php');
    exit();
}

// =================================================================
//  PROCESS FORM DATA (With new DOB field)
// =================================================================
$full_name = trim($_POST['full_name']);
$email = trim($_POST['email']);
$role = $_POST['role'];
$dob = $_POST['dob']; // New Date of Birth field
$admin_id = $_SESSION['user_id'];

// --- Server-Side Validation ---
if (empty($full_name) || empty($email) || empty($role) || empty($dob)) {
    header('Location: employee_management.php?error=' . urlencode('All fields (including Date of Birth) are required to create an invitation.'));
    exit();
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: employee_management.php?error=' . urlencode('Invalid email format.'));
    exit();
}
$allowed_roles = ['admin', 'educator', 'support'];
if (!in_array($role, $allowed_roles)) {
    header('Location: employee_management.php?error=' . urlencode('Invalid role selected.'));
    exit();
}
// Validate the date format
if (!DateTime::createFromFormat('Y-m-d', $dob)) {
    header('Location: employee_management.php?error=' . urlencode('Invalid Date of Birth format.'));
    exit();
}

try {
    // Check if email already exists in users or employees table
    $stmt_check_user = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt_check_user->bind_param("s", $email);
    $stmt_check_user->execute();
    if ($stmt_check_user->get_result()->num_rows > 0) {
        header('Location: employee_management.php?error=' . urlencode('This email is already registered as a user.'));
        exit();
    }
    $stmt_check_user->close();

    $stmt_check_emp = $conn->prepare("SELECT id FROM employees WHERE email = ? AND is_registered = 0");
    $stmt_check_emp->bind_param("s", $email);
    $stmt_check_emp->execute();
    if ($stmt_check_emp->get_result()->num_rows > 0) {
        header('Location: employee_management.php?error=' . urlencode('An active invitation for this email already exists.'));
        exit();
    }
    $stmt_check_emp->close();

    // =================================================================
    //  GENERATE TEMPORARY PASSWORD (New Logic)
    // =================================================================
    
    // 1. Get the First Name, capitalize it, and remove non-alphabetic characters
    $name_parts = explode(' ', $full_name);
    $first_name = preg_replace("/[^A-Za-z]/", '', $name_parts[0]);
    $first_name_capitalized = ucfirst(strtolower($first_name)); // e.g., "John-Doe" -> "John"
    
    // 2. Format the Date of Birth as ddmmyy
    $date_obj = new DateTime($dob);
    $dob_formatted = $date_obj->format('dmy'); // e.g., "010205"

    // 3. Construct the plaintext temporary password
    $temp_password_plaintext = "{$first_name_capitalized}@{$dob_formatted}"; // e.g., "John@010205"

    // 4. Hash the new password for secure storage
    $hashed_temp_password = password_hash($temp_password_plaintext, PASSWORD_DEFAULT);
    
    // Generate the unique registration token
    $registration_token = bin2hex(random_bytes(32));

    // =================================================================
    //  INSERT INVITATION INTO DATABASE
    // =================================================================
    
    // Insert the invitation, now including the DOB and the new hashed password
    $stmt_insert = $conn->prepare("INSERT INTO employees (full_name, email, dob, role_assigned, registration_token, temp_password, created_by_admin_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt_insert->bind_param("ssssssi", $full_name, $email, $dob, $role, $registration_token, $hashed_temp_password, $admin_id);
    
    if ($stmt_insert->execute()) {
        header('Location: employee_management.php?message=' . urlencode('Invitation for ' . $full_name . ' has been created successfully.'));
    } else {
        throw new Exception("Database execution failed when inserting invitation.");
    }
    $stmt_insert->close();

} catch (Exception $e) {
    error_log("Add employee failed: " . $e->getMessage());
    header('Location: employee_management.php?error=' . urlencode('A system error occurred. Please try again.'));
    exit();
}
?>