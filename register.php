<?php
/**
 * SkillPath Project: Registration Handler
 * Updated to save Gender, Department, and Batch info.
 */

require_once 'db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = htmlspecialchars(trim($_POST['name']));
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $role = htmlspecialchars($_POST['role']);
    $gender = htmlspecialchars($_POST['gender']);

    // Handle optional fields (if user is instructor, batch might be empty)
    $department_id = !empty($_POST['department_id']) ? $_POST['department_id'] : NULL;
    $batch_id = !empty($_POST['batch_id']) ? $_POST['batch_id'] : NULL;

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

        // Check for duplicate email
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            header("Location: login.php?status=error&message=Email%20already%20registered.");
            exit();
        }
        $check->close();

        // Insert new user
        $sql = "INSERT INTO users (full_name, email, password, role, gender, department_id, batch_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssii", $full_name, $email, $hashed_password, $role, $gender, $department_id, $batch_id);

        if ($stmt->execute()) {
            header("Location: login.php?status=success&message=Registration%20successful!%20Please%20login.");
        } else {
            header("Location: login.php?status=error&message=Registration%20failed.");
        }
        $stmt->close();
        $conn->close();

    } catch (Exception $e) {
        header("Location: login.php?status=error&message=" . urlencode($e->getMessage()));
    }
} else {
    header("Location: login.php");
}
?>