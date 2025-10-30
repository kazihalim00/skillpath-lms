<?php
/**
 * SkillPath Project: Student Courses Page (Initial Setup)
 *
 * This page will display all available courses and handle enrollment.
 */
session_start();
require_once 'db_config.php';

// Security Check: Must be logged in and the role must be 'student'
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    session_unset();
    session_destroy();
    header("Location: login.html?status=error&message=Access%20Denied.");
    exit();
}
// NOTE: Functional logic will be added in a later commit
$user_name = $_SESSION['user_name'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Courses | SkillPath</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen flex flex-col items-center p-4">
    <div class="w-full max-w-6xl bg-white shadow-xl rounded-xl p-8 md:p-12 mt-8">
        <h1 class="text-3xl font-bold text-blue-700">Course Catalog (Initial Setup)</h1>
        <a href="student_dashboard.php" class="text-indigo-500 hover:text-indigo-700 font-semibold">&larr; Back to
            Dashboard</a>
        <div class="p-6 text-center text-gray-500 mt-8 border-dashed border-2 rounded-lg">
            <p class="text-lg mb-2">Available courses will be displayed here for enrollment.</p>
        </div>
    </div>
</body>

</html>