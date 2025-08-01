<?php
session_start();
header('Content-Type: application/json');
require_once '../config/db_connect.php';

// --- 1. Security & Initial Validation ---
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'master_admin'])) {
    echo json_encode(['status' => 'error', 'message' => 'Access Denied.']); exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']); exit();
}

// The 'action' parameter from the JavaScript determines what to do
$action = $_POST['action'] ?? '';

try {
    // --- 2. "Router" to handle different actions ---
    switch ($action) {
        case 'update_content':
            $content_id = intval($_POST['content_id'] ?? 0);
            $content_type = $_POST['content_type'] ?? '';
            if (empty($content_id) || empty($content_type)) {
                throw new Exception("Missing required content identifiers.");
            }

            if ($content_type === 'video') {
                $title = trim($_POST['title']); $video_id = trim($_POST['video_id']);
                $educator_id = !empty($_POST['educator_id']) ? intval($_POST['educator_id']) : null;
                $lecture_date = !empty($_POST['lecture_date']) ? $_POST['lecture_date'] : null;
                $duration = !empty($_POST['duration_minutes']) ? intval($_POST['duration_minutes']) : null;
                if (empty($title) || empty($video_id)) { throw new Exception("Title and Video ID are required."); }
                
                $stmt = $conn->prepare("UPDATE content_items SET title = ?, content_path_or_link = ?, educator_id = ?, lecture_date = ?, duration_minutes = ? WHERE id = ?");
                $stmt->bind_param("ssisii", $title, $video_id, $educator_id, $lecture_date, $duration, $content_id);
            
            } elseif ($content_type === 'pdf') {
                $title = trim($_POST['title']); $content_category = $_POST['content_category'];
                if (empty($title)) { throw new Exception("Title is required for PDF materials."); }
                
                if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
                    // Logic to handle file replacement
                    $stmt_old_path = $conn->prepare("SELECT content_path_or_link FROM content_items WHERE id = ?");
                    $stmt_old_path->bind_param("i", $content_id); $stmt_old_path->execute();
                    $old_file_path = $_SERVER['DOCUMENT_ROOT'] . $stmt_old_path->get_result()->fetch_assoc()['content_path_or_link'];
                    $stmt_old_path->close();
                    
                    // Securely handle the new file upload
                    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/'; // Ensure this folder exists and is writable
                    if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }
                    $new_file_name = uniqid('', true) . '.pdf';
                    if (!move_uploaded_file($_FILES['pdf_file']['tmp_name'], $upload_dir . $new_file_name)) { throw new Exception("Failed to move uploaded file."); }
                    $new_path_db = '/uploads/' . $new_file_name;

                    $stmt = $conn->prepare("UPDATE content_items SET title = ?, content_category = ?, content_path_or_link = ? WHERE id = ?");
                    $stmt->bind_param("sssi", $title, $content_category, $new_path_db, $content_id);
                    
                    if ($stmt->execute()) {
                        if (file_exists($old_file_path) && is_file($old_file_path)) { unlink($old_file_path); }
                        echo json_encode(['status' => 'success', 'message' => 'PDF details and file updated!', 'new_path' => $new_path_db, 'old_path' => $old_file_path]);
                        exit();
                    } else { throw new Exception("Database update failed."); }
                } else {
                    $stmt = $conn->prepare("UPDATE content_items SET title = ?, content_category = ? WHERE id = ?");
                    $stmt->bind_param("ssi", $title, $content_category, $content_id);
                }
            } else { throw new Exception("Invalid content type for update."); }
            
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Content updated successfully!']);
            } else { throw new Exception("Database update failed."); }
            break;

        case 'link_material':
            $lecture_id = intval($_POST['lecture_id']);
            $material_id = intval($_POST['material_id']);
            if (empty($lecture_id) || empty($material_id)) { throw new Exception("Invalid IDs provided."); }

            $stmt = $conn->prepare("INSERT IGNORE INTO lecture_materials_map (lecture_id, material_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $lecture_id, $material_id);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Material linked successfully.']);
            } else { throw new Exception("Database link failed."); }
            break;

        case 'unlink_material':
            $lecture_id = intval($_POST['lecture_id']);
            $material_id = intval($_POST['material_id']);
            if (empty($lecture_id) || empty($material_id)) { throw new Exception("Invalid IDs provided."); }

            $stmt = $conn->prepare("DELETE FROM lecture_materials_map WHERE lecture_id = ? AND material_id = ?");
            $stmt->bind_param("ii", $lecture_id, $material_id);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Material unlinked successfully.']);
            } else { throw new Exception("Database unlink failed."); }
            break;

        default:
            throw new Exception('Invalid action requested.');
    }

    if(isset($stmt)) $stmt->close();

} catch (Exception $e) {
    http_response_code(500); // Send a server error status
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>