<?php
/**
 * SkillPath Project: Student Grades Page (Initial Setup)
 *
 * This page will display the student's grades.
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
$user_name = $_SESSION['user_name'];
?>
<!DOCTYPE html>
<html lang="en">

<body class="min-h-screen flex flex-col items-center p-4">
    <div class="w-full max-w-4xl bg-white shadow-xl rounded-xl p-8 md:p-12 mt-8">
        <h1 class="text-3xl font-bold text-purple-700">My Official Grades (Initial Setup)</h1>
        <a href="student_dashboard.php" class="text-indigo-500 hover:text-indigo-700 font-semibold">&larr; Back to
            Dashboard</a>
        <div class="p-6 text-center text-gray-500 mt-8 border-dashed border-2 rounded-lg">
            <p class="text-lg mb-2">Your grades will be listed here.</p>
        </div>
    </div>
</body>

</html>