<?php
/**
 * SkillPath Project: Instructor Material Upload Page
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

// --- Process Material Upload ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_material'])) {
    $course_id = $_POST['course_id'];
    $module_title = trim($_POST['module_title']) ?: 'General';
    $material_title = trim($_POST['material_title']);
    $material_type = $_POST['material_type'];
    $file_url = trim(isset($_POST['file_url']) ? $_POST['file_url'] : '');
    $final_url = '';

    // 1. Handle Local File Upload
    if (isset($_FILES['local_file']) && $_FILES['local_file']['error'] == UPLOAD_ERR_OK) {
        $uploaded_file_name_raw = basename($_FILES['local_file']['name']);
        $safe_file_name = preg_replace("/[^a-zA-Z0-9._-]/", "_", $uploaded_file_name_raw);
        $unique_file_name = time() . "_" . $safe_file_name;

        // Ensure absolute path works across different OS (Mac/Windows)
        $target_dir_absolute = __DIR__ . '/uploads/materials/';
        $target_file = $target_dir_absolute . $unique_file_name;

        // Relative path for Database
        $final_url = "uploads/materials/{$unique_file_name}";

        if (!is_dir($target_dir_absolute)) {
            mkdir($target_dir_absolute, 0777, true);
        }

        if (!move_uploaded_file($_FILES['local_file']['tmp_name'], $target_file)) {
            $message = "Error: File move failed. Check 'uploads' folder permissions.";
            goto fetch_courses;
        }

    } elseif (!empty($file_url)) {
        // 2. Fallback to External Link
        $final_url = $file_url;
    }

    if (empty($material_title) || empty($final_url)) {
        $message = "Error: Title and a source (link or file) are required.";
        goto fetch_courses;
    }

    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
        $sql = "INSERT INTO course_materials (course_id, module_title, material_title, file_url, material_type) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issss", $course_id, $module_title, $material_title, $final_url, $material_type);

        if ($stmt->execute()) {
            $message = "Success! Material '{$material_title}' uploaded.";
        } else {
            $message = "Error uploading material: " . $conn->error;
        }
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        $message = "Database Error: " . $e->getMessage();
    }
}

fetch_courses:
// --- Fetch Instructor's Courses ---
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
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
}

// FIX: Use 'name' instead of 'user_name'
$user_name = $_SESSION['name'] ?? 'Instructor';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Upload Materials | SkillPath</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f9;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col items-center p-4">

    <div class="w-full max-w-4xl bg-white shadow-xl rounded-xl p-8 md:p-12 mt-8 border-t-4 border-purple-500">
        <div class="flex justify-between items-center border-b pb-4 mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Upload Course Materials</h1>
            <a href="instructor_dashboard.php" class="text-indigo-600 hover:underline font-semibold">&larr; Back to
                Dashboard</a>
        </div>

        <?php if ($message): ?>
            <div
                class="p-4 mb-4 rounded-lg <?php echo strpos($message, 'Success') !== false ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="instructor_material_upload.php" class="space-y-6" enctype="multipart/form-data">
            <input type="hidden" name="upload_material" value="1">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Select Course</label>
                    <select name="course_id" required class="w-full px-4 py-2 border rounded-lg focus:ring-purple-500">
                        <option value="">-- Choose a Course --</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['id']; ?>">
                                <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Module / Topic</label>
                    <input type="text" name="module_title" placeholder="e.g., Week 1: Intro" required
                        class="w-full px-4 py-2 border rounded-lg">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Material Title</label>
                    <input type="text" name="material_title" placeholder="e.g., Lecture Slides" required
                        class="w-full px-4 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Material Type</label>
                    <select name="material_type" required class="w-full px-4 py-2 border rounded-lg">
                        <option value="pdf">PDF / Slide Deck</option>
                        <option value="video">Video Lecture</option>
                        <option value="document">Document</option>
                    </select>
                </div>
            </div>

            <div class="border-t pt-6">
                <p class="text-lg font-bold text-gray-800 mb-4">Source (Choose ONE):</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="p-4 bg-gray-50 border rounded-lg">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Option 1: Upload File</label>
                        <input type="file" name="local_file" accept=".pdf,.doc,.docx,.ppt,.pptx,.jpg,.png,.mp4"
                            class="w-full text-sm">
                    </div>

                    <div class="p-4 bg-gray-50 border rounded-lg">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Option 2: External Link</label>
                        <input type="url" name="file_url" placeholder="https://drive.google.com/..."
                            class="w-full px-3 py-2 border rounded-lg bg-white">
                    </div>
                </div>
            </div>

            <button type="submit"
                class="w-full py-3 bg-purple-600 text-white font-bold rounded-lg hover:bg-purple-700 transition">
                Upload Material
            </button>
        </form>
    </div>
</body>

</html>