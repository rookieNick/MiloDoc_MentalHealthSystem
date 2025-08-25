<?php

session_start();

if (isset($_SESSION["user"])) {
    if (($_SESSION["user"] == "") || $_SESSION["usertype"] != 'a') {
        header("location: ../login.php");
    }
} else {
    header("location: ../login.php");
}

if ($_GET) {
    include("../connection.php");
    include(__DIR__ . '/../minigames/gamedb.php');

    $mission = $_GET["mission"];
    $activate = isset($_GET["activate"]) ? $_GET["activate"] : false;

    if ($activate) {
        setBingoMissionStatus($mission, 'enabled');
    } else {
        setBingoMissionStatus($mission, 'disabled');
    }

    header("location: manageBingo.php");
}
?>