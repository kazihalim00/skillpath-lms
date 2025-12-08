<?php
/**
 * SkillPath Project: Course Oversight
 * Fixed: Now displays the Department Name (via Instructor's Dept).
 */
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$user_name = $_SESSION['name'] ?? 'Admin';
$courses = [];
$message = $_GET['message'] ?? null;
$msg_type = $_GET['status'] ?? 'info';

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

    // FETCH COURSES + INSTRUCTOR + DEPARTMENT
    // We link Course -> Instructor -> Department
    $sql = "SELECT c.id, c.course_code, c.title, c.status, c.created_at, 
                   u.full_name as instructor_name,
                   d.code as dept_code
            FROM courses c
            LEFT JOIN users u ON c.instructor_id = u.id 
            LEFT JOIN departments d ON u.department_id = d.id
            ORDER BY c.created_at DESC";

    $result = $conn->query($sql);

    if ($result) {
        $courses = $result->fetch_all(MYSQLI_ASSOC);
    }
    $conn->close();
} catch (Exception $e) {
    $message = "Database Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Course Oversight | SkillPath</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f9;
        }

        .status-published {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-draft {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-archived {
            background-color: #e5e7eb;
            color: #374151;
        }
    </style>
</head>

<body class="p-8 flex justify-center">

    <div class="w-full max-w-7xl bg-white shadow-xl rounded-xl p-8 border-t-4 border-teal-600">
        <div class="flex justify-between items-center border-b pb-4 mb-6">
            <h1 class="text-3xl font-bold text-gray-800">
                Course Oversight (<?= count($courses) ?>)
            </h1>
            <a href="admin_dashboard.php" class="text-indigo-600 hover:text-indigo-800 font-semibold transition">
                &larr; Back to Dashboard
            </a>
        </div>

        <?php if ($message): ?>
            <div
                class="p-4 mb-4 text-sm rounded-lg <?php echo $msg_type == 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="overflow-x-auto rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase font-bold">
                    <tr>
                        <th class="py-3 px-4 text-left">Code</th>
                        <th class="py-3 px-4 text-left">Title</th>
                        <th class="py-3 px-4 text-left">Dept</th>
                        <th class="py-3 px-4 text-left">Instructor</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-center">Created</th>
                        <th class="py-3 px-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($courses)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500 italic">No courses found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($courses as $course): ?>
                            <tr class="hover:bg-gray-50 transition duration-150">
                                <td class="px-4 py-4 font-mono font-bold text-indigo-600">
                                    <?= htmlspecialchars($course['course_code']) ?>
                                </td>
                                <td class="px-4 py-4 font-medium text-gray-900">
                                    <?= htmlspecialchars($course['title']) ?>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded text-xs font-bold uppercase">
                                        <?= htmlspecialchars($course['dept_code'] ?? 'N/A') ?>
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-gray-600">
                                    <?= htmlspecialchars($course['instructor_name'] ?? 'Unknown') ?>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span
                                        class="px-2 py-1 text-xs font-bold rounded-full <?= 'status-' . strtolower($course['status']) ?>">
                                        <?= ucfirst($course['status']) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-center text-gray-500">
                                    <?= date('M d, Y', strtotime($course['created_at'])) ?>
                                </td>
                                <td class="px-4 py-4 text-center font-medium">
                                    <a href="admin_course_edit.php?id=<?= $course['id'] ?>"
                                        class="text-indigo-600 hover:text-indigo-900 mx-2 hover:underline">Edit</a>
                                    <span class="text-gray-300">|</span>
                                    <a href="admin_course_delete.php?id=<?= $course['id'] ?>"
                                        class="text-red-600 hover:text-red-900 mx-2 hover:underline"
                                        onclick="return confirm('Are you sure? This cannot be undone.');">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>