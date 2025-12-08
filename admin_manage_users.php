<?php
/**
 * SkillPath - Admin User Management List
 * Fixed: Links now correctly point to User Edit/Delete files.
 */
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$users = [];
$message = $_GET['message'] ?? null;
$msg_type = $_GET['status'] ?? 'info';

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
    $result = $conn->query("SELECT id, full_name, email, role, created_at FROM users ORDER BY created_at DESC");
    if ($result) {
        $users = $result->fetch_all(MYSQLI_ASSOC);
    }
    $conn->close();
} catch (Exception $e) {
    $message = "Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Manage Users | SkillPath</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f9;
        }
    </style>
</head>

<body class="p-8 flex justify-center">

    <div class="w-full max-w-6xl bg-white shadow-xl rounded-xl p-8 border-t-4 border-orange-500">
        <div class="flex justify-between items-center mb-6 border-b pb-4">
            <h1 class="text-3xl font-bold text-gray-800">Manage Users (<?= count($users) ?>)</h1>
            <a href="admin_dashboard.php" class="text-indigo-600 hover:underline font-semibold">&larr; Back to
                Dashboard</a>
        </div>

        <?php if ($message): ?>
            <div
                class="p-4 mb-4 rounded <?php echo $msg_type == 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 uppercase text-sm font-bold">
                        <th class="p-4 border-b">ID</th>
                        <th class="p-4 border-b">Name</th>
                        <th class="p-4 border-b">Email</th>
                        <th class="p-4 border-b">Role</th>
                        <th class="p-4 border-b text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr class="hover:bg-gray-50 border-b transition">
                            <td class="p-4 text-gray-500"><?= $u['id'] ?></td>
                            <td class="p-4 font-bold text-gray-800"><?= htmlspecialchars($u['full_name']) ?></td>
                            <td class="p-4 text-gray-600"><?= htmlspecialchars($u['email']) ?></td>
                            <td class="p-4">
                                <span
                                    class="px-2 py-1 rounded text-xs font-bold uppercase 
                                <?php echo $u['role'] === 'admin' ? 'bg-purple-100 text-purple-700' : ($u['role'] === 'instructor' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700'); ?>">
                                    <?= $u['role'] ?>
                                </span>
                            </td>
                            <td class="p-4 text-center">
                                <a href="admin_user_edit.php?id=<?= $u['id'] ?>"
                                    class="text-indigo-600 hover:underline font-medium mr-3">Edit</a>

                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                    <a href="admin_user_delete.php?id=<?= $u['id'] ?>"
                                        class="text-red-600 hover:underline font-medium"
                                        onclick="return confirm('Delete this user? This cannot be undone.');">Delete</a>
                                <?php else: ?>
                                    <span class="text-gray-300 cursor-not-allowed">Delete</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>