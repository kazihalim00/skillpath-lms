<?php
/**
 * SkillPath Project: Logout Script
 *
 * This script securely logs the user out and redirects to the login page.
 */

// Start the session if not already started
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to the login page with a success message
header("Location: login.html?status=logout");
exit;
?>