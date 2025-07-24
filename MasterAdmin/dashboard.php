<?php
// =================================================================
//  SESSION VALIDATION - MASTER ADMIN GUARD
// =================================================================
session_start();
// This guard remains the same, ensuring only a master admin can see this hub.
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'master_admin') {
    session_unset(); session_destroy();
    header('Location: ../Login/login.php?error=' . urlencode('Access Denied.'));
    exit();
}
// You should also have session token validation here.

$username = htmlspecialchars($_SESSION['username']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Admin Dashboard - StudyCore</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f0f2f5; color: #333; margin: 0; }
        .navbar { background-color: #fff; padding: 1rem 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .navbar-brand { font-size: 1.5rem; font-weight: 700; color: #1e40af; text-decoration: none; }
        .navbar-logout a { background-color: #dc3545; color: #fff; padding: 0.5rem 1rem; border-radius: 5px; text-decoration: none; font-weight: 500; }
        .container { padding: 2rem; max-width: 1200px; margin: 2rem auto; }
        .header { margin-bottom: 2rem; }
        .header h1 { color: #1e40af; margin: 0; }
        .header p { font-size: 1.2rem; color: #555; }
        .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; }
        .feature-card {
            background-color: #fff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            text-decoration: none;
            color: inherit;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }
        .feature-card h2 { margin-top: 0; color: #2563eb; }
        .feature-card p { color: #4b5563; line-height: 1.6; }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="dashboard.php" class="navbar-brand">StudyCore Master Panel</a>
        <div class="navbar-logout">
            <a href="../Login/logout.php">Logout</a>
        </div>
    </nav>
    <div class="container">
        <div class="header">
            <h1>Welcome, <?php echo $username; ?>!</h1>
            <p>Select a management panel to continue.</p>
        </div>

        <div class="features-grid">
            <a href="employee_management.php" class="feature-card">
                <h2>Employee Management</h2>
                <p>Add new privileged users (Admins, Educators, Support) and manage pending invitations.</p>
            </a>
            
            <a href="#" class="feature-card">
                <h2>User Management</h2>
                <p>View, search, and manage all registered student accounts on the platform.</p>
            </a>

            <a href="#" class="feature-card">
                <h2>Plan Management</h2>
                <p>Create, edit, and manage subscription plans available to users.</p>
            </a>
            
            <a href="#" class="feature-card">
                <h2>Site Analytics</h2>
                <p>View registration statistics, user activity, and other key site metrics.</p>
            </a>
        </div>
    </div>
</body>
</html>