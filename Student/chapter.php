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
$chapter_id = $_GET['id'] ?? 0;
if (empty($chapter_id)) { die("No chapter selected."); }

$chapter = [];
$content_items = [];
try {
    // Fetch user's name for the header
    $stmt_name = $conn->prepare("SELECT name FROM users WHERE id = ?");
    $stmt_name->bind_param("i", $user_id);
    $stmt_name->execute();
    $user = $stmt_name->get_result()->fetch_assoc();
    $full_name = htmlspecialchars($user['name'] ?? 'User');
    $first_initial = !empty($full_name) ? strtoupper(substr($full_name, 0, 1)) : '?';
    
    // Fetch chapter details and its parent course for the back link
    $stmt_chapter = $conn->prepare("SELECT c.chapter_name, co.course_key FROM chapters c JOIN subjects s ON c.subject_id = s.id JOIN courses co ON s.course_id = co.id WHERE c.id = ?");
    $stmt_chapter->bind_param("i", $chapter_id);
    $stmt_chapter->execute();
    $chapter = $stmt_chapter->get_result()->fetch_assoc();
    if (!$chapter) { die("Chapter not found."); }
    
    // Fetch all content items for this chapter, joining with educator info
    $stmt_content = $conn->prepare("SELECT ci.*, e.name as educator_name, e.thumbnail_url FROM content_items ci LEFT JOIN educators e ON ci.educator_id = e.id WHERE ci.chapter_id = ? ORDER BY ci.lecture_date, ci.id");
    $stmt_content->bind_param("i", $chapter_id);
    $stmt_content->execute();
    $results = $stmt_content->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($results as $item) {
        $content_items[$item['content_category']][] = $item;
    }
} catch (Exception $e) { die("Database error: " . $e->getMessage()); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($chapter['chapter_name']); ?> - StudyCore</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/chapter_style.css">
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
                <!-- Back arrow now correctly links to the parent course page -->
                <span class="back-arrow"><a href="course.php?view=<?php echo htmlspecialchars($chapter['course_key']); ?>">←</a></span>
                <h1><?php echo htmlspecialchars($chapter['chapter_name']); ?></h1>
            </div>

            <div class="content-card">
                <div class="tab-nav">
                    <button class="tab-link active" onclick="openTab(event, 'live_class')">Live Class</button>
                    <button class="tab-link" onclick="openTab(event, 'notes')">Notes</button>
                    <button class="tab-link" onclick="openTab(event, 'dpp')">DPP</button>
                    <button class="tab-link" onclick="openTab(event, 'exercise')">Exercise</button>
                    <button class="tab-link" onclick="openTab(event, 'pyq')">PYQ's</button>
                </div>

                <!-- Live Class Tab -->
                <div id="live_class" class="tab-content active">
                    <div class="content-grid">
                        <?php if (!empty($content_items['live_class'])): foreach ($content_items['live_class'] as $item): ?>
                            <div class="lecture-card">
                                <div class="lecture-details">
                                    <div class="lecture-thumbnail"><img src="<?php echo htmlspecialchars($item['thumbnail_url'] ?? '/images/default_avatar.png'); ?>" alt="Educator"></div>
                                    <div class="lecture-info">
                                        <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                                        <p>by <?php echo htmlspecialchars($item['educator_name'] ?? 'StudyCore'); ?></p>
                                        <div class="lecture-meta">
                                            <span>📅 <?php echo date('d M Y', strtotime($item['lecture_date'])); ?></span>
                                            <span>🕒 <?php echo htmlspecialchars($item['duration_minutes']); ?> min</span>
                                        </div>
                                    </div>
                                    <a href="player.php?id=<?php echo $item['id']; ?>" class="btn-watch">Watch now</a>
                                </div>
                            </div>
                        <?php endforeach; else: ?>
                            <p>No lectures available for this chapter yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Notes Tab -->
                <div id="notes" class="tab-content">
                    <ul class="chapter-list" style="list-style-type: disc; padding-left: 20px;">
                        <?php if (!empty($content_items['notes'])): foreach ($content_items['notes'] as $item): ?>
                            <li><a href="view.php?id=<?php echo $item['id']; ?>" style="border:none; padding: 0.5rem 0;"><?php echo htmlspecialchars($item['title']); ?> (PDF)</a></li>
                        <?php endforeach; else: ?>
                            <p>No notes available for this chapter yet.</p>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- DPP Tab -->
                <div id="dpp" class="tab-content">
                    <ul class="chapter-list" style="list-style-type: disc; padding-left: 20px;">
                         <?php if (!empty($content_items['dpp'])): foreach ($content_items['dpp'] as $item): ?>
                            <li><a href="view.php?id=<?php echo $item['id']; ?>" style="border:none; padding: 0.5rem 0;"><?php echo htmlspecialchars($item['title']); ?> (PDF)</a></li>
                        <?php endforeach; else: ?>
                            <p>No DPPs available for this chapter yet.</p>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <!-- PYQ Tab -->
                 <div id="pyq" class="tab-content">
                    <p>Please visit the Test Series section for Previous Year Questions. <a href="test_series.php">Go to Test Series</a></p>
                </div>
                
                <!-- Exercise Tab -->
                 <div id="exercise" class="tab-content">
                    <p>Exercises for this chapter are coming soon!</p>
                </div>
            </div>
        </main>
    </div>
    <script src="../JS/student_dashboard.js"></script>
    <script>
        // --- Tab Switching Logic ---
        function openTab(event, tabName) {
            // Hide all tab content panes
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            // Deactivate all tab link buttons
            document.querySelectorAll('.tab-link').forEach(link => link.classList.remove('active'));
            
            // Show the target tab content
            document.getElementById(tabName).classList.add('active');
            // Activate the clicked tab link
            event.currentTarget.classList.add('active');
        }

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
        });
    </script>
</body>
</html>