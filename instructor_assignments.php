<?php
/**
 * SkillPath Project: Instructor Assignments Page (Functional)
 *
 * FIX: This version implements real file uploads for assignment instructions
 * and fixes the 'due_date' error by using a simple DATE input.
 */

session_start();
require_once 'db_config.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'instructor') {
    header("Location: login.html?status=error&message=Access%20Denied.");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = null;
$courses = [];
$assignments = [];
$selected_course_id = $_GET['course_id'] ?? null;

// --- Process Assignment Creation ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_assignment'])) {
    $course_id = $_POST['course_id'];
    $title = trim(isset($_POST['title']) ? $_POST['title'] : '');
    $description = trim(isset($_POST['description']) ? $_POST['description'] : '');
    $due_date_raw = isset($_POST['due_date']) ? $_POST['due_date'] : ''; // Input: YYYY-MM-DD
    $max_points = (int)($_POST['max_points'] ?? 100);
    $file_path_for_db = null; // This will store the path for the DB

    if (empty($title) || empty($due_date_raw)) {
        $message = "Title and Due Date are required.";
        goto end_process;
    }

    // --- FILE UPLOAD LOGIC ---
    if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/uploads/assignments/'; // Absolute path on server
        $file_name = uniqid() . '_' . basename($_FILES['assignment_file']['name']);
        $target_file = $upload_dir . $file_name;

        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Try to move the uploaded file
        if (move_uploaded_file($_FILES['assignment_file']['tmp_name'], $target_file)) {
            $file_path_for_db = "uploads/assignments/" . $file_name; // Relative path for DB
        } else {
            $message = "Error: Failed to move uploaded file. Check folder permissions.";
            goto end_process;
        }
    } else {
        $message = "Error: Assignment instruction file is required.";
        goto end_process;
    }
    // --- END FILE UPLOAD LOGIC ---


    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
        // NOTE: We now insert $file_path_for_db into the new 'file_path' column
        $sql = "INSERT INTO assignments (course_id, title, description, file_path, due_date, max_points) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        // issdsi = integer, string, string, string, string, integer
        $stmt->bind_param("issssi", $course_id, $title, $description, $file_path_for_db, $due_date_raw, $max_points); 
        
        if ($stmt->execute()) {
            $message = "Assignment '{$title}' created successfully!";
        } else {
            $message = "Error creating assignment: " . $conn->error;
        }
        $stmt->close();
        $conn->close();
        header("Location: instructor_assignments.php?course_id=" . urlencode($course_id) . "&status=success&message=" . urlencode($message));
        exit();

    } catch (Exception $e) {
        $message = "Database Error: " . $e->getMessage();
    }
    
    $selected_course_id = $course_id;
}
end_process:


// --- Fetch Instructor's Courses and Assignments (Rest of the file) ---
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
    if ($conn->connect_error) { throw new Exception("Connection failed: " . $conn->connect_error); }
    
    $sql = "SELECT id, course_code, title FROM courses WHERE instructor_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) { $courses[] = $row; }
    $stmt->close();

    if ($selected_course_id) {
        $sql = "SELECT a.id, a.title, a.description, a.due_date, a.max_points, 
                       (SELECT COUNT(s.id) FROM submissions s WHERE s.assignment_id = a.id) AS submission_count
                FROM assignments a 
                WHERE a.course_id = ? 
                ORDER BY a.due_date DESC";
        $stmt_assign = $conn->prepare($sql);
        $stmt_assign->bind_param("i", $selected_course_id);
        $stmt_assign->execute();
        $assignments_result = $stmt_assign->get_result();
        
        while ($row = $assignments_result->fetch_assoc()) { $assignments[] = $row; }
        $stmt_assign->close();
    }
    $conn->close();

} catch (Exception $e) { $message = "Database Error: " . $e->getMessage(); }

if (isset($_GET['message'])) { $message = $_GET['message']; }
$user_name = $_SESSION['user_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Assignments | SkillPath</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style> body { font-family: 'Inter', sans-serif; background-color: #f4f7f9; } </style>
</head>
<body class="min-h-screen flex flex-col items-center p-4">

    <div class="w-full max-w-6xl bg-white shadow-xl rounded-xl p-8 md:p-12 mt-8">
        <div class="flex justify-between items-center border-b pb-4 mb-6">
            <h1 class="text-3xl font-bold text-teal-700">
                Course Assessments (Assignments)
            </h1>
            <a href="instructor_dashboard.php" class="text-indigo-500 hover:text-indigo-700 font-semibold">
                &larr; Back to Dashboard
            </a>
        </div>
        
        <?php if ($message): ?>
            <div class="p-4 mb-4 rounded-lg <?php echo strpos($message, 'successfully') !== false ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="GET" action="instructor_assignments.php" class="mb-8 p-4 border border-gray-200 rounded-lg bg-gray-50">
            <label for="course_select" class="block text-lg font-medium text-gray-700 mb-2">Select Course to Manage Assessments</label>
            <div class="flex space-x-4">
                <select id="course_select" name="course_id" required 
                        class="flex-grow px-4 py-2 border border-gray-300 rounded-lg shadow-sm">
                    <option value="">-- Choose a Course --</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo htmlspecialchars($course['id']); ?>" 
                                <?php echo ($selected_course_id == $course['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white font-medium rounded-lg shadow-md hover:bg-indigo-700">
                    Load Assignments
                </button>
            </div>
        </form>

        <?php if ($selected_course_id): ?>
            
            <div class="grid grid-cols-3 gap-6 mb-8">
                <div class="col-span-2 p-6 border border-gray-200 rounded-lg">
                    </div>

                <div class="col-span-1 p-6 border-2 border-dashed border-teal-300 rounded-lg bg-teal-50">
                    <h2 class="text-xl font-bold text-teal-800 mb-4">Create New</h2>
                    <form method="POST" action="instructor_assignments.php" class="space-y-4" enctype="multipart/form-data">
                        <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($selected_course_id); ?>">
                        <input type="hidden" name="create_assignment" value="1">

                        <div>
                            <input type="text" name="title" placeholder="Assignment Title" required class="w-full px-3 py-2 border rounded-lg">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Due Date:</label>
                            <input type="date" name="due_date" required class="w-full px-3 py-2 border rounded-lg text-sm">
                        </div>
                        <div>
                            <input type="number" name="max_points" placeholder="Max Points (e.g., 100)" required min="1" value="100" class="w-full px-3 py-2 border rounded-lg">
                        </div>
                        
                        <div class="border border-gray-300 p-2 rounded-lg bg-white">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Instruction File (PDF/Doc)</label>
                            <input type="file" name="assignment_file" accept=".pdf,.doc,.docx" class="w-full text-xs py-1" required>
                        </div>
                        
                        <div>
                            <textarea name="description" placeholder="Assignment Details..." rows="3" class="w-full px-3 py-2 border rounded-lg text-sm"></textarea>
                        </div>
                        <button type="submit" class="w-full py-2 bg-teal-600 text-white font-medium rounded-lg hover:bg-teal-700">
                            Create Assignment
                        </button>
                    </form>
                </div>
            </div>

        <?php else: ?>
            <div class="p-6 text-center text-gray-500 border-dashed border-2 rounded-lg">
                <p class="text-lg mb-2">Please select a course above to manage assignments and quizzes.</p>
            </div>
        <?php endif; ?>

        <div class="mt-8 text-center text-gray-400 text-xs border-t pt-4">
            <p>Instructor Page - Role: <?php echo $_SESSION['user_role']; ?> | User ID: <?php echo $_SESSION['user_id']; ?></p>
        </div>
    </div>

</body>
</html>