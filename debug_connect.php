<?php
/**
 * SkillPath Project: Connection Debugger
 *
 * This script attempts to connect to the database using the settings
 * in db_config.php and prints the exact error message provided by MySQL.
 */

// Include the database configuration file
require_once 'db_config.php';

echo '<!DOCTYPE html><html><head><title>DB Connection Test</title>';
echo '<script src="https://cdn.tailwindcss.com"></script>';
echo '<style>body { font-family: sans-serif; padding: 20px; background-color: #f4f7f9; }</style>';
echo '</head><body>';

echo '<div class="max-w-md mx-auto p-6 mt-10 rounded-xl shadow-2xl text-center bg-white">';
echo '<h1 class="text-2xl font-bold mb-4">SkillPath Connection Debugger</h1>';

// Attempt to connect using the constants defined in db_config.php
// We use the full arguments: host, user, password, database, port
$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

if ($conn->connect_error) {
    // Connection Failed: Print the specific error provided by MySQL
    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">';
    echo '<strong class="font-bold">CONNECTION FAILED!</strong>';
    echo '<p class="mt-2 text-left"><strong>Settings Used:</strong></p>';
    echo '<ul class="list-disc list-inside text-left">';
    echo '<li>Host: ' . DB_HOST . '</li>';
    echo '<li>Port: ' . DB_PORT . '</li>';
    echo '<li>User: ' . DB_USER . '</li>';
    echo '<li>DB Name: ' . DB_NAME . '</li>';
    echo '</ul>';
    echo '<p class="mt-4"><strong>MySQL Error:</strong> ' . $conn->connect_error . '</p>';
    echo '</div>';
} else {
    // Connection Successful
    echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">';
    echo '<strong class="font-bold">SUCCESS!</strong>';
    echo '<p class="mt-2">Database connection established successfully. The problem is fixed!</p>';
    echo '</div>';
    $conn->close();
}

echo '<a href="login.html" class="mt-6 inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg transition duration-150">Go to Login Page</a>';
echo '</div></body></html>';
