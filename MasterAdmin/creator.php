<?php
session_start();
require_once '../config/db_connect.php';

// --- Security & Page Setup ---
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'master_admin'])) {
    header('Location: ../Login/login.php?error=' . urlencode('Access Denied.'));
    exit();
}
$admin_name = $_SESSION['username'];
$type = $_GET['type'] ?? '';
$page_title = "Create New Item";
$parent_id = 0;
$parent_name = '';

// --- Fetch contextual data (e.g., parent course/subject name) ---
try {
    switch ($type) {
        case 'subject':
            $page_title = "Add New Subject";
            $parent_id = intval($_GET['course_id'] ?? 0);
            $stmt = $conn->prepare("SELECT course_name FROM courses WHERE id = ?");
            $stmt->bind_param("i", $parent_id); $stmt->execute();
            $parent_name = $stmt->get_result()->fetch_assoc()['course_name'] ?? 'Unknown Course';
            break;
        case 'chapter':
            $page_title = "Add New Chapter";
            $parent_id = intval($_GET['subject_id'] ?? 0);
            $stmt = $conn->prepare("SELECT subject_name FROM subjects WHERE id = ?");
            $stmt->bind_param("i", $parent_id); $stmt->execute();
            $parent_name = $stmt->get_result()->fetch_assoc()['subject_name'] ?? 'Unknown Subject';
            break;
        case 'content_item':
            $page_title = "Add New Content";
            $parent_id = intval($_GET['chapter_id'] ?? 0);
            $stmt = $conn->prepare("SELECT chapter_name FROM chapters WHERE id = ?");
            $stmt->bind_param("i", $parent_id); $stmt->execute();
            $parent_name = $stmt->get_result()->fetch_assoc()['chapter_name'] ?? 'Unknown Chapter';
            // Fetch educators for the dropdown
            $educators = $conn->query("SELECT id, name FROM educators ORDER BY name")->fetch_all(MYSQLI_ASSOC);
            break;
        case 'course': $page_title = "Add New Course"; break;
        default: die("Invalid creation type specified.");
    }
} catch (Exception $e) { die("Database error: " . $e->getMessage()); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - StudyCore</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/admin_style.css">
</head>
<body>
    <nav class="navbar">
        <a href="dashboard.php" class="navbar-brand">StudyCore Admin</a>
        <div style="display: flex; align-items: center; gap: 1rem;">
            <button id="darkModeToggle" class="dark-mode-toggle" aria-label="Toggle dark mode">☀️</button>
            <a href="../Login/logout.php" class="btn" style="background-color: var(--danger-color); color: white;">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="header">
            <h1><?php echo $page_title; ?></h1>
            <p><a href="content_management.php">← Back to Content Management</a></p>
        </div>
        
        <div class="content-card" style="max-width: 800px; margin: auto;">
            <div style="padding: 1.5rem;">
                <?php if (isset($_GET['error'])): ?><p class="message error"><?php echo htmlspecialchars($_GET['error']); ?></p><?php endif; ?>
                
                <?php if ($parent_name): ?>
                    <p style="font-size: 1.1rem;">Adding to: <strong><?php echo htmlspecialchars($parent_name); ?></strong></p>
                <?php endif; ?>

                <!-- The form `enctype` is CRITICAL for file uploads -->
                <form action="handle_creator.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
                    <input type="hidden" name="parent_id" value="<?php echo htmlspecialchars($parent_id); ?>">

                    <?php if ($type === 'course'): ?>
                        <!-- Course Form -->
                        <div class="form-group"><label for="course_name">Course Name</label><input type="text" id="course_name" name="course_name" required></div>
                        <div class="form-group"><label for="course_key">Course Key (e.g., 'jee')</label><input type="text" id="course_key" name="course_key" required></div>
                        <div class="form-group"><label for="description">Description</label><textarea id="description" name="description" style="width:100%; min-height:100px;"></textarea></div>

                    <?php elseif ($type === 'subject'): ?>
                        <!-- Subject Form -->
                        <div class="form-group"><label for="subject_name">Subject Name</label><input type="text" id="subject_name" name="subject_name" required></div>

                    <?php elseif ($type === 'chapter'): ?>
                        <!-- Chapter Form -->
                        <div class="form-group"><label for="chapter_name">Chapter Name</label><input type="text" id="chapter_name" name="chapter_name" required></div>

                    <?php elseif ($type === 'content_item'): ?>
                        <!-- Content Item Form (Upgraded) -->
                        <div class="form-group"><label for="title">Content Title</label><input type="text" id="title" name="title" required></div>
                        <div class="form-group"><label for="content_category">Content Category</label>
                            <select id="content_category" name="content_category" required>
                                <option value="live_class">Live Class (Video)</option>
                                <option value="notes">Notes (PDF)</option>
                                <option value="dpp">DPP (PDF)</option>
                                <option value="exercise">Exercise (PDF)</option>
                            </select>
                        </div>
                        <input type="hidden" id="content_type" name="content_type" value="video"> <!-- Hidden input to hold the type -->
                        
                        <div id="videoInputGroup" class="form-group">
                            <label for="content_path_or_link_text">YouTube Video ID</label>
                            <input type="text" id="content_path_or_link_text" name="video_id" placeholder="e.g., dQw4w9WgXcQ">
                        </div>
                        <div id="pdfInputGroup" class="form-group" style="display:none;">
                            <label for="pdf_file">Upload PDF File</label>
                            <input type="file" id="pdf_file" name="pdf_file" accept=".pdf">
                        </div>
                        
                        <div class="form-group"><label for="educator_id">Educator</label>
                            <select id="educator_id" name="educator_id"><option value="">None</option>
                                <?php foreach ($educators as $educator): ?><option value="<?php echo $educator['id']; ?>"><?php echo htmlspecialchars($educator['name']); ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div id="videoDetailsGroup">
                             <div class="form-group"><label for="lecture_date">Lecture Date</label><input type="date" id="lecture_date" name="lecture_date"></div>
                             <div class="form-group"><label for="duration_minutes">Duration (in minutes)</label><input type="number" id="duration_minutes" name="duration_minutes"></div>
                        </div>

                    <?php endif; ?>

                    <button type="submit" class="btn btn-success">Save New Item</button>
                </form>
            </div>
        </div>
    </div>
    <script src ="../JS/admin_dashboard.js"></script>
    <script src ="../JS/home.js"></script>
    <script>

        // --- Smart Form Logic for Content Creator ---
        const categorySelect = document.getElementById('content_category');
        if (categorySelect) {
            const contentTypeInput = document.getElementById('content_type');
            const videoGroup = document.getElementById('videoInputGroup');
            const videoDetails = document.getElementById('videoDetailsGroup');
            const pdfGroup = document.getElementById('pdfInputGroup');
            const videoInput = document.getElementById('content_path_or_link_text');
            const pdfInput = document.getElementById('pdf_file');

            categorySelect.addEventListener('change', function() {
                const selectedCategory = this.value;

                if (selectedCategory === 'live_class') {
                    // Show video fields, hide PDF fields
                    contentTypeInput.value = 'video';
                    videoGroup.style.display = 'block';
                    videoDetails.style.display = 'block';
                    pdfGroup.style.display = 'none';
                    videoInput.required = true;
                    pdfInput.required = false;
                } else {
                    // Show PDF fields, hide video fields
                    contentTypeInput.value = 'pdf';
                    videoGroup.style.display = 'none';
                    videoDetails.style.display = 'none';
                    pdfGroup.style.display = 'block';
                    videoInput.required = false;
                    pdfInput.required = true;
                }
            });
            // Trigger change on page load to set initial state
            categorySelect.dispatchEvent(new Event('change'));
        }
    </script>
</body>
</html>