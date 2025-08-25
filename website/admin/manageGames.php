<?php
session_start();

if(isset($_SESSION["user"])){
    if(($_SESSION["user"])=="" or $_SESSION['usertype']!='a'){
        header("location: ../login.php");
    }
} else {
    header("location: ../login.php");
}

// import database
include("../connection.php");
include(__DIR__ . '/../minigames/gamedb.php');

// Fetch games and times played
$gameList = getAllGames();
$gamePlayCounts = getGamePlayCounts();

// Define manage links
$gameManageLinks = [
    '1' => '/admin/manageBingo.php',
    '2' => '/admin/manageMindfulCounting.php',
    '3' => '/admin/manageQuiz.php',
    '4' => '/admin/manageCardMatching.php',
    '5' => '/admin/manageSpaceshipShooter.php'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/animations.css">  
    <link rel="stylesheet" href="../css/main.css">  
    <link rel="stylesheet" href="../css/admin.css">
    <title>Games</title>
    <style>
        .popup {
            animation: transitionIn-Y-bottom 0.5s;
        }
        .sub-table {
            animation: transitionIn-Y-bottom 0.5s;
        }
        .scroll-table-wrapper {
            max-height: 600px;
            overflow-y: auto;
            margin-top: 10px;
            border: 1px solid #ddd;
            width: 93%;
        }
        .sub-table {
            width: 100%;
            border-collapse: collapse;
        }
        .sub-table thead th {
            position: sticky;
            top: 0;
            background-color: #f1f1f1;
            z-index: 1;
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ccc;
        }
        .sub-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .height670 {
            width: 100%;
            height: 670px;
        }
    </style>
</head>
<body>
<div class="container">
    <?php include(__DIR__ . '/adminMenu.php'); ?>
    <div class="dash-body">
        <table border="0" width="100%" style="border-spacing: 0; margin:0; padding:0; margin-top:25px;">
            <tr>
                <td colspan="4">
                    <center>
                        <div class="height670 scroll">
                            <table border="0" width="100%" style="border-spacing: 0; margin:0; padding:0; margin-top:25px;">
                                <tr>
                                    <td width="13%">
                                        <a href="index.php">
                                            <button class="login-btn btn-primary-soft btn btn-icon-back" style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px">
                                                <font class="tn-in-text">Back</font>
                                            </button>
                                        </a>
                                    </td>
                                    <td>
                                        <p class="heading-main12" style="margin-left: 45px;font-size:20px;color:rgb(49, 49, 49)">All Games (<?php echo count($gameList); ?>)</p>
                                    </td>
                                </tr>

                                <tr>
                                    <td colspan="4">
                                        <center>
                                            <div class="height670 scroll">
                                                <div class="scroll-table-wrapper">
                                                    <table class="sub-table">
                                                        <thead style="transform: translateY(-1px);">
                                                            <tr>
                                                                <th>Game ID</th>
                                                                <th>Game Name</th>
                                                                <th>Status</th>
                                                                <th>Game Played</th>
                                                                <th>Events</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            if (empty($gameList)) {
                                                                echo '<tr>
                                                                    <td colspan="5">
                                                                        <br><br><br>
                                                                        <center>
                                                                            <img src="../img/notfound.svg" width="25%">
                                                                            <br>
                                                                            <p class="heading-main12" style="font-size:20px;color:rgb(49, 49, 49)">
                                                                                No games found!
                                                                            </p>
                                                                        </center>
                                                                        <br><br><br>
                                                                    </td>
                                                                </tr>';
                                                            } else {
                                                                foreach ($gameList as $game) {
                                                                    $gameId = $game["gameId"];
                                                                    $status = htmlspecialchars($game["status"]) == 1 ? "enabled" : "disabled";
                                                                    $badgeStyle = ($status === "enabled")
                                                                        ? 'background-color: #d4edda; color: #155724;'
                                                                        : 'background-color: #f8d7da; color: #721c24;';
                                                                    
                                                                    $playedCount = isset($gamePlayCounts[$gameId]) ? $gamePlayCounts[$gameId] : 0;

                                                                    echo '<tr>
                                                                        <td>' . htmlspecialchars($gameId) . '</td>
                                                                        <td>' . htmlspecialchars($game["gameName"]) . '</td>
                                                                        <td>
                                                                            <span style="padding: 8px 12px; border-radius: 12px; font-weight: bold; font-size: 0.9em; ' . $badgeStyle . '">
                                                                                ' . $status . '
                                                                            </span>
                                                                        </td>
                                                                        <td>' . str_pad($playedCount, 5, " ", STR_PAD_LEFT) . '</td>
                                                                        <td>
                                                                            <div style="display:flex;justify-content: center;">
                                                                                <a href="' . $gameManageLinks[$gameId] . '" class="non-style-link">
                                                                                    <button class="btn-primary-soft btn button-icon btn-edit">
                                                                                        <font class="tn-in-text">Manage</font>
                                                                                    </button>
                                                                                </a>
                                                                                &nbsp;&nbsp;&nbsp;';
                                                                                
                                                                            if ($status === "enabled") {
                                                                                echo '<a href="?action=drop&id=' . $gameId . '&name=' . $game["gameName"] . '" class="non-style-link">
                                                                                    <button class="btn-primary-soft btn button-icon btn-delete">
                                                                                        <font class="tn-in-text">Disable</font>
                                                                                    </button>
                                                                                </a>';
                                                                            } else {
                                                                                echo '<a href="?action=activate&id=' . $gameId . '&name=' . $game["gameName"] . '" class="non-style-link">
                                                                                    <button class="btn-primary-soft btn button-icon btn-enabled">
                                                                                        <font class="tn-in-text">Enable</font>
                                                                                    </button>
                                                                                </a>';
                                                                            }

                                                                            echo '</div>
                                                                        </td>
                                                                    </tr>';
                                                                }
                                                            }
                                                            ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </center>
                                    </td> 
                                </tr>    
                            </table>
                        </div>
                    </center>
                </td>
            </tr>  
        </table>
    </div>
</div>
<?php 
if($_GET){
    $id = $_GET["id"];
    $action = $_GET["action"];

    if($action == 'drop'){
        $nameget = $_GET["name"];
        echo '
        <div id="popup1" class="overlay">
            <div class="popup">
                <center>
                    <h2>Are you sure?</h2>
                    <a class="close" href="manageGames.php">&times;</a>
                    <div class="content">
                        You want to <b>disable</b> this game?<br>(' . htmlspecialchars(substr($nameget,0,40)) . ').
                    </div>
                    <div style="display: flex; justify-content: center;">
                        <a href="update-game-status.php?id=' . $id . '" class="non-style-link">
                            <button class="btn-primary btn" style="display: flex; justify-content: center; align-items: center; margin:10px; padding:10px;">
                                <font class="tn-in-text">&nbsp;Yes&nbsp;</font>
                            </button>
                        </a>&nbsp;&nbsp;&nbsp;
                        <a href="manageGames.php" class="non-style-link">
                            <button class="btn-primary btn" style="display: flex; justify-content: center; align-items: center; margin:10px; padding:10px;">
                                <font class="tn-in-text">&nbsp;&nbsp;No&nbsp;&nbsp;</font>
                            </button>
                        </a>
                    </div>
                </center>
            </div>
        </div>
        ';
    } else if($action == 'activate'){
        $nameget = $_GET["name"];
        echo '
        <div id="popup1" class="overlay">
            <div class="popup">
                <center>
                    <h2>Are you sure?</h2>
                    <a class="close" href="manageGames.php">&times;</a>
                    <div class="content">
                        You want to <b>activate</b> this game?<br>(' . htmlspecialchars(substr($nameget,0,40)) . ').
                    </div>
                    <div style="display: flex; justify-content: center;">
                        <a href="update-game-status.php?id=' . $id . '&activate=true" class="non-style-link">
                            <button class="btn-primary btn" style="display: flex; justify-content: center; align-items: center; margin:10px; padding:10px;">
                                <font class="tn-in-text">&nbsp;Yes&nbsp;</font>
                            </button>
                        </a>&nbsp;&nbsp;&nbsp;
                        <a href="manageGames.php" class="non-style-link">
                            <button class="btn-primary btn" style="display: flex; justify-content: center; align-items: center; margin:10px; padding:10px;">
                                <font class="tn-in-text">&nbsp;&nbsp;No&nbsp;&nbsp;</font>
                            </button>
                        </a>
                    </div>
                </center>
            </div>
        </div>
        ';
    }
}
?>
</body>
</html>
