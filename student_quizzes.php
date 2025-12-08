<?php
/**
 * SkillPath - Student Quizzes List
 * Fixed: Session keys and SQL logic.
 */
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$course_id = $_GET['course_id'] ?? 0;
$quizzes = [];

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

    // Fetch Quizzes + Attempt Status
    $sql = "SELECT q.id, q.title, q.max_points, q.due_date, qa.score, qa.completed_at 
            FROM quizzes q
            LEFT JOIN quiz_attempts qa ON (q.id = qa.quiz_id AND qa.student_id = ?)
            WHERE q.course_id = ? AND q.status = 'published'
            ORDER BY q.due_date ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $quizzes = $result->fetch_all(MYSQLI_ASSOC);
    }
    $conn->close();
} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Quizzes</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f9;
        }
    </style>
</head>

<body class="p-8 flex justify-center">
    <div class="w-full max-w-4xl bg-white shadow-xl rounded-xl p-8 border-t-4 border-yellow-500">
        <div class="flex justify-between items-center mb-6 border-b pb-4">
            <h1 class="text-3xl font-bold text-gray-800">Available Quizzes</h1>
            <a href="student_course_content.php?course_id=<?= $course_id ?>"
                class="text-yellow-600 hover:underline font-semibold">&larr; Back to Course</a>
        </div>

        <?php if (empty($quizzes)): ?>
            <div class="p-10 text-center border-2 border-dashed border-gray-300 rounded-lg text-gray-500">
                No quizzes available for this course yet.
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($quizzes as $q): ?>
                    <div class="p-6 border rounded-lg bg-gray-50 flex justify-between items-center hover:shadow-sm transition">
                        <div>
                            <h3 class="font-bold text-lg text-gray-800"><?= htmlspecialchars($q['title']) ?></h3>
                            <p class="text-sm text-gray-500 mt-1">Due: <?= date('M d, Y', strtotime($q['due_date'])) ?> |
                                Points: <?= $q['max_points'] ?></p>
                        </div>
                        <div>
                            <?php if ($q['completed_at']): ?>
                                <span
                                    class="bg-green-100 text-green-800 px-3 py-1 rounded-full font-bold text-sm border border-green-200">
                                    Score: <?= $q['score'] ?> / <?= $q['max_points'] ?>
                                </span>
                            <?php else: ?>
                                <a href="student_take_quiz.php?quiz_id=<?= $q['id'] ?>"
                                    class="bg-yellow-500 text-white px-5 py-2 rounded-lg font-bold hover:bg-yellow-600 shadow-sm transition">
                                    Start Quiz
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>