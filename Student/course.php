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
$course_key = $_GET['view'] ?? '';
if (empty($course_key)) { die("Error: No course selected."); }

// Initialize variables
$course = [];
$subjects = [];
$full_name = 'User';
$first_initial = '?';

try {
    // Fetch user's name for the header
    $stmt_name = $conn->prepare("SELECT name FROM users WHERE id = ?");
    $stmt_name->bind_param("i", $user_id);
    $stmt_name->execute();
    $user = $stmt_name->get_result()->fetch_assoc();
    $full_name = htmlspecialchars($user['name'] ?? 'User');
    $first_initial = !empty($full_name) ? strtoupper(substr($full_name, 0, 1)) : '?';
    $stmt_name->close();

    // Fetch course details
    $stmt_course = $conn->prepare("SELECT id, course_name, description FROM courses WHERE course_key = ?");
    $stmt_course->bind_param("s", $course_key);
    $stmt_course->execute();
    $course = $stmt_course->get_result()->fetch_assoc();
    $stmt_course->close();

    if ($course) {
        // Fetch subjects and their chapters for this specific course
        $stmt_subjects = $conn->prepare("
            SELECT s.id as subject_id, s.subject_name, c.id as chapter_id, c.chapter_name 
            FROM subjects s 
            LEFT JOIN chapters c ON s.id = c.subject_id 
            WHERE s.course_id = ? 
            ORDER BY s.id, c.id
        ");
        $stmt_subjects->bind_param("i", $course['id']);
        $stmt_subjects->execute();
        $results = $stmt_subjects->get_result()->fetch_all(MYSQLI_ASSOC);
        // Group the chapters under their respective subjects
        foreach ($results as $row) {
            $subjects[$row['subject_id']]['subject_name'] = $row['subject_name'];
            if ($row['chapter_id']) {
                $subjects[$row['subject_id']]['chapters'][] = ['id' => $row['chapter_id'], 'name' => $row['chapter_name']];
            }
        }
        $stmt_subjects->close();
    } else {
        die("Course not found.");
    }
} catch (Exception $e) { die("Database Error: " . $e->getMessage()); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['course_name']); ?> - StudyCore</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/course_style.css">
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
            <div class="page-header">
                <h1><?php echo htmlspecialchars($course['course_name']); ?></h1>
                <p><?php echo htmlspecialchars($course['description'] ?? 'Select a subject below to view its chapters and start learning.'); ?></p>
            </div>

            <div class="content-card">
                <div class="tab-nav">
                    <?php if (empty($subjects)): ?>
                        <p style="padding: 0 1.5rem;">Subjects for this course are coming soon.</p>
                    <?php else: ?>
                        <?php $is_first_subject = true; foreach ($subjects as $subject_id => $subject): ?>
                            <button class="tab-link <?php if($is_first_subject) { echo 'active'; $is_first_subject = false; } ?>" onclick="openTab(event, 'subject-<?php echo $subject_id; ?>')">
                                <?php echo htmlspecialchars($subject['subject_name']); ?>
                            </button>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div style="padding: 1.5rem;">
                    <div class="table-toolbar">
                        <h2 style="margin:0; font-size: 1.25rem;">Chapters</h2>
                        <input type="search" id="chapterSearch" class="search-bar" placeholder="Search for a chapter...">
                    </div>

                    <?php if (!empty($subjects)): ?>
                        <?php $is_first_subject = true; foreach ($subjects as $subject_id => $subject): ?>
                            <div id="subject-<?php echo $subject_id; ?>" class="tab-content <?php if($is_first_subject) { echo 'active'; $is_first_subject = false; } ?>">
                                <?php if (!empty($subject['chapters'])): ?>
                                    <ul class="chapter-list">
                                        <?php foreach ($subject['chapters'] as $chapter): ?>
                                            <li><a href="chapter.php?id=<?php echo $chapter['id']; ?>"><?php echo htmlspecialchars($chapter['name']); ?></a></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p style="text-align: center; padding: 2rem; color: var(--text-light);">Chapters for <?php echo htmlspecialchars($subject['subject_name']); ?> are coming soon!</p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <script src="../JS/student_dashboard.js"></script>
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
        });

        // --- Tab Switching Logic ---
        function openTab(event, tabId) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-link').forEach(link => link.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            event.currentTarget.classList.add('active');
        }

        // --- Live Chapter Search Logic ---
        const searchInput = document.getElementById('chapterSearch');
        if (searchInput) {
            searchInput.addEventListener('keyup', () => {
                const filter = searchInput.value.toLowerCase();
                const allChapterLinks = document.querySelectorAll('.chapter-list li'); 
                allChapterLinks.forEach(item => {
                    const text = item.textContent || item.innerText;
                    item.style.display = text.toLowerCase().includes(filter) ? "" : "none";
                });
            });
        }
    </script>
</body>
</html>