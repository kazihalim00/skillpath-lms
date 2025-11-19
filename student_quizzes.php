<?php
/**
 * SkillPath Project: Student Quizzes Page (Functional Structure)
 *
 * This page lists available quizzes for an enrolled course and tracks student attempts.
 */

session_start();
require_once 'db_config.php';

// Security Check: Must be logged in and be a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: login.html?status=error&message=Access%20Denied.");
    exit();
}
$user_id = $_SESSION['user_id'];
$message = null;
$quizzes = [];
$course_id = $_GET['course_id'] ?? null;
$course_title = "Course Quizzes";

// --- Fetch Quizzes and Status ---
if ($course_id) {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

        // Fetch course title
        $title_sql = "SELECT title FROM courses WHERE id = ?";
        $title_stmt = $conn->prepare($title_sql);
        $title_stmt->bind_param("i", $course_id);
        $title_stmt->execute();
        $title_row = $title_stmt->get_result()->fetch_assoc();
        $course_title = ($title_row ? $title_row['title'] : 'Unknown Course') . " Quizzes";
        $title_stmt->close();

        // Fetch published quizzes for this course, joining with attempts to show status
        $sql = "SELECT q.id, q.title, q.due_date, q.max_points, q.status,
                       a.score, a.completed_at
                FROM quizzes q 
                LEFT JOIN quiz_attempts a ON q.id = a.quiz_id AND a.student_id = ?
                WHERE q.course_id = ? AND q.status = 'published'
                ORDER BY q.due_date ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $course_id);
        $stmt->execute();
        $quizzes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $conn->close();

    } catch (Exception $e) {
        $message = "Database Error: " . $e->getMessage();
    }
}

// Handle redirect messages
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
    <title><?php echo htmlspecialchars($course_title); ?> | SkillPath</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f9;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col items-center p-4">

    <div class="w-full max-w-6xl bg-white shadow-xl rounded-xl p-8 md:p-12 mt-8">
        <div class="flex justify-between items-center border-b pb-4 mb-6">
            <h1 class="text-3xl font-bold text-gray-800">
                <?php echo htmlspecialchars($course_title); ?>
            </h1>
            <a href="student_course_content.php?course_id=<?php echo htmlspecialchars($course_id); ?>"
                class="text-indigo-500 hover:text-indigo-700 font-semibold">
                &larr; Back to Course Content
            </a>
        </div>

        <?php if ($message): ?>
            <div
                class="p-4 mb-4 rounded-lg <?php echo strpos($message, 'submitted') !== false ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($quizzes)): ?>
            <div class="p-6 text-center text-gray-500 border-dashed border-2 rounded-lg">
                <p class="text-lg mb-2">No quizzes are currently available for this course.</p>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($quizzes as $quiz):
                    $is_completed = !is_null($quiz['completed_at']);
                    $due_date = strtotime($quiz['due_date']);
                    $is_overdue = time() > $due_date && !$is_completed;

                    $status_class = 'border-blue-500 bg-white';
                    $status_text = "Start Quiz";
                    $link_class = 'bg-blue-600 hover:bg-blue-700';

                    if ($is_completed) {
                        $status_class = 'border-green-500 bg-green-50';
                        $status_text = "Score: {$quiz['score']}/{$quiz['max_points']}";
                        $link_class = 'bg-green-600 cursor-pointer';
                    } elseif ($is_overdue) {
                        $status_class = 'border-red-500 bg-red-50';
                        $status_text = "OVERDUE";
                        $link_class = 'bg-gray-400 cursor-default';
                    }
                    ?>
                    <div class="p-5 border-l-4 rounded-lg shadow-md <?php echo $status_class; ?>">
                        <div class="flex justify-between items-center">
                            <div>
                                <h2 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($quiz['title']); ?></h2>
                                <p class="text-sm text-gray-600 mt-1">Due: <?php echo date('M j, Y H:i A', $due_date); ?> | Max
                                    Points: <?php echo htmlspecialchars($quiz['max_points']); ?></p>
                            </div>
                            <div class="text-right">
                                <span
                                    class="px-3 py-1 text-sm font-semibold rounded-full text-white <?php echo $is_completed ? 'bg-green-600' : ($is_overdue ? 'bg-red-600' : 'bg-blue-600'); ?>">
                                    <?php echo $status_text; ?>
                                </span>
                                <p class="text-xs text-gray-500 mt-1">
                                    <?php echo $is_completed ? 'Completed: ' . date('M j, Y', strtotime($quiz['completed_at'])) : ''; ?>
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 border-t pt-4">
                            <?php if ($is_completed): ?>
                                <a href="student_quiz_results.php?quiz_id=<?php echo htmlspecialchars($quiz['id']); ?>"
                                    class="w-full block py-2 text-white font-medium rounded-lg text-center bg-green-600 hover:bg-green-700 transition">
                                    View Results
                                </a>
                            <?php elseif ($is_overdue): ?>
                                <button disabled class="w-full py-2 text-white font-medium rounded-lg <?php echo $link_class; ?>">
                                    Time Expired
                                </button>
                            <?php else: ?>
                                <a href="student_take_quiz.php?quiz_id=<?php echo htmlspecialchars($quiz['id']); ?>"
                                    class="w-full block py-2 text-white font-medium rounded-lg text-center <?php echo $link_class; ?>">
                                    Start Quiz Now
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="mt-8 text-center text-gray-400 text-xs border-t pt-4">
            <p>Student Page - Role: <?php echo $_SESSION['user_role']; ?> | User ID: <?php echo $_SESSION['user_id']; ?>
            </p>
        </div>
    </div>
</body>

</html>