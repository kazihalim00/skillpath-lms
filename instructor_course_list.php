<?php
/**
 * SkillPath Project: Instructor Course List Page
 *
 * This page fetches and displays all courses created by the logged-in instructor.
 */

session_start();

// Include the database configuration file
require_once 'db_config.php';

// Security Check: Must be logged in and the role must be 'instructor'
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'instructor') {
    session_unset();
    session_destroy();
    header("Location: login.html?status=error&message=Access%20Denied.%20Please%20log%20in%20as%20an%20Instructor.");
    exit();
}

// Get the user ID from the session, which is used to filter courses
$user_id = $_SESSION['user_id'];
$courses = [];
$message = null;

// --- Fetch Courses from Database ---
try {
    // Connect to the database
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // Select courses created by the current instructor
    $sql = "SELECT id, course_code, title, status, created_at FROM courses WHERE instructor_id = ?";
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
    // Display error message if the connection or query failed
    $message = "Error fetching courses: " . $e->getMessage();
}

$user_name = $_SESSION['user_name'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses | SkillPath</title>
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
    </style>
</head>

<body class="min-h-screen flex flex-col items-center p-4">

    <div class="w-full max-w-6xl bg-white shadow-xl rounded-xl p-8 md:p-12 mt-8">
        <div class="flex justify-between items-center border-b pb-4 mb-6">
            <h1 class="text-3xl font-bold text-gray-800">
                My Created Courses
            </h1>
            <a href="instructor_dashboard.php" class="text-indigo-500 hover:text-indigo-700 font-semibold">
                &larr; Back to Dashboard
            </a>
        </div>

        <?php if ($message): ?>
            <div class="p-4 mb-4 rounded-lg bg-red-100 text-red-700 border border-red-300">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($courses)): ?>
            <div class="text-center py-10 border-dashed border-2 border-gray-300 rounded-lg">
                <p class="text-lg text-gray-600 mb-2">You haven't created any courses yet.</p>
                <a href="instructor_course_create.php" class="text-teal-500 hover:underline font-semibold">
                    Click here to create your first course.
                </a>
                <p class="text-xs mt-4 text-gray-400">If you have created courses, ensure your user ID is properly linked in
                    the database.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200 rounded-lg overflow-hidden">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code
                            </th>
                            <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title
                            </th>
                            <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status</th>
                            <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Created</th>
                            <th class="py-3 px-6 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $course): ?>
                            <tr class="hover:bg-gray-50 transition duration-150">
                                <td class="py-3 px-4 border-b text-sm font-mono">
                                    <?php echo htmlspecialchars($course['course_code']); ?>
                                </td>
                                <td class="py-3 px-4 border-b font-medium text-gray-800">
                                    <?php echo htmlspecialchars($course['title']); ?>
                                </td>
                                <td class="py-3 px-4 border-b">
                                    <?php
                                    $status_color = $course['status'] === 'published' ? 'status-published' : 'status-draft';
                                    echo "<span class='inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {$status_color}'>" . ucfirst($course['status']) . "</span>";
                                    ?>
                                </td>
                                <td class="py-3 px-4 border-b text-sm text-gray-500">
                                    <?php echo date('M d, Y', strtotime($course['created_at'])); ?>
                                </td>
                                <td class="py-3 px-4 border-b text-center text-sm">
                                    <a href="instructor_course_edit.php?id=<?php echo htmlspecialchars($course['id']); ?>"
                                        class="text-blue-500 hover:text-blue-700 font-medium">Edit</a>
                                    <span class="text-gray-300">|</span>
                                    <a href="instructor_course_delete.php?id=<?php echo htmlspecialchars($course['id']); ?>"
                                        onclick="return confirm('Are you sure you want to delete this course?')"
                                        class="text-red-500 hover:text-red-700 font-medium">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="mt-8 text-center text-gray-400 text-xs border-t pt-4">
            <p>Instructor Page - Role: <?php echo $_SESSION['user_role']; ?> | User ID:
                <?php echo $_SESSION['user_id']; ?>
            </p>
        </div>
    </div>

</body>

</html>