<?php
session_start();

if (!isset($_SESSION["user"]) || $_SESSION["user"] == "" || $_SESSION["usertype"] != 'a') {
    header("location: ../login.php");
    exit;
}

include("../connection.php");
include(__DIR__ . '/../minigames/gamedb.php');

$level = getShooterGameLevel();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Spaceship Shooter Level</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/animations.css">  
    <link rel="stylesheet" href="../css/main.css">  
    <link rel="stylesheet" href="../css/admin.css">

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
            z-index: 0;
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
    <?php include("adminMenu.php"); ?>
    <div class="dash-body">
        <table border="0" width="100%" style="margin-top:25px;">
            <tr>
                <td width="13%">
                    <a href="manageGames.php">
                        <button class="login-btn btn-primary-soft btn btn-icon-back" style="margin-left:20px;width:125px">
                            <font class="tn-in-text">Back</font>
                        </button>
                    </a>
                </td>
                <td>
                    <p class="heading-main12" style="margin-left: 45px;font-size:20px;color:rgb(49, 49, 49)">
                        Spaceship Shooter Level
                    </p>
                </td>
            </tr>

            <tr>
                <td colspan="4">
                    <center>
                        <div class="height670 scroll">
                            <div class="scroll-table-wrapper">
                                <table class="sub-table">
                                    <thead>
                                        <tr>
                                            <th>Shooting Speed (ms)</th>
                                            <th>Enemy Spawn Speed (ms)</th>
                                            <th>Base Enemy Speed</th>
                                            <th>Speed Increment/Kill</th>
                                            <th>Events</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($level): ?>
                                            <tr>
                                                <td><?= $level['shooting_speed_ms'] ?></td>
                                                <td><?= $level['enemy_spawn_speed_ms'] ?></td>
                                                <td><?= $level['base_enemy_speed'] ?></td>
                                                <td><?= $level['speed_increment_per_kill'] ?></td>
                                                <td>
                                                    <a href="?action=edit" class="non-style-link">
                                                        <button class="btn-primary-soft btn button-icon btn-edit">
                                                            <font class="tn-in-text">Edit</font>
                                                        </button>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <tr><td colspan="7"><center>No level data found.</center></td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </center>
                </td>
            </tr>
        </table>
    </div>
</div>

<?php
if (isset($_GET['action']) && $_GET['action'] == 'edit' && $level):
    $error = $_GET['error'] ?? '0';

    $errorlist = [
        '1' => '<label class="form-label" style="color:red;text-align:center;">All fields are required.</label>',
        '2' => '<label class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Database error while updating the level.</label>',
        '3' => '<label class="form-label" style="color:green;text-align:center;">Edit Successful.</label>',
        '0' => '',
    ];
?>
<div id="popup1" class="overlay">
    <div class="popup">
        <center>
            <a class="close" href="manageSpaceshipShooter.php">&times;</a>
            <div class="abc">
                <form action="updateShooterGameLevel.php" method="POST" class="add-new-form">
                    <table width="80%" class="sub-table scrolldown add-doc-form-container" border="0">
                        <tr>
                            <td colspan="2"><?= $errorlist[$error] ?></td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <p style="font-size: 25px; font-weight: 500;">Edit Shooter Game Level</p><br>
                                Level ID: <?= htmlspecialchars($level['id']) ?>
                                <input type="hidden" name="id" value="<?= htmlspecialchars($level['id']) ?>">
                                <input type="hidden" name="updated_by" value="<?= htmlspecialchars($_SESSION['user']) ?>">
                            </td>
                        </tr>
                        <tr>
                            <td class="label-td">
                                <label>Shooting Speed (ms):</label>
                                <input type="number" name="shooting_speed_ms" class="input-text" value="<?= htmlspecialchars($level['shooting_speed_ms']) ?>" required>
                            </td>
                            <td class="label-td">
                                <label>Enemy Spawn Speed (ms):</label>
                                <input type="number" name="enemy_spawn_speed_ms" class="input-text" value="<?= htmlspecialchars($level['enemy_spawn_speed_ms']) ?>" required>
                            </td>
                        </tr>
                        <tr>
                            <td class="label-td">
                                <label>Base Enemy Speed:</label>
                                <input type="number" step="0.01" name="base_enemy_speed" class="input-text" value="<?= htmlspecialchars($level['base_enemy_speed']) ?>" required>
                            </td>
                            <td class="label-td">
                                <label>Speed Increment per Kill:</label>
                                <input type="number" step="0.01" name="speed_increment_per_kill" class="input-text" value="<?= htmlspecialchars($level['speed_increment_per_kill']) ?>" required>
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
        </center>
    </div>
</div>
<?php endif; ?>
</body>
</html>