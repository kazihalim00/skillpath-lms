<?php
/**
 * SkillPath - Admin Dashboard
 * Updated: Restored "Batch Course Allocation" button.
 */
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['name'] ?? 'Administrator';

// Optional: Fetch quick stats
$stats = ['users' => 0, 'courses' => 0];
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
    $stats['users'] = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
    $stats['courses'] = $conn->query("SELECT COUNT(*) FROM courses")->fetch_row()[0];
    $conn->close();
} catch (Exception $e) {
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | SkillPath</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f9;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col">

    <nav class="bg-slate-800 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center">
                    <span class="text-xl font-bold tracking-tight">SkillPath <span
                            class="text-slate-400">Admin</span></span>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-slate-300">Welcome, <?= htmlspecialchars($admin_name) ?></span>
                    <a href="logout.php"
                        class="bg-red-600 hover:bg-red-700 px-3 py-1 rounded text-sm font-medium transition">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 w-full">

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
            <div class="bg-white p-6 rounded-lg shadow border-l-4 border-blue-500">
                <p class="text-sm text-gray-500 font-medium uppercase">Total Users</p>
                <p class="text-3xl font-bold text-gray-800"><?= $stats['users'] ?></p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow border-l-4 border-teal-500">
                <p class="text-sm text-gray-500 font-medium uppercase">Total Courses</p>
                <p class="text-3xl font-bold text-gray-800"><?= $stats['courses'] ?></p>
            </div>
        </div>

        <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-2">Administrative Tools</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

            <div class="bg-white p-6 rounded-xl shadow-sm hover:shadow-md transition border-t-4 border-indigo-600">
                <h3 class="text-lg font-bold text-gray-800 mb-2">Batch Course Allocation</h3>
                <p class="text-gray-600 text-sm mb-4">Assign specific courses to an entire batch (e.g., "Batch 4 takes
                    SWE101").</p>
                <a href="admin_enroll_batch.php"
                    class="text-indigo-600 font-bold hover:underline inline-flex items-center">
                    Manage Enrollments &rarr;
                </a>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm hover:shadow-md transition border-t-4 border-purple-600">
                <h3 class="text-lg font-bold text-gray-800 mb-2">Instructors by Dept</h3>
                <p class="text-gray-600 text-sm mb-4">View list of instructors filtered by their department (e.g., SWE).
                </p>
                <a href="admin_view_instructors.php"
                    class="text-purple-600 font-bold hover:underline inline-flex items-center">
                    View Instructors &rarr;
                </a>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm hover:shadow-md transition border-t-4 border-blue-600">
                <h3 class="text-lg font-bold text-gray-800 mb-2">Manage Structure</h3>
                <p class="text-gray-600 text-sm mb-4">Create or edit Departments, Batches, and Sections.</p>
                <a href="admin_manage_structure.php"
                    class="text-blue-600 font-bold hover:underline inline-flex items-center">
                    Edit Structure &rarr;
                </a>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm hover:shadow-md transition border-t-4 border-teal-600">
                <h3 class="text-lg font-bold text-gray-800 mb-2">Course Oversight</h3>
                <p class="text-gray-600 text-sm mb-4">View all courses and monitor instructor activity.</p>
                <a href="course_oversight.php" class="text-teal-600 font-bold hover:underline inline-flex items-center">
                    View Courses &rarr;
                </a>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm hover:shadow-md transition border-t-4 border-orange-500">
                <h3 class="text-lg font-bold text-gray-800 mb-2">Manage Users</h3>
                <p class="text-gray-600 text-sm mb-4">Edit or delete user accounts (Students/Instructors).</p>
                <a href="admin_manage_users.php"
                    class="text-orange-600 font-bold hover:underline inline-flex items-center">
                    Edit Users &rarr;
                </a>
            </div>

        </div>
    </main>
</body>

</html>