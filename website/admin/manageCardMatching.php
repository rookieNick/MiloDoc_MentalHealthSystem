<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/animations.css">  
    <link rel="stylesheet" href="../css/main.css">  
    <link rel="stylesheet" href="../css/admin.css">
        
    <title>Memory Game Tips</title>
    <style>
        .popup{
            animation: transitionIn-Y-bottom 0.5s;
        }
        .sub-table{
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

        .height670{
            width: 100%;
            height: 670px;
        }

        .sub-table.scrolldown.add-doc-form-container {
            max-height: 490px;
            overflow-y: auto;
        }
</style>
</head>
<body>
    <?php
    session_start();

    if(isset($_SESSION["user"])){
        if(($_SESSION["user"])=="" or $_SESSION['usertype']!='a'){
            header("location: ../login.php");
        }
    } else {
        header("location: ../login.php");
    }
    
    include("../connection.php");
    include(__DIR__ . '/../minigames/gamedb.php');
    $tipList = getAllMemoryGameTips();
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
                                            <a href="manageGames.php"><button class="login-btn btn-primary-soft btn btn-icon-back" style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px">
                                                <font class="tn-in-text">Back</font></button></a>
                                        </td>
                                        <td>
                                            <p class="heading-main12" style="margin-left: 45px;font-size:20px;color:rgb(49, 49, 49)">All Memory Game Tips (<?php echo count($tipList); ?>)</p>
                                        </td>
                                        <td colspan="2" style="padding-right: 45px;">
                                            <div style="display: flex; justify-content: flex-end;">
                                                <a href="?action=add&id=none&error=0" class="non-style-link">
                                                    <button class="login-btn btn-primary btn button-icon" style="background-image: url('../img/icons/add.svg');">
                                                        Add New
                                                    </button>
                                                </a>
                                            </div>
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
                                                                    <th>Tip ID</th>
                                                                    <th>Tip Text</th>
                                                                    <th>Status</th>
                                                                    <th>Events</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php
                                                                if (empty($tipList)) {
                                                                    echo '<tr>
                                                                            <td colspan="4">
                                                                                <br><br><br>
                                                                                <center>
                                                                                    <img src="../img/notfound.svg" width="25%">
                                                                                    <br>
                                                                                    <p class="heading-main12" style="font-size:20px;color:rgb(49, 49, 49)">
                                                                                        No memory tips found!
                                                                                    </p>
                                                                                </center>
                                                                                <br><br><br>
                                                                            </td>
                                                                        </tr>';
                                                                } else {
                                                                    foreach ($tipList as $tip) {
                                                                        $status = htmlspecialchars($tip["status"]) == 1 ? "enabled" : "disabled";
                                                                        $badgeStyle = ($status === "enabled")
                                                                        ? 'background-color: #d4edda; color: #155724;'
                                                                        : 'background-color: #f8d7da; color: #721c24;';

                                                                        echo '<tr>
                                                                            <td>' . htmlspecialchars($tip["tipId"]) . '</td>
                                                                            <td>' . htmlspecialchars($tip["tipText"]) . '</td>
                                                                            <td>
                                                                                <span style="padding: 8px 12px; border-radius: 12px; font-weight: bold; font-size: 0.9em; ' . $badgeStyle . '">
                                                                                    ' . $status . '
                                                                                </span>
                                                                            </td>
                                                                            <td>
                                                                                <div style="display:flex;justify-content: center;">
                                                                                    <a href="?action=edit&id=' . $tip["tipId"] . '&error=0" class="non-style-link">
                                                                                        <button class="btn-primary-soft btn button-icon btn-edit">
                                                                                            <font class="tn-in-text">Edit</font>
                                                                                        </button>
                                                                                    </a>
                                                                                    &nbsp;&nbsp;&nbsp;';
                                                                        if ($status === "enabled") {
                                                                            echo '<a href="?action=drop&id=' . $tip["tipId"] . '&name=' . urlencode($tip["tipText"]) . '" class="non-style-link">
                                                                                <button class="btn-primary-soft btn button-icon btn-delete">
                                                                                    <font class="tn-in-text">Disable</font>
                                                                                </button>
                                                                            </a>';
                                                                        } else {
                                                                            echo '<a href="?action=activate&id=' . $tip["tipId"] . '&name=' . urlencode($tip["tipText"]) . '" class="non-style-link">
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

    if($action == 'add') {
        $error_1 = $_GET["error"] ?? '0';
        $errorlist = array(
            '1' => '<label class="form-label" style="color:rgb(255, 62, 62);text-align:center;">All fields are required.</label>',
            '2' => '<label class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Database error while inserting the tip.</label>',
            '4' => '', // Success
            '0' => '',
        );
        
        if ($error_1 != '4') {
            echo '
            <div id="popup1" class="overlay">
                <div class="popup">
                    <center>
                        <a class="close" href="manageCardMatching.php">&times;</a> 
                        <div style="display: flex;justify-content: center;">
                            <div class="abc">
                                <table width="80%" class="sub-table scrolldown add-doc-form-container" border="0">
                                    <tr>
                                        <td class="label-td" colspan="2">' . $errorlist[$error_1] . '</td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">
                                            <p style="padding: 0;margin: 0;text-align: left;font-size: 25px;font-weight: 500;">Add New Tip</p><br><br>
                                        </td>
                                    </tr>
                                    <form action="add-new-tip.php" method="POST" class="add-new-form">
                                        <tr>
                                            <td class="label-td" colspan="2">
                                                <label class="form-label">Tip Text:</label>
                                                <input type="text" name="tipText" class="input-text" placeholder="Enter the tip" required>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="label-td" colspan="2">
                                                <label class="form-label">Status:</label>
                                                <select name="status" class="box" required>
                                                    <option value="1">Enabled</option>
                                                    <option value="0">Disabled</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2">
                                                <input type="reset" value="Reset" class="login-btn btn-primary-soft btn">
                                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                <input type="submit" value="Add" class="login-btn btn-primary btn">
                                            </td>
                                        </tr>
                                    </form>
                                </table>
                            </div>
                        </div>
                    </center>
                    <br><br>
                </div>
            </div>';
        }
    } else if($action == 'edit'){
        // Fetch the tip details from the database
        $tip = getMemoryGameTipById($id);
        $tipText = $tip['tipText'];
        $status = $tip['status'];

        $error_1 = $_GET["error"] ?? '0';

        $errorlist = array(
            '1' => '<label class="form-label" style="color:red;text-align:center;">All fields are required.</label>',
            '2' => '<label class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Database error while updating the tip.</label>',
            '3' => '<label class="form-label" style="color:green;text-align:center;">Edit Successful.</label>', // Success
            '0' => '',
        );

        echo '
        <div id="popup1" class="overlay">
            <div class="popup">
                <center>
                    <a class="close" href="manageCardMatching.php">&times;</a>
                    <div style="display: flex;justify-content: center;">
                        <div class="abc">
                            <form action="update-tip-detail.php" method="POST" class="add-new-form">
                            <table width="80%" class="sub-table scrolldown add-doc-form-container" border="0">
                                <tr>
                                    <td colspan="2">' . $errorlist[$error_1] . '</td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <p style="font-size: 25px; font-weight: 500; text-align: left;">Edit Tip</p>
                                        Tip ID: '.$id.'<br><br>
                                        <input type="hidden" name="tipId" value="'.$id.'">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                        <label>Tip Text:</label>
                                        <textarea name="tipText" class="input-text" required>'.$tipText.'</textarea>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                        <label>Status:</label>
                                        <select name="status" class="box" required>
                                            <option value="1"' . ($status == 1 ? ' selected' : '') . '>Enabled</option>
                                            <option value="0"' . ($status == 0 ? ' selected' : '') . '>Disabled</option>
                                        </select>
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
    } else if($action == 'drop'){
        $nameget = $_GET["name"];
        echo '
        <div id="popup1" class="overlay">
            <div class="popup">
                <center>
                    <h2>Are you sure?</h2>
                    <a class="close" href="manageCardMatching.php">&times;</a>
                    <div class="content">
                        You want to <b>disable</b> this tip?<br>(' . htmlspecialchars(substr($nameget,0,40)) . ').
                    </div>
                    <div style="display: flex; justify-content: center;">
                        <a href="update-tip-status.php?id=' . $id . '" class="non-style-link">
                            <button class="btn-primary btn" style="display: flex; justify-content: center; align-items: center; margin:10px; padding:10px;">
                                <font class="tn-in-text">&nbsp;Yes&nbsp;</font>
                            </button>
                        </a>&nbsp;&nbsp;&nbsp;
                        <a href="manageCardMatching.php" class="non-style-link">
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
                    <a class="close" href="manageCardMatching.php">&times;</a>
                    <div class="content">
                        You want to <b>activate</b> this tip?<br>(' . htmlspecialchars(substr($nameget,0,40)) . ').
                    </div>
                    <div style="display: flex; justify-content: center;">
                        <a href="update-tip-status.php?id=' . $id . '&activate=true" class="non-style-link">
                            <button class="btn-primary btn" style="display: flex; justify-content: center; align-items: center; margin:10px; padding:10px;">
                                <font class="tn-in-text">&nbsp;Yes&nbsp;</font>
                            </button>
                        </a>&nbsp;&nbsp;&nbsp;
                        <a href="manageCardMatching.php" class="non-style-link">
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
