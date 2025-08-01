<?php
session_start();
require_once '../config/db_connect.php';

// --- 1. Check if user is logged in ---
if (!isset($_SESSION['user_id'])) { header('Location: ../Login/login.php?error=' . urlencode('You must be logged in.')); exit(); }
$user_id = $_SESSION['user_id'];

// --- 2. Full Session Token Validation ---
try {
    if (!isset($_SESSION['session_token'])) { throw new Exception("Session token missing."); }
    $stmt_session = $conn->prepare("SELECT session_id FROM active_sessions WHERE user_id = ? AND session_token = ?");
    $stmt_session->bind_param("is", $user_id, $_SESSION['session_token']);
    $stmt_session->execute();
    if ($stmt_session->get_result()->num_rows === 0) { throw new Exception("Session token invalid."); }
    $stmt_session->close();
} catch (Exception $e) {
    session_unset(); session_destroy();
    header('Location: ../Login/login.php?error=' . urlencode('Your session is invalid. Please log in again.'));
    exit();
}

// --- 3. Subscription Status Check ---
$has_active_plan = false;
try {
    $stmt_sub = $conn->prepare("SELECT 1 FROM user_subscriptions WHERE user_id = ? AND status = 'active' AND end_date > NOW() LIMIT 1");
    $stmt_sub->bind_param("i", $user_id);
    $stmt_sub->execute();
    if ($stmt_sub->get_result()->num_rows === 1) { $has_active_plan = true; }
    $stmt_sub->close();
} catch (Exception $e) { /* Fails safe to false */ }

if (!$has_active_plan) {
    header('Location: manage_plan.php?err=' . urlencode('You need an active subscription to access this content.'));
    exit();
}

// --- Page-Specific Logic Starts Here ---
$content_id = $_GET['id'] ?? 0;
if (empty($content_id)) { die("Error: No content selected."); }

$content_item = null;
try {
    $stmt_name = $conn->prepare("SELECT name FROM users WHERE id = ?");
    $stmt_name->bind_param("i", $user_id);
    $stmt_name->execute();
    $user = $stmt_name->get_result()->fetch_assoc();
    $full_name = htmlspecialchars($user['name'] ?? 'User');
    $first_initial = !empty($full_name) ? strtoupper(substr($full_name, 0, 1)) : '?';
    $stmt_name->close();

    $stmt = $conn->prepare("SELECT title, content_path_or_link FROM content_items WHERE id = ? AND content_type = 'pdf'");
    $stmt->bind_param("i", $content_id);
    $stmt->execute();
    $content_item = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$content_item) { die("PDF material not found."); }
} catch (Exception $e) { die("Database error: " . $e->getMessage()); }

$pdf_path = htmlspecialchars($content_item['content_path_or_link']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($content_item['title']); ?> - StudyCore</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/chapter_style.css">
    <style>
        .pdf-viewer { width: 100%; height: calc(100vh - 200px); border: 1px solid var(--border-color); border-radius: 8px; }
        .main-content { display: flex; flex-direction: column; height: calc(100vh - 80px); /* Full height minus header */ }
        .content-card { flex-grow: 1; display: flex; flex-direction: column; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <a href="home.php" class="header-logo">StudyCore</a>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <button id="darkModeToggle" class="dark-mode-toggle" aria-label="Toggle dark mode">☀️</button>
                <div class="profile-section">
                    <button id="profileButton" class="profile-button">
                        <div class="profile-avatar"><?php echo $first_initial; ?></div>
                        <span class="profile-name"><?php echo $full_name; ?></span>
                    </button>
                    <div id="profileDropdown" class="profile-dropdown">
                        <a href="home.php">Home</a>
                        <a href="my_profile.php">My Profile</a>
                        <a href="manage_plan.php">Manage Plan</a>
                        <div class="dropdown-divider"></div>
                        <a href="../Login/logout.php">Logout</a>
                    </div>
                </div>
            </div>
        </header>
        <main class="main-content">
             <div class="chapter-header">
                <span class="back-arrow"><a href="javascript:history.back()">←</a></span>
                <h1><?php echo htmlspecialchars($content_item['title']); ?></h1>
            </div>
            <div class="content-card">
                <iframe src="<?php echo $pdf_path; ?>" class="pdf-viewer" title="<?php echo htmlspecialchars($content_item['title']); ?>"></iframe>
            </div>
        </main>
    </div>
    <script src="../JS/student_dashboard.js"></script>
    <script>
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
        });
    </script>
</body>
</html>