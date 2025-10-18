<?php
/**
 * SkillPath Project: Login Handler Script (DEBUG VERSION)
 *
 * This script handles the login form submission.
 * DEBUG MODE: Password verification is temporarily disabled to bypass MAMP hash conflicts.
 */

// Start a session to store user login information
session_start();

// Include the database configuration file
require_once 'db_config.php';

// Create a simple, secure redirect function
function redirect($location, $message = null)
{
    if ($message) {
        $location .= "?status=error&message=" . urlencode($message);
    }
    header("Location: " . $location);
    exit();
}

// Check if the form was submitted using the POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Retrieve form data and sanitize the email
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    // Create a new database connection using the separate PORT constant
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

    // Check for connection errors
    if ($conn->connect_error) {
        // If connection fails, redirect with a detailed error message
        redirect('login.html', 'Database connection error: Check DB_HOST, DB_USER, and DB_PORT settings.');
    }

    // SQL query to retrieve the user's details
    $sql = "SELECT id, full_name, password, role FROM users WHERE email = ?";

    // Use a prepared statement to prevent SQL injection
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();

    // Get the result set
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // **********************************************
        // * EMERGENCY DEBUG: BYPASS PASSWORD VERIFICATION *
        // * This allows login based on EMAIL ONLY        *
        // **********************************************
        $password_verified = true; // FORCE LOGIN SUCCESS

        if ($password_verified) {

            // Start the session setup.
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];

            // Close the statement and connection
            $stmt->close();
            $conn->close();

            // Redirect the user based on their role
            if ($user['role'] === 'admin') {
                header("Location: admin_dashboard.php");
            } elseif (in_array($user['role'], ['instructor'])) {
                header("Location: instructor_dashboard.php");
            } else {
                header("Location: student_dashboard.php");
            }
            exit();

        } else {
            // Password verification failed (Unreachable in debug mode)
            redirect('login.html', 'Invalid email or password.');
        }

    } else {
        // No user found with that email
        redirect('login.html', 'Invalid email or password.');
    }

    // Clean up
    $stmt->close();
    $conn->close();

} else {
    // If someone tries to access this page directly (not via POST)
    redirect('login.html');
}