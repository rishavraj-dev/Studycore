<?php
// =================================================================
//  SESSION VALIDATION - THE GUARD FOR ALL PROTECTED PAGES
// =================================================================
session_start();
require_once '../config/db_connect.php';

// 1. Check if user is logged in at all
if (!isset($_SESSION['user_id'])) {
    header('Location: ../Login/login.php?error=' . urlencode('You must be logged in to access this page.'));
    exit();
}

// 2. Check if the session token in the PHP session still exists in the database.
// This is the crucial check to see if this session has been logged out by another device.
try {
    $stmt = $conn->prepare("SELECT session_id FROM active_sessions WHERE user_id = ? AND session_token = ? LIMIT 1");
    $stmt->bind_param("is", $_SESSION['user_id'], $_SESSION['session_token']);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        // The session token is not in the database. Destroy the session and log out.
        session_unset();
        session_destroy();
        header('Location: ../Login/login.php?error=' . urlencode('Your session has expired or you signed in on another device.'));
        exit();
    }
    $stmt->close();
} catch (Exception $e) {
    // Database error
    session_unset();
    session_destroy();
    header('Location: ../Login/login.php?error=' . urlencode('A session validation error occurred.'));
    exit();
}

// If we reach this point, the user is authenticated and the session is valid.
// You can now safely display the page content.
$username = htmlspecialchars($_SESSION['username']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - StudyCore</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
            color: #333;
            margin: 0;
        }
        .navbar {
            background-color: #fff;
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e40af;
            text-decoration: none;
        }
        .navbar-logout a {
            background-color: #dc3545;
            color: #fff;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        .navbar-logout a:hover {
            background-color: #c82333;
        }
        .container {
            padding: 2rem;
            max-width: 1000px;
            margin: 2rem auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .welcome-message {
            font-size: 1.8rem;
            font-weight: 600;
            color: #1e40af;
            margin-bottom: 1rem;
        }
        .content p {
            font-size: 1.1rem;
            line-height: 1.6;
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="#" class="navbar-brand">StudyCore Dashboard</a>
        <div class="navbar-logout">
            <a href="../Login/logout.php">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="welcome-message">
            Welcome, <?php echo $username; ?>!
        </div>
        <div class="content">
            <p>This is your protected student dashboard. Only logged-in users with a valid session can see this page.</p>
            <p>From here, you will be able to access your study materials, view your test series results, and manage your subscription.</p>
            <!-- All your future dashboard content will go here -->
        </div>
    </div>

</body>
</html>