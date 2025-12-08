<?php
/**
 * SkillPath - Instructor Dashboard
 * * WHY IS THIS CODE SHORTER?
 * 1. We used Tailwind CSS to replace long style blocks.
 * 2. We moved the "Batch/60 Marks" logic to a separate file (instructor_batches.php).
 * 3. This acts as a navigation hub, so it loads faster.
 */
session_start();
require_once 'db_config.php';

// 1. Security Check: Kick user out if not an instructor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: login.php");
    exit();
}

$instructor_name = $_SESSION['name'] ?? 'Instructor';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Dashboard | SkillPath</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f7f9fb;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col">

    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold text-blue-600">SkillPath <span
                            class="text-gray-500 text-lg font-medium">| Instructor</span></h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700 font-medium">Welcome,
                        <?php echo htmlspecialchars($instructor_name); ?></span>
                    <a href="logout.php"
                        class="bg-red-50 text-red-600 px-4 py-2 rounded-md text-sm font-medium hover:bg-red-100 transition">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-800">Course Management Tools</h2>
            <p class="text-gray-600 mt-2">Manage your courses, students, and assessments from one central hub.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">

            <div class="bg-white p-6 rounded-lg shadow-sm border-t-4 border-teal-500 hover:shadow-md transition">
                <h3 class="text-xl font-bold text-gray-800 mb-2">New Course</h3>
                <p class="text-gray-600 mb-4 text-sm">Design and publish new learning modules.</p>
                <a href="instructor_course_create.php"
                    class="text-teal-600 font-semibold hover:underline inline-flex items-center">
                    Start Creation &rarr;
                </a>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-sm border-t-4 border-purple-500 hover:shadow-md transition">
                <h3 class="text-xl font-bold text-gray-800 mb-2">Upload Materials</h3>
                <p class="text-gray-600 mb-4 text-sm">Add videos, PDFs, and external file links.</p>
                <a href="instructor_material_upload.php"
                    class="text-purple-600 font-semibold hover:underline inline-flex items-center">
                    Add Files &rarr;
                </a>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-sm border-t-4 border-blue-500 hover:shadow-md transition">
                <h3 class="text-xl font-bold text-gray-800 mb-2">Manage Assignments</h3>
                <p class="text-gray-600 mb-4 text-sm">Create assignments and set deadlines.</p>
                <a href="instructor_assignments.php"
                    class="text-blue-600 font-semibold hover:underline inline-flex items-center">
                    Create/View &rarr;
                </a>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-sm border-t-4 border-yellow-500 hover:shadow-md transition">
                <h3 class="text-xl font-bold text-gray-800 mb-2">Quiz Builder</h3>
                <p class="text-gray-600 mb-4 text-sm">Design and structure multiple-choice quizzes.</p>
                <a href="instructor_quiz_create.php"
                    class="text-yellow-600 font-semibold hover:underline inline-flex items-center">
                    Start Building &rarr;
                </a>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-sm border-t-4 border-indigo-500 hover:shadow-md transition">
                <h3 class="text-xl font-bold text-gray-800 mb-2">Batch Info</h3>
                <p class="text-gray-600 mb-4 text-sm">View batches (e.g., SWE Batch 4), courses, and 60 marks.</p>
                <a href="instructor_batches.php"
                    class="text-indigo-600 font-semibold hover:underline inline-flex items-center">
                    View Batches &rarr;
                </a>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-sm border-t-4 border-red-500 hover:shadow-md transition">
                <h3 class="text-xl font-bold text-gray-800 mb-2">Grade Appeals</h3>
                <p class="text-gray-600 mb-4 text-sm">View and resolve student grade challenges.</p>
                <a href="instructor_appeals.php"
                    class="text-red-600 font-semibold hover:underline inline-flex items-center">
                    View Appeals &rarr;
                </a>
            </div>
        </div>

        <div class="bg-white p-8 rounded-lg shadow-sm border-t-4 border-orange-500 hover:shadow-md transition mb-10">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Grade Submissions (Assignment & Quiz)</h3>
                    <p class="text-gray-600 text-sm max-w-2xl">Review student submissions, assign grades, and record
                        final scores.</p>
                </div>
                <div class="mt-4 md:mt-0">
                    <a href="instructor_gradebook.php"
                        class="bg-orange-50 text-orange-700 hover:bg-orange-100 px-6 py-3 rounded-lg font-semibold transition inline-flex items-center">
                        Go to Grading Interface &rarr;
                    </a>
                </div>
            </div>
        </div>

    </main>

    <footer class="bg-white border-t border-gray-200 mt-auto py-6">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="text-gray-500 text-sm">© <?php echo date("Y"); ?> SkillPath. All rights reserved.</p>
</div>
</footer>

</body>

</html>