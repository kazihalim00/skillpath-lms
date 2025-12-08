<?php
/**
 * SkillPath - Login Logic (index.php)
 * FIXED: Supports both Encrypted (Hashed) and Plain Text passwords.
 */
session_start();
require_once 'db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $_POST['email'];
    $password = $_POST['password'];

    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

        // 1. Check if email exists
        $stmt = $conn->prepare("SELECT id, full_name, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $stored_password = $user['password'];

            // 2. VERIFY PASSWORD (Hybrid Check)
            // Checks if password matches the Hash OR if it matches Plain Text
            if (password_verify($password, $stored_password) || $password === $stored_password) {

                // 3. Set Session Variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];

                // 4. Redirect based on Role
                if ($user['role'] === 'instructor') {
                    header("Location: instructor_dashboard.php");
                    exit();
                } elseif ($user['role'] === 'admin') {
                    header("Location: admin_dashboard.php");
                    exit();
                } else {
                    // Default to student
                    header("Location: student_dashboard.php");
                    exit();
                }

            } else {
                // Wrong Password
                header("Location: login.php?status=error&message=Incorrect+password");
                exit();
            }
        } else {
            // User not found
            header("Location: login.php?status=error&message=User+not+found");
            exit();
        }

        $stmt->close();
        $conn->close();

    } catch (Exception $e) {
        header("Location: login.php?status=error&message=System+Error");
    }
} else {
    header("Location: login.php");
    exit();
}
?>