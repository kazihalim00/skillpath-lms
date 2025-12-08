<?php
/**
 * SkillPath - Student Take Quiz
 * Fixed: Robust submission logic to ensure students can attend properly.
 */
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$quiz_id = $_GET['quiz_id'] ?? ($_POST['quiz_id'] ?? null);
$message = "";
$questions = [];
$quiz_info = null;

// --- Handle Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finish_quiz'])) {
    $quiz_id = $_POST['quiz_id'];
    $max_points = intval($_POST['max_points_total']);
    $answers = $_POST['answers'] ?? [];

    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

        // 1. Get Correct Answers
        $stmt = $conn->prepare("SELECT id, correct_answer FROM questions WHERE quiz_id = ?");
        $stmt->bind_param("i", $quiz_id);
        $stmt->execute();
        $res = $stmt->get_result();

        $correct_count = 0;
        $total_q = 0;

        while ($row = $res->fetch_assoc()) {
            $qid = $row['id'];
            if (isset($answers[$qid]) && $answers[$qid] === $row['correct_answer']) {
                $correct_count++;
            }
            $total_q++;
        }
        $stmt->close();

        // 2. Calculate Score
        $final_score = 0;
        if ($total_q > 0) {
            $final_score = round(($correct_count / $total_q) * $max_points);
        }

        // 3. Save Attempt
        $save_stmt = $conn->prepare("REPLACE INTO quiz_attempts (quiz_id, student_id, score, completed_at) VALUES (?, ?, ?, NOW())");
        $save_stmt->bind_param("iii", $quiz_id, $user_id, $final_score);

        if ($save_stmt->execute()) {
            // Redirect to Results Page
            header("Location: student_quiz_results.php?quiz_id=$quiz_id");
            exit();
        } else {
            $message = "Error saving quiz: " . $save_stmt->error;
        }
        $conn->close();

    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
    }
}

// --- Fetch Quiz ---
if ($quiz_id) {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

        // Header
        $stmt = $conn->prepare("SELECT title, max_points, course_id FROM quizzes WHERE id = ?");
        $stmt->bind_param("i", $quiz_id);
        $stmt->execute();
        $quiz_info = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Questions
        if ($quiz_info) {
            $q_stmt = $conn->prepare("SELECT id, question_text, answer_a, answer_b, answer_c, answer_d FROM questions WHERE quiz_id = ?");
            $q_stmt->bind_param("i", $quiz_id);
            $q_stmt->execute();
            $questions = $q_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $q_stmt->close();
        }
        $conn->close();
    } catch (Exception $e) {
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Take Quiz</title>
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

    <div class="w-full max-w-3xl bg-white shadow-xl rounded-xl p-8 border-t-4 border-yellow-500">

        <?php if ($quiz_info): ?>
            <div class="border-b pb-6 mb-6 text-center">
                <h1 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($quiz_info['title']) ?></h1>
                <p class="text-gray-500">Answer all questions carefully.</p>
            </div>

            <form method="POST">
                <input type="hidden" name="quiz_id" value="<?= $quiz_id ?>">
                <input type="hidden" name="max_points_total" value="<?= $quiz_info['max_points'] ?>">
                <input type="hidden" name="finish_quiz" value="1">

                <div class="space-y-8">
                    <?php foreach ($questions as $idx => $q): ?>
                        <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                            <p class="font-bold text-lg text-gray-800 mb-4"><?= ($idx + 1) ?>.
                                <?= htmlspecialchars($q['question_text']) ?>
                            </p>

                            <div class="space-y-3">
                                <?php
                                $opts = ['A' => $q['answer_a'], 'B' => $q['answer_b'], 'C' => $q['answer_c'], 'D' => $q['answer_d']];
                                foreach ($opts as $key => $val):
                                    if (!empty($val)):
                                        ?>
                                        <label
                                            class="flex items-center p-3 bg-white border rounded cursor-pointer hover:bg-yellow-50 transition">
                                            <input type="radio" name="answers[<?= $q['id'] ?>]" value="<?= $key ?>"
                                                class="w-5 h-5 text-yellow-600 focus:ring-yellow-500">
                                            <span class="ml-3 text-gray-700 font-medium"><?= htmlspecialchars($val) ?></span>
                                        </label>
                                    <?php endif; endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-8 pt-6 border-t">
                    <button type="submit"
                        class="w-full py-3 bg-yellow-600 text-white font-bold rounded-lg hover:bg-yellow-700 shadow-md transition">
                        Submit Quiz
                    </button>
                </div>
            </form>

        <?php else: ?>
            <p class="text-center text-red-500">Quiz not found or invalid ID.</p>
        <?php endif; ?>

    </div>
</body>

</html>