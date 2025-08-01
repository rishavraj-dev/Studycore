<?php
session_start();
require_once '../config/db_connect.php';

// --- 1. Security & Initial Fetch ---
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'master_admin'])) {
    header('Location: ../Login/login.php'); exit();
}
$content_id = intval($_GET['id'] ?? 0);
if (empty($content_id)) { die("Invalid request: No content ID specified."); }

// --- 2. Determine Content Type and Fetch All Necessary Data ---
$content_item = null; $linked_items = []; $available_items = [];
$educators = []; $all_categories = ['notes', 'dpp', 'exercise'];

try {
    $stmt_main = $conn->prepare("SELECT * FROM content_items WHERE id = ?");
    $stmt_main->bind_param("i", $content_id);
    $stmt_main->execute();
    $content_item = $stmt_main->get_result()->fetch_assoc();
    if (!$content_item) { die("Content item not found."); }
    $stmt_main->close();
    $chapter_id = $content_item['chapter_id'];

    if ($content_item['content_type'] === 'video') {
        $page_title = "Edit Lecture";
        $educators = $conn->query("SELECT id, name FROM educators ORDER BY name")->fetch_all(MYSQLI_ASSOC);
        
        $stmt_linked = $conn->prepare("SELECT ci.id, ci.title FROM content_items ci JOIN lecture_materials_map map ON ci.id = map.material_id WHERE map.lecture_id = ?");
        $stmt_linked->bind_param("i", $content_id);
        $stmt_linked->execute();
        $linked_items = $stmt_linked->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_linked->close();
        
        $linked_ids = array_column($linked_items, 'id');
        $linked_ids[] = $content_id;
        
        $query = "SELECT id, title FROM content_items WHERE chapter_id = ? AND content_type = 'pdf'";
        if (!empty($linked_ids)) {
            $in_clause = implode(',', array_fill(0, count($linked_ids), '?'));
            $query .= " AND id NOT IN ($in_clause)";
            $types = "i" . str_repeat('i', count($linked_ids));
            $params = array_merge([$chapter_id], $linked_ids);
        } else {
            $types = "i";
            $params = [$chapter_id];
        }
        $stmt_avail = $conn->prepare($query);
        $stmt_avail->bind_param($types, ...$params);
        $stmt_avail->execute();
        $available_items = $stmt_avail->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_avail->close();

    } elseif ($content_item['content_type'] === 'pdf') {
        $page_title = "Edit PDF Material";
        
        $stmt_linked = $conn->prepare("SELECT ci.id, ci.title FROM content_items ci JOIN lecture_materials_map map ON ci.id = map.lecture_id WHERE map.material_id = ?");
        $stmt_linked->bind_param("i", $content_id);
        $stmt_linked->execute();
        $linked_items = $stmt_linked->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_linked->close();
        $linked_ids = array_column($linked_items, 'id');
        
        $query = "SELECT id, title FROM content_items WHERE chapter_id = ? AND content_type = 'video'";
        if (!empty($linked_ids)) {
            $in_clause = implode(',', array_fill(0, count($linked_ids), '?'));
            $query .= " AND id NOT IN ($in_clause)";
            $types = "i" . str_repeat('i', count($linked_ids));
            $params = array_merge([$chapter_id], $linked_ids);
        } else {
            $types = "i";
            $params = [$chapter_id];
        }
        $stmt_avail = $conn->prepare($query);
        $stmt_avail->bind_param($types, ...$params);
        $stmt_avail->execute();
        $available_items = $stmt_avail->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_avail->close();
    }
} catch (Exception $e) { die("Database error: " . $e->getMessage()); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - StudyCore Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/admin_style.css">
    <style>
        .grid-container { display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; }
        .materials-manager { background-color: var(--bg-main); padding: 1.5rem; border-radius: 8px; }
        .materials-manager h3 { margin-top: 0; }
        .material-list { list-style: none; padding: 0; margin: 0; max-height: 200px; overflow-y: auto; border: 1px solid var(--border-color); border-radius: 6px; }
        .material-list li { display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; border-bottom: 1px solid var(--border-color); font-size: 0.9rem; }
        .material-list li:last-child { border-bottom: none; }
        .btn-link { background-color: var(--success-color); color: white; }
        .btn-unlink { background-color: var(--danger-color); color: white; }
        @media (max-width: 992px) { .grid-container { grid-template-columns: 1fr; } }
    </style>
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
            <h1><?php echo htmlspecialchars($page_title); ?></h1>
            <p><a href="content_management.php">← Back to Content Management</a></p>
        </div>
        <div id="messageContainer"></div>

        <div class="grid-container">
            <!-- Left Column: Main Edit Form -->
            <div class="content-card">
                <div style="padding: 1.5rem;">
                    <?php if ($content_item['content_type'] === 'video'): ?>
                        <form id="editForm" onsubmit="handleFormSubmit(event)">
                            <input type="hidden" name="content_id" value="<?php echo $content_item['id']; ?>">
                            <input type="hidden" name="content_type" value="video">
                            <div class="form-group"><label>Title</label><input type="text" name="title" value="<?php echo htmlspecialchars($content_item['title']); ?>" required></div>
                            <div class="form-group"><label>YouTube Video ID</label><input type="text" name="video_id" value="<?php echo htmlspecialchars($content_item['content_path_or_link']); ?>" required></div>
                            <div class="form-group"><label>Educator</label><select name="educator_id"><option value="">None</option><?php foreach ($educators as $e): ?><option value="<?php echo $e['id']; ?>" <?php if ($e['id'] == $content_item['educator_id']) echo 'selected'; ?>><?php echo htmlspecialchars($e['name']); ?></option><?php endforeach; ?></select></div>
                            <div class="form-group"><label>Lecture Date</label><input type="date" name="lecture_date" value="<?php echo htmlspecialchars($content_item['lecture_date']); ?>"></div>
                            <div class="form-group"><label>Duration (minutes)</label><input type="number" name="duration_minutes" value="<?php echo htmlspecialchars($content_item['duration_minutes']); ?>"></div>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </form>
                    <?php elseif ($content_item['content_type'] === 'pdf'): ?>
                        <form id="editForm" onsubmit="handleFormSubmit(event)" enctype="multipart/form-data">
                             <input type="hidden" name="content_id" value="<?php echo $content_item['id']; ?>">
                             <input type="hidden" name="content_type" value="pdf">
                             <div class="form-group"><label>Title</label><input type="text" name="title" value="<?php echo htmlspecialchars($content_item['title']); ?>" required></div>
                             <div class="form-group"><label>Content Category</label><select name="content_category"><?php foreach($all_categories as $cat): ?><option value="<?php echo $cat; ?>" <?php if($cat == $content_item['content_category']) echo 'selected'; ?>><?php echo ucfirst($cat); ?></option><?php endforeach; ?></select></div>
                             <div class="form-group"><label>Current File</label><p><a href="<?php echo htmlspecialchars($content_item['content_path_or_link']); ?>" target="_blank">View Current PDF</a></p></div>
                             <div class="form-group"><label>Upload New PDF (Optional)</label><small>Only select a file if you want to replace the current one.</small><input type="file" name="pdf_file" accept=".pdf"></div>
                             <button type="submit" class="btn btn-primary">Save Changes</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Right Column: Linking Manager -->
            <div class="materials-manager">
                <?php if ($content_item['content_type'] === 'video'): ?>
                    <h3>Link Materials to this Lecture</h3>
                    <div><h4>Currently Linked PDFs</h4><ul id="linkedList" class="material-list"><?php if(empty($linked_items)) echo '<li data-id="0">None</li>'; foreach($linked_items as $item) { echo "<li data-id='{$item['id']}'><span>".htmlspecialchars($item['title'])."</span><button class='btn btn-unlink' onclick=\"handleLink('unlink_material', {$content_item['id']}, {$item['id']})\">Unlink</button></li>"; } ?></ul></div>
                    <div style="margin-top: 1.5rem;"><h4>Available PDFs in this Chapter</h4><ul id="availableList" class="material-list"><?php if(empty($available_items)) echo '<li data-id="0">None</li>'; foreach($available_items as $item) { echo "<li data-id='{$item['id']}'><span>".htmlspecialchars($item['title'])."</span><button class='btn btn-link' onclick=\"handleLink('link_material', {$content_item['id']}, {$item['id']})\">Link</button></li>"; } ?></ul></div>
                <?php elseif ($content_item['content_type'] === 'pdf'): ?>
                     <h3>Link this PDF to Lectures</h3>
                    <div><h4>Currently Linked To</h4><ul id="linkedList" class="material-list"><?php if(empty($linked_items)) echo '<li data-id="0">None</li>'; foreach($linked_items as $item) { echo "<li data-id='{$item['id']}'><span>".htmlspecialchars($item['title'])."</span><button class='btn btn-unlink' onclick=\"handleLink('unlink_material', {$item['id']}, {$content_item['id']})\">Unlink</button></li>"; } ?></ul></div>
                    <div style="margin-top: 1.5rem;"><h4>Available Lectures in this Chapter</h4><ul id="availableList" class="material-list"><?php if(empty($available_items)) echo '<li data-id="0">None</li>'; foreach($available_items as $item) { echo "<li data-id='{$item['id']}'><span>".htmlspecialchars($item['title'])."</span><button class='btn btn-link' onclick=\"handleLink('link_material', {$item['id']}, {$content_item['id']})\">Link</button></li>"; } ?></ul></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

<script>
    const msgContainer = document.getElementById('messageContainer');

    async function handleFormSubmit(event) {
        event.preventDefault();
        const formData = new FormData(event.target);
        formData.append('action', 'update_content'); 
        
        const response = await fetch('handle_editor.php', { method: 'POST', body: formData });
        const data = await response.json();
        
        showMessage(data.message, data.status === 'success');
        if (data.status === 'success' && data.new_path) {
            // If a file was replaced, a reload is the safest way to update links
            setTimeout(() => window.location.reload(), 1500);
        }
    }

    async function handleLink(action, lectureId, materialId) {
        const response = await fetch('handle_editor.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=${action}&lecture_id=${lectureId}&material_id=${materialId}`
        });
        const data = await response.json();
        
        showMessage(data.message, data.status === 'success');
        if (data.status === 'success') {
            // Reload the page to see the updated lists
            setTimeout(() => window.location.reload(), 1000);
        }
    }
    
    function showMessage(msg, isSuccess) {
        const messageClass = isSuccess ? 'success' : 'error';
        msgContainer.innerHTML = `<p class="message ${messageClass}">${msg}</p>`;
        window.scrollTo(0, 0);
        setTimeout(() => msgContainer.innerHTML = '', 4000);
    }
</script>
<script src ="../JS/home.js"></script>
</body>
</html>