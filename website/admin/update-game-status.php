<?php
session_start();
if (!isset($_SESSION["user"]) || $_SESSION["user"] == "" || $_SESSION['usertype'] != 'a') {
    header("location: ../login.php");
    exit();
}

include("../connection.php");
include(__DIR__ . '/../minigames/gamedb.php');

if ($_GET) {
    $id = $_GET["id"];
    $activate = isset($_GET["activate"]) ? $_GET["activate"] : false;

    if ($activate) {
        setGameStatus($id, 'enabled'); // Activate game
    } else {
        setGameStatus($id, 'disabled'); // Disable game
    }

    header("Location: manageGames.php");
    exit();
}
?>
