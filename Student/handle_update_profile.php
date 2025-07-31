<?php
session_start();
require_once '../config/db_connect.php';

// --- 1. Security & Validation ---
// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../Login/login.php');
    exit();
}
// Ensure form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profile_settings.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// --- 2. Process Form Data ---
$full_name = trim($_POST['name'] ?? '');
$phone_code = $_POST['phone_code'] ?? null;
$phone_number = trim($_POST['phone_number'] ?? '');

// Server-side validation
if (empty($full_name)) {
    header('Location: profile_settings.php?err=' . urlencode('Full Name cannot be empty.'));
    exit();
}
// Optional: Add more validation for phone number format if needed

// Prepare phone number for database insertion
$phone_number_to_save = !empty($phone_number) ? $phone_number : null;
$phone_code_to_save = !empty($phone_number) ? $phone_code : null;


// --- 3. Update the Database ---
try {
    $stmt = $conn->prepare("UPDATE users SET name = ?, phone_country_code = ?, phone_number = ? WHERE id = ?");
    // sssi = string, string, string, integer
    $stmt->bind_param("sssi", $full_name, $phone_code_to_save, $phone_number_to_save, $user_id);
    
    if ($stmt->execute()) {
        // Success
        header('Location: profile_settings.php?msg=' . urlencode('Your profile has been updated successfully.'));
    } else {
        // Database execution error
        throw new Exception("Database update failed.");
    }
    $stmt->close();

} catch (Exception $e) {
    // Catch any other exceptions
    error_log("Update profile error: " . $e->getMessage());
    header('Location: profile_settings.php?err=' . urlencode('A system error occurred. Please try again.'));
    exit();
}

?>