<?php
session_start();

// Check if the user is logged in and is an admin
if (isset($_SESSION["user"])) {
    if (($_SESSION["user"]) == "" || $_SESSION['usertype'] != 'a') {
        header("location: ../login.php");
        exit();
    }
} else {
    header("location: ../login.php");
    exit();
}

// Import database connection
include("../connection.php");

// Check if 'id' is provided
if (!isset($_GET['id'])) {
    header("Location: testDescription.php");
    exit();
}

$id = (int)$_GET['id'];

// Perform deletion
$database->query("DELETE FROM test_result_description WHERE id=$id");

// Redirect back
header("Location: testDescription.php");
exit();
?>
