<?php
session_start();
require_once '../config/db_connect.php';

// --- 1. Security & Validation ---
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'master_admin'])) {
    header('Location: ../Login/login.php'); exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: content_management.php'); exit();
}

$type = $_POST['type'] ?? '';
$admin_id = $_SESSION['user_id'];

try {
    // --- 2. "Router" to handle different creation types ---
    switch ($type) {
        case 'course':
            $course_name = trim($_POST['course_name']);
            $course_key = trim($_POST['course_key']);
            $description = trim($_POST['description']);
            if (empty($course_name) || empty($course_key)) { throw new Exception("Course Name and Key are required."); }
            
            $stmt = $conn->prepare("INSERT INTO courses (course_name, course_key, description) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $course_name, $course_key, $description);
            break;

        case 'subject':
            $subject_name = trim($_POST['subject_name']);
            $parent_id = intval($_POST['parent_id']); // course_id
            if (empty($subject_name) || $parent_id === 0) { throw new Exception("Subject Name and a parent Course are required."); }

            $stmt = $conn->prepare("INSERT INTO subjects (subject_name, course_id) VALUES (?, ?)");
            $stmt->bind_param("si", $subject_name, $parent_id);
            break;

        case 'chapter':
            $chapter_name = trim($_POST['chapter_name']);
            $parent_id = intval($_POST['parent_id']); // subject_id
            if (empty($chapter_name) || $parent_id === 0) { throw new Exception("Chapter Name and a parent Subject are required."); }

            $stmt = $conn->prepare("INSERT INTO chapters (chapter_name, subject_id) VALUES (?, ?)");
            $stmt->bind_param("si", $chapter_name, $parent_id);
            break;

        case 'content_item':
            // This is the most complex case
            $title = trim($_POST['title']);
            $parent_id = intval($_POST['parent_id']); // chapter_id
            $content_category = $_POST['content_category'];
            $content_type = $_POST['content_type'];
            $educator_id = !empty($_POST['educator_id']) ? intval($_POST['educator_id']) : null;
            
            $content_path = '';

            if ($content_type === 'video') {
                $content_path = trim($_POST['video_id']);
                $lecture_date = !empty($_POST['lecture_date']) ? $_POST['lecture_date'] : null;
                $duration = !empty($_POST['duration_minutes']) ? intval($_POST['duration_minutes']) : null;
            } elseif ($content_type === 'pdf') {
                if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
                    // --- Secure File Upload Logic ---
                    $upload_dir = '../uploads/'; // IMPORTANT: Make sure this folder exists and is writable!
                    if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }
                    
                    $file_tmp_path = $_FILES['pdf_file']['tmp_name'];
                    $file_name = $_FILES['pdf_file']['name'];
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                    if ($file_ext !== 'pdf') { throw new Exception("Only PDF files are allowed."); }
                    
                    // Create a unique filename to prevent overwrites
                    $new_file_name = uniqid('', true) . '.' . $file_ext;
                    $dest_path = $upload_dir . $new_file_name;

                    if (!move_uploaded_file($file_tmp_path, $dest_path)) {
                        throw new Exception("Failed to move uploaded file.");
                    }
                    // We store the relative path from the website root
                    $content_path = '/uploads/' . $new_file_name;
                    $lecture_date = null; // PDFs don't have these details
                    $duration = null;
                } else {
                    throw new Exception("PDF file upload failed or no file was selected.");
                }
            }
            
            $stmt = $conn->prepare("INSERT INTO content_items (chapter_id, educator_id, title, content_category, content_type, content_path_or_link, lecture_date, duration_minutes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iisssssi", $parent_id, $educator_id, $title, $content_category, $content_type, $content_path, $lecture_date, $duration);
            break;

        default:
            throw new Exception("Invalid form submission type.");
    }
    
    // --- 3. Execute the Query & Redirect ---
    if ($stmt->execute()) {
        header('Location: content_management.php?message=' . urlencode(ucfirst($type) . ' created successfully!'));
    } else {
        throw new Exception("Database execution failed: " . $conn->error);
    }
    $stmt->close();
    exit();

} catch(Exception $e) {
    // Redirect back to the creator page with an error
    $error_url = "creator.php?type={$type}&error=" . urlencode($e->getMessage());
    if(isset($_POST['parent_id'])) {
        // Re-append the parent ID to the URL to keep context
        $param_name = str_replace('_item', '', $type) . '_id';
        if ($type === 'subject') $param_name = 'course_id';
        $error_url .= "&{$param_name}=" . intval($_POST['parent_id']);
    }
    header("Location: " . $error_url);
    exit();
}
?>