<?php
/**
 * SkillPath Project: Instructor Course Creation Page
 * Fix: Corrected Session variable name to prevent "Undefined array key" error.
 */

session_start();
require_once 'db_config.php';

// STANDARD SECURITY CHECK
// Checks if user is logged in AND is an instructor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
// FIX: Use 'name' instead of 'user_name' to match your login logic
$user_name = $_SESSION['name'] ?? 'Instructor';

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

            // Prepare SQL to prevent SQL Injection
            $sql = "INSERT INTO courses (course_code, title, description, instructor_id, status) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssis", $course_code, $title, $description, $user_id, $status);

            if ($stmt->execute()) {
                $new_course_id = $conn->insert_id;
                $message = "Success! Course '{$title}' created successfully.";
                $message_type = "success";
                // Optional: Redirect to dashboard after success to avoid re-submission
                // header("Location: instructor_dashboard.php"); 
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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Course | SkillPath</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f7f9fb;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col items-center p-4">

    <div class="w-full max-w-2xl bg-white shadow-xl rounded-xl p-8 md:p-12 mt-8 border-t-4 border-teal-600">

        <div class="flex justify-between items-center border-b pb-4 mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Create New Course</h1>
                <p class="text-sm text-gray-500 mt-1">Instructor: <?php echo htmlspecialchars($user_name); ?></p>
            </div>
            <a href="instructor_dashboard.php" class="text-indigo-600 hover:text-indigo-800 font-semibold transition">
                &larr; Back to Dashboard
            </a>
        </div>

        <?php if ($message): ?>
            <div
                class="p-4 mb-6 rounded-lg text-sm font-medium <?php echo $message_type === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="instructor_course_create.php" class="space-y-6">
            <input type="hidden" name="create_course" value="1">

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Course Code</label>
                <input type="text" name="course_code" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition"
                    placeholder="e.g., SWE101">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Course Title</label>
                <input type="text" name="title" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition"
                    placeholder="e.g., Introduction to Programming">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                <textarea name="description" rows="4"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition"
                    placeholder="Enter a brief description of the course..."></textarea>
            </div>

            <button type="submit"
                class="w-full py-3 bg-teal-600 text-white font-bold rounded-lg hover:bg-teal-700 shadow-md transition duration-200">
                Save New Course
            </button>
        </form>

        <div class="mt-8 text-center text-gray-400 text-xs border-t pt-4">
            <p>SkillPath Internal System | User ID: <?php echo $user_id; ?></p>
        </div>
    </div>
</body>

</html>