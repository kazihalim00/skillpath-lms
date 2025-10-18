<?php
/**
 * SkillPath Project: Logout Handler
 *
 * This script securely logs the user out by destroying the session
 * and redirects them to the login page.
 */
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to the login page
header("Location: login.html?status=logout");
exit();