<?php
/**
 * SkillPath Project: Registration Handler
 *
 * This script processes the registration form data, hashes the password,
 * and inserts the new user's information into the 'users' table in the database.
 */

// Include the database configuration file
require_once 'db_config.php';

// Check if the form was submitted using the POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Retrieve form data and sanitize it
    $full_name = htmlspecialchars($_POST['name']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password']; // Get the password from the form
    $role = htmlspecialchars($_POST['role']);

    // Hash the password for security before storing it in the database
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Create a new database connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT); // Using DB_PORT here

    // Check for connection errors
    if ($conn->connect_error) {
        // Instead of dying, we redirect with an error message
        header("Location: login.html?status=error&message=Database%20connection%20failed:%20" . urlencode($conn->connect_error));
        exit();
    }

    // SQL query to insert new user data into the 'users' table
    $sql = "INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)";
    
    // Use a prepared statement to prevent SQL injection attacks
    $stmt = $conn->prepare($sql);
    
    // Bind parameters to the statement
    $stmt->bind_param("ssss", $full_name, $email, $hashed_password, $role);

    // Execute the statement
    if ($stmt->execute()) {
        // Redirect to a success page or back to the login page
        // with a success message
        header("Location: login.html?status=success");
    } else {
        // Handle potential errors, like a duplicate email
        if ($conn->errno == 1062) {
             header("Location: login.html?status=error&message=Email%20already%20registered.");
        } else {
            echo "Error: " . $stmt->error;
        }
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();

} else {
    // If the form was not submitted correctly, redirect
    header("Location: login.html");
}
