<?php
/**
 * SkillPath Project: Instructor Quiz Creation Page (Functional Structure)
 * This page provides the interface to create a new quiz, including adding questions.
 */

session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'instructor') {
    header("Location: login.html?status=error&message=Access%20Denied.");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = null;
$courses = [];
$selected_course_id = $_GET['course_id'] ?? null;
$quiz_id = $_GET['quiz_id'] ?? null; // Get quiz_id from URL

// --- Step 1: Process Quiz Header Creation (Save to 'quizzes' table) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_quiz_header'])) {
    $quiz_title = trim($_POST['title']);
    $course_id = $_POST['course_id'];
    $due_date_raw = $_POST['due_date']; // Input: YYYY-MM-DDTHH:MM
    $max_points = (int) $_POST['max_points'];

    try {
        // 1. Parse the input string exactly as the browser sends it (YYYY-MM-DDTHH:MM)
        $input_format = 'Y-m-d\TH:i';
        $dateTime = DateTime::createFromFormat($input_format, $due_date_raw);

        if ($dateTime === false) {
            // This branch catches manual input errors or severe formatting issues
            throw new Exception("Date parsing failed. Invalid input submitted.");
        }

        // 2. Format the object into the precise MySQL DATETIME format (YYYY-MM-DD HH:MM:SS)
        $due_date_sql = $dateTime->format('Y-m-d H:i:s');

    } catch (Exception $e) {
        $message = "Error: Invalid date format submitted. Please use the calendar picker.";
        $selected_course_id = $course_id;
        goto end_process; // Skip database interaction on failure
    }

    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
        $sql = "INSERT INTO quizzes (course_id, title, due_date, max_points) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        // Binding the corrected $due_date_sql string
        $stmt->bind_param("issi", $course_id, $quiz_title, $due_date_sql, $max_points);

        if ($stmt->execute()) {
            $new_quiz_id = $conn->insert_id;
            // Redirect to the same page but with the new quiz_id, to start adding questions.
            header("Location: instructor_quiz_create.php?course_id={$course_id}&quiz_id={$new_quiz_id}&status=header_success");
            exit();
        } else {
            $message = "Error creating quiz header: " . $conn->error;
        }
        $stmt->close();
        $conn->close();

    } catch (Exception $e) {
        $message = "Database Error: " . $e->getMessage();
    }

    $selected_course_id = $course_id;
}

// --- Step 2: Process Question Submission (Saves to 'questions' table) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_question'])) {
    $quiz_id = $_POST['quiz_id'];
    $course_id = $_POST['course_id'];
    $question_text = trim($_POST['question_text']);
    $answer_a = trim($_POST['answer_a']);
    $answer_b = trim($_POST['answer_b']);
    $answer_c = trim($_POST['answer_c']);
    $answer_d = trim($_POST['answer_d']);
    $correct_answer = $_POST['correct_answer'];

    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
        $sql = "INSERT INTO questions (quiz_id, question_text, answer_a, answer_b, answer_c, answer_d, correct_answer) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issssss", $quiz_id, $question_text, $answer_a, $answer_b, $answer_c, $answer_d, $correct_answer);

        if ($stmt->execute()) {
            
        } else {
            $message = "Error adding question: " . $conn->error;
        }
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        $message = "Database Error: " . $e->getMessage();
    }

    // Redirect to show success/failure and remain on the question form
    header("Location: instructor_quiz_create.php?course_id={$course_id}&quiz_id={$quiz_id}&status=question_success");
    exit();
}
end_process:


// --- Fetch Instructor's Courses ---
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
    $sql = "SELECT id, course_code, title FROM courses WHERE instructor_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
    $stmt->close();
    $conn->close();
} catch (Exception $e) { /* silent fail for dropdown */
}

// Get quiz info if quiz_id is set
if (isset($_GET['quiz_id'])) {
    $quiz_id = $_GET['quiz_id'];
    $selected_course_id = $_GET['course_id'] ?? $selected_course_id;
}
// Handle messages from redirects
if (isset($_GET['status']) && $_GET['status'] === 'header_success') {
    $message = "Quiz Header Created (ID: {$quiz_id}). Now add questions below.";
}
if (isset($_GET['status']) && $_GET['status'] === 'question_success') {
    $message = "Question successfully added to Quiz ID: {$quiz_id}. Add the next question.";
}
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}


$user_name = $_SESSION['user_name'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Quiz | SkillPath</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f9;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col items-center p-4 bg-gray-100">

    <div class="w-full max-w-4xl bg-white shadow-xl rounded-xl p-8 md:p-12 mt-8">
        <div class="flex justify-between items-center border-b pb-4 mb-6">
            <h1 class="text-3xl font-bold text-gray-800">
                Quiz Builder
                <?php echo $quiz_id ? "(Quiz ID: {$quiz_id})" : ""; ?>
            </h1>
            <a href="instructor_dashboard.php" class="text-indigo-500 hover:text-indigo-700 font-semibold">
                &larr; Back to Dashboard
            </a>
        </div>

        <?php if ($message): ?>
            <div
                class="p-4 mb-4 rounded-lg <?php echo strpos($message, 'success') !== false ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Form 1: Quiz Header (Visible when $quiz_id is not set) -->
        <?php if (!$quiz_id): ?>
            <h2 class="text-xl font-semibold mb-4 text-gray-700">Step 1: Define Quiz Header</h2>
            <form method="POST" action="instructor_quiz_create.php" class="space-y-4 border p-4 rounded-lg bg-yellow-50">
                <input type="hidden" name="create_quiz_header" value="1">

                <div>
                    <label class="block text-sm font-medium text-gray-700">Course</label>
                    <select name="course_id" required class="w-full px-3 py-2 border rounded-lg">
                        <option value="">-- Select Course --</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo htmlspecialchars($course['id']); ?>">
                                <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <input type="text" name="title" placeholder="Quiz Title (e.g., Midterm Exam)" required
                        class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Due Date/Time:</label>
                    <input type="datetime-local" name="due_date" required class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <input type="number" name="max_points" placeholder="Max Points" required value="100"
                        class="w-full px-3 py-2 border rounded-lg">
                </div>
                <button type="submit"
                    class="w-full py-2 bg-yellow-600 text-white font-medium rounded-lg hover:bg-yellow-700">
                    Create Quiz Header (Go to Step 2)
                </button>
            </form>
        <?php endif; ?>


        <!-- Form 2: Add Questions (Visible when $quiz_id is set) -->
        <?php if ($quiz_id): ?>
            <h2 class="text-xl font-semibold mb-4 text-gray-700">Step 2: Add Multiple Choice Questions</h2>
            <div class="mb-4 p-3 bg-blue-100 border-l-4 border-blue-600 text-blue-800">
                Status: Adding questions to Quiz ID: **<?php echo htmlspecialchars($quiz_id); ?>**.
            </div>

            <form method="POST" action="instructor_quiz_create.php"
                class="space-y-4 border p-6 rounded-lg bg-white shadow-lg">
                <input type="hidden" name="add_question" value="1">
                <input type="hidden" name="quiz_id" value="<?php echo htmlspecialchars($quiz_id); ?>">
                <input type="hidden" name="course_id"
                    value="<?php echo htmlspecialchars($selected_course_id ?? $_GET['course_id']); ?>">

                <div>
                    <label class="block text-sm font-medium text-gray-700">Question Text</label>
                    <textarea name="question_text" rows="3" required class="w-full px-3 py-2 border rounded-lg"></textarea>
                </div>

                <h3 class="text-lg font-semibold text-gray-700 mt-4">Answer Options (A, B, C, D)</h3>
                <div class="grid grid-cols-2 gap-4">
                    <input type="text" name="answer_a" placeholder="Option A" required class="px-3 py-2 border rounded-lg">
                    <input type="text" name="answer_b" placeholder="Option B" required class="px-3 py-2 border rounded-lg">
                    <input type="text" name="answer_c" placeholder="Option C (Optional)"
                        class="px-3 py-2 border rounded-lg">
                    <input type="text" name="answer_d" placeholder="Option D (Optional)"
                        class="px-3 py-2 border rounded-lg">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mt-4">Correct Answer</label>
                    <select name="correct_answer" required class="px-3 py-2 border rounded-lg">
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                    </select>
                </div>

                <div class="flex justify-between pt-4">
                    <button type="submit" class="py-2 px-4 bg-teal-600 text-white font-medium rounded-lg hover:bg-teal-700">
                        Add Question
                    </button>
                    <a href="instructor_course_list.php"
                        class="py-2 px-4 bg-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-400">
                        Finish & Publish Quiz
                    </a>
                </div>
            </form>
        <?php endif; ?>

        <div class="mt-8 text-center text-gray-400 text-xs border-t pt-4">
            <p>Instructor Page | User ID: <?php echo $user_id; ?></p>
        </div>
    </div>
</body>

</html>