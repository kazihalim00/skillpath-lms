<?php
/**
 * SkillPath Project: Instructor Dashboard
 * * This is the secure landing page for Instructor users.
 */

session_start();
require_once 'db_config.php'; // Include DB config

// Security Check: Must be logged in and the role must be 'instructor'
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'instructor') {
    session_unset();
    session_destroy();
    header("Location: login.html?status=error&message=Access%20Denied.%20Please%20log%20in.");
    exit();
}

// Get user info
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
// NOTE: Course fetching logic omitted in this initial commit for simplicity

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Dashboard | SkillPath</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f9;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col items-center p-4">

    <div class="w-full max-w-4xl flex justify-end mb-4">
        <a href="logout.php"
            class="py-2 px-4 bg-red-500 text-white font-semibold rounded-lg shadow-md hover:bg-red-600 transition duration-300">
            Logout
        </a>
    </div>

    <div class="w-full max-w-4xl bg-white shadow-2xl rounded-xl p-8 md:p-12">
        <h1 class="text-4xl font-extrabold text-teal-700 mb-2">
            Hello, Professor <?php echo htmlspecialchars(explode(' ', $user_name)[0]); ?>
        </h1>
        <p class="text-lg text-gray-500 mb-8 border-b pb-4">
            You are logged in as an **Instructor**.
        </p>

        <div class="mb-8 border border-gray-200 rounded-xl p-4 bg-gray-50">
            <p class="text-center text-gray-600">Course list loading...</p>
        </div>

        <h2 class="text-2xl font-bold text-gray-700 mb-6">Course Management Tools</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <div
                class="bg-white p-6 rounded-xl shadow-lg border-t-4 border-teal-500 hover:shadow-xl transition duration-300">
                <h3 class="text-xl font-semibold text-gray-800">Create New Course</h3>
                <p class="text-gray-600 mt-2 text-sm">Design and publish new learning modules for students.</p>
                <a href="instructor_course_create.php"
                    class="text-teal-500 hover:text-teal-700 mt-3 block text-sm font-medium">Start Creation →</a>
            </div>

            <div
                class="bg-white p-6 rounded-xl shadow-lg border-t-4 border-orange-500 hover:shadow-xl transition duration-300">
                <h3 class="text-xl font-semibold text-gray-800">Grade Submissions</h3>
                <p class="text-gray-600 mt-2 text-sm">Review student assignments and provide timely feedback.</p>
                <a href="instructor_gradebook.php"
                    class="text-orange-500 hover:text-orange-700 mt-3 block text-sm font-medium">Go to Grading →</a>
            </div>
        </div>

        <div class="mt-8 text-center text-gray-400 text-xs border-t pt-4">
            <p>Debug Info: Role: <?php echo $_SESSION['user_role']; ?> | User ID: <?php echo $_SESSION['user_id']; ?>
            </p>
        </div>
    </div>
</body>
</html>