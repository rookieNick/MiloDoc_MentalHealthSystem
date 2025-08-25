<?php
include("../connection.php");
include(__DIR__ . '/../minigames/gamedb.php');

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $activate = isset($_GET['activate']) ? true : false;

    if ($activate) {
        // Activate the tip
        $sql = "UPDATE memorygame_tips SET status = 1 WHERE tipId = ?";
    } else {
        // Disable the tip
        $sql = "UPDATE memorygame_tips SET status = 0 WHERE tipId = ?";
    }

    $stmt = $database->prepare($sql);
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header("Location: manageCardMatching.php"); // Redirect back to manage page
        exit();
    } else {
        echo "Something went wrong!";
    }
    $stmt->close();
} else {
    echo "Invalid request!";
}
?>
