<?php
/**
 * SkillPath Project: Admin Dashboard
 * * This is the secure landing page for Admin users.
 */

session_start();

// Check if the user is NOT logged in or if their role is NOT 'admin'
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    // If they fail the check, destroy the session and send them back to the login page
    session_unset();
    session_destroy();
    header("Location: login.html?status=error&message=Access%20Denied.%20Please%20log%20in%20as%20an%20Admin.");
    exit();
}

// User is successfully authenticated as an Admin.
$user_name = $_SESSION['user_name'];

// Include the configuration file to access DB_NAME for the debug info
require_once 'db_config.php';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | SkillPath</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f9;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col items-center p-4">

    <div class="w-full max-w-4xl flex justify-end mb-4">
        <a href="logout.php"
            class="py-2 px-4 bg-red-500 text-white font-semibold rounded-lg shadow-md hover:bg-red-600 transition duration-300">
            Logout
        </a>
    </div>

    <div class="w-full max-w-4xl bg-white shadow-2xl rounded-xl p-8 md:p-12">
        <h1 class="text-4xl font-extrabold text-indigo-700 mb-2">
            Welcome, <?php echo htmlspecialchars($user_name); ?>
        </h1>
        <p class="text-lg text-gray-500 mb-8 border-b pb-4">
            You are logged in as an **Administrator**.
        </p>

        <h2 class="text-2xl font-bold text-gray-700 mb-6">Admin Tools & Oversight</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <div
                class="bg-gray-50 p-6 rounded-xl shadow-lg border-t-4 border-blue-500 hover:shadow-xl transition duration-300">
                <h3 class="text-xl font-semibold text-gray-800 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20v-2a3 3 0 015.356-1.857M7 20h4m-4 0v-2c0-.656-.126-1.283-.356-1.857M17 20h4m-4 0v-2c0-.656-.126-1.283-.356-1.857">
                        </path>
                    </svg>
                    User Management
                </h3>
                <p class="text-gray-600 mt-2 text-sm">Oversee all student, instructor, and admin accounts.</p>
                <a href="user_management.php"
                    class="text-blue-500 hover:text-blue-700 mt-3 block text-sm font-medium">View Users →</a>
            </div>

            <div
                class="bg-gray-50 p-6 rounded-xl shadow-lg border-t-4 border-red-500 hover:shadow-xl transition duration-300">
                <h3 class="text-xl font-semibold text-gray-800 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6.253v13m0-13C10.832 5.477 9.203 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.8 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.8 5 16.5 5c1.691 0 3.332.477 4.5 1.253v13C19.832 18.477 18.2 18 16.5 18s-3.332.477-4.5 1.253">
                        </path>
                    </svg>
                    Course Oversight
                </h3>
                <p class="text-gray-600 mt-2 text-sm">Approve, manage, and audit all course content.</p>
                <a href="course_oversight.php"
                    class="text-red-500 hover:text-red-700 mt-3 block text-sm font-medium">Manage Courses →</a>
            </div>

            <div
                class="bg-gray-50 p-6 rounded-xl shadow-lg border-t-4 border-green-500 hover:shadow-xl transition duration-300">
                <h3 class="text-xl font-semibold text-gray-800 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6m4 0a2 2 0 002 2h2a2 2 0 002-2m-8 0h4m-4 0v-1m8 1v-1m8 1v-1m0-9a2 2 0 00-2-2h-4a2 2 0 00-2 2v1m-4 0h4m-4 0v-1m8 1v-1m0 0a2 2 0 00-2-2h-4a2 2 0 00-2 2v1m-4 0h4m-4 0v-1">
                        </path>
                    </svg>
                    System Reports
                </h3>
                <p class="text-gray-600 mt-2 text-sm">Access system analytics, performance logs, and reports.</p>
                <a href="system_reports.php"
                    class="text-green-500 hover:text-green-700 mt-3 block text-sm font-medium">View Analytics →</a>
            </div>

        </div>

        <div class="mt-8 text-center text-gray-400 text-xs border-t pt-4">
            <p>Debug Info: Database: <?php echo DB_NAME; ?> | Role: <?php echo $_SESSION['user_role']; ?> | User ID:
                <?php echo $_SESSION['user_id']; ?></p>
        </div>
    </div>
</body>

</html>