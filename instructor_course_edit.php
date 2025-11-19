<?php
/**
 * SkillPath Project: Instructor Course Edit Page
 *
 * This page contains the form to edit an existing course.
 */

session_start();
require_once 'db_config.php';

// Security Check: Must be logged in and the role must be 'instructor'
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'instructor') {
    session_unset();
    session_destroy();
    header("Location: login.html?status=error&message=Access%20Denied.");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = null;
$message_type = null;
$course_data = null;
$course_id = $_GET['id'] ?? null;

// --- Fetch Course Data for the Form ---
if ($course_id) {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }

        $sql = "SELECT course_code, title, description, status FROM courses WHERE id = ? AND instructor_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $course_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $course_data = $result->fetch_assoc();
        } else {
            $message = "Error: Course not found or you do not have permission to edit it.";
            $message_type = "error";
        }
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        $message = "Database Error: " . $e->getMessage();
        $message_type = "error";
    }
} else {
    $message = "Error: No course ID provided.";
    $message_type = "error";
}

// --- Process Form Submission for Update ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_course'])) {
    $updated_code = strtoupper(trim($_POST['course_code']));
    $updated_title = trim($_POST['title']);
    $updated_description = trim($_POST['description']);
    $updated_status = $_POST['status'];

    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }

        $sql = "UPDATE courses SET course_code = ?, title = ?, description = ?, status = ? WHERE id = ? AND instructor_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssii", $updated_code, $updated_title, $updated_description, $updated_status, $course_id, $user_id);

        if ($stmt->execute()) {
            $message = "Success! Course updated successfully.";
            $message_type = "success";
            // Re-fetch data to show the updated information on the page
            $course_data['course_code'] = $updated_code;
            $course_data['title'] = $updated_title;
            $course_data['description'] = $updated_description;
            $course_data['status'] = $updated_status;
        } else {
            $message = "Error updating course: " . $conn->error;
            $message_type = "error";
        }
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        $message = "Database Error: " . $e->getMessage();
        $message_type = "error";
    }
}
$user_name = $_SESSION['user_name'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Course | SkillPath</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen flex flex-col items-center p-4 bg-gray-100">

    <div class="w-full max-w-4xl bg-white shadow-xl rounded-xl p-8 md:p-12 mt-8">
        <div class="flex justify-between items-center border-b pb-4 mb-6">
            <h1 class="text-3xl font-bold text-gray-800">
                Edit Course: <?php echo htmlspecialchars($course_data['title'] ?? 'N/A'); ?>
            </h1>
            <a href="instructor_course_list.php" class="text-indigo-500 hover:text-indigo-700 font-semibold">
                &larr; Back to Course List
            </a>
        </div>

        <?php if ($message): ?>
            <div
                class="p-4 mb-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($course_data): ?>
            <form method="POST" action="instructor_course_edit.php?id=<?php echo htmlspecialchars($course_id); ?>"
                class="space-y-6">
                <input type="hidden" name="update_course" value="1">

                <div>
                    <label for="course_code" class="block text-sm font-medium text-gray-700 mb-1">Course Code <span
                            class="text-red-500">*</span></label>
                    <input type="text" id="course_code" name="course_code" required maxlength="10"
                        value="<?php echo htmlspecialchars($course_data['course_code']); ?>"
                        class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md">
                </div>

                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Course Title <span
                            class="text-red-500">*</span></label>
                    <input type="text" id="title" name="title" required
                        value="<?php echo htmlspecialchars($course_data['title']); ?>"
                        class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md">
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Course Description
                        (Optional)</label>
                    <textarea id="description" name="description" rows="5"
                        class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md"><?php echo htmlspecialchars($course_data['description']); ?></textarea>
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Course Status</label>
                    <select id="status" name="status" class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md">
                        <option value="draft" <?php echo $course_data['status'] === 'draft' ? 'selected' : ''; ?>>Draft
                        </option>
                        <option value="published" <?php echo $course_data['status'] === 'published' ? 'selected' : ''; ?>>
                            Published</option>
                        <option value="archived" <?php echo $course_data['status'] === 'archived' ? 'selected' : ''; ?>>
                            Archived</option>
                    </select>
                </div>

                <div class="flex justify-end space-x-4">
                    <button type="submit"
                        class="py-2 px-4 bg-indigo-600 text-white font-medium rounded-md shadow-md hover:bg-indigo-700">
                        Update Course
                    </button>
                    <a href="instructor_course_list.php"
                        class="py-2 px-4 bg-gray-300 text-gray-700 font-medium rounded-md shadow-md hover:bg-gray-400">
                        Cancel
                    </a>
                </div>
            </form>
        <?php else: ?>
            <div class="p-6 text-center text-gray-500">
                <p>Course not found or an error occurred.</p>
                <a href="instructor_course_list.php"
                    class="mt-4 inline-block text-indigo-500 hover:text-indigo-700 font-semibold">
                    &larr; Return to Course List
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>
