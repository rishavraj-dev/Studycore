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
$related_materials = [];
try {
    $stmt_name = $conn->prepare("SELECT name FROM users WHERE id = ?");
    $stmt_name->bind_param("i", $user_id);
    $stmt_name->execute();
    $user = $stmt_name->get_result()->fetch_assoc();
    $full_name = htmlspecialchars($user['name'] ?? 'User');
    $first_initial = !empty($full_name) ? strtoupper(substr($full_name, 0, 1)) : '?';
    $stmt_name->close();

    $stmt_video = $conn->prepare("SELECT title, content_path_or_link, chapter_id FROM content_items WHERE id = ? AND content_type = 'video'");
    $stmt_video->bind_param("i", $content_id);
    $stmt_video->execute();
    $content_item = $stmt_video->get_result()->fetch_assoc();
    $stmt_video->close();
    if (!$content_item) { die("Video lecture not found."); }

    $stmt_materials = $conn->prepare("SELECT ci.id, ci.title, ci.content_type FROM lecture_materials_map map JOIN content_items ci ON map.material_id = ci.id WHERE map.lecture_id = ?");
    $stmt_materials->bind_param("i", $content_id);
    $stmt_materials->execute();
    $related_materials = $stmt_materials->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_materials->close();
} catch (Exception $e) { die("Database error: " . $e->getMessage()); }

$embed_url = "https://www.youtube.com/embed/" . htmlspecialchars($content_item['content_path_or_link']);
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
        .video-container { position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; max-width: 100%; background: #000; border-radius: 8px; }
        .video-container iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0; }
        .material-list { list-style: none; padding: 0; }
        .material-list li a { display: block; padding: 0.75rem 0; text-decoration: none; color: var(--primary-color); font-weight: 500; border-bottom: 1px solid var(--border-color); }
        .material-list li:last-child a { border-bottom: none; }
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
                <span class="back-arrow"><a href="chapter.php?id=<?php echo htmlspecialchars($content_item['chapter_id']); ?>">←</a></span>
                <h1><?php echo htmlspecialchars($content_item['title']); ?></h1>
            </div>

            <div class="content-card" style="padding: 0; overflow: hidden;">
                <div class="video-container">
                    <iframe src="<?php echo $embed_url; ?>" title="YouTube video player" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                </div>
            </div>
            
            <div class="content-card" style="margin-top: 2rem;">
                 <div class="tab-nav">
                    <button class="tab-link active">Study Material</button>
                    <!-- Future tabs like "My Notes" can be added here -->
                </div>
                <div class="tab-content active" style="padding: 1.5rem;">
                     <?php if (!empty($related_materials)): ?>
                        <ul class="material-list">
                            <?php foreach ($related_materials as $material): ?>
                                <li>
                                    <a href="view.php?id=<?php echo $material['id']; ?>" target="_blank">
                                        <?php echo htmlspecialchars($material['title']); ?> (<?php echo strtoupper($material['content_type']); ?>)
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>No study materials are linked to this lecture.</p>
                    <?php endif; ?>
                </div>
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