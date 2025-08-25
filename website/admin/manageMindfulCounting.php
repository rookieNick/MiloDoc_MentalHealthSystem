<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/animations.css">  
    <link rel="stylesheet" href="../css/main.css">  
    <link rel="stylesheet" href="../css/admin.css">
        
    <title>Manage Mindful Counting Levels</title>
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
<?php
session_start();

if (isset($_SESSION["user"])) {
    if (($_SESSION["user"]) == "" || $_SESSION['usertype'] != 'a') {
        header("location: ../login.php");
    }
} else {
    header("location: ../login.php");
}

include("../connection.php");
include(__DIR__ . '/../minigames/gamedb.php');
$levelList = getAllLevelData();
?>
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
                                        <a href="manageGames.php">
                                            <button class="login-btn btn-primary-soft btn btn-icon-back" style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px">
                                                <font class="tn-in-text">Back</font>
                                            </button>
                                        </a>
                                    </td>
                                    <td>
                                        <p class="heading-main12" style="margin-left: 45px;font-size:20px;color:rgb(49, 49, 49)">Mindful Counting Levels (<?php echo count($levelList); ?>)</p>
                                    </td>
                                    <td colspan="2" style="padding-right: 45px;"></td> <!-- no add button -->
                                </tr>

                                <tr>
                                    <td colspan="4">
                                        <center>
                                            <div class="height670 scroll">
                                                <div class="scroll-table-wrapper">
                                                    <table class="sub-table">
                                                        <thead style="transform: translateY(-1px);">
                                                            <tr>
                                                                <th>Level Name</th>
                                                                <th>Minimum Flight Time</th>
                                                                <th>Speed Variation</th>
                                                                <th>Base Bird Speed</th>
                                                                <th>Bird Speed Random Gap</th>
                                                                <th>Game Timeout</th>
                                                                <th>Events</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            if (empty($levelList)) {
                                                                echo '<tr>
                                                                        <td colspan="7">
                                                                            <br><br><br>
                                                                            <center>
                                                                                <img src="../img/notfound.svg" width="25%">
                                                                                <br>
                                                                                <p class="heading-main12" style="font-size:20px;color:rgb(49, 49, 49)">
                                                                                    No levels found!
                                                                                </p>
                                                                            </center>
                                                                            <br><br><br>
                                                                        </td>
                                                                    </tr>';
                                                            } else {
                                                                foreach ($levelList as $levelName => $details) {
                                                                    echo '<tr>
                                                                        <td>' . htmlspecialchars($levelName) . '</td>
                                                                        <td>' . htmlspecialchars($details['minimum_flight_time']) . '</td>
                                                                        <td>' . htmlspecialchars($details['speed_variation']) . '</td>
                                                                        <td>' . htmlspecialchars($details['base_bird_speed']) . '</td>
                                                                        <td>' . htmlspecialchars($details['bird_speed_random_gap']) . '</td>
                                                                        <td>' . htmlspecialchars($details['game_timeout']) . '</td>
                                                                        <td>
                                                                            <div style="display:flex;justify-content: center;">
                                                                                <a href="?action=edit&level=' . urlencode($levelName) . '&error=0" class="non-style-link">
                                                                                    <button class="btn-primary-soft btn button-icon btn-edit">
                                                                                        <font class="tn-in-text">Edit</font>
                                                                                    </button>
                                                                                </a>
                                                                            </div>
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
if (isset($_GET["action"]) && $_GET["action"] == "edit" && isset($_GET["level"])) {

    $levelName = $_GET["level"];
    $error_1 = $_GET["error"] ?? '0';

    $level = getMindfulCountingLevel($levelName);

    if ($level) {
        $id = $level['id'];
        $minimum_flight_time = $level['minimum_flight_time'];
        $speed_variation = $level['speed_variation'];
        $base_bird_speed = $level['base_bird_speed'];
        $bird_speed_random_gap = $level['bird_speed_random_gap'];
        $game_timeout = $level['game_timeout'];

        $errorlist = array(
            '1' => '<label class="form-label" style="color:red;text-align:center;">All fields are required.</label>',
            '2' => '<label class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Database error while updating the level.</label>',
            '3' => '<label class="form-label" style="color:green;text-align:center;">Edit Successful.</label>', // Success
            '0' => '',
        );

        echo '
        <div id="popup1" class="overlay">
            <div class="popup">
                <center>
                    <a class="close" href="manageMindfulCounting.php">&times;</a>
                    <div style="display: flex;justify-content: center;">
                        <div class="abc">
                            <form action="updateMindfulCountingLevel.php" method="POST" class="add-new-form">
                                <table width="80%" class="sub-table scrolldown add-doc-form-container" border="0">
                                    <tr>
                                        <td colspan="2">' . $errorlist[$error_1] . '</td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">
                                            <p style="font-size: 25px; font-weight: 500; text-align: left;">Edit Mindful Counting Level</p>
                                            Level ID: ' . htmlspecialchars($id) . '<br><br>
                                            <input type="hidden" name="id" value="' . htmlspecialchars($id) . '">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="label-td" colspan="2">
                                            <label>Level Name:</label>
                                            <input type="text" name="level_name" class="input-text" value="' . htmlspecialchars($levelName) . '" required>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="label-td">
                                            <label>Minimum Flight Time (seconds):</label>
                                            <input type="number" step="0.1" name="minimum_flight_time" class="input-text" value="' . htmlspecialchars($minimum_flight_time) . '" required>
                                        </td>
                                        <td class="label-td">
                                            <label>Speed Variation:</label>
                                            <input type="number" step="0.01" name="speed_variation" class="input-text" value="' . htmlspecialchars($speed_variation) . '" required>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="label-td">
                                            <label>Base Bird Speed:</label>
                                            <input type="number" step="0.01" name="base_bird_speed" class="input-text" value="' . htmlspecialchars($base_bird_speed) . '" required>
                                        </td>
                                        <td class="label-td">
                                            <label>Bird Speed Random Gap:</label>
                                            <input type="number" step="0.01" name="bird_speed_random_gap" class="input-text" value="' . htmlspecialchars($bird_speed_random_gap) . '" required>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="label-td" colspan="2">
                                            <label>Game Timeout (seconds):</label>
                                            <input type="number" step="1" name="game_timeout" class="input-text" value="' . htmlspecialchars($game_timeout) . '" required>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">
                                            <input type="reset" value="Reset" class="login-btn btn-primary-soft btn">
                                            &nbsp;&nbsp;&nbsp;
                                            <input type="submit" value="Save" class="login-btn btn-primary btn">
                                        </td>
                                    </tr>
                                </table>
                            </form>
                        </div>
                    </div>
                </center>
            </div>
        </div>';
    }
}
?>
</body>
</html>
