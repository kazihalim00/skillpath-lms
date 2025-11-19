<?php
/**
 * SkillPath Project: Student Take Quiz Page (Functional)
 */

session_start();
require_once 'db_config.php';

// Security Check: Must be logged in and be a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: login.html?status=error&message=Access%20Denied.");
    exit();
}
$user_id = $_SESSION['user_id'];
$quiz_id = $_GET['quiz_id'] ?? null;
$message = null;
$quiz_info = null;
$questions = [];

// --- Process Quiz Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['finish_quiz'])) {
    $quiz_id = $_POST['quiz_id'];
    $course_id = $_POST['course_id'];
    $max_points = (int) $_POST['max_points_total'];
    $student_answers = $_POST['answers'] ?? []; // Array of answers: [question_id => answer]

    $total_correct = 0;
    $total_questions = 0;
    $final_score = 0;

    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

        // 1. Fetch the correct answers from the database
        $sql = "SELECT id, correct_answer FROM questions WHERE quiz_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $quiz_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $correct_answers_map = [];

        while ($row = $result->fetch_assoc()) {
            $correct_answers_map[$row['id']] = $row['correct_answer'];
            $total_questions++;
        }
        $stmt->close();

        // 2. Compare student's answers to the correct answers
        if ($total_questions > 0) {
            foreach ($student_answers as $question_id => $student_answer) {
                if (isset($correct_answers_map[$question_id]) && $correct_answers_map[$question_id] === $student_answer) {
                    $total_correct++;
                }
            }

            // 3. Calculate the final score
            // (e.g., 2 correct / 5 total) * 100 points = 40
            $final_score = round(($total_correct / $total_questions) * $max_points);
        }

        // 4. Save the actual score to the database
        $sql_save = "REPLACE INTO quiz_attempts (quiz_id, student_id, score, completed_at) VALUES (?, ?, ?, NOW())";
        $stmt_save = $conn->prepare($sql_save);
        $stmt_save->bind_param("iii", $quiz_id, $user_id, $final_score);

        if ($stmt_save->execute()) {
            $message = "Quiz submitted! Your final score is {$final_score} points.";
            // Redirect back to the quiz list view to show the updated score
            header("Location: student_quizzes.php?status=submitted&message=" . urlencode($message) . "&course_id=" . urlencode($course_id));
            exit();
        }
        $stmt_save->close();
        $conn->close();
    } catch (Exception $e) {
        $message = "Database Error: " . $e->getMessage();
    }
}


// --- Fetch Quiz Details and Questions ---
if ($quiz_id) {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

        // Fetch quiz header info
        $sql = "SELECT id, title, course_id, max_points FROM quizzes WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $quiz_id);
        $stmt->execute();
        $quiz_info = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($quiz_info) {
            // Fetch questions
            $sql = "SELECT id, question_text, answer_a, answer_b, answer_c, answer_d FROM questions WHERE quiz_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $quiz_id);
            $stmt->execute();
            $questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
        $conn->close();

    } catch (Exception $e) {
        $message = "Database Error: " . $e->getMessage();
    }
} else {
    $message = "No quiz ID specified.";
}
$course_id = $quiz_info['course_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take Quiz: <?php echo htmlspecialchars($quiz_info['title'] ?? 'N/A'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f9;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col items-center p-4 bg-gray-100">

    <div class="w-full max-w-4xl bg-white shadow-2xl rounded-xl p-8 md:p-12 mt-8">
        <div class="flex justify-between items-center border-b pb-4 mb-6">
            <h1 class="text-3xl font-bold text-gray-800">
                <?php echo htmlspecialchars($quiz_info['title'] ?? 'Quiz'); ?>
            </h1>
            <p class="text-xl font-semibold text-orange-600">Max Points:
                <?php echo htmlspecialchars($quiz_info['max_points'] ?? 0); ?>
            </p>
        </div>

        <?php if ($message): ?>
            <div class="p-4 mb-4 rounded-lg bg-red-100 text-red-700">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($questions)): ?>
            <div class="p-6 text-center text-gray-500 border-dashed border-2 rounded-lg">
                <p class="text-lg mb-2">This quiz has no questions yet. Please check back later.</p>
            </div>
        <?php else: ?>
            <form method="POST" action="student_take_quiz.php" class="space-y-8">
                <input type="hidden" name="quiz_id" value="<?php echo htmlspecialchars($quiz_id); ?>">
                <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course_id); ?>">
                <input type="hidden" name="max_points_total"
                    value="<?php echo htmlspecialchars($quiz_info['max_points']); ?>">
                <input type="hidden" name="finish_quiz" value="1">

                <?php foreach ($questions as $index => $q): ?>
                    <div class="p-5 border rounded-lg shadow-sm bg-gray-50">
                        <p class="text-lg font-bold mb-4 text-gray-800">
                            <?php echo ($index + 1) . ". " . htmlspecialchars($q['question_text']); ?>
                        </p>

                        <div class="space-y-2">
                            <?php
                            $options = ['A' => $q['answer_a'], 'B' => $q['answer_b'], 'C' => $q['answer_c'], 'D' => $q['answer_d']];
                            foreach ($options as $key => $answer):
                                if (!empty($answer)):
                                    ?>
                                    <label
                                        class="flex items-center p-3 border rounded-lg cursor-pointer bg-white hover:bg-blue-50 transition duration-150">
                                        <input type="radio" name="answers[<?php echo htmlspecialchars($q['id']); ?>]"
                                            value="<?php echo $key; ?>" required
                                            class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                                        <span
                                            class="ml-3 text-gray-700 font-medium"><?php echo htmlspecialchars($key) . ') ' . htmlspecialchars($answer); ?></span>
                                    </label>
                                    <?php
                                endif;
                            endforeach;
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="border-t pt-6">
                    <button type="submit"
                        class="w-full py-3 bg-red-600 text-white font-bold rounded-lg shadow-lg hover:bg-red-700 transition">
                        Finish Quiz & Submit Answers
                    </button>
                </div>
            </form>
        <?php endif; ?>

        <div class="mt-8 text-center text-gray-400 text-xs border-t pt-4">
            <p>Student Quiz Attempt | User ID: <?php echo $user_id; ?></p>
        </div>
    </div>
</body>

</html>