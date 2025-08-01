<?php
session_start();
header('Content-Type: application/json');
require_once '../config/db_connect.php';

// --- Security Check ---
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'master_admin'])) {
    echo json_encode(['error' => 'Access Denied.']);
    exit();
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_courses':
            $res = $conn->query("SELECT id, course_name FROM courses ORDER BY course_name");
            $data = $res->fetch_all(MYSQLI_ASSOC);
            break;

        case 'get_subjects':
            $course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
            if ($course_id === 0) throw new Exception('Course ID is required.');
            $stmt = $conn->prepare("SELECT id, subject_name FROM subjects WHERE course_id=? ORDER BY subject_name");
            $stmt->bind_param("i", $course_id);
            $stmt->execute();
            $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            break;

        case 'get_chapters':
            $subject_id = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;
            if ($subject_id === 0) throw new Exception('Subject ID is required.');
            $stmt = $conn->prepare("SELECT id, chapter_name FROM chapters WHERE subject_id=? ORDER BY chapter_name");
            $stmt->bind_param("i", $subject_id);
            $stmt->execute();
            $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            break;

        case 'get_content_items':
            $chapter_id = isset($_GET['chapter_id']) ? intval($_GET['chapter_id']) : 0;
            if ($chapter_id === 0) throw new Exception('Chapter ID is required.');
            $stmt = $conn->prepare("SELECT id, title, content_category FROM content_items WHERE chapter_id=? ORDER BY title");
            $stmt->bind_param("i", $chapter_id);
            $stmt->execute();
            $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            break;

        default:
            throw new Exception('Invalid action requested.');
    }
    echo json_encode($data);

} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
