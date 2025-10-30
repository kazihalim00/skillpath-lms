<?php
/**
 * SkillPath Project: Course Oversight Page (Placeholder)
 */

session_start();
require_once 'db_config.php';
// Security Check: Must be logged in as Admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    session_unset();
    session_destroy();
    header("Location: login.html?status=error");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<body class="min-h-screen flex flex-col items-center p-4">
    <div class="w-full max-w-6xl bg-white shadow-xl rounded-xl p-8 md:p-12 mt-8">
        <h1 class="text-3xl font-bold text-gray-800">Course Oversight (Placeholder)</h1>
        <a href="admin_dashboard.php" class="text-indigo-500 hover:text-indigo-700 font-semibold">&larr; Back to
            Dashboard</a>
        <div class="p-6 text-center text-gray-500 mt-8 border-dashed border-2 rounded-lg">
            <p class="text-lg mb-2">Course list will be displayed here.</p>
        </div>
    </div>
</body>

</html>