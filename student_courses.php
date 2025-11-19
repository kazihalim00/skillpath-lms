<?php
/**
 * SkillPath Project: Student Courses Page (Complete Enrollment Feature)
 *
 * This page displays all available courses and allows the student to enroll.
 */

session_start();
require_once 'db_config.php';

// Security Check: Must be logged in and the role must be 'student'
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    session_unset();
    session_destroy();
    header("Location: login.html?status=error&message=Access%20Denied.%20Please%20log%20in.");
    exit();
}
$user_id = $_SESSION['user_id'];
$message = null;
$courses = [];
$enrolled_course_ids = [];

// --- Process Enrollment Request ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['enroll_course_id'])) {
    $course_to_enroll = $_POST['enroll_course_id'];
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }

        $sql = "INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $course_to_enroll);

        if ($stmt->execute()) {
            $message = "Enrollment successful! You are now taking this course.";
        } else {
            if ($conn->errno == 1062) {
                $message = "You are already enrolled in this course.";
            } else {
                $message = "Error during enrollment: " . $conn->error;
            }
        }
        $stmt->close();
        $conn->close();

    } catch (Exception $e) {
        $message = "Database Error: " . $e->getMessage();
    }
}

// --- Fetch Available Courses and Enrollment Status ---
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // 1. Get IDs of courses the student is already enrolled in
    $enroll_sql = "SELECT course_id FROM enrollments WHERE student_id = ?";
    $enroll_stmt = $conn->prepare($enroll_sql);
    $enroll_stmt->bind_param("i", $user_id);
    $enroll_stmt->execute();
    $enroll_result = $enroll_stmt->get_result();
    while ($row = $enroll_result->fetch_assoc()) {
        $enrolled_course_ids[] = $row['course_id'];
    }
    $enroll_stmt->close();

    // 2. Get all published courses
    $course_sql = "SELECT c.id, c.course_code, c.title, u.full_name AS instructor 
                   FROM courses c 
                   JOIN users u ON c.instructor_id = u.id 
                   WHERE c.status = 'published'"; // Only show published courses
    $course_result = $conn->query($course_sql);
    while ($row = $course_result->fetch_assoc()) {
        $row['is_enrolled'] = in_array($row['id'], $enrolled_course_ids);
        $courses[] = $row;
    }

    $conn->close();
} catch (Exception $e) {
    $message = "Database Error: Could not fetch courses: " . $e->getMessage();
}

$user_name = $_SESSION['user_name'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Courses | SkillPath</title>
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
            <h1 class="text-3xl font-bold text-blue-700">
                Course Catalog (<?php echo count($courses); ?> Available)
            </h1>
            <a href="student_dashboard.php" class="text-indigo-500 hover:text-indigo-700 font-semibold">
                &larr; Back to Dashboard
            </a>
        </div>

        <?php if ($message): ?>
            <div
                class="p-4 mb-4 rounded-lg <?php echo strpos($message, 'successful') !== false ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">
            <?php if (empty($courses)): ?>
                <div class="col-span-full p-8 text-center text-gray-500 border-dashed border-2 rounded-lg">
                    No courses are currently published in the catalog.
                </div>
            <?php else: ?>
                <?php foreach ($courses as $course): ?>
                    <div
                        class="bg-gray-50 p-6 rounded-xl shadow-lg border-l-4 <?php echo $course['is_enrolled'] ? 'border-green-500' : 'border-blue-500'; ?> hover:shadow-xl transition duration-300">
                        <h3 class="text-xl font-bold text-gray-800 mb-1"><?php echo htmlspecialchars($course['title']); ?></h3>
                        <p class="text-sm font-mono text-gray-500 mb-3"><?php echo htmlspecialchars($course['course_code']); ?>
                        </p>

                        <p class="text-sm text-gray-600">Instructor: <?php echo htmlspecialchars($course['instructor']); ?></p>

                        <div class="mt-4">
                            <?php if ($course['is_enrolled']): ?>
                                <a href="student_course_content.php?course_id=<?php echo htmlspecialchars($course['id']); ?>"
                                    class="w-full block py-2 text-white font-medium text-center rounded-lg bg-green-500 hover:bg-green-600 transition duration-150">
                                    Access Course
                                </a>
                            <?php else: ?>
                                <form method="POST" action="student_courses.php">
                                    <input type="hidden" name="enroll_course_id"
                                        value="<?php echo htmlspecialchars($course['id']); ?>">
                                    <button type="submit"
                                        class="w-full py-2 bg-blue-600 text-white font-medium rounded-lg shadow-md hover:bg-blue-700 transition duration-150">
                                        Enroll Now
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="mt-8 text-center text-gray-400 text-xs border-t pt-4">
            <p>Student Page - Role: <?php echo $_SESSION['user_role']; ?> | User ID: <?php echo $_SESSION['user_id']; ?>
            </p>
        </div>
    </div>
</body>

</html>