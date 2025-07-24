<?php
// ===============================================
// StudyCore - Database Connection File
// ===============================================

// Your database credentials from InfinityFree
$servername = "YOUR_MYSQL_HOST_NAME"; // e.g., sql201.infinityfree.com
$username = "YOUR_MYSQL_USERNAME";    // e.g., if0_38884532
$password = "YOUR_MYSQL_PASSWORD";    // Find this in your control panel
$dbname = "YOUR_MYSQL_DATABASE_NAME";// e.g., if0_38884532_studycorenew

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // In a real production environment, you might want to log this error
    // instead of showing it to the user for security reasons.
    die("Connection failed: " . $conn->connect_error);
}

// Set the character set to utf8mb4 for full Unicode support
$conn->set_charset("utf8mb4");

?>