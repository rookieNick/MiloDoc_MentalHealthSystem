<?php
session_start();
include("../connection.php");
include(__DIR__ . '/../minigames/gamedb.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tipText = trim($_POST["tipText"] ?? '');
    $status = $_POST["status"] ?? '';

    // validation
    if (empty($tipText) || !isset($status)) {
        header("Location: manageCardMatching.php?action=add&error=1");
        exit();
    }

    // Insert into database
    $result = insertTip($tipText, $status);

    if ($result["success"]) {
        header("Location: manageCardMatching.php?action=add&error=4");
        exit();
    } else {
        header("Location: manageCardMatching.php?action=add&error=2");
        exit();
    }
} else {
    // Redirect if accessed directly
    header("Location: manageCardMatching.php");
    exit();
}
?>
