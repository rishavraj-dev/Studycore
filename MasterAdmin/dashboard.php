<?php
session_start();
require_once '../config/db_connect.php';

// Security Guard: Check for Admin or Master Admin role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'master_admin'])) {
    header('Location: ../Login/login.php?error=' . urlencode('Access Denied.'));
    exit();
}
// Full session token validation would also go here for maximum security

$username = htmlspecialchars($_SESSION['username']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - StudyCore</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- This page now correctly uses the dedicated admin stylesheet -->
    <link rel="stylesheet" href="../CSS/admin_style.css">
</head>
<body>
    <nav class="navbar">
        <a href="dashboard.php" class="navbar-brand">StudyCore Admin</a>
        <div style="display: flex; align-items: center; gap: 1rem;">
            <button id="darkModeToggle" class="dark-mode-toggle" aria-label="Toggle dark mode">☀️</button>
            <a href="../Login/logout.php" class="btn-logout" style="color: white; text-decoration: none; background-color: var(--danger-color); padding: 0.5rem 1rem; border-radius: 6px;">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="header">
            <h1>Welcome, <?php echo $username; ?>!</h1>
            <p style="color: var(--text-light); font-size: 1.1rem;">Select a management panel to continue.</p>
        </div>

        <div class="features-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
            
            <!-- Card 1: Employee Management -->
            <a href="employee_management.php" class="feature-card" style="text-decoration: none; color: inherit;">
                <div class="content-card" style="padding: 2rem; transition: transform 0.2s, box-shadow 0.2s;">
                    <h2 style="margin-top: 0; color: var(--primary-color);">Employee Management</h2>
                    <p style="color: var(--text-light); line-height: 1.6;">Add new privileged users (Admins, Educators, Support) and manage pending invitations.</p>
                </div>
            </a>
            
            <!-- Card 2: Content Management (Links to the page we will build next) -->
            <a href="content_management.php" class="feature-card" style="text-decoration: none; color: inherit;">
                <div class="content-card" style="padding: 2rem; transition: transform 0.2s, box-shadow 0.2s;">
                    <h2 style="margin-top: 0; color: var(--primary-color);">Content Management</h2>
                    <p style="color: var(--text-light); line-height: 1.6;">Manage courses, subjects, chapters, and all educational content like PDFs and videos.</p>
                </div>
            </a>

            <!-- Card 3: Plan Management (Placeholder for the future) -->
            <a href="#" class="feature-card" style="text-decoration: none; color: inherit;">
                <div class="content-card" style="padding: 2rem; transition: transform 0.2s, box-shadow 0.2s;">
                    <h2 style="margin-top: 0; color: var(--primary-color);">Plan Management</h2>
                    <p style="color: var(--text-light); line-height: 1.6;">Create, edit, and manage subscription plans available to students.</p>
                </div>
            </a>
            
            <!-- Card 4: User Management (Placeholder for the future) -->
            <a href="#" class="feature-card" style="text-decoration: none; color: inherit;">
                <div class="content-card" style="padding: 2rem; transition: transform 0.2s, box-shadow 0.2s;">
                    <h2 style="margin-top: 0; color: var(--primary-color);">Student Management</h2>
                    <p style="color: var(--text-light); line-height: 1.6;">View, search, and manage all registered student accounts on the platform.</p>
                </div>
            </a>

        </div>
    </div>

    <script>
        // --- Dark Mode Logic ---
        document.addEventListener('DOMContentLoaded', () => {
            const toggleButton = document.getElementById('darkModeToggle');
            if (toggleButton) {
                const applyTheme = (theme) => {
                    document.body.classList.toggle('dark-mode', theme === 'dark');
                    toggleButton.textContent = theme === 'dark' ? '🌙' : '☀️';
                };
                const savedTheme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                applyTheme(savedTheme);
                toggleButton.addEventListener('click', () => {
                    const newTheme = document.body.classList.contains('dark-mode') ? 'light' : 'dark';
                    localStorage.setItem('theme', newTheme);
                    applyTheme(newTheme);
                });
            }
            // Add hover effect to cards
            document.querySelectorAll('.feature-card .content-card').forEach(card => {
                card.addEventListener('mouseenter', () => {
                    card.style.transform = 'translateY(-5px)';
                    card.style.boxShadow = '0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -4px rgba(0,0,0,0.1)';
                });
                card.addEventListener('mouseleave', () => {
                    card.style.transform = 'none';
                    card.style.boxShadow = 'var(--shadow-md)';
                });
            });
        });
    </script>
</body>
</html>