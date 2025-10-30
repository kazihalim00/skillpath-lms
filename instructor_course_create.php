<?php
/**
 * SkillPath Project: Instructor Course Creation Page
 *
 * This page contains the form for creating a new course and the logic
 * to insert the new course into the 'courses' table.
 */

session_start();

// Include the database configuration file (needed for DB_HOST, DB_USER, etc.)
require_once 'db_config.php';

// Security Check: Must be logged in and the role must be 'instructor'
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'instructor') {
    session_unset();
    session_destroy();
    header("Location: login.html?status=error&message=Access%20Denied.%20Please%20log%20in%20as%20an%20Instructor.");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = null;
$message_type = null;

// --- STEP 1: Process Form Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Sanitize and retrieve form data
    $course_code = strtoupper(trim($_POST['course_code']));
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $status = 'draft'; // New courses start as a draft by default

    // Validate essential fields
    if (empty($course_code) || empty($title)) {
        $message = "Course Code and Title are required fields.";
        $message_type = "error";
    } else {
        // Connect to the database
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

        if ($conn->connect_error) {
            $message = "Database connection failed: " . $conn->connect_error;
            $message_type = "error";
        } else {
            // Check if the course code already exists
            $check_sql = "SELECT id FROM courses WHERE course_code = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("s", $course_code);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                $message = "Error: A course with this code already exists. Please choose a unique code.";
                $message_type = "error";
            } else {
                // Insert the new course into the database
                $insert_sql = "INSERT INTO courses (course_code, title, description, instructor_id, status) VALUES (?, ?, ?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_sql);
                // The 'sssis' refers to string, string, string, integer (for user_id), string
                $insert_stmt->bind_param("sssis", $course_code, $title, $description, $user_id, $status);

                if ($insert_stmt->execute()) {
                    // --- SUCCESS REDIRECT FIX ---
                    header("Location: instructor_course_create.php?status=success&code=" . urlencode($course_code));
                    exit();

                } else {
                    $message = "Error creating course: " . $conn->error;
                    $message_type = "error";
                }
                $insert_stmt->close();
            }
            $check_stmt->close();
            $conn->close();
        }
    }
}

// --- STEP 2: Handle Success Message on Page Load (via URL parameter) ---
if (isset($_GET['status']) && $_GET['status'] === 'success') {
    $code = htmlspecialchars($_GET['code'] ?? 'Course');
    $message = "Success! Course '{$code}' created and saved as DRAFT.";
    $message_type = "success";
}

$user_name = $_SESSION['user_name'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Course | SkillPath</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f9;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col items-center p-4">

    <div class="w-full max-w-4xl bg-white shadow-xl rounded-xl p-8 md:p-12 mt-8">
        <div class="flex justify-between items-center border-b pb-4 mb-6">
            <h1 class="text-3xl font-bold text-teal-700">
                Create New Course
            </h1>
            <a href="instructor_dashboard.php" class="text-indigo-500 hover:text-indigo-700 font-semibold">
                &larr; Back to Dashboard
            </a>
        </div>

        <?php if ($message): ?>
            <div
                class="p-4 mb-4 rounded-lg flex justify-between items-center <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700 border border-green-300' : 'bg-red-100 text-red-700 border border-red-300'; ?>">
                <span class="font-medium"><?php echo htmlspecialchars($message); ?></span>
                <?php if ($message_type === 'success'): ?>
                    <a href="instructor_course_list.php"
                        class="bg-teal-500 text-white text-sm py-1 px-3 rounded hover:bg-teal-600 transition duration-150">
                        View All Courses
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="instructor_course_create.php" class="space-y-6">

            <div>
                <label for="course_code" class="block text-sm font-medium text-gray-700 mb-1">Course Code (e.g., SWE401)
                    <span class="text-red-500">*</span></label>
                <input type="text" id="course_code" name="course_code" required maxlength="10"
                    value="<?php echo htmlspecialchars($_POST['course_code'] ?? ''); ?>"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500 uppercase transition duration-150"
                    placeholder="MAX 10 CHARACTERS">
            </div>

            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Course Title <span
                        class="text-red-500">*</span></label>
                <input type="text" id="title" name="title" required
                    value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500 transition duration-150"
                    placeholder="e.g., Web Application Development">
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Course Description
                    (Optional)</label>
                <textarea id="description" name="description" rows="5"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500 transition duration-150"
                    placeholder="Provide a detailed description of the course content and objectives."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
            </div>

            <div>
                <button type="submit"
                    class="w-full py-3 px-4 border border-transparent rounded-md shadow-sm text-lg font-medium text-white bg-teal-600 hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 transition duration-150">
                    Create Course as DRAFT
                </button>
            </div>
        </form>

        <div class="mt-8 text-center text-gray-400 text-xs border-t pt-4">
            <p>Instructor Page - Role: <?php echo $_SESSION['user_role']; ?> | User ID:
                <?php echo $_SESSION['user_id']; ?>
            </p>
        </div>
    </div>

</body>

</html>