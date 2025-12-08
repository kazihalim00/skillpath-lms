<?php
/**
 * SkillPath - Student Assignments
 * Fixes: Session variables and correct 'submission_path' logic.
 */
session_start();
require_once 'db_config.php';

// 1. SECURITY CHECK (Fixed Session Keys)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$message = "";
$course_id_filter = $_GET['course_id'] ?? null;

// --- Handle Submission Upload ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['submission_file'])) {
    $assignment_id = $_POST['assignment_id'];

    // File Logic
    $upload_dir = 'uploads/submissions/';
    if (!is_dir($upload_dir))
        mkdir($upload_dir, 0777, true);

    $file_name = "stu{$student_id}_" . time() . "_" . basename($_FILES['submission_file']['name']);
    $target_file = $upload_dir . $file_name;

    if (move_uploaded_file($_FILES['submission_file']['tmp_name'], $target_file)) {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

            // Insert or Update using 'submission_path'
            $sql = "INSERT INTO submissions (student_id, assignment_id, submission_path, submitted_at) 
                    VALUES (?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE submission_path = VALUES(submission_path), submitted_at = NOW()";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iis", $student_id, $assignment_id, $target_file);

            if ($stmt->execute()) {
                $message = "Assignment submitted successfully!";
            } else {
                $message = "Database Error: " . $stmt->error;
            }
            $stmt->close();
            $conn->close();
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
        }
    } else {
        $message = "File upload failed. Check permissions.";
    }
}

// --- Fetch Assignments ---
$assignments = [];
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

    // Base SQL: Get assignments for courses the student is enrolled in
    $sql = "SELECT a.id, a.title, a.description, a.due_date, a.max_points, c.course_code, 
                   s.submission_path, s.grade, s.submitted_at
            FROM assignments a
            JOIN enrollments e ON a.course_id = e.course_id
            JOIN courses c ON a.course_id = c.id
            LEFT JOIN submissions s ON (a.id = s.assignment_id AND s.student_id = ?)
            WHERE e.student_id = ?";

    // Filter by specific course if clicked from dashboard
    if ($course_id_filter) {
        $sql .= " AND a.course_id = " . intval($course_id_filter);
    }

    $sql .= " ORDER BY a.due_date ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $student_id, $student_id);
    $stmt->execute();
    $assignments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $conn->close();
} catch (Exception $e) {
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>My Assignments | SkillPath</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f9;
        }
    </style>
</head>

<body class="p-8 flex justify-center">

    <div class="w-full max-w-5xl bg-white shadow-xl rounded-xl p-8 border-t-4 border-blue-500">
        <div class="flex justify-between items-center mb-6 border-b pb-4">
            <h1 class="text-2xl font-bold text-gray-800">My Assignments</h1>
            <a href="student_dashboard.php" class="text-blue-600 hover:underline">&larr; Back to Dashboard</a>
        </div>

        <?php if ($message): ?>
            <div class="p-4 mb-6 bg-green-100 text-green-700 rounded-lg text-center font-bold border border-green-200">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if (empty($assignments)): ?>
            <p class="text-gray-500 text-center py-10 bg-gray-50 rounded-lg border-2 border-dashed">
                No assignments found for your enrolled courses.
            </p>
        <?php else: ?>
            <div class="grid gap-6">
                <?php foreach ($assignments as $a): ?>
                    <div class="p-6 border rounded-xl hover:shadow-md transition bg-gray-50">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <span class="bg-blue-100 text-blue-800 text-xs font-bold px-2 py-1 rounded">
                                    <?= htmlspecialchars($a['course_code']) ?>
                                </span>
                                <h3 class="text-xl font-bold text-gray-800 mt-2"><?= htmlspecialchars($a['title']) ?></h3>
                                <p class="text-sm text-gray-500">Due: <?= date('M d, g:i a', strtotime($a['due_date'])) ?></p>
                            </div>
                            <div class="text-right">
                                <?php if ($a['grade']): ?>
                                    <span class="block text-2xl font-bold text-green-600"><?= $a['grade'] ?> /
                                        <?= $a['max_points'] ?></span>
                                    <span class="text-xs text-gray-400 uppercase font-bold">Graded</span>
                                <?php else: ?>
                                    <span class="block text-sm text-gray-400 font-medium">Max: <?= $a['max_points'] ?> pts</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <p class="text-gray-600 text-sm mb-4 bg-white p-3 rounded border border-gray-200">
                            <?= nl2br(htmlspecialchars($a['description'] ?? 'No description provided.')) ?>
                        </p>

                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <?php if (!empty($a['submission_path'])): ?>
                                <div
                                    class="flex items-center justify-between bg-green-50 p-3 rounded text-green-700 mb-2 border border-green-200">
                                    <span class="text-sm font-bold flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                clip-rule="evenodd"></path>
                                        </svg>
                                        Submitted
                                    </span>
                                    <a href="<?= htmlspecialchars($a['submission_path']) ?>" target="_blank"
                                        class="text-xs underline hover:text-green-900 font-semibold">View File</a>
                                </div>
                                <p class="text-xs text-gray-400">You can upload again to overwrite your previous submission.</p>
                            <?php else: ?>
                                <p class="text-sm text-red-500 font-bold mb-2">Not Submitted</p>
                            <?php endif; ?>

                            <form method="POST" enctype="multipart/form-data" class="flex gap-2 mt-2">
                                <input type="hidden" name="assignment_id" value="<?= $a['id'] ?>">
                                <input type="file" name="submission_file" required class="block w-full text-sm text-slate-500
                                file:mr-4 file:py-2 file:px-4
                                file:rounded-full file:border-0
                                file:text-sm file:font-semibold
                                file:bg-blue-50 file:text-blue-700
                                hover:file:bg-blue-100">
                                <button
                                    class="bg-blue-600 text-white px-4 py-2 rounded font-bold text-sm hover:bg-blue-700 transition">Submit</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>