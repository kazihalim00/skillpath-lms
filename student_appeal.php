<?php
/**
 * SkillPath - Student Specific Challenge
 */
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$course_id = $_GET['course_id'] ?? 0;
$component = $_GET['component'] ?? 'General'; // e.g., 'Viva' or 'Lab Report'
$course_title = $_GET['title'] ?? 'Course';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = $_POST['reason'];
    $comp_name = $_POST['component_name']; // from hidden field
    $cid = $_POST['course_id'];

    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
        $sql = "INSERT INTO grade_appeals (student_id, course_id, item_type, component_name, reason, status) 
                VALUES (?, ?, 'grade', ?, ?, 'pending')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiss", $user_id, $cid, $comp_name, $reason);

        if ($stmt->execute()) {
            header("Location: student_my_appeals.php?msg=submitted");
            exit();
        }
        $conn->close();
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Challenge Grade</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f9;
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white shadow-xl rounded-xl p-8 border-t-4 border-orange-500">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Challenge Grade</h1>
        <p class="text-gray-600 mb-6">
            Course: <strong><?= htmlspecialchars($course_title) ?></strong><br>
            Component: <span class="text-orange-600 font-bold"><?= htmlspecialchars($component) ?></span>
        </p>

        <form method="POST">
            <input type="hidden" name="course_id" value="<?= htmlspecialchars($course_id) ?>">
            <input type="hidden" name="component_name" value="<?= htmlspecialchars($component) ?>">

            <label class="block text-sm font-bold text-gray-700 mb-2">Reason for Challenge</label>
            <textarea name="reason" rows="4" class="w-full border rounded p-3 mb-4"
                placeholder="Explain why your score should be reviewed..." required></textarea>

            <div class="flex justify-end gap-3">
                <a href="student_grades.php"
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded hover:bg-orange-700">Submit
                    Challenge</button>
            </div>
        </form>
    </div>
</body>

</html>