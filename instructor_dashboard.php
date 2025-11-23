<?php
/**
 * SkillPath Project: Instructor Dashboard (Final Organization)
 * This file organizes the tools into the requested professional workflow: 
 * Creation -> Materials -> Assessment -> Grading.
 */

session_start();
require_once 'db_config.php'; // Include DB config

// Security Check: Must be logged in and the role must be 'instructor'
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'instructor') {
    session_unset();
    session_destroy();
    header("Location: login.html?status=error&message=Access%20Denied.%20Please%20log%20in.");
    exit();
}

// Get user info
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$courses = [];
$error_message = null;

// --- Fetch Instructor's Courses ---
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Select courses created by the current instructor, ordered by most recent
    $sql = "SELECT id, course_code, title, status FROM courses WHERE instructor_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    $error_message = "Database Error: Could not fetch courses.";
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Dashboard | SkillPath</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f9;
        }

        .status-published {
            background-color: #d1fae5;
            color: #065f46;
        }

        /* Green */
        .status-draft {
            background-color: #fef3c7;
            color: #92400e;
        }

        /* Yellow */
        .course-list {
            max-height: 200px;
            overflow-y: auto;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col items-center p-4">

    <div class="w-full max-w-4xl flex justify-end mb-4">
        <a href="logout.php"
            class="py-2 px-4 bg-red-500 text-white font-semibold rounded-lg shadow-md hover:bg-red-600 transition duration-300">
            Logout
        </a>
    </div>

    <div class="w-full max-w-4xl bg-white shadow-2xl rounded-xl p-8 md:p-12">
        <h1 class="text-4xl font-extrabold text-teal-700 mb-2">
            Hello, <?php echo htmlspecialchars(explode(' ', $user_name)[0]); ?> Ma'am
        </h1>
        <p class="text-lg text-gray-500 mb-8 border-b pb-4">
            You are logged in as an **Instructor**.
        </p>

        <?php if ($error_message): ?>
            <div class="p-4 mb-6 bg-red-100 border border-red-300 text-red-700 rounded-lg"><?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="mb-8 border border-gray-200 rounded-xl p-4 bg-gray-50">
            <div class="flex justify-between items-center mb-3">
                <h2 class="text-xl font-bold text-gray-700">My Courses (<?php echo count($courses); ?> Total)</h2>
                <a href="instructor_course_list.php" class="text-sm text-teal-500 hover:text-teal-700 font-medium">View
                    All →</a>
            </div>

            <div class="course-list pr-2">
                <?php if (empty($courses)): ?>
                    <div class="p-4 text-center text-gray-500 border border-dashed rounded-lg bg-white">
                        You have not created any courses yet.
                        <a href="instructor_course_create.php" class="text-indigo-500 hover:underline block mt-2">Start your
                            first course here.</a>
                    </div>
                <?php else: ?>
                    <ul class="space-y-3">
                        <?php foreach ($courses as $course): ?>
                            <li
                                class="flex justify-between items-center p-3 bg-white border rounded-lg shadow-sm hover:shadow-md transition duration-150">
                                <div>
                                    <span
                                        class="font-medium text-gray-800"><?php echo htmlspecialchars($course['title']); ?></span>
                                    <span
                                        class="text-sm text-gray-500 ml-2">(<?php echo htmlspecialchars($course['course_code']); ?>)</span>
                                </div>
                                <span
                                    class="px-2 py-0.5 rounded-full text-xs font-medium <?php echo 'status-' . strtolower($course['status']); ?>">
                                    <?php echo ucfirst($course['status']); ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <h2 class="text-2xl font-bold text-gray-700 mb-6">Instructor Workflow Tools</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

            <div
                class="bg-white p-4 rounded-xl shadow-lg border-t-4 border-teal-500 hover:shadow-xl transition duration-300">
                <h3 class="text-lg font-semibold text-gray-800">New Course</h3>
                <p class="text-gray-600 mt-1 text-xs">Start creating a new course shell.</p>
                <a href="instructor_course_create.php"
                    class="text-teal-500 hover:text-teal-700 mt-2 block text-sm font-medium">Start Creation →</a>
            </div>

            <div
                class="bg-white p-4 rounded-xl shadow-lg border-t-4 border-purple-500 hover:shadow-xl transition duration-300">
                <h3 class="text-lg font-semibold text-gray-800">Upload Materials</h3>
                <p class="text-gray-600 mt-1 text-xs">Add videos, PDFs, and external file links.</p>
                <a href="instructor_material_upload.php"
                    class="text-purple-500 hover:text-purple-700 mt-2 block text-sm font-medium">Add Files →</a>
            </div>

            <div
                class="bg-white p-4 rounded-xl shadow-lg border-t-4 border-indigo-500 hover:shadow-xl transition duration-300">
                <h3 class="text-lg font-semibold text-gray-800">Manage Assignments</h3>
                <p class="text-gray-600 mt-1 text-xs">Create assignments and set deadlines.</p>
                <a href="instructor_assignments.php"
                    class="text-indigo-500 hover:text-indigo-700 mt-2 block text-sm font-medium">Create/View →</a>
            </div>
            <div
                class="bg-white p-4 rounded-xl shadow-lg border-t-4 border-red-500 hover:shadow-xl transition duration-300">
                <h3 class="text-lg font-semibold text-gray-800">Grade Appeals</h3>
                <p class="text-gray-600 mt-1 text-xs">View student grade challenges.</p>
                <a href="instructor_appeals.php"
                    class="text-red-500 hover:text-red-700 mt-2 block text-sm font-medium">View Appeals →</a>
            </div>
            <div
                class="bg-white p-4 rounded-xl shadow-lg border-t-4 border-yellow-600 hover:shadow-xl transition duration-300">
                <h3 class="text-lg font-semibold text-gray-800">Quiz Builder</h3>
                <p class="text-gray-600 mt-1 text-xs">Design and structure multiple-choice quizzes.</p>
                <a href="instructor_quiz_create.php"
                    class="text-yellow-600 hover:text-yellow-700 mt-2 block text-sm font-medium">Start Building →</a>
            </div>

            <div
                class="bg-white p-4 rounded-xl shadow-lg border-t-4 border-orange-500 hover:shadow-xl transition duration-300 mt-4 lg:col-span-4">
                <h3 class="text-2xl font-semibold text-gray-800">Grade Submissions (Assignment & Quiz)</h3>
                <p class="text-gray-600 mt-1 text-sm">Review student submissions and record final scores for all
                    assessment types.</p>
                <a href="instructor_gradebook.php"
                    class="text-orange-500 hover:text-orange-700 mt-2 block text-sm font-medium">Go to Grading Interface
                    →</a>
            </div>

        </div>

        <div class="mt-8 text-center text-gray-400 text-xs border-t pt-4">
            <p>Debug Info: Role: <?php echo $_SESSION['user_role']; ?> | User ID: <?php echo $_SESSION['user_id']; ?>
            </p>
        </div>
    </div>
</body>

</html>