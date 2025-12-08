<?php
/**
 * ONE-TIME SCRIPT: Session Killer
 * This script forces the destruction of any broken PHP session.
 * Run this in your browser once to clear old login attempts.
 */
session_start();
session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html>

<head>
    <title>Session Cleared</title>
</head>

<body>
    <h1 style="color: green;">Session Successfully Reset!</h1>
    <p>You can now try logging in again without interference from old sessions.</p>
    <a href="login.html">Go to Login Page</a>
</body>

</html>