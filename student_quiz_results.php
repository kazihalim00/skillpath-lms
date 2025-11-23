<?php
/**
 * SkillPath Project: Student Quiz Results Page (Functional)
 *
 * This page displays the final score and quiz details after completion.
 * This fulfills the 'Track Progress' objective.
 */

session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: login.html?status=error&message=Access%20Denied.");
    exit();
}
$user_id = $_SESSION['user_id'];
$quiz_id = $_GET['quiz_id'] ?? null;
$result_info = null;
$message = null;

if ($quiz_id) {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

        // Fetch attempt data, score, and quiz name
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
        $message = "Database Error: " . $e->getMessage();
    }
} else {
    $message = "No quiz ID specified.";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Results | <?php echo htmlspecialchars($result_info['quiz_title'] ?? 'N/A'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f9;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col items-center p-4 bg-gray-100">

    <div class="w-full max-w-lg bg-white shadow-2xl rounded-xl p-8 md:p-12 mt-8">
        <div class="flex justify-between items-center border-b pb-4 mb-6">
            <h1 class="text-3xl font-bold text-gray-800">
                Quiz Results
            </h1>
            <a href="student_quizzes.php?course_id=<?php echo htmlspecialchars($result_info['course_id'] ?? 0); ?>"
                class="text-indigo-500 hover:text-indigo-700 font-semibold">
                &larr; Back to Quizzes
            </a>
        </div>

        <?php if ($result_info):
            $score = $result_info['score'];
            $max_points = $result_info['max_points'];
            $percentage = round(($score / $max_points) * 100);
            $grade_text = $percentage >= 80 ? 'Excellent! Passed.' : ($percentage >= 70 ? 'Good Job! Passed.' : 'Needs Improvement. Failed.');
            ?>
            <div class="text-center space-y-6">
                <p class="text-2xl font-semibold text-gray-700"><?php echo htmlspecialchars($result_info['quiz_title']); ?>
                </p>

                <div
                    class="p-6 rounded-xl <?php echo $percentage >= 70 ? 'bg-green-100 border-green-600' : 'bg-red-100 border-red-600'; ?> border-l-8">
                    <p class="text-5xl font-extrabold <?php echo $percentage >= 70 ? 'text-green-700' : 'text-red-700'; ?>">
                        <?php echo $percentage; ?>%
                    </p>
                    <p class="text-xl font-semibold mt-2 text-gray-800">
                        Final Score: **<?php echo htmlspecialchars($score); ?>** /
                        <?php echo htmlspecialchars($max_points); ?> Points
                    </p>
                </div>

                <p class="text-2xl font-bold text-gray-800"><?php echo $grade_text; ?></p>

                <div class="pt-4 border-t">
                    <a href="student_grades.php"
                        class="py-2 px-4 bg-purple-600 text-white font-medium rounded-lg shadow-md hover:bg-purple-700">
                        View All Grades Report
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="p-6 bg-red-100 text-red-700 rounded-lg">
                <?php echo htmlspecialchars($message ?? 'Could not load quiz results.'); ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>