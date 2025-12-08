<?php
/**
 * SkillPath Project: Instructor Quiz Creation
 * Fixed: Session variable names matching index.php
 */
session_start();
require_once 'db_config.php';

// FIX: Use 'role' instead of 'user_role'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: login.php?status=error&message=Access%20Denied.");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = null;
$courses = [];
$quiz_id = $_GET['quiz_id'] ?? null;
$selected_course_id = $_GET['course_id'] ?? null;

// --- Step 1: Create Quiz Header ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_quiz_header'])) {
    $quiz_title = trim($_POST['title']);
    $course_id = $_POST['course_id'];
    $due_date_raw = $_POST['due_date'];
    $max_points = (int) $_POST['max_points'];

    try {
        // Fix Date Format
        $dateTime = DateTime::createFromFormat('Y-m-d\TH:i', $due_date_raw);
        if (!$dateTime)
            throw new Exception("Invalid date format.");
        $due_date_sql = $dateTime->format('Y-m-d H:i:s');

        $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
        $stmt = $conn->prepare("INSERT INTO quizzes (course_id, title, due_date, max_points) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issi", $course_id, $quiz_title, $due_date_sql, $max_points);

        if ($stmt->execute()) {
            $new_quiz_id = $conn->insert_id;
            header("Location: instructor_quiz_create.php?course_id={$course_id}&quiz_id={$new_quiz_id}&status=header_success");
            exit();
        } else {
            $message = "Error: " . $conn->error;
        }
        $conn->close();
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
    }
}

// --- Step 2: Add Question ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_question'])) {
    $quiz_id = $_POST['quiz_id'];
    $course_id = $_POST['course_id'];
    // Collect fields
    $q_text = trim($_POST['question_text']);
    $ans_a = trim($_POST['answer_a']);
    $ans_b = trim($_POST['answer_b']);
    $ans_c = trim($_POST['answer_c']);
    $ans_d = trim($_POST['answer_d']);
    $correct = $_POST['correct_answer'];

    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
        $stmt = $conn->prepare("INSERT INTO questions (quiz_id, question_text, answer_a, answer_b, answer_c, answer_d, correct_answer) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssss", $quiz_id, $q_text, $ans_a, $ans_b, $ans_c, $ans_d, $correct);
        $stmt->execute();
        $conn->close();
        header("Location: instructor_quiz_create.php?course_id={$course_id}&quiz_id={$quiz_id}&status=question_success");
        exit();
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
    }
}

// --- Fetch Courses ---
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
    $res = $conn->query("SELECT id, course_code, title FROM courses WHERE instructor_id = $user_id");
    while ($row = $res->fetch_assoc()) {
        $courses[] = $row;
    }
    $conn->close();
} catch (Exception $e) {
}

if (isset($_GET['status'])) {
    if ($_GET['status'] == 'header_success')
        $message = "Quiz Created! Now add questions.";
    if ($_GET['status'] == 'question_success')
        $message = "Question Added Successfully!";
}

// FIX: Use 'name' instead of 'user_name'
$user_name = $_SESSION['name'] ?? 'Instructor';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Quiz Builder | SkillPath</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f9;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col items-center p-4">

    <div class="w-full max-w-4xl bg-white shadow-xl rounded-xl p-8 md:p-12 mt-8 border-t-4 border-yellow-500">
        <div class="flex justify-between items-center border-b pb-4 mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Quiz Builder</h1>
            <a href="instructor_dashboard.php" class="text-indigo-600 hover:underline font-semibold">&larr;
                Dashboard</a>
        </div>

        <?php if ($message): ?>
            <div class="p-4 mb-4 rounded-lg bg-green-100 text-green-700 font-medium">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if (!$quiz_id): ?>
            <h2 class="text-xl font-bold text-gray-700 mb-4">Step 1: Quiz Details</h2>
            <form method="POST" class="space-y-4 bg-yellow-50 p-6 rounded-lg">
                <input type="hidden" name="create_quiz_header" value="1">
                <select name="course_id" required class="w-full px-4 py-2 border rounded">
                    <option value="">-- Select Course --</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['course_code'] . ' - ' . $c['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="title" placeholder="Quiz Title" required class="w-full px-4 py-2 border rounded">
                <input type="datetime-local" name="due_date" required class="w-full px-4 py-2 border rounded">
                <input type="number" name="max_points" value="100" required class="w-full px-4 py-2 border rounded">
                <button class="w-full bg-yellow-600 text-white font-bold py-2 rounded hover:bg-yellow-700">Create
                    Quiz</button>
            </form>

        <?php else: ?>
            <div class="mb-6 p-4 bg-blue-50 border-l-4 border-blue-500">
                Editing Quiz ID: <strong><?= $quiz_id ?></strong>
            </div>

            <h2 class="text-xl font-bold text-gray-700 mb-4">Step 2: Add Questions</h2>
            <form method="POST" class="space-y-4 bg-gray-50 p-6 rounded-lg border">
                <input type="hidden" name="add_question" value="1">
                <input type="hidden" name="quiz_id" value="<?= $quiz_id ?>">
                <input type="hidden" name="course_id" value="<?= $selected_course_id ?>">

                <textarea name="question_text" rows="2" placeholder="Question Text" required
                    class="w-full px-4 py-2 border rounded"></textarea>

                <div class="grid grid-cols-2 gap-4">
                    <input type="text" name="answer_a" placeholder="Option A" required class="px-4 py-2 border rounded">
                    <input type="text" name="answer_b" placeholder="Option B" required class="px-4 py-2 border rounded">
                    <input type="text" name="answer_c" placeholder="Option C" class="px-4 py-2 border rounded">
                    <input type="text" name="answer_d" placeholder="Option D" class="px-4 py-2 border rounded">
                </div>

                <label class="block font-medium">Correct Answer:</label>
                <select name="correct_answer" class="px-4 py-2 border rounded w-1/4">
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="C">C</option>
                    <option value="D">D</option>
                </select>

                <div class="flex justify-between mt-4">
                    <button class="bg-teal-600 text-white font-bold py-2 px-6 rounded hover:bg-teal-700">Add
                        Question</button>
                    <a href="instructor_dashboard.php"
                        class="bg-gray-300 text-gray-700 font-bold py-2 px-6 rounded hover:bg-gray-400">Finish</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>

</html>