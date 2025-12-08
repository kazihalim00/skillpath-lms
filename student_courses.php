<?php
/**
 * SkillPath - Student Courses View
 * Fixed: Session keys. Shows courses assigned by Admin via Batch.
 */
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$courses = [];

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
    $sql = "SELECT c.id, c.title, c.course_code, c.description, u.full_name as instructor 
            FROM enrollments e
            JOIN courses c ON e.course_id = c.id
            LEFT JOIN users u ON c.instructor_id = u.id
            WHERE e.student_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $conn->close();
} catch (Exception $e) {
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Courses</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f9;
        }
    </style>
</head>

<body class="p-8 flex justify-center">
    <div class="w-full max-w-6xl bg-white shadow-xl rounded-xl p-8 border-t-4 border-purple-500">
        <div class="flex justify-between items-center mb-6 border-b pb-4">
            <h1 class="text-3xl font-bold text-gray-800">My Enrolled Courses</h1>
            <a href="student_dashboard.php" class="text-purple-600 hover:underline">&larr; Dashboard</a>
        </div>

        <?php if (empty($courses)): ?>
            <div class="p-8 text-center text-gray-500 border-2 border-dashed rounded-lg">
                No courses assigned. Please wait for Admin allocation.
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($courses as $c): ?>
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition">
                        <div class="flex justify-between items-start mb-3">
                            <span
                                class="bg-purple-100 text-purple-700 text-xs font-bold px-2 py-1 rounded"><?= htmlspecialchars($c['course_code']) ?></span>
                        </div>
                        <h2 class="text-xl font-bold text-gray-800 mb-2"><?= htmlspecialchars($c['title']) ?></h2>
                        <p class="text-xs text-gray-400 mb-4">Instructor: <?= htmlspecialchars($c['instructor']) ?></p>

                        <a href="student_course_content.php?course_id=<?= $c['id'] ?>"
                            class="block w-full bg-purple-600 text-white text-center py-2 rounded text-sm font-bold hover:bg-purple-700 transition">
                            Enter Course
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>