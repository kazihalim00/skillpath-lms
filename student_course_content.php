<?php
/**
 * SkillPath Project: Student Course Content Page (Final Functional Content)
 *
 * This page dynamically fetches materials from the 'course_materials' table,
 * groups them by module, and displays them. It replaces all mock data.
 */

session_start();
require_once 'db_config.php';

// Security Check: Must be logged in and be a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: login.html?status=error&message=Access%20Denied.");
    exit();
}
$user_id = $_SESSION['user_id'];
$course_id = $_GET['course_id'] ?? null;
$course_info = null;
$message = null;

// This will store our materials grouped by module
// e.g., ["Module 1"] => [ [material1], [material2] ]
$grouped_materials = [];

/**
 * Helper function to determine the correct link target.
 * External links (YouTube, Drive) open in a new tab.
 * Local files open relative to the server.
 */
function getLinkProperties($url)
{
    if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
        return ['href' => $url, 'target' => '_blank']; // External link
    }
    // Local file
    return ['href' => $url, 'target' => '_blank'];
}


// --- Fetch Course Title, Check Enrollment, and Fetch Materials ---
if ($course_id) {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

        // Check if student is enrolled AND fetch course info
        $sql = "SELECT c.title, c.course_code FROM courses c JOIN enrollments e ON c.id = e.course_id WHERE c.id = ? AND e.student_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $course_id, $user_id);
        $stmt->execute();
        $course_info = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$course_info) {
            $message = "You are not enrolled in this course or the course does not exist.";
        } else {
            // NEW: Fetch REAL Uploaded Materials (FROM course_materials table)
            $materials_sql = "SELECT id, module_title, material_title, file_url, material_type 
                              FROM course_materials 
                              WHERE course_id = ? 
                              ORDER BY module_title ASC, uploaded_at ASC";
            $materials_stmt = $conn->prepare($materials_sql);
            $materials_stmt->bind_param("i", $course_id);
            $materials_stmt->execute();
            $result = $materials_stmt->get_result();

            // Group the materials by their module title
            while ($row = $result->fetch_assoc()) {
                $grouped_materials[$row['module_title']][] = $row;
            }
            $materials_stmt->close();
        }
        $conn->close();

    } catch (Exception $e) {
        $message = "Database Error: " . $e->getMessage();
    }
} else {
    $message = "No course selected.";
}

$user_name = $_SESSION['user_name'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course_info['title'] ?? 'Course'); ?> | Content</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f9;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col items-center p-4 bg-gray-100">

    <div class="w-full max-w-5xl bg-white shadow-2xl rounded-xl p-8 md:p-12 mt-8">
        <div class="flex justify-between items-center border-b pb-4 mb-6">
            <h1 class="text-3xl font-bold text-gray-800">
                <?php echo htmlspecialchars($course_info['title'] ?? 'Course Content'); ?>
                <span
                    class="text-xl text-gray-500 ml-2">(<?php echo htmlspecialchars($course_info['course_code'] ?? 'N/A'); ?>)</span>
            </h1>
            <a href="student_courses.php" class="text-indigo-500 hover:text-indigo-700 font-semibold">
                &larr; Back to Catalog
            </a>
        </div>

        <?php if ($message || !$course_info): ?>
            <div class="p-6 text-center bg-red-100 border border-red-300 text-red-700 rounded-lg">
                <?php echo htmlspecialchars($message ?? 'Error loading course.'); ?>
            </div>
        <?php else: ?>

            <!-- REMOVED 2-COLUMN GRID - NOW A SINGLE COLUMN LAYOUT -->
            <div class="w-full space-y-8">

                <!-- Section 1: Assessments -->
                <div>
                    <h2 class="text-2xl font-bold text-gray-700 mb-4">Assessments</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <a href="student_assignments.php?course_id=<?php echo htmlspecialchars($course_id); ?>"
                            class="py-3 px-4 w-full block text-white font-semibold rounded-lg text-center bg-green-500 hover:bg-green-600 transition">
                            View Assignments
                        </a>
                        <a href="student_quizzes.php?course_id=<?php echo htmlspecialchars($course_id); ?>"
                            class="py-3 px-4 w-full block text-white font-semibold rounded-lg text-center bg-yellow-600 hover:bg-yellow-700 transition">
                            View Quizzes
                        </a>
                    </div>
                </div>

                <!-- Section 2: Course Materials (Full Width) -->
                <div class="pt-6 border-t mt-6">
                    <h2 class="text-2xl font-bold text-gray-700 mb-4">Course Materials</h2>
                    <?php if (empty($grouped_materials)): ?>
                        <p class="text-gray-500 text-sm p-6 text-center border-dashed border-2 rounded-lg">Instructor has not
                            uploaded any materials yet.</p>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($grouped_materials as $module_title => $materials): ?>
                                <div class="bg-white border rounded-lg shadow-md overflow-hidden">
                                    <div class="bg-indigo-600 text-white p-4 text-xl font-bold">
                                        <?php echo htmlspecialchars($module_title); ?>
                                    </div>
                                    <ul class="divide-y divide-gray-200">
                                        <?php foreach ($materials as $material):
                                            $link_props = getLinkProperties($material['file_url']);
                                            ?>
                                            <li class="flex justify-between items-center p-4 hover:bg-gray-50">
                                                <!-- THIS IS THE FIX: Link now points directly to the file URL -->
                                                <a href="<?php echo htmlspecialchars($link_props['href']); ?>"
                                                    target="<?php echo htmlspecialchars($link_props['target']); ?>"
                                                    class="text-gray-700 hover:text-blue-700 w-full flex justify-between items-center">

                                                    <span>
                                                        <span
                                                            class="mr-2 text-sm text-gray-500 font-medium">[<?php echo ucfirst($material['material_type']); ?>]</span>
                                                        <?php echo htmlspecialchars($material['material_title']); ?>
                                                    </span>
                                                    <span class="text-xs text-indigo-500 font-medium">
                                                        <?php echo ($material['material_type'] == 'video' || $material['material_type'] == 'pdf') ? 'View' : 'Download'; ?>
                                                    </span>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="mt-8 text-center text-gray-400 text-xs border-t pt-4">
            <p>Student Content View | User ID: <?php echo $user_id; ?></p>
        </div>
    </div>
</body>

</html>