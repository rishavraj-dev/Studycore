<?php
session_start();
require_once '../config/db_connect.php';

// =================================================================
//  SECURITY CHECKS (Corrected)
// =================================================================
// **FIXED**: Use in_array for correct role checking
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'master_admin'])) {
    header('Location: ../Login/login.php?error=' . urlencode('Access Denied.'));
    exit();
}
// 2. Ensure the form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // **FIXED**: Redirect to the correct management page
    header('Location: employee_management.php');
    exit();
}

// =================================================================
//  PROCESS FORM DATA
// =================================================================
if (!isset($_POST['employee_id']) || !isset($_POST['confirmation'])) {
    header('Location: employee_management.php?error=' . urlencode('Invalid request.'));
    exit();
}

$employee_id = $_POST['employee_id'];

try {
    // Prepare and execute the update query
    $stmt = $conn->prepare("
        UPDATE employees 
        SET 
            invitation_sent_at = NOW(),
            token_expires_at = NOW() + INTERVAL 6 DAY
        WHERE 
            id = ? AND invitation_sent_at IS NULL AND is_registered = 0
    ");

    $stmt->bind_param("i", $employee_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            // Success
            header('Location: employee_management.php?message=' . urlencode('Invitation timer has been started successfully!'));
            exit();
        } else {
            // This case handles if the user tries to submit the form twice quickly
            header('Location: employee_management.php?error=' . urlencode('This invitation has already been sent or is invalid.'));
            exit();
        }
    } else {
        throw new Exception("Database update failed.");
    }

} catch (Exception $e) {
    error_log("Start timer failed: " . $e->getMessage());
    header('Location: employee_management.php?error=' . urlencode('A system error occurred. Please try again.'));
    exit();
}

?>