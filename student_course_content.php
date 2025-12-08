<?php
/**
 * SkillPath - Student Course Content
 * Fixed: Added 'download' attribute to prevent browser errors with .docx files.
 */
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$course_id = $_GET['course_id'] ?? null;
$course_info = null;
$grouped_materials = [];

if ($course_id) {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

        // Course Info
        $sql = "SELECT title, course_code FROM courses WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        $course_info = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Materials
        $mat_sql = "SELECT * FROM course_materials WHERE course_id = ? ORDER BY module_title ASC";
        $mat_stmt = $conn->prepare($mat_sql);
        $mat_stmt->bind_param("i", $course_id);
        $mat_stmt->execute();
        $res = $mat_stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $grouped_materials[$row['module_title']][] = $row;
        }
        $conn->close();
    } catch (Exception $e) {
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title><?= htmlspecialchars($course_info['title'] ?? 'Course') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f9;
        }
    </style>
</head>

<body class="p-8 flex justify-center">
    <div class="w-full max-w-5xl bg-white shadow-xl rounded-xl p-8 border-t-4 border-teal-500">
        <div class="flex justify-between items-center mb-6 border-b pb-4">
            <h1 class="text-2xl font-bold text-gray-800">
                <?= htmlspecialchars($course_info['title'] ?? 'Error') ?>
                <span class="text-gray-400 text-lg">(<?= htmlspecialchars($course_info['course_code'] ?? '') ?>)</span>
            </h1>
            <a href="student_courses.php" class="text-teal-600 hover:underline">&larr; Back to Courses</a>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-8">
            <a href="student_assignments.php?course_id=<?= $course_id ?>"
                class="bg-blue-50 text-blue-700 text-center py-3 rounded-lg font-bold hover:bg-blue-100">View
                Assignments</a>
            <a href="student_quizzes.php?course_id=<?= $course_id ?>"
                class="bg-yellow-50 text-yellow-700 text-center py-3 rounded-lg font-bold hover:bg-yellow-100">Take
                Quizzes</a>
        </div>

        <h2 class="text-xl font-bold text-gray-700 mb-4">Course Materials</h2>
        <?php if (empty($grouped_materials)): ?>
            <p class="text-gray-500 italic">No materials uploaded yet.</p>
        <?php else: ?>
            <?php foreach ($grouped_materials as $module => $items): ?>
                <div class="mb-6 border rounded-lg overflow-hidden">
                    <div class="bg-gray-100 px-4 py-2 font-bold text-gray-700"><?= htmlspecialchars($module) ?></div>
                    <ul class="divide-y">
                        <?php foreach ($items as $item): ?>
                            <li class="p-4 flex justify-between hover:bg-gray-50">
                                <span><?= htmlspecialchars($item['material_title']) ?></span>
                                <a href="<?= htmlspecialchars($item['file_url']) ?>" download
                                    class="text-teal-600 font-bold text-sm flex items-center gap-1 hover:text-teal-800">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    Download
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>

</html>