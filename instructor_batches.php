<?php
/**
 * SkillPath - Instructor Batches
 * This file lists the batches so you can select one to view its courses.
 */
session_start();
require_once 'db_config.php';

// Check Login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: login.php");
    exit();
}

// Fetch Batches from Database
$batches = [];
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
    $result = $conn->query("SELECT * FROM batches ORDER BY id DESC");
    if ($result) {
        $batches = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    // Ignore error
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Select Batch | SkillPath</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
</head>

<body class="bg-gray-50 font-sans min-h-screen p-10" style="font-family: 'Inter', sans-serif;">

    <div class="max-w-6xl mx-auto">
        <a href="instructor_dashboard.php" class="text-blue-600 hover:underline mb-6 inline-block font-medium">&larr;
            Back to Dashboard</a>

        <div class="flex justify-between items-end mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Select a Batch</h1>
                <p class="text-gray-600 mt-2">Choose a batch to view enrolled courses and 60 marks.</p>
            </div>
        </div>

        <?php if (empty($batches)): ?>
            <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-6 rounded-lg text-center">
                <p class="font-medium">No batches found in the database.</p>
                <p class="text-sm mt-1">Please ask an administrator to add batches.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($batches as $batch): ?>
                    <a href="instructor_batch_courses.php?batch_id=<?= $batch['id'] ?>"
                        class="group block bg-white p-6 rounded-xl shadow-sm hover:shadow-md transition border border-gray-100 hover:border-indigo-500 relative overflow-hidden">
                        <div class="absolute top-0 left-0 w-1 h-full bg-indigo-500 group-hover:bg-indigo-600 transition"></div>

                        <h2 class="text-xl font-bold text-gray-800 group-hover:text-indigo-600 transition">
                            <?= htmlspecialchars($batch['batch_name'] ?? 'Unnamed Batch') ?>
                        </h2>
                        <div class="mt-4 flex justify-between items-center">
                            <span class="text-sm text-gray-500 bg-gray-100 px-2 py-1 rounded">ID: <?= $batch['id'] ?></span>
                            <span class="text-indigo-500 font-medium text-sm group-hover:translate-x-1 transition">View Courses
                                &rarr;</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>