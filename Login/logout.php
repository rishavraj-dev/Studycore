<?php
session_start();
require_once '../config/db_connect.php';

// Check if a user is actually logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['session_token'])) {
    try {
        // Find the specific session token in the database and delete it.
        // This is what logs the user out from this specific device.
        $stmt = $conn->prepare("DELETE FROM active_sessions WHERE user_id = ? AND session_token = ?");
        $stmt->bind_param("is", $_SESSION['user_id'], $_SESSION['session_token']);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        // Log the error, but proceed with logout anyway for security
        error_log("Logout session deletion failed: " . $e->getMessage());
    }
}

// Unset all of the session variables.
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session.
session_destroy();

// Redirect to the main homepage after logout
header('Location: ../index.html');
exit();
?>