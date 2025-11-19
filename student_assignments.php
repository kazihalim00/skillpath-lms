<?php
/**
 * SkillPath Project: Student Assignments View (Functional)
 *
 * FIX: This version removes text submission and implements file upload.
 */

session_start();
require_once 'db_config.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: login.html?status=error&message=Access%20Denied.");
    exit();
}
$user_id = $_SESSION['user_id'];
$message = null;
$assignments = [];
$course_id = $_GET['course_id'] ?? null;
$course_title = "Course Assignments";

// --- Process File Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_assignment'])) {
    $assignment_id = $_POST['assignment_id'];
    $course_id = $_POST['course_id'];
    $submission_path_for_db = null;

    if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/uploads/submissions/';
        $file_name = "stu{$user_id}_assign{$assignment_id}_" . basename($_FILES['submission_file']['name']);
        $target_file = $upload_dir . $file_name;

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        if (move_uploaded_file($_FILES['submission_file']['tmp_name'], $target_file)) {
            $submission_path_for_db = "uploads/submissions/" . $file_name;
        } else {
            $message = "Error: Failed to move submission file. Check permissions.";
            goto end_process;
        }
    } else {
        $message = "Error: No file selected or file upload error.";
        goto end_process;
    }

    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

        // Check if submission exists (UNIQUE constraint check)
        $check_sql = "SELECT id FROM submissions WHERE assignment_id = ? AND student_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ii", $assignment_id, $user_id);
        $check_stmt->execute();

        if ($check_stmt->get_result()->num_rows > 0) {
            $message = "You have already submitted this assignment.";
        } else {
            // Insert new submission path
            $sql = "INSERT INTO submissions (assignment_id, student_id, submission_path) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iis", $assignment_id, $user_id, $submission_path_for_db);

            if ($stmt->execute()) {
                $message = "File submitted successfully! Waiting for instructor review.";
            } else {
                $message = "Error submitting file: " . $conn->error;
            }
            $stmt->close();
        }
        $check_stmt->close();
        $conn->close();

    } catch (Exception $e) {
        $message = "Database Error: " . $e->getMessage();
    }

    header("Location: student_assignments.php?course_id=" . urlencode($course_id) . "&status=success&message=" . urlencode($message));
    exit();
}
end_process:


// --- Fetch Assignments and Status ---
if ($course_id) {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

        $title_sql = "SELECT title FROM courses WHERE id = ?";
        $title_stmt = $conn->prepare($title_sql);
        $title_stmt->bind_param("i", $course_id);
        $title_stmt->execute();
        $title_row = $title_stmt->get_result()->fetch_assoc();
        $course_title = ($title_row ? $title_row['title'] : 'Unknown Course') . " Assignments";
        $title_stmt->close();

        // Fetch assignments, joining with submissions to show status
        $sql = "SELECT a.id, a.title, a.description, a.file_path, a.due_date, a.max_points, 
                       s.submitted_at, s.grade, s.submission_path
                FROM assignments a 
                LEFT JOIN submissions s ON a.id = s.assignment_id AND s.student_id = ?
                WHERE a.course_id = ? 
                ORDER BY a.due_date ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $course_id);
        $stmt->execute();
        $assignments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $conn->close();

    } catch (Exception $e) {
        $message = "Database Error: " . $e->getMessage();
    }
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
            <a href="student_dashboard.php" class="text-indigo-500 hover:text-indigo-700 font-semibold">
                &larr; Back to Dashboard
            </a>
        </div>

        <?php if ($message): ?>
            <div
                class="p-4 mb-4 rounded-lg <?php echo strpos($message, 'successfully') !== false ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($course_id && empty($assignments)): ?>
            <div class="p-6 text-center text-gray-500 border-dashed border-2 rounded-lg">
                <p class="text-lg mb-2">The instructor has not posted any assignments for this course yet.</p>
            </div>
        <?php elseif ($course_id): ?>
            <div class="space-y-6">
                <?php foreach ($assignments as $assign):
                    $is_submitted = !empty($assign['submitted_at']);
                    $is_graded = !is_null($assign['grade']);
                    $due_date = strtotime($assign['due_date']);
                    $is_overdue = time() > $due_date && !$is_submitted;

                    $status_class = 'border-blue-500 bg-white';
                    $status_text = "Pending Submission";

                    if ($is_graded) {
                        $status_class = 'border-green-500 bg-green-50';
                        $status_text = "Graded: {$assign['grade']}/{$assign['max_points']}";
                    } elseif ($is_submitted) {
                        $status_class = 'border-yellow-500 bg-yellow-50';
                        $status_text = "Submitted";
                    } elseif ($is_overdue) {
                        $status_class = 'border-red-500 bg-red-50';
                        $status_text = "OVERDUE";
                    }
                    ?>
                    <div class="p-5 border-l-4 rounded-lg shadow-md <?php echo $status_class; ?>">
                        <div class="flex justify-between items-start">
                            <div>
                                <h2 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($assign['title']); ?>
                                </h2>
                                <p class="text-sm text-gray-600 mt-1">Due: <?php echo date('M j, Y', $due_date); ?> | Max
                                    Points: <?php echo htmlspecialchars($assign['max_points']); ?></p>
                            </div>
                            <span
                                class="px-3 py-1 text-sm font-semibold rounded-full <?php echo $is_graded ? 'bg-green-600 text-white' : ($is_submitted ? 'bg-yellow-100 text-yellow-800' : ($is_overdue ? 'bg-red-600 text-white' : 'bg-blue-100 text-blue-800')); ?>">
                                <?php echo $status_text; ?>
                            </span>
                        </div>

                        <p class="text-gray-700 text-sm mt-3 border-t pt-3">
                            <?php echo nl2br(htmlspecialchars($assign['description'])); ?>
                        </p>

                        <?php if ($assign['file_path']): ?>
                            <a href="<?php echo htmlspecialchars($assign['file_path']); ?>" target="_blank"
                                class="inline-block mt-2 py-1 px-3 bg-indigo-100 text-indigo-700 rounded-full text-sm font-medium">
                                Download Instructions (PDF/Doc)
                            </a>
                        <?php endif; ?>


                        <?php if (!$is_submitted && !$is_overdue): ?>
                            <form method="POST"
                                action="student_assignments.php?course_id=<?php echo htmlspecialchars($course_id); ?>"
                                class="mt-4 border-t pt-4" enctype="multipart/form-data">
                                <input type="hidden" name="assignment_id" value="<?php echo htmlspecialchars($assign['id']); ?>">
                                <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course_id); ?>">
                                <input type="hidden" name="submit_assignment" value="1">
                                <p class="text-sm font-medium text-gray-700 mb-2">Upload Your Submission:</p>
                                <input type="file" name="submission_file" required class="w-full text-sm py-1">
                                <button type="submit"
                                    class="mt-2 py-2 px-4 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700">
                                    Submit Assignment File
                                </button>
                            </form>
                        <?php elseif ($is_submitted): ?>
                            <div class="mt-4 border-t pt-4 text-sm font-medium">
                                Submitted on: <?php echo date('M j, Y H:i A', strtotime($assign['submitted_at'])); ?>
                                <a href="<?php echo htmlspecialchars($assign['submission_path']); ?>" target="_blank"
                                    class="ml-4 text-indigo-600">(View Your Submission)</a>
                            </div>
                        <?php endif; ?>
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