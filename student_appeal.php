<?php
/**
 * SkillPath Project: Student Appeal Page
 *
 * FIX: Forces item_type to lowercase to prevent "Data truncated" error.
 */
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$item_type_from_url = $_GET['type'] ?? '';
$item_id = $_GET['id'] ?? 0;
$course_id = $_GET['course_id'] ?? 0;
$item_title = $_GET['title'] ?? 'Item';

// --- Handle Form Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $reason = trim($_POST['reason']);
    $item_type_raw = $_POST['item_type'];
    $item_id = $_POST['item_id'];
    $course_id = $_POST['course_id'];

    // *** THE 100% FIX IS HERE ***
    // Force the item_type to lowercase to match the ENUM ('quiz', 'assignment')
    $item_type = strtolower($item_type_raw);

    if (!empty($reason) && in_array($item_type, ['quiz', 'assignment'])) {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
            $sql = "INSERT INTO grade_appeals (student_id, course_id, item_type, item_id, reason) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            // Bind the corrected, lowercase $item_type
            $stmt->bind_param("iiiss", $user_id, $course_id, $item_type, $item_id, $reason);

            if ($stmt->execute()) {
                header("Location: student_grades.php?status=success&message=Appeal+submitted+successfully.");
                exit();
            } else {
                // This might trigger if they appeal the same item twice
                $error = "Error: You may have already appealed this item.";
            }
            $conn->close();
        } catch (Exception $e) {
            $error = "DB Error: " . (strpos($e->getMessage(), 'Duplicate entry') !== false ? 'You have already appealed this item.' : $e->getMessage());
        }
    } else {
        $error = "Please provide a valid reason for the appeal.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Appeal Grade | SkillPath</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen flex flex-col items-center justify-center p-4 bg-gray-100">
    <div class="w-full max-w-md bg-white shadow-xl rounded-xl p-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Challenge Grade</h2>
        <p class="text-gray-600 mb-4">Appeal for:
            <strong><?php echo htmlspecialchars(urldecode($item_title)); ?></strong>
        </p>

        <?php if (isset($error)): ?>
            <div class="p-3 mb-3 bg-red-100 text-red-700 text-sm rounded-lg">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <!-- This value is now correctly passed from the URL -->
            <input type="hidden" name="item_type" value="<?php echo htmlspecialchars($item_type_from_url); ?>">
            <input type="hidden" name="item_id" value="<?php echo htmlspecialchars($item_id); ?>">
            <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course_id); ?>">

            <label class="block text-sm font-medium text-gray-700 mb-2">Reason for Appeal / Challenge</label>
            <textarea name="reason" rows="5" required class="w-full border rounded-lg p-3"
                placeholder="Explain why you think your grade should be reviewed..."></textarea>

            <div class="flex justify-end mt-4 space-x-3">
                <a href="student_grades.php" class="px-4 py-2 text-gray-600 hover:text-gray-800">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700">Submit
                    Appeal</button>
            </div>
        </form>
    </div>
</body>

</html>