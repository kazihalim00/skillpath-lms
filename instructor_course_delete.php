<?php
/**
 * SkillPath Project: Instructor Course Delete Page
 *
 * This page handles the deletion of a course from the database.
 */

session_start();
require_once 'db_config.php';

// Security Check: Must be logged in as an Instructor
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'instructor') {
    header("Location: login.php?status=error&message=Access%20Denied.");
    exit();
}

$user_id = $_SESSION['user_id'];
$course_id = $_GET['id'] ?? null;
$message = null;

if ($course_id) {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }

        $sql = "DELETE FROM courses WHERE id = ? AND instructor_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $course_id, $user_id);

        if ($stmt->execute()) {
            $message = "Success! Course has been deleted.";
        } else {
            $message = "Error: Could not delete course. " . $conn->error;
        }
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        $message = "Database Error: " . $e->getMessage();
    }
} else {
    $message = "Error: No course ID provided.";
}

// Redirect back to the course list with a status message
header("Location: instructor_course_list.php?status=delete&message=" . urlencode($message));
exit();
?>