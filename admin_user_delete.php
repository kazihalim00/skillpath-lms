<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$id = $_GET['id'] ?? null;
$msg = "Error deleting user.";
$status = "error";

if ($id && $id != $_SESSION['user_id']) {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $msg = "User deleted successfully.";
            $status = "success";
        }
        $conn->close();
    } catch (Exception $e) {
        $msg = "Database Error: " . $e->getMessage();
    }
}

// FIX: Redirects back to the User List page
header("Location: admin_manage_users.php?message=" . urlencode($msg) . "&status=$status");
exit();
?>