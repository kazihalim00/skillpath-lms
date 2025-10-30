<?php
/**
 * SkillPath Project: Student Dashboard
 * * This is the secure landing page for Student users.
 */

// Start a session to access user login information
session_start();

// Security Check: Must be logged in and the role must be 'student'
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    session_unset();
    session_destroy();
    header("Location: login.html?status=error&message=Access%20Denied.%20Please%20log%20in.");
    exit();
}

// User is successfully authenticated as a Student.
$user_name = $_SESSION['user_name'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | SkillPath</title>
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
        <h1 class="text-4xl font-extrabold text-blue-700 mb-2">
            Welcome Back, <?php echo htmlspecialchars(explode(' ', $user_name)[0]); ?>
        </h1>
        <p class="text-lg text-gray-500 mb-8 border-b pb-4">
            You are logged in as a **Student**.
        </p>

        <h2 class="text-2xl font-bold text-gray-700 mb-6">Your Courses & Progress</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <div
                class="bg-gray-50 p-6 rounded-xl shadow-lg border-t-4 border-blue-500 hover:shadow-xl transition duration-300">
                <h3 class="text-xl font-semibold text-gray-800">My Enrolled Courses</h3>
                <p class="text-gray-600 mt-2 text-sm">Access your course materials, lectures, and quizzes.</p>
                <a href="student_courses.php"
                    class="text-blue-500 hover:text-blue-700 mt-3 block text-sm font-medium">View Courses →</a>
            </div>

            <div
                class="bg-gray-50 p-6 rounded-xl shadow-lg border-t-4 border-purple-500 hover:shadow-xl transition duration-300">
                <h3 class="text-xl font-semibold text-gray-800">Check Grades</h3>
                <p class="text-gray-600 mt-2 text-sm">View your official grades and performance summaries.</p>
                <a href="student_grades.php"
                    class="text-purple-500 hover:text-purple-700 mt-3 block text-sm font-medium">View Grades →</a>
            </div>
        </div>

        <div class="mt-8 text-center text-gray-400 text-xs border-t pt-4">
            <p>Debug Info: Role: <?php echo $_SESSION['user_role']; ?> | User ID: <?php echo $_SESSION['user_id']; ?>
            </p>
        </div>
    </div>
</body>

</html>