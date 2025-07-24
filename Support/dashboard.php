<?php
session_start();
// This guard only allows users with the 'support' role.
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'support') {
    session_unset(); 
    session_destroy();
    header('Location: ../Login/login.php?error=' . urlencode('Access Denied.'));
    exit();
}

$username = htmlspecialchars($_SESSION['username']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Dashboard - StudyCore</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f0f2f5; color: #333; margin: 0; }
        .navbar { background-color: #fff; padding: 1rem 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .navbar-brand { font-size: 1.5rem; font-weight: 700; color: #1e40af; text-decoration: none; }
        .navbar-logout a { background-color: #dc3545; color: #fff; padding: 0.5rem 1rem; border-radius: 5px; text-decoration: none; font-weight: 500; }
        .container { padding: 2rem; max-width: 1200px; margin: 2rem auto; background-color: #fff; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .welcome-message { font-size: 1.8rem; font-weight: 600; color: #1e40af; }
        p { font-size: 1.1rem; }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="#" class="navbar-brand">StudyCore Support Desk</a>
        <div class="navbar-logout">
            <a href="../Login/logout.php">Logout</a>
        </div>
    </nav>
    <div class="container">
        <h1 class="welcome-message">Welcome, <?php echo $username; ?>! (Support)</h1>
        <p>The support ticket management system is under construction.</p>
    </div>
</body>
</html>