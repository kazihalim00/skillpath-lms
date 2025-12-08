<?php
/**
 * SkillPath - Student 60 Marks Info
 * Fixed: Shows Custom Columns and allows Specific Challenges.
 */
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$grades = [];

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

    // Fetch Course Info + Marks + Custom Column Names
    $sql = "SELECT c.id as course_id, c.title, c.course_code,
                   c.assess_1_name, c.assess_2_name, c.assess_3_name,
                   COALESCE(m.attendance, 0) as att,
                   COALESCE(m.class_test, 0) as ct,
                   COALESCE(m.viva, 0) as viva,
                   COALESCE(m.total, 0) as total,
                   u.full_name as instructor
            FROM enrollments e
            JOIN courses c ON e.course_id = c.id
            LEFT JOIN users u ON c.instructor_id = u.id
            LEFT JOIN student_marks m ON (e.student_id = m.student_id AND e.course_id = m.course_id)
            WHERE e.student_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $grades = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $conn->close();
} catch (Exception $e) {
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>My Grades</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f9;
        }
    </style>
</head>

<body class="p-8 flex justify-center">

    <div class="w-full max-w-6xl bg-white shadow-xl rounded-xl p-8 border-t-4 border-teal-500">
        <div class="flex justify-between items-center mb-8 border-b pb-4">
            <h1 class="text-3xl font-bold text-gray-800">60 Marks Info</h1>
            <a href="student_dashboard.php" class="text-teal-600 hover:underline">&larr; Back</a>
        </div>

        <?php if (empty($grades)): ?>
            <p class="text-center text-gray-500 p-8">No grades found.</p>
        <?php else: ?>
            <div class="grid gap-6">
                <?php foreach ($grades as $g):
                    $col1 = $g['assess_1_name'] ?: 'Attendance';
                    $col2 = $g['assess_2_name'] ?: 'Class Test';
                    $col3 = $g['assess_3_name'] ?: 'Viva';
                    ?>
                    <div class="border rounded-xl p-6 bg-gray-50 hover:shadow-md transition">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h2 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($g['title']) ?></h2>
                                <p class="text-sm text-gray-500"><?= htmlspecialchars($g['course_code']) ?> | Instr:
                                    <?= htmlspecialchars($g['instructor']) ?>
                                </p>
                            </div>
                            <div class="text-right">
                                <span class="block text-2xl font-bold text-teal-700"><?= $g['total'] ?> / 60</span>
                                <span class="text-xs text-gray-400">Total Score</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-4 text-center">
                            <div class="bg-white p-3 rounded border">
                                <p class="text-xs text-gray-500 uppercase"><?= htmlspecialchars($col1) ?></p>
                                <p class="text-lg font-bold"><?= $g['att'] ?></p>
                                <a href="student_appeal.php?course_id=<?= $g['course_id'] ?>&component=<?= urlencode($col1) ?>&title=<?= urlencode($g['title']) ?>"
                                    class="text-xs text-orange-600 hover:underline">Challenge</a>
                            </div>
                            <div class="bg-white p-3 rounded border">
                                <p class="text-xs text-gray-500 uppercase"><?= htmlspecialchars($col2) ?></p>
                                <p class="text-lg font-bold"><?= $g['ct'] ?></p>
                                <a href="student_appeal.php?course_id=<?= $g['course_id'] ?>&component=<?= urlencode($col2) ?>&title=<?= urlencode($g['title']) ?>"
                                    class="text-xs text-orange-600 hover:underline">Challenge</a>
                            </div>
                            <div class="bg-white p-3 rounded border">
                                <p class="text-xs text-gray-500 uppercase"><?= htmlspecialchars($col3) ?></p>
                                <p class="text-lg font-bold"><?= $g['viva'] ?></p>
                                <a href="student_appeal.php?course_id=<?= $g['course_id'] ?>&component=<?= urlencode($col3) ?>&title=<?= urlencode($g['title']) ?>"
                                    class="text-xs text-orange-600 hover:underline">Challenge</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>