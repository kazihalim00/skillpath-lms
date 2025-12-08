<?php
/**
 * SkillPath - Student Quiz Results
 * Fixed: Shows Exact Score (e.g., 8/10) instead of just percentage.
 */
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$quiz_id = $_GET['quiz_id'] ?? null;
$result_info = null;

if ($quiz_id) {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
        $sql = "SELECT q.title AS quiz_title, q.max_points, a.score, q.course_id
                FROM quiz_attempts a
                JOIN quizzes q ON a.quiz_id = q.id
                WHERE a.quiz_id = ? AND a.student_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $quiz_id, $user_id);
        $stmt->execute();
        $result_info = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Quiz Result</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f9;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col items-center justify-center p-4">

    <div class="w-full max-w-lg bg-white shadow-2xl rounded-xl p-8 text-center border-t-4 border-green-500">

        <?php if ($result_info):
            $score = $result_info['score'];
            $max = $result_info['max_points'];
            $percent = ($max > 0) ? round(($score / $max) * 100) : 0;
            ?>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Quiz Completed!</h1>
            <p class="text-gray-500 mb-6">You have submitted:
                <strong><?= htmlspecialchars($result_info['quiz_title']) ?></strong>
            </p>

            <div class="bg-green-50 rounded-xl p-6 border border-green-100 mb-6">
                <p class="text-sm text-green-600 font-bold uppercase tracking-wide">Your Score</p>
                <p class="text-5xl font-extrabold text-green-700 mt-2">
                    <?= $score ?> <span class="text-2xl text-green-400">/ <?= $max ?></span>
                </p>
                <p class="text-gray-500 text-sm mt-2"><?= $percent ?>% Accuracy</p>
            </div>

            <div class="flex justify-center gap-4">
                <a href="student_quizzes.php?course_id=<?= $result_info['course_id'] ?>"
                    class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg font-bold hover:bg-gray-300 transition">Back to
                    Quizzes</a>
            </div>

        <?php else: ?>
            <p class="text-red-500">Result not found.</p>
            <a href="student_dashboard.php" class="text-blue-500 underline mt-4 block">Go Home</a>
        <?php endif; ?>
    </div>

</body>

</html>