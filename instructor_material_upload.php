<?php
/**
 * SkillPath Project: Instructor Material Upload Page (Functional)
 *
 * This page allows instructors to upload materials (links or files)
 * and associate them with a specific course and module.
 */

session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'instructor') {
    header("Location: login.html?status=error&message=Access%20Denied.");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = null;
$courses = [];

// --- Process Material Upload ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_material'])) {
    $course_id = $_POST['course_id'];
    $module_title = trim($_POST['module_title']) ?: 'General'; // NEW: Get module title, default to 'General'
    $material_title = trim($_POST['material_title']);
    $material_type = $_POST['material_type'];
    $file_url = trim(isset($_POST['file_url']) ? $_POST['file_url'] : '');
    $uploaded_file_name = null;
    $final_url = '';

    // 1. Handle Local File Upload (If file was selected)
    if (isset($_FILES['local_file']) && $_FILES['local_file']['error'] == UPLOAD_ERR_OK) {
        $uploaded_file_name_raw = basename($_FILES['local_file']['name']);
        // Sanitize the file name to be web-safe (replaces spaces, etc.)
        $safe_file_name = preg_replace("/[^a-zA-Z0-9._-]/", "_", $uploaded_file_name_raw);
        $unique_file_name = time() . "_" . $safe_file_name;


        $target_dir_absolute = __DIR__ . '/uploads/materials/';
        $target_file = $target_dir_absolute . $unique_file_name;

        // Use a relative path for the database, which is more portable
        // This path must match the folder structure
        $final_url = "uploads/materials/{$unique_file_name}";

        if (!is_dir($target_dir_absolute)) {
            if (!mkdir($target_dir_absolute, 0777, true)) {
                $message = "Error: Failed to create upload directory. Check server permissions.";
                goto fetch_courses;
            }
        }
        if (!move_uploaded_file($_FILES['local_file']['tmp_name'], $target_file)) {
            $message = "Error: File move failed. Please check MAMP permissions for the 'uploads' folder.";
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
        // NEW: Insert module_title into the database
        $sql = "INSERT INTO course_materials (course_id, module_title, material_title, file_url, material_type) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issss", $course_id, $module_title, $material_title, $final_url, $material_type);

        if ($stmt->execute()) {
            $message = "Material '{$material_title}' uploaded successfully to module '{$module_title}'!";
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

// --- Fetch Instructor's Courses (for the dropdown) ---
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
    /* silent fail for dropdown */
}

$user_name = $_SESSION['user_name'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Materials | SkillPath</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f9;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col items-center p-4 bg-gray-100">

    <div class="w-full max-w-4xl bg-white shadow-2xl rounded-xl p-8 md:p-12 mt-8">
        <div class="flex justify-between items-center border-b pb-4 mb-6">
            <h1 class="text-3xl font-bold text-gray-800">
                Upload Course Materials (Files & Links)
            </h1>
            <a href="instructor_dashboard.php" class="text-indigo-500 hover:text-indigo-700 font-semibold">
                &larr; Back to Dashboard
            </a>
        </div>

        <?php if ($message): ?>
            <div
                class="p-4 mb-4 rounded-lg <?php echo strpos($message, 'success') !== false ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <h2 class="text-xl font-semibold mb-4 text-gray-700">Add New Material</h2>
        <form method="POST" action="instructor_material_upload.php" class="space-y-4 border p-6 rounded-lg bg-yellow-50"
            enctype="multipart/form-data">
            <input type="hidden" name="upload_material" value="1">

            <div>
                <label class="block text-sm font-medium text-gray-700">Select Course</label>
                <select name="course_id" required class="w-full px-3 py-2 border rounded-lg">
                    <option value="">-- Choose a Course --</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo htmlspecialchars($course['id']); ?>">
                            <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Module / Topic Title</label>
                <input type="text" name="module_title" placeholder="e.g., Module 1: Introduction or Week 5 Lecture"
                    required class="w-full px-3 py-2 border rounded-lg">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Material Title</label>
                    <input type="text" name="material_title" placeholder="e.g., SDLC Video or Chapter 1 Notes" required
                        class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Material Type</label>
                    <select name="material_type" required class="w-full px-3 py-2 border rounded-lg">
                        <option value="video">Video Lecture (YouTube/Drive)</option>
                        <option value="pdf">PDF / Slide Deck</option>
                        <option value="document">Document / Link</option>
                        <option value="image">Image / Diagram</option>
                    </select>
                </div>
            </div>

            <div class="border-t pt-4 space-y-4">
                <p class="text-lg font-bold text-gray-800">Source (Choose ONE):</p>

                <div class="p-3 bg-white border rounded-lg">
                    <label class="block text-sm font-medium text-gray-700 mb-1">1. Upload from Device (.pdf, .doc,
                        .mp4)</label>
                    <input type="file" name="local_file" accept=".pdf,.doc,.docx,.jpg,.png,.mp4"
                        class="w-full text-sm py-1">
                </div>

                <p class="text-center text-gray-500 font-semibold">-- OR --</p>

                <div class="p-3 bg-white border rounded-lg">
                    <label class="block text-sm font-medium text-gray-700 mb-1">2. Paste Drive or YouTube Link (for
                        Videos)</label>
                    <input type="url" name="file_url" placeholder="https://drive.google.com/ or https://youtu.be/..."
                        class="w-full px-3 py-2 border rounded-lg">
                    <p class="text-xs text-red-600 mt-1">NOTE: If you use Option 1, leave this field blank.</p>
                </div>
            </div>

            <button type="submit" class="w-full py-2 bg-teal-600 text-white font-medium rounded-lg hover:bg-teal-700">
                Save Material
            </button>
        </form>
    </div>
</body>

</html>