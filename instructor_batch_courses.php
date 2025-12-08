<?php
/**
 * SkillPath - Instructor Batch Courses (FILTERED VERSION)
 * Logic: Shows courses taught by this instructor, BUT ONLY if students from the selected Batch are enrolled.
 */
session_start();
require_once 'db_config.php';

// 1. Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: login.php");
    exit();
}

$instructor_id = $_SESSION['user_id'];
$batch_id = isset($_GET['batch_id']) ? intval($_GET['batch_id']) : 0;

if ($batch_id === 0) {
    header("Location: instructor_batches.php");
    exit();
}

$batch_name = "Selected Batch";
$courses = [];

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

    // 2. Get Batch Name
    $stmt = $conn->prepare("SELECT batch_name FROM batches WHERE id = ?");
    $stmt->bind_param("i", $batch_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $batch_name = $row['batch_name'];
    }
    $stmt->close();

    // 3. SMART FILTER QUERY
    // Find courses taught by ME, where at least one student from THIS BATCH is enrolled.
    // We join 'courses' -> 'enrollments' -> 'users' (to get the student's batch)
    $sql = "SELECT DISTINCT c.* FROM courses c
            JOIN enrollments e ON c.id = e.course_id
            JOIN users u ON e.student_id = u.id
            WHERE c.instructor_id = ? 
            AND u.batch_id = ?";

    $stmt2 = $conn->prepare($sql);
    $stmt2->bind_param("ii", $instructor_id, $batch_id);
    $stmt2->execute();
    $result = $stmt2->get_result();

    if ($result) {
        $courses = $result->fetch_all(MYSQLI_ASSOC);
    }

    $stmt2->close();
    $conn->close();

} catch (Exception $e) {
    // If table 'enrollments' uses 'user_id' instead of 'student_id', this might fail.
    // Let me know if you see a database error!
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Courses for <?php echo htmlspecialchars($batch_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen p-10">

    <div class="max-w-6xl mx-auto">
        <a href="instructor_batches.php" class="text-blue-600 hover:underline mb-6 inline-block font-medium">&larr; Back
            to Batch List</a>

        <div class="mb-8 border-b pb-4">
            <h1 class="text-3xl font-bold text-gray-800">
                Courses for <span class="text-indigo-600"><?php echo htmlspecialchars($batch_name); ?></span>
            </h1>
            <p class="text-gray-600 mt-2">Only showing courses where students from this batch are enrolled.</p>
        </div>

        <?php if (empty($courses)): ?>
            <div class="bg-yellow-50 border border-yellow-200 p-8 rounded-lg text-center">
                <p class="text-yellow-800 text-lg font-medium">No active enrollments found.</p>
                <p class="text-yellow-700 text-sm mt-2">
                    Students from <strong><?= htmlspecialchars($batch_name) ?></strong> haven't enrolled in any of your
                    courses yet.
                </p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($courses as $course): ?>
                    <a href="instructor_marks.php?course_id=<?= $course['id'] ?>&batch_id=<?= $batch_id ?>"
                        class="block bg-white p-6 rounded-xl shadow-sm hover:shadow-lg transition border-t-4 border-teal-500 group">

                        <div class="flex justify-between items-start">
                            <div>
                                <h2 class="text-xl font-bold text-gray-800 group-hover:text-teal-600 transition">
                                    <?= htmlspecialchars($course['title']) ?>
                                </h2>
                                <p class="text-sm text-gray-500 font-mono mt-1">
                                    <?= htmlspecialchars($course['course_code']) ?>
                                </p>
                            </div>
                        </div>

                        <div class="mt-6 flex items-center text-teal-600 font-medium">
                            <span class="mr-2">Enter 60 Marks</span>
                            <span>&rarr;</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>