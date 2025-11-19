<?php
/**
 * SkillPath Project: Instructor Course Creation Page (Functional)
 *
 * This page allows the instructor to create a new course shell (title, code).
 */

session_start();
require_once 'db_config.php';

// Security Check: Must be logged in and the role must be 'instructor'
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'instructor') {
    header("Location: login.html?status=error&message=Access%20Denied.");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = null;
$message_type = null;

// --- Process Course Creation ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_course'])) {
    $course_code = trim($_POST['course_code']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $status = 'draft'; // All new courses start as draft

    if (empty($course_code) || empty($title)) {
        $message = "Error: Course Code and Title are required.";
        $message_type = "error";
    } else {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
            $sql = "INSERT INTO courses (course_code, title, description, instructor_id, status) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssis", $course_code, $title, $description, $user_id, $status);

            if ($stmt->execute()) {
                $new_course_id = $conn->insert_id;
                $message = "Success! Course '{$title}' created. You can now add materials and assignments.";
                // Redirect to the course list to show the new course
                header("Location: instructor_course_list.php?status=success&message=" . urlencode($message));
                exit();
            } else {
                $message = "Error creating course: " . $conn->error;
                $message_type = "error";
            }
            $stmt->close();
            $conn->close();

        } catch (Exception $e) {
            $message = "Database Error: " . $e->getMessage();
            $message_type = "error";
        }
    }
}

$user_name = $_SESSION['user_name'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Course | SkillPath</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f9;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col items-center p-4 bg-gray-100">

    <div class="w-full max-w-2xl bg-white shadow-xl rounded-xl p-8 md:p-12 mt-8">
        <div class="flex justify-between items-center border-b pb-4 mb-6">
            <h1 class="text-3xl font-bold text-teal-700">
                Create New Course
            </h1>
            <a href="instructor_dashboard.php" class="text-indigo-500 hover:text-indigo-700 font-semibold">
                &larr; Back to Dashboard
            </a>
        </div>

        <?php if ($message): ?>
            <div
                class="p-4 mb-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="instructor_course_create.php" class="space-y-4">
            <input type="hidden" name="create_course" value="1">

            <div>
                <label class="block text-sm font-medium text-gray-700">Course Code (e.g., SWE101)</label>
                <input type="text" name="course_code" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Course Title (e.g., Introduction to
                    Programming)</label>
                <input type="text" name="title" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Description</label>
                <textarea name="description" rows="4" class="w-full px-3 py-2 border rounded-lg"
                    placeholder="Enter a brief description of the course..."></textarea>
            </div>

            <button type="submit" class="w-full py-2 bg-teal-600 text-white font-medium rounded-lg hover:bg-teal-700">
                Save New Course
            </button>
        </form>

        <div class="mt-8 text-center text-gray-400 text-xs border-t pt-4">
            <p>Instructor Page | User ID: <?php echo $user_id; ?></p>
        </div>
    </div>
</body>

</html>