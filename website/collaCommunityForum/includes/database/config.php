<?php
// config.php

/**
 * Establish and return a PDO connection.
 * 
 * @return PDO
 * @throws PDOException If the connection fails
 */
function getDatabaseConnection() {
    // Database configuration
    $db_host = 'localhost';       // Database host
    $db_name = 'mdoc';          // Database name
    $db_user = 'root';            // Database username
    $db_pass = '';                // Database password
    $db_port = 3306;              // Database port (optional)

    try {
        $pdo = new PDO(
            "mysql:dbname=$db_name;host=$db_host;port=$db_port", // Connection string
            $db_user,                                           // Username
            $db_pass,                                           // Password
            [
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ, // Fetch as objects
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION     // Throw exceptions on errors
            ]
        );

        // Connection successful
        return $pdo;
    } catch (PDOException $e) {
        // Handle connection errors
        die("Database connection failed: " . $e->getMessage());
    }

}
