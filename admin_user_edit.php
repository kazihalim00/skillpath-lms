<?php
/**
 * SkillPath - Admin User Edit
 * FIX: Links Instructors to Departments (like SWE).
 */
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$id = $_GET['id'] ?? null;
$user_data = null;
$message = "";
$departments = [];

$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

// 1. Fetch Departments (This will now find SWE because of Step 1)
$dept_res = $conn->query("SELECT * FROM departments ORDER BY code ASC");
if ($dept_res)
    $departments = $dept_res->fetch_all(MYSQLI_ASSOC);

// 2. Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['user_id'];
    $name = $_POST['full_name'];
    $role = $_POST['role'];
    $dept_id = !empty($_POST['department_id']) ? $_POST['department_id'] : NULL;

    $stmt = $conn->prepare("UPDATE users SET full_name=?, role=?, department_id=? WHERE id=?");
    $stmt->bind_param("ssii", $name, $role, $dept_id, $id);

    if ($stmt->execute()) {
        $message = "Success! User updated.";
        // Refresh data immediately
        $user_data['department_id'] = $dept_id;
        $user_data['full_name'] = $name;
        $user_data['role'] = $role;
    } else {
        $message = "Error: " . $conn->error;
    }
    $stmt->close();
}

// 3. Fetch User Data
if ($id) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $user_data = $stmt->get_result()->fetch_assoc();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Edit User</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 p-10 flex justify-center">
    <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-lg border-t-4 border-orange-500">
        <h1 class="text-2xl font-bold mb-6 text-gray-800">Edit User</h1>

        <?php if ($message): ?>
            <div class="bg-green-100 text-green-700 p-3 rounded mb-4 font-bold text-center"><?= $message ?></div>
        <?php endif; ?>

        <?php if ($user_data): ?>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="user_id" value="<?= $user_data['id'] ?>">

                <div>
                    <label class="block font-bold text-gray-700">Name</label>
                    <input type="text" name="full_name" value="<?= htmlspecialchars($user_data['full_name']) ?>"
                        class="w-full border p-2 rounded">
                </div>

                <div>
                    <label class="block font-bold text-gray-700">Email (Read-only)</label>
                    <input type="text" value="<?= htmlspecialchars($user_data['email']) ?>" readonly
                        class="w-full border p-2 rounded bg-gray-100 text-gray-500">
                </div>

                <div>
                    <label class="block font-bold text-gray-700">Role</label>
                    <select name="role" class="w-full border p-2 rounded">
                        <option value="student" <?= $user_data['role'] == 'student' ? 'selected' : '' ?>>Student</option>
                        <option value="instructor" <?= $user_data['role'] == 'instructor' ? 'selected' : '' ?>>Instructor
                        </option>
                        <option value="admin" <?= $user_data['role'] == 'admin' ? 'selected' : '' ?>>Administrator</option>
                    </select>
                </div>

                <div class="bg-blue-50 p-4 rounded border border-blue-200">
                    <label class="block font-bold text-blue-800 mb-2">Department</label>
                    <select name="department_id" class="w-full border p-2 rounded">
                        <option value="">-- None Assigned --</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept['id'] ?>" <?= $user_data['department_id'] == $dept['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept['code']) ?> - <?= htmlspecialchars($dept['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-xs text-gray-600 mt-2">
                        * Assigning "SWE" here will automatically link all this teacher's courses to SWE.
                    </p>
                </div>

                <div class="flex justify-between pt-4">
                    <button class="bg-orange-600 text-white px-6 py-2 rounded font-bold hover:bg-orange-700">Update
                        User</button>
                    <a href="admin_manage_users.php"
                        class="bg-gray-300 text-gray-700 px-6 py-2 rounded hover:bg-gray-400">Cancel</a>
                </div>
            </form>
        <?php else: ?>
            <p class="text-red-500">User not found.</p>
        <?php endif; ?>
    </div>
</body>

</html>