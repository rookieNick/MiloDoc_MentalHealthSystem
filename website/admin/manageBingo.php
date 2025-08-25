<?php
session_start();
if (!isset($_SESSION["user"]) || $_SESSION["user"] == "" || $_SESSION['usertype'] != 'a') {
    header("location: ../login.php");
    exit;
}

include("../connection.php");
include(__DIR__ . '/../minigames/gamedb.php');
$missionList = getAllBingoMissionData();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Bingo Missions</title>
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
                                        <a href="manageGames.php">
                                            <button class="login-btn btn-primary-soft btn btn-icon-back" style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px">
                                                <font class="tn-in-text">Back</font>
                                            </button>
                                        </a>
                                    </td>
                                    <td>
                                        <p class="heading-main12" style="margin-left: 45px;font-size:20px;color:rgb(49, 49, 49)">Bingo Missions (<?php echo count($missionList); ?>)</p>
                                    </td>
                                    <td colspan="2" style="padding-right: 45px;"></td>
                                </tr>

                                <tr>
                                    <td colspan="4">
                                        <center>
                                            <div class="height670 scroll">
                                                <div class="scroll-table-wrapper">
                                                    <table class="sub-table">
                                                        <thead style="transform: translateY(-1px);">
                                                            <tr>
                                                                <th>Mission Number</th>
                                                                <th>Mission Name</th>
                                                                <th>Description</th>
                                                                <th>Mission Type</th>
                                                                <th>Target Value</th>
                                                                <th>Status</th>
                                                                <th>Events</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            if (empty($missionList)) {
                                                                echo '<tr>
                                                                        <td colspan="7">
                                                                            <br><br><br>
                                                                            <center>
                                                                                <img src="../img/notfound.svg" width="25%">
                                                                                <br>
                                                                                <p class="heading-main12" style="font-size:20px;color:rgb(49, 49, 49)">
                                                                                    No bingo missions found!
                                                                                </p>
                                                                            </center>
                                                                            <br><br><br>
                                                                        </td>
                                                                    </tr>';
                                                            } else {
                                                                foreach ($missionList as $missionNumber => $mission) {
                                                                    $status = htmlspecialchars($mission['status'] ?? '');
                                                                    $badgeStyle = ($status === 'enabled')
                                                                        ? 'background-color: #d4edda; color: #155724;'
                                                                        : 'background-color: #f8d7da; color: #721c24;';
                                                        
                                                                    echo '<tr>
                                                                        <td>' . htmlspecialchars($missionNumber) . '</td>
                                                                        <td>' . htmlspecialchars($mission['missionName']) . '</td>
                                                                        <td>' . htmlspecialchars($mission['missionDescription']) . '</td>
                                                                        <td>' . htmlspecialchars($mission['missionType']) . '</td>
                                                                        <td>' . htmlspecialchars($mission['targetValue']) . '</td>
                                                                        <td>
                                                                            <span style="padding: 8px 12px; border-radius: 12px; font-weight: bold; font-size: 0.9em; ' . $badgeStyle . '">
                                                                                ' . $status . '
                                                                            </span>
                                                                        </td>
                                                                        <td>
                                                                            <div style="display:flex;justify-content: center;">
                                                                                <!-- Edit Button -->
                                                                                <a href="?action=edit&mission=' . urlencode($missionNumber) . '&error=0" class="non-style-link">
                                                                                    <button class="btn-primary-soft btn button-icon btn-edit">
                                                                                        <font class="tn-in-text">Edit</font>
                                                                                    </button>
                                                                                </a>
                                                                                &nbsp;&nbsp;&nbsp;
                                                                                <!-- Enable/Disable Button -->
                                                                        ';
                                                                        
                                                                        $isExcludedMission = in_array($missionNumber, [48, 49, 50]);
                                                                        // Determine if mission is 48, 49, or 50
                                                                        if($isExcludedMission) {
                                                                            // Disabled button for missions 48, 49, 50 or if the mission is already disabled
                                                                            $disabledStyle = $isExcludedMission ? 'background-color: #e0e0e0; cursor: not-allowed; color: #1969AA;' : '';
                                                                            echo '<button class="btn-primary-soft btn button-icon btn-delete-nohover" style="' . $disabledStyle . '" disabled>
                                                                                    <font class="tn-in-text">Disable</font>
                                                                                </button>';
                                                                        } else if ($status === "enabled") {
                                                                            echo '<a href="?action=disable&mission=' . urlencode($missionNumber) . '&missionName=' . urlencode($mission['missionName']) . '" class="non-style-link">
                                                                                    <button class="btn-primary-soft btn button-icon btn-delete">
                                                                                        <font class="tn-in-text">Disable</font>
                                                                                    </button>
                                                                                </a>';
                                                                        } else if($isExcludedMission) {
                                                                            // Disabled button for missions 48, 49, 50 or if the mission is already disabled
                                                                            $disabledStyle = $isExcludedMission ? 'background-color: #e0e0e0; cursor: not-allowed; color: #1969AA;' : '';
                                                                            echo '<button class="btn-primary-soft btn button-icon btn-delete" style="' . $disabledStyle . '" disabled>
                                                                                    <font class="tn-in-text">Disable</font>
                                                                                </button>';
                                                                        }
                                                                        else {
                                                                            echo '<a href="?action=enable&mission=' . urlencode($missionNumber) . '&missionName=' . urlencode($mission['missionName']) . '" class="non-style-link">
                                                                                    <button class="btn-primary-soft btn button-icon btn-enabled">
                                                                                        <font class="tn-in-text">Enable</font>
                                                                                    </button>
                                                                                </a>';
                                                                        }
                                                                        echo '
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
if ($_GET) {
    $mission = $_GET["mission"];
    $action = $_GET["action"];
    $missionName = $_GET["missionName"] ?? ''; // Get the mission name if available
    
    if ($action == 'disable') {

        $enabledCount = countEnabledBingoMissionsExcluding();
        
        if ($enabledCount <= 9) {
            // Not enough enabled missions left after disabling
            echo '
            <div id="popup1" class="overlay">
                <div class="popup">
                    <center>
                        <h2>Not Allowed</h2>
                        <a class="close" href="manageBingo.php">&times;</a>
                        <div class="content">
                            Cannot disable this mission. Bingo must have at least 9 enabled missions.
                        </div>
                        <div style="display: flex; justify-content: center;">
                            <a href="manageBingo.php" class="non-style-link">
                                <button class="btn-primary btn" style="margin:10px;padding:10px;">
                                    <font class="tn-in-text">&nbsp;&nbsp;OK&nbsp;&nbsp;</font>
                                </button>
                            </a>
                        </div>
                    </center>
                </div>
            </div>';
        } else {
        echo '
        <div id="popup1" class="overlay">
            <div class="popup">
                <center>
                    <h2>Are you sure?</h2>
                    <a class="close" href="manageBingo.php">&times;</a>
                    <div class="content">
                        You want to disable this mission ' . htmlspecialchars($mission) .'?<br>(' . htmlspecialchars($missionName) . ')
                    </div>
                    <div style="display: flex; justify-content: center;">
                        <a href="disabled-bingo.php?mission=' . urlencode($mission) . '" class="non-style-link">
                            <button class="btn-primary btn" style="margin:10px;padding:10px;">
                                <font class="tn-in-text">&nbsp;Yes&nbsp;</font>
                            </button>
                        </a>&nbsp;&nbsp;&nbsp;
                        <a href="manageBingo.php" class="non-style-link">
                            <button class="btn-primary btn" style="margin:10px;padding:10px;">
                                <font class="tn-in-text">&nbsp;&nbsp;No&nbsp;&nbsp;</font>
                            </button>
                        </a>
                    </div>
                </center>
            </div>
        </div>';
        }
    } elseif ($action == 'enable') {
        echo '
        <div id="popup1" class="overlay">
            <div class="popup">
                <center>
                    <h2>Are you sure?</h2>
                    <a class="close" href="manageBingo.php">&times;</a>
                    <div class="content">
                        You want to enable this mission ' . htmlspecialchars($mission) .'?<br>(' . htmlspecialchars($missionName) . ')
                    </div>
                    <div style="display: flex; justify-content: center;">
                        <a href="disabled-bingo.php?mission=' . urlencode($mission) . '&activate=true" class="non-style-link">
                            <button class="btn-primary btn" style="margin:10px;padding:10px;">
                                <font class="tn-in-text">&nbsp;Yes&nbsp;</font>
                            </button>
                        </a>&nbsp;&nbsp;&nbsp;
                        <a href="manageBingo.php" class="non-style-link">
                            <button class="btn-primary btn" style="margin:10px;padding:10px;">
                                <font class="tn-in-text">&nbsp;&nbsp;No&nbsp;&nbsp;</font>
                            </button>
                        </a>
                    </div>
                </center>
            </div>
        </div>';
    } elseif($action == 'edit'){
        $mission = getBingoMissionById($mission);
        
        $missionNumber = $mission['missionNumber'];
        $missionName = $mission['missionName'];
        $missionDescription = $mission['missionDescription'];
        $missionType = $mission['missionType'];
        $targetValue = $mission['targetValue'];
        $status = $mission['status'];
    
        $error_1 = $_GET["error"] ?? '0';
    
        $errorlist = array(
            '1' => '<label class="form-label" style="color:red;text-align:center;">All fields are required.</label>',
            '2' => '<label class="form-label" style="color:red;text-align:center;">Invalid mission type.</label>',
            '3' => '<label class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Database error while updating the mission.</label>',
            '4' => '<label class="form-label" style="color:green;text-align:center;">Edit Successful.</label>', // Success
            '5' => '<label class="form-label" style="color:red;text-align:center;">Percentage cannot more than 100%.</label>',
            '6' => '<label class="form-label" style="color:red;text-align:center;">Target cannot less than 1</label>',
            '7' => '<label class="form-label" style="color:red;text-align:center;">Select Difficult Level at the range of 1 to 4</label>',
            '0' => '',
        );
    
        echo '
        <div id="popup1" class="overlay">
            <div class="popup">
                <center>
                    <a class="close" href="manageBingo.php">&times;</a>
                    <div style="display: flex;justify-content: center;">
                        <div class="abc">
                            <form action="update-bingo-mission.php" method="POST" class="add-new-form">
                            <table width="80%" class="sub-table scrolldown add-doc-form-container" border="0">
                                <tr>
                                    <td colspan="2">' . $errorlist[$error_1] . '</td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <p style="font-size: 25px; font-weight: 500; text-align: left;">Edit Bingo Mission</p>
                                        Mission ID: '.$missionNumber.'<br><br>
                                        <input type="hidden" name="missionNumber" value="'.$missionNumber.'">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                        <label>Mission Name:</label>
                                        <input type="text" name="missionName" class="input-text" value="'.$missionName.'" required>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                        <label>Mission Description:</label>
                                        <textarea name="missionDescription" class="input-text" required>'.$missionDescription.'</textarea>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                        <label>Mission Type:</label>
                                        <select name="missionType" class="box" required>
                                            <option value="game"'.($missionType == 'game' ? ' selected' : '').'>Game</option>
                                            <option value="scoreTarget"'.($missionType == 'scoreTarget' ? ' selected' : '').'>Score Target</option>
                                            <option value="requiredTime"'.($missionType == 'requiredTime' ? ' selected' : '').'>Required Time</option>
                                            <option value="percentage"'.($missionType == 'percentage' ? ' selected' : '').'>Percentage</option>
                                            <option value="level"'.($missionType == 'level' ? ' selected' : '').'>Level</option>
                                            <option value="requiredDay"'.($missionType == 'requiredDay' ? ' selected' : '').'>Required Day</option>
                                            <option value="target"'.($missionType == 'target' ? ' selected' : '').'>Target</option>
                                            <option value="other"'.($missionType == 'other' ? ' selected' : '').'>Other</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                        <label>Target Value:</label>
                                        <input type="number" name="targetValue" class="input-text" value="'.$targetValue.'" required>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                        <label>Status:</label>
                                        ' . (
                                            in_array((int)$missionNumber, [48, 49, 50])
                                            ? '<input type="hidden" name="status" value="' . htmlspecialchars($status) . '">
                                            <span style="color: #555;">' . htmlspecialchars(ucfirst($status)) . ' (locked)</span>'
                                            : '<select name="status" class="box" required>
                                                <option value="enabled"' . ($status == 'enabled' ? ' selected' : '') . '>Enabled</option>
                                                <option value="disabled"' . ($status == 'disabled' ? ' selected' : '') . '>Disabled</option>
                                            </select>'
                                        ) . '
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
    };
}
?>
</body>
</html>
