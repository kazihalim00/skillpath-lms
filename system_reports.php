<?php
/**
 * SkillPath Project: System Reports Page (Functional)
 *
 * This page pulls and displays a few key metrics from the database.
 */
session_start();
require_once 'db_config.php';

// Security Check: Must be logged in as Admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    session_unset();
    session_destroy();
    header("Location: login.html?status=error&message=Access%20Denied.%20Please%20log%20in%20as%20an%20Admin.");
    exit();
}
$user_name = $_SESSION['user_name'];
$total_users = 0;
$total_courses = 0;
$message = null;

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Fetch total users
    $user_result = $conn->query("SELECT COUNT(*) AS total FROM users");
    $total_users = $user_result->fetch_assoc()['total'];

    // Fetch total courses
    $course_result = $conn->query("SELECT COUNT(*) AS total FROM courses");
    $total_courses = $course_result->fetch_assoc()['total'];

    $conn->close();
} catch (Exception $e) {
    $message = "Database Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Reports | SkillPath</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f9;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col items-center p-4">

    <div class="w-full max-w-4xl bg-white shadow-xl rounded-xl p-8 md:p-12 mt-8">
        <div class="flex justify-between items-center border-b pb-4 mb-6">
            <h1 class="text-3xl font-bold text-gray-800">
                System Reports
            </h1>
            <a href="admin_dashboard.php" class="text-blue-500 hover:underline">
                &larr; Back to Dashboard
            </a>
        </div>

        <?php if ($message): ?>
            <div class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            <div class="bg-blue-100 border-l-4 border-blue-500 p-6 rounded-lg shadow-md">
                <p class="text-sm font-medium text-blue-700">Total Registered Users</p>
                <p class="mt-2 text-4xl font-extrabold text-blue-900"><?php echo $total_users; ?></p>
            </div>
            <div class="bg-green-100 border-l-4 border-green-500 p-6 rounded-lg shadow-md">
                <p class="text-sm font-medium text-green-700">Total Courses</p>
                <p class="mt-2 text-4xl font-extrabold text-green-900"><?php echo $total_courses; ?></p>
            </div>
        </div>

        <div class="mt-8 text-center text-gray-400 text-xs border-t pt-4">
            <p>Admin Page - Role: <?php echo $_SESSION['user_role']; ?> | User ID: <?php echo $_SESSION['user_id']; ?>
            </p>
        </div>
    </div>

</body>

</html>