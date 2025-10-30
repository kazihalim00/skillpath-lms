<?php
/**
 * SkillPath Project: Instructor Gradebook Page (Initial Setup)
 *
 */

session_start();
require_once 'db_config.php';

// Security Check: Must be logged in and the role must be 'instructor'
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'instructor') {
    session_unset();
    session_destroy();
    header("Location: login.html?status=error&message=Access%20Denied.%20Please%20log%20in.");
    exit();
}
$user_id = $_SESSION['user_id'];
$courses = [];
$message = null;

// NOTE: No functional logic for fetching students or grades in this initial commit.
// Only fetching courses for the dropdown.

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    $sql = "SELECT id, course_code, title FROM courses WHERE instructor_id = ?";
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
    $message = "Database Error: " . $e->getMessage();
}

$user_name = $_SESSION['user_name'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade Submissions | SkillPath</title>
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
            <h1 class="text-3xl font-bold text-orange-700">
                Grade Submissions (Initial Setup)
            </h1>
            <a href="instructor_dashboard.php" class="text-indigo-500 hover:text-indigo-700 font-semibold">
                &larr; Back to Dashboard
            </a>
        </div>

        <?php if ($message): ?>
            <div class="p-4 mb-4 rounded-lg bg-red-100 border border-red-300 text-red-700">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="GET" action="instructor_gradebook.php"
            class="mb-8 p-4 border border-gray-200 rounded-lg bg-gray-50">
            <label for="course_select" class="block text-lg font-medium text-gray-700 mb-2">Select Course to
                Grade</label>
            <div class="flex space-x-4">
                <select id="course_select" name="course_id" required
                    class="flex-grow px-4 py-2 border border-gray-300 rounded-lg shadow-sm">
                    <option value="">-- Choose a Course --</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo htmlspecialchars($course['id']); ?>">
                            <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white font-medium rounded-lg">
                    Load Students
                </button>
            </div>
        </form>

        <div class="p-6 text-center text-gray-500 border-dashed border-2 rounded-lg">
            <p class="text-lg mb-2">Select a course above to load enrolled students.</p>
        </div>

        <div class="mt-8 text-center text-gray-400 text-xs border-t pt-4">
            <p>Instructor Page - Role: <?php echo $_SESSION['user_role']; ?> | User ID:
                <?php echo $_SESSION['user_id']; ?>
            </p>
        </div>
    </div>

</body>

</html>