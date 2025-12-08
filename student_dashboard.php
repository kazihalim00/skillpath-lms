<?php
/**
 * SkillPath - Student Dashboard
 * Fixed: Session keys ('role', 'name') and Batch Info display.
 */
session_start();
require_once 'db_config.php';

// 1. SECURITY CHECK (FIXED)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$student_name = $_SESSION['name'] ?? 'Student';
$batch_name = "No Batch Assigned";

// 2. Fetch Batch Info
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
    $sql_user = "SELECT b.batch_name FROM users u LEFT JOIN batches b ON u.batch_id = b.id WHERE u.id = ?";
    $stmt = $conn->prepare($sql_user);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $batch_name = $row['batch_name'] ?? "No Batch Assigned";
    }
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Student Dashboard | SkillPath</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f7f9fb;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col">

    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center">
                    <span class="text-2xl font-bold text-teal-600">SkillPath <span class="text-gray-400 text-lg">|
                            Student</span></span>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600">
                        <?= htmlspecialchars($student_name) ?>
                        <span
                            class="bg-teal-50 text-teal-700 text-xs px-2 py-1 rounded ml-2 border border-teal-100"><?= htmlspecialchars($batch_name) ?></span>
                    </span>
                    <a href="logout.php"
                        class="bg-red-50 text-red-600 px-3 py-1 rounded text-sm hover:bg-red-100 transition">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 w-full">

        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-800">Student Portal</h2>
            <p class="text-gray-600 mt-2">Welcome back. Here is your academic overview.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">

            <a href="student_assignments.php"
                class="bg-white p-6 rounded-lg shadow-sm border-t-4 border-blue-500 hover:shadow-md transition group">
                <h3 class="text-xl font-bold text-gray-800 mb-2 group-hover:text-blue-600">My Assignments</h3>
                <p class="text-gray-600 mb-4 text-sm">View pending tasks, due dates, and submit your files.</p>
                <span class="text-blue-600 font-semibold text-sm">View Assignments &rarr;</span>
            </a>

            <a href="student_courses.php"
                class="bg-white p-6 rounded-lg shadow-sm border-t-4 border-purple-500 hover:shadow-md transition group">
                <h3 class="text-xl font-bold text-gray-800 mb-2 group-hover:text-purple-600">Enrolled Courses</h3>
                <p class="text-gray-600 mb-4 text-sm">Access course materials and details assigned to your batch.</p>
                <span class="text-purple-600 font-semibold text-sm">Go to Courses &rarr;</span>
            </a>

            <a href="student_grades.php"
                class="bg-white p-6 rounded-lg shadow-sm border-t-4 border-teal-500 hover:shadow-md transition group">
                <h3 class="text-xl font-bold text-gray-800 mb-2 group-hover:text-teal-600">60 Marks Info</h3>
                <p class="text-gray-600 mb-4 text-sm">Check your Attendance, Class Test, and Viva scores.</p>
                <span class="text-teal-600 font-semibold text-sm">Check Grades &rarr;</span>
            </a>

            <a href="student_my_appeals.php"
                class="bg-white p-6 rounded-lg shadow-sm border-t-4 border-orange-500 hover:shadow-md transition group">
                <h3 class="text-xl font-bold text-gray-800 mb-2 group-hover:text-orange-600">My Appeals</h3>
                <p class="text-gray-600 mb-4 text-sm">Check the status of your grade challenges.</p>
                <span class="text-orange-600 font-semibold text-sm">View Status &rarr;</span>
            </a>

        </div>
    </main>
</body>

</html>