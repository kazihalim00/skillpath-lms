<?php
/**
 * SkillPath Project: Instructor Assignments Page
 * Fixed: Session variable names matching index.php
 */

session_start();
require_once 'db_config.php';

// FIX: Use 'role' instead of 'user_role'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: login.php?status=error&message=Access%20Denied.");
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
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $due_date_raw = $_POST['due_date'];
    $max_points = (int) ($_POST['max_points'] ?? 100);
    $file_path_for_db = null;

    if (empty($title) || empty($due_date_raw)) {
        $message = "Title and Due Date are required.";
        goto end_process;
    }

    // File Upload
    if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/uploads/assignments/';
        $file_name = uniqid() . '_' . basename($_FILES['assignment_file']['name']);

        if (!is_dir($upload_dir))
            mkdir($upload_dir, 0777, true);

        if (move_uploaded_file($_FILES['assignment_file']['tmp_name'], $upload_dir . $file_name)) {
            $file_path_for_db = "uploads/assignments/" . $file_name;
        } else {
            $message = "Error: Failed to move uploaded file.";
            goto end_process;
        }
    }

    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
        $sql = "INSERT INTO assignments (course_id, title, description, file_path, due_date, max_points) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issssi", $course_id, $title, $description, $file_path_for_db, $due_date_raw, $max_points);

        if ($stmt->execute()) {
            $message = "Assignment '{$title}' created successfully!";
        } else {
            $message = "Error: " . $conn->error;
        }
        $stmt->close();
        $conn->close();

        // Refresh page to show new assignment
        header("Location: instructor_assignments.php?course_id={$course_id}&message=" . urlencode($message));
        exit();

    } catch (Exception $e) {
        $message = "Database Error: " . $e->getMessage();
    }

    $selected_course_id = $course_id;
}
end_process:

// --- Fetch Data ---
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

    // Get Courses
    $sql = "SELECT id, course_code, title FROM courses WHERE instructor_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
    $stmt->close();

    // Get Assignments for selected course
    if ($selected_course_id) {
        $sql = "SELECT * FROM assignments WHERE course_id = ? ORDER BY due_date DESC";
        $stmt_assign = $conn->prepare($sql);
        $stmt_assign->bind_param("i", $selected_course_id);
        $stmt_assign->execute();
        $res = $stmt_assign->get_result();
        while ($row = $res->fetch_assoc()) {
            $assignments[] = $row;
        }
        $stmt_assign->close();
    }
    $conn->close();
} catch (Exception $e) {
}

if (isset($_GET['message'])) {
    $message = $_GET['message'];
}

// FIX: Use 'name' instead of 'user_name'
$user_name = $_SESSION['name'] ?? 'Instructor';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Manage Assignments | SkillPath</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f9;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col items-center p-4">

    <div class="w-full max-w-6xl bg-white shadow-xl rounded-xl p-8 md:p-12 mt-8 border-t-4 border-blue-500">
        <div class="flex justify-between items-center border-b pb-4 mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Manage Assignments</h1>
            <a href="instructor_dashboard.php" class="text-indigo-600 hover:underline font-semibold">&larr; Back to
                Dashboard</a>
        </div>

        <?php if ($message): ?>
            <div class="p-4 mb-4 rounded-lg bg-blue-50 text-blue-700 border border-blue-200">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="GET" action="instructor_assignments.php"
            class="mb-8 p-4 bg-gray-50 border rounded-lg flex gap-4 items-end">
            <div class="flex-grow">
                <label class="block text-sm font-bold text-gray-700 mb-1">Select Course</label>
                <select name="course_id" class="w-full px-4 py-2 border rounded-lg">
                    <option value="">-- Choose Course --</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($selected_course_id == $c['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['course_code'] . ' - ' . $c['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit"
                class="px-6 py-2 bg-indigo-600 text-white font-bold rounded-lg hover:bg-indigo-700">Load</button>
        </form>

        <?php if ($selected_course_id): ?>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <div class="lg:col-span-1 bg-blue-50 p-6 rounded-lg border border-blue-100">
                    <h2 class="text-xl font-bold text-blue-800 mb-4">Create New</h2>
                    <form method="POST" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" name="create_assignment" value="1">
                        <input type="hidden" name="course_id" value="<?= $selected_course_id ?>">

                        <input type="text" name="title" placeholder="Title" required
                            class="w-full px-3 py-2 border rounded">
                        <input type="date" name="due_date" required class="w-full px-3 py-2 border rounded">
                        <input type="number" name="max_points" value="100" class="w-full px-3 py-2 border rounded">

                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">Instruction File (Optional)</label>
                            <input type="file" name="assignment_file" class="w-full text-xs">
                        </div>

                        <textarea name="description" placeholder="Instructions..." rows="3"
                            class="w-full px-3 py-2 border rounded"></textarea>

                        <button
                            class="w-full bg-blue-600 text-white py-2 rounded font-bold hover:bg-blue-700">Create</button>
                    </form>
                </div>

                <div class="lg:col-span-2">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Existing Assignments</h2>
                    <?php if (empty($assignments)): ?>
                        <p class="text-gray-500 italic">No assignments created yet.</p>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($assignments as $a): ?>
                                <div class="p-4 border rounded-lg hover:shadow-md transition bg-white">
                                    <h3 class="font-bold text-lg"><?= htmlspecialchars($a['title']) ?></h3>
                                    <p class="text-sm text-gray-500">Due: <?= htmlspecialchars($a['due_date']) ?> | Points:
                                        <?= $a['max_points'] ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>