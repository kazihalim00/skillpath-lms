<?php
/**
 * SkillPath - Admin View Instructors by Department
 * Filter instructors based on their assigned department.
 */
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$departments = [];
$instructors = [];
$selected_dept = $_GET['dept_id'] ?? '';

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

    // 1. Fetch Departments for Dropdown
    $dept_res = $conn->query("SELECT * FROM departments ORDER BY code ASC");
    if ($dept_res)
        $departments = $dept_res->fetch_all(MYSQLI_ASSOC);

    // 2. Fetch Instructors
    // If a department is selected, filter by it. Otherwise get all.
    $sql = "SELECT u.id, u.full_name, u.email, d.code as dept_code, d.name as dept_name 
            FROM users u 
            LEFT JOIN departments d ON u.department_id = d.id 
            WHERE u.role = 'instructor'";

    if (!empty($selected_dept)) {
        $sql .= " AND u.department_id = " . intval($selected_dept);
    }

    $sql .= " ORDER BY u.full_name ASC";

    $result = $conn->query($sql);
    if ($result)
        $instructors = $result->fetch_all(MYSQLI_ASSOC);

    $conn->close();
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Instructors by Dept | SkillPath</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f9;
        }
    </style>
</head>

<body class="p-8 flex justify-center">

    <div class="w-full max-w-5xl bg-white shadow-xl rounded-xl p-8 border-t-4 border-purple-600">

        <div class="flex justify-between items-center mb-8 border-b pb-4">
            <h1 class="text-3xl font-bold text-gray-800">Instructor Directory</h1>
            <a href="admin_dashboard.php" class="text-indigo-600 hover:underline font-semibold">&larr; Back to
                Dashboard</a>
        </div>

        <form method="GET" class="mb-8 bg-purple-50 p-6 rounded-lg border border-purple-100 flex items-center gap-4">
            <div class="flex-grow">
                <label class="block text-sm font-bold text-gray-700 mb-1">Filter by Department</label>
                <select name="dept_id"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 bg-white"
                    onchange="this.form.submit()">
                    <option value="">-- Show All Departments --</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= $dept['id'] ?>" <?= $selected_dept == $dept['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dept['code'] . ' - ' . $dept['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mt-6">
                <a href="admin_view_instructors.php" class="text-sm text-gray-500 hover:text-gray-700 underline">Reset
                    Filter</a>
            </div>
        </form>

        <h2 class="text-xl font-bold text-gray-800 mb-4">
            <?= empty($selected_dept) ? "All Instructors" : "Instructors in Selected Dept" ?>
            <span class="text-gray-400 text-sm font-normal">(<?= count($instructors) ?> found)</span>
        </h2>

        <?php if (empty($instructors)): ?>
            <div class="text-center p-10 bg-gray-50 border-2 border-dashed rounded-lg text-gray-500">
                No instructors found matching this criteria.
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-100 text-gray-600 uppercase text-xs font-bold">
                            <th class="p-4 border-b">Name</th>
                            <th class="p-4 border-b">Email</th>
                            <th class="p-4 border-b text-center">Department</th>
                            <th class="p-4 border-b text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($instructors as $inst): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="p-4 font-bold text-gray-800"><?= htmlspecialchars($inst['full_name']) ?></td>
                                <td class="p-4 text-gray-600"><?= htmlspecialchars($inst['email']) ?></td>
                                <td class="p-4 text-center">
                                    <?php if (!empty($inst['dept_code'])): ?>
                                        <span class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-bold">
                                            <?= htmlspecialchars($inst['dept_code']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-sm italic">Not Assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="text-green-600 text-sm font-medium">Active</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>

</body>

</html>