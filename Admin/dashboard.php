<?php
session_start();
// This guard allows BOTH 'admin' and 'master_admin' to view this page.
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'master_admin'])) {
    session_unset(); 
    session_destroy();
    header('Location: ../Login/login.php?error=' . urlencode('Access Denied.'));
    exit();
}

// A full session token validation would also be here in a real scenario.
$username = htmlspecialchars($_SESSION['username']);
$user_type = htmlspecialchars($_SESSION['user_type']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - StudyCore</title>
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
        <a href="#" class="navbar-brand">StudyCore Admin Panel</a>
        <div class="navbar-logout">
            <a href="../Login/logout.php">Logout</a>
        </div>
    </nav>
    <div class="container">
        <h1 class="welcome-message">Welcome, <?php echo $username; ?>! (Admin)</h1>
        <p>This dashboard is a placeholder for the Admin role. Full functionality is under construction.</p>
    </div>
</body>
</html>