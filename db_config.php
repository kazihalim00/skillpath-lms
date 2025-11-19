<?php
/**
 * SkillPath Project: Database Configuration
 */


/** The name of the database for SkillPath (confirmed correct: learning_php) */
define('DB_NAME', 'learning_php');

/** MySQL database username (confirmed correct: root) */
define('DB_USER', 'root');

/** MySQL database password (Most common MAMP password) */
define('DB_PASSWORD', 'root'); // <--- USING 'root'

/** MySQL hostname (Standard local IP) */
define('DB_HOST', '127.0.0.1'); // <--- Using IP for robust connection

/** MySQL Port (Confirmed MAMP default) */
define('DB_PORT', 8889); // <--- Setting the explicit port

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');