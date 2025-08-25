<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/animations.css">  
    <link rel="stylesheet" href="../css/main.css">  
    <link rel="stylesheet" href="../css/admin.css">
    <title>Patients</title>
    <style>
        .popup {
            animation: transitionIn-Y-bottom 0.5s;
        }
        .sub-table {
            animation: transitionIn-Y-bottom 0.5s;
        }
    </style>
</head>
<body>

<?php
session_start();
if(isset($_SESSION["user"])) {
    if(($_SESSION["user"])=="" || $_SESSION['usertype']!='d') {
        header("location: ../login.php");
    } else {
        $useremail = $_SESSION["user"];
    }
} else {
    header("location: ../login.php");
}

include("../connection.php");

$userrow = $database->query("SELECT * FROM doctor WHERE docemail='$useremail'");
$userfetch = $userrow->fetch_assoc();
$userid = $userfetch["docid"];
$username = $userfetch["docname"];

$selecttype = "My";
$current = "My patients Only";

if ($_POST) {
    if (isset($_POST["search"])) {
        $keyword = $_POST["search12"];
        $sqlmain = "SELECT DISTINCT patient.pid, patient.pname, patient.pemail, patient.pnic, patient.pdob, patient.ptel 
                    FROM appointment 
                    INNER JOIN patient ON patient.pid = appointment.pid 
                    INNER JOIN schedule ON schedule.scheduleid = appointment.scheduleid 
                    WHERE schedule.docid = $userid 
                    AND (patient.pemail LIKE '%$keyword%' OR patient.pname LIKE '%$keyword%')";
    } else {
        $sqlmain = "SELECT DISTINCT patient.pid, patient.pname, patient.pemail, patient.pnic, patient.pdob, patient.ptel 
                    FROM appointment 
                    INNER JOIN patient ON patient.pid = appointment.pid 
                    INNER JOIN schedule ON schedule.scheduleid = appointment.scheduleid 
                    WHERE schedule.docid = $userid";
    }
} else {
    $sqlmain = "SELECT DISTINCT patient.pid, patient.pname, patient.pemail, patient.pnic, patient.pdob, patient.ptel 
                FROM appointment 
                INNER JOIN patient ON patient.pid = appointment.pid 
                INNER JOIN schedule ON schedule.scheduleid = appointment.scheduleid 
                WHERE schedule.docid = $userid";
}
?>

<div class="container">
    <?php include(__DIR__ . '/doctorMenu.php'); ?>

    <div class="dash-body">
        <table border="0" width="100%" style="border-spacing: 0; margin-top: 25px;">
            <tr>
                <td width="13%">
                    <a href="index.php">
                        <button class="login-btn btn-primary-soft btn btn-icon-back" style="padding: 11px; margin-left: 20px; width: 125px;">
                            <font class="tn-in-text">Back</font>
                        </button>
                    </a>
                </td>
                <td>
                    <form action="" method="post" class="header-search">
                        <input type="search" name="search12" class="input-text header-searchbar" placeholder="Search Patient name or Email" list="patient">&nbsp;&nbsp;
                        <?php
                        echo '<datalist id="patient">';
                        $list11 = $database->query($sqlmain);
                        for ($y = 0; $y < $list11->num_rows; $y++) {
                            $row00 = $list11->fetch_assoc();
                            $d = $row00["pname"];
                            $c = $row00["pemail"];
                            echo "<option value='$d'><br/>";
                            echo "<option value='$c'><br/>";
                        }
                        echo '</datalist>';
                        ?>
                        <input type="submit" value="Search" name="search" class="login-btn btn-primary btn" style="padding: 10px 25px;">
                    </form>
                </td>
                <td width="15%">
                    <p style="font-size: 14px; color: rgb(119, 119, 119); padding: 0; margin: 0; text-align: right;">Today's Date</p>
                    <p class="heading-sub12" style="padding: 0; margin: 0;">
                        <?php
                        date_default_timezone_set('Asia/Singapore');
                        echo date('Y-m-d');
                        ?>
                    </p>
                </td>
                <td width="10%">
                    <button class="btn-label" style="display: flex; justify-content: center; align-items: center;">
                        <img src="../img/calendar.svg" width="100%">
                    </button>
                </td>
            </tr>

            <tr>
                <td colspan="4" style="padding-top:10px;">
                    <p class="heading-main12" style="margin-left: 45px; font-size: 18px; color: rgb(49, 49, 49);">
                        <?php echo $selecttype . " Patients (" . $list11->num_rows . ")"; ?>
                    </p>
                </td>
            </tr>

            <tr>
                <td colspan="4" style="padding-top: 0; width: 100%;">
                    <center>
                        <table class="filter-container" border="0">
                            <form action="" method="post">
                                <td style="text-align: right;">Show Details About : &nbsp;</td>
                                <td width="30%">
                                    <select name="showonly" class="box filter-container-items" style="width:90%; height: 37px; margin: 0;">
                                        <option value="my" selected>My Patients Only</option>
                                    </select>
                                </td>
                                <td width="12%">
                                    <input type="submit" name="filter" value="Filter" class="btn-primary-soft btn button-icon btn-filter" style="padding: 15px; margin: 0; width: 100%;">
                                </td>
                            </form>
                        </table>
                    </center>
                </td>
            </tr>

            <tr>
                <td colspan="4">
                    <center>
                        <div class="abc scroll">
                            <table width="93%" class="sub-table scrolldown" style="border-spacing:0;">
                                <thead>
                                    <tr>
                                        <th class="table-headin">Name</th>
                                        <th class="table-headin">NIC</th>
                                        <th class="table-headin">Telephone</th>
                                        <th class="table-headin">Email</th>
                                        <th class="table-headin">Date of Birth</th>
                                        <th class="table-headin">Events</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $result = $database->query($sqlmain);
                                    if ($result->num_rows == 0) {
                                        echo '<tr>
                                                <td colspan="6">
                                                    <br><br><br><br>
                                                    <center>
                                                        <img src="../img/notfound.svg" width="25%">
                                                        <br>
                                                        <p class="heading-main12" style="margin-left: 45px; font-size: 20px; color: rgb(49, 49, 49);">
                                                            We couldn\'t find anything related to your keywords!
                                                        </p>
                                                        <a class="non-style-link" href="patient.php">
                                                            <button class="login-btn btn-primary-soft btn" style="margin-left:20px;">&nbsp; Show all Patients &nbsp;</button>
                                                        </a>
                                                    </center>
                                                    <br><br><br><br>
                                                </td>
                                            </tr>';
                                    } else {
                                        while ($row = $result->fetch_assoc()) {
                                            $pid = $row["pid"];
                                            $name = $row["pname"];
                                            $email = $row["pemail"];
                                            $nic = $row["pnic"];
                                            $dob = $row["pdob"];
                                            $tel = $row["ptel"];

                                            echo '<tr>
                                                    <td>&nbsp;' . substr($name, 0, 35) . '</td>
                                                    <td>' . substr($nic, 0, 12) . '</td>
                                                    <td>' . substr($tel, 0, 10) . '</td>
                                                    <td>' . substr($email, 0, 20) . '</td>
                                                    <td>' . substr($dob, 0, 10) . '</td>
                                                    <td>
                                                        <div style="display:flex;justify-content: center;">
                                                            <a href="?action=view&id=' . $pid . '" class="non-style-link">
                                                                <button class="btn-primary-soft btn button-icon btn-view" style="padding-left: 40px; padding-top: 12px; padding-bottom: 12px; margin-top: 10px;">
                                                                    <font class="tn-in-text">View</font>
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
                    </center>
                </td> 
            </tr>
        </table>
    </div>
</div>

<?php
if ($_GET) {
    $id = $_GET["id"];
    $action = $_GET["action"];

    $sqlmain = "SELECT * FROM patient WHERE pid='$id'";
    $result = $database->query($sqlmain);
    $row = $result->fetch_assoc();

    $name = $row["pname"];
    $email = $row["pemail"];
    $nic = $row["pnic"];
    $dob = $row["pdob"];
    $tele = $row["ptel"];
    $address = $row["paddress"];

    echo '
    <div id="popup1" class="overlay">
        <div class="popup">
            <center>
                <a class="close" href="patient.php">&times;</a>
                <div class="content"></div>
                <div style="display: flex; justify-content: center;">
                    <table width="80%" class="sub-table scrolldown add-doc-form-container" border="0">
                        <tr>
                            <td><p style="padding: 0; margin: 0; text-align: left; font-size: 25px; font-weight: 500;">View Details.</p><br><br></td>
                        </tr>
                        <tr><td class="label-td" colspan="2"><label class="form-label">Patient ID: </label></td></tr>
                        <tr><td class="label-td" colspan="2">P-'.$id.'<br><br></td></tr>
                        <tr><td class="label-td" colspan="2"><label class="form-label">Name: </label></td></tr>
                        <tr><td class="label-td" colspan="2">'.$name.'<br><br></td></tr>
                        <tr><td class="label-td" colspan="2"><label class="form-label">Email: </label></td></tr>
                        <tr><td class="label-td" colspan="2">'.$email.'<br><br></td></tr>
                        <tr><td class="label-td" colspan="2"><label class="form-label">NIC: </label></td></tr>
                        <tr><td class="label-td" colspan="2">'.$nic.'<br><br></td></tr>
                        <tr><td class="label-td" colspan="2"><label class="form-label">Telephone: </label></td></tr>
                        <tr><td class="label-td" colspan="2">'.$tele.'<br><br></td></tr>
                        <tr><td class="label-td" colspan="2"><label class="form-label">Address: </label></td></tr>
                        <tr><td class="label-td" colspan="2">'.$address.'<br><br></td></tr>
                        <tr><td class="label-td" colspan="2"><label class="form-label">Date of Birth: </label></td></tr>
                        <tr><td class="label-td" colspan="2">'.$dob.'<br><br></td></tr>
                        <tr><td colspan="2">
                            <a href="patient.php"><input type="button" value="OK" class="login-btn btn-primary-soft btn"></a>
                        </td></tr>
                    </table>
                </div>
            </center>
            <br><br>
        </div>
    </div>';
}
?>

</body>
</html>
