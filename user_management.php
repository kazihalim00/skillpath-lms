<?php
/**
 * SkillPath Project: User Management Page (Functional)
 *
 * This page pulls all user data from the 'users' table and displays it.
 */

// Start a session and include config
session_start();
require_once 'db_config.php';

// Security Check: Must be logged in as Admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    session_unset();
    session_destroy();
    header("Location: login.html?status=error&message=Access%20Denied.%20Please%20log%20in%20as%20an%20Admin.");
    exit();
}

$user_name = $_SESSION['user_name'];
$users = []; // Array to hold user data
$error_message = null;
$admin_user_id = $_SESSION['user_id']; // For protecting the logged-in user

// Database logic to fetch all users
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    $sql = "SELECT id, full_name, email, role, created_at FROM users ORDER BY created_at DESC";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }

    $conn->close();

} catch (Exception $e) {
    $error_message = "Database Error: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | SkillPath</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f9;
        }

        .role-admin {
            background-color: #fee2e2;
            color: #991b1b;
        }

        /* Red */
        .role-instructor {
            background-color: #fef9c3;
            color: #854d0e;
        }

        /* Yellow */
        .role-student {
            background-color: #dbeafe;
            color: #1e40af;
        }

        /* Blue */
    </style>
</head>

<body class="min-h-screen flex flex-col items-center p-4">

    <div class="w-full max-w-5xl bg-white shadow-2xl rounded-xl p-8 md:p-12 mt-8">
        <div class="flex justify-between items-center border-b pb-4 mb-6">
            <h1 class="text-3xl font-bold text-gray-800">
                User Management (Total Users: <?php echo count($users); ?>)
            </h1>
            <a href="admin_dashboard.php" class="text-indigo-500 hover:text-indigo-700 font-semibold">
                &larr; Back to Dashboard
            </a>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID
                        </th>
                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name
                        </th>
                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email
                        </th>
                        <th class="py-3 px-6 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Role</th>
                        <th class="py-3 px-6 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Joined</th>
                        <th class="py-3 px-6 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">No users found in the database.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($user['id']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($user['full_name']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($user['email']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo 'role-' . strtolower($user['role']); ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                    <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <a href="admin_user_edit.php?id=<?php echo htmlspecialchars($user['id']); ?>"
                                        class="text-indigo-600 hover:text-indigo-900 mx-1">Edit</a>

                                    <?php if ($user['id'] != $admin_user_id): ?>
                                        <span class="text-gray-300">|</span>
                                        <a href="#"
                                            onclick="return confirm('WARNING: Deleting user <?php echo htmlspecialchars($user['email']); ?> will also delete their courses and enrollments. Proceed?')"
                                            class="text-red-600 hover:text-red-900 mx-1">Delete</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-8 text-center text-gray-400 text-xs border-t pt-4">
            <p>Logged in as: <?php echo $_SESSION['user_name']; ?> | Admin Panel</p>
        </div>
    </div>
</body>

</html>