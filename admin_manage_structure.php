<?php
/**
 * SkillPath Project: Admin Academic Structure Management
 * Fixed: Styling updated to match modern dashboard design.
 */
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = "";

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

        // Add Department
        if (isset($_POST['add_dept'])) {
            $name = trim($_POST['dept_name']);
            $code = trim($_POST['dept_code']);

            $stmt = $conn->prepare("INSERT INTO departments (name, code) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $code);
            if ($stmt->execute())
                $message = "Department '$code' added successfully!";
            else
                $message = "Error: " . $conn->error;
            $stmt->close();
        }

        // Add Batch
        if (isset($_POST['add_batch'])) {
            $dept_id = $_POST['dept_id'];
            $batch_name = trim($_POST['batch_name']);
            $status = $_POST['status'];

            $stmt = $conn->prepare("INSERT INTO batches (department_id, batch_name, status) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $dept_id, $batch_name, $status);
            if ($stmt->execute())
                $message = "Batch '$batch_name' added successfully!";
            else
                $message = "Error: " . $conn->error;
            $stmt->close();
        }
        $conn->close();
    } catch (Exception $e) {
        $message = "Database Error: " . $e->getMessage();
    }
}

// Fetch Departments
$departments = [];
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
    $departments = $conn->query("SELECT * FROM departments ORDER BY code ASC")->fetch_all(MYSQLI_ASSOC);
    $conn->close();
} catch (Exception $e) {
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Structure | SkillPath</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f9;
        }
    </style>
</head>

<body class="p-10 flex justify-center">

    <div class="w-full max-w-5xl bg-white shadow-xl rounded-xl p-8 border-t-4 border-blue-600">
        <div class="flex justify-between items-center mb-8 border-b pb-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Academic Structure</h1>
                <p class="text-gray-500 text-sm mt-1">Manage university departments and student batches.</p>
            </div>
            <a href="admin_dashboard.php" class="text-indigo-600 hover:text-indigo-800 font-semibold transition">
                &larr; Back to Dashboard
            </a>
        </div>

        <?php if ($message): ?>
            <div
                class="bg-green-50 border border-green-200 text-green-700 p-4 rounded-lg mb-8 text-center font-medium shadow-sm">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

            <div class="bg-blue-50 p-8 rounded-xl border border-blue-100 shadow-sm hover:shadow-md transition">
                <div class="flex items-center gap-3 mb-6">
                    <div class="bg-blue-100 p-2 rounded-lg text-blue-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                            </path>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-gray-800">Add Department</h2>
                </div>

                <form method="POST" action="" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Department Name</label>
                        <input type="text" name="dept_name" placeholder="e.g. Computer Science"
                            class="w-full border border-gray-300 px-4 py-2 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                            required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Dept Code</label>
                        <input type="text" name="dept_code" placeholder="e.g. CSE"
                            class="w-full border border-gray-300 px-4 py-2 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition uppercase"
                            required>
                    </div>
                    <button type="submit" name="add_dept"
                        class="w-full bg-blue-600 text-white px-4 py-2.5 rounded-lg font-bold hover:bg-blue-700 shadow-md transition">
                        Create Department
                    </button>
                </form>
            </div>

            <div class="bg-indigo-50 p-8 rounded-xl border border-indigo-100 shadow-sm hover:shadow-md transition">
                <div class="flex items-center gap-3 mb-6">
                    <div class="bg-indigo-100 p-2 rounded-lg text-indigo-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                            </path>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-gray-800">Add Batch</h2>
                </div>

                <form method="POST" action="" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Department</label>
                        <select name="dept_id"
                            class="w-full border border-gray-300 px-4 py-2 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none bg-white"
                            required>
                            <option value="">-- Select --</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['id'] ?>">
                                    <?= htmlspecialchars($dept['code']) . ' - ' . htmlspecialchars($dept['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Batch Name</label>
                        <input type="text" name="batch_name" placeholder="e.g. 4th Batch"
                            class="w-full border border-gray-300 px-4 py-2 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition"
                            required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
                        <select name="status"
                            class="w-full border border-gray-300 px-4 py-2 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                            <option value="active">Active</option>
                            <option value="graduated">Graduated</option>
                        </select>
                    </div>
                    <button type="submit" name="add_batch"
                        class="w-full bg-indigo-600 text-white px-4 py-2.5 rounded-lg font-bold hover:bg-indigo-700 shadow-md transition">
                        Create Batch
                    </button>
                </form>
            </div>

        </div>
    </div>
</body>

</html>