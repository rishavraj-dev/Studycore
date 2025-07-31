<?php
session_start();
require_once '../config/db_connect.php';

// --- 1. Security Guard ---
if (!isset($_SESSION['user_id'])) { header('Location: ../Login/login.php'); exit(); }
// Add your full session token validation here for security.
$user_id = $_SESSION['user_id'];

// --- 2. Fetch User and Subscription Status ---
$user = [];
$has_active_plan = false; // Default to false
try {
    // Fetch user's name
    $stmt_user = $conn->prepare("SELECT name FROM users WHERE id = ?");
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $user = $stmt_user->get_result()->fetch_assoc();
    $stmt_user->close();

    // ** THE CRITICAL SUBSCRIPTION CHECK **
    $stmt_sub = $conn->prepare("SELECT 1 FROM user_subscriptions WHERE user_id = ? AND status = 'active' AND end_date > NOW() LIMIT 1");
    $stmt_sub->bind_param("i", $user_id);
    $stmt_sub->execute();
    if ($stmt_sub->get_result()->num_rows === 1) {
        $has_active_plan = true;
    }
    $stmt_sub->close();
} catch (Exception $e) { die("Database error."); }

$full_name = htmlspecialchars($user['name'] ?? 'User');
$first_initial = !empty($full_name) ? strtoupper(substr($full_name, 0, 1)) : '?';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - StudyCore</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Link to the new, self-contained stylesheet -->
    <link rel="stylesheet" href="../CSS/home.css">
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
            <!-- ** THIS IS THE CONDITIONAL LOGIC FOR THE OVERLAY ** -->
            <?php if (!$has_active_plan): ?>
                <div class="content-locked-overlay"></div>
                <div class="expired-plan-notice">
                    <h2>Your Plan Has Expired</h2>
                    <p>Please purchase or reactivate a plan to continue using our services.</p>
                    <br>
                    <a href="manage_plan.php" class="btn-primary">Go to Plans Page</a>
                </div>
            <?php endif; ?>
            
            <div class="welcome-card" style="margin-bottom: 2rem;">
                <h1>Welcome back, <?php echo explode(' ', $full_name)[0]; ?>!</h1>
                <p>Choose a section below to start your learning journey.</p>
            </div>

            <div class="features-grid">
                <a href="#" class="feature-card"><h2>JEE Prep</h2><p>Access exclusive materials, mock tests, and strategies for the Joint Entrance Examination.</p></a>
                <a href="#" class="feature-card"><h2>NEET Prep</h2><p>Unlock our comprehensive resources for the National Eligibility cum Entrance Test.</p></a>
                <a href="#" class="feature-card"><h2>Test Series</h2><p>Practice with our advanced test series for all major competitive exams.</p></a>
                <a href="#" class="feature-card"><h2>Class 12</h2><p>Master your board exam subjects with detailed notes and practice questions.</p></a>
                <a href="#" class="feature-card"><h2>Class 11</h2><p>Build a strong foundation for your final school years and competitive exams.</p></a>
                <a href="#" class="feature-card"><h2>Class 10</h2><p>Excel in your board exams with our specially curated study materials.</p></a>
            </div>
        </main>
    </div>

    <!-- The shared dashboard script for dropdowns and dark mode -->
    <script src="../JS/student_dashboard.js"></script>
    <!-- The new, page-specific script -->
    <script src="../JS/home.js"></script>
</body>
</html>