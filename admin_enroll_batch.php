<?php
/**
 * SkillPath - Admin Bulk Enrollment
 */
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll_batch'])) {
    $batch_id = intval($_POST['batch_id']);
    $course_id = intval($_POST['course_id']);

    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
        // Enroll students from this batch into the course
        $sql = "INSERT IGNORE INTO enrollments (student_id, course_id) 
                SELECT id, ? FROM users WHERE batch_id = ? AND role = 'student'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $course_id, $batch_id);
        if ($stmt->execute()) {
            $count = $stmt->affected_rows;
            $message = "Success! Enrolled $count students into the course.";
        } else {
            $message = "Error: " . $conn->error;
        }
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        $message = "Database Error: " . $e->getMessage();
    }
}

$batches = [];
$courses = [];
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
    $batches = $conn->query("SELECT * FROM batches")->fetch_all(MYSQLI_ASSOC);
    $courses = $conn->query("SELECT id, course_code, title FROM courses")->fetch_all(MYSQLI_ASSOC);
    $conn->close();
} catch (Exception $e) {
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin - Batch Enrollment</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-100 p-10 flex justify-center">
    <div class="w-full max-w-2xl bg-white p-8 rounded-xl shadow-lg border-t-4 border-indigo-600">
        <a href="admin_dashboard.php" class="text-blue-600 hover:text-blue-800 font-semibold mb-6 inline-block">&larr;
            Back to Dashboard</a>
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Admin: Batch Course Allocation</h1>
        <p class="text-gray-600 text-sm mb-6">Assign a "Fixed Course" to a specific Batch.</p>

        <?php if ($message): ?>
            <div class="p-4 mb-6 bg-green-100 text-green-700 rounded-lg font-bold text-center border border-green-200">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <input type="hidden" name="enroll_batch" value="1">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Select Batch</label>
                <select name="batch_id" required
                    class="w-full p-3 border rounded-lg bg-gray-50 focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Choose Batch --</option>
                    <?php foreach ($batches as $b): ?>
                        <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['batch_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Select Course</label>
                <select name="course_id" required
                    class="w-full p-3 border rounded-lg bg-gray-50 focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Choose Course --</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['course_code'] . " - " . $c['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit"
                class="w-full bg-indigo-600 text-white font-bold py-3 rounded-lg hover:bg-indigo-700 transition shadow-md">Enroll
                Entire Batch</button>
        </form>
    </div>
</body>

</html>