<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/animations.css">  
    <link rel="stylesheet" href="../css/main.css">  
    <link rel="stylesheet" href="../css/admin.css">
        
    <title>Appointments</title>
    <style>
        .popup{
            animation: transitionIn-Y-bottom 0.5s;
        }
        .sub-table{
            animation: transitionIn-Y-bottom 0.5s;
        }
</style>
</head>
<body>
<?php

    session_start();

    if(isset($_SESSION["user"])){
        if(($_SESSION["user"])=="" or $_SESSION['usertype']!='p'){
            header("location: ../login.php");
        }else{
            $useremail=$_SESSION["user"];
        }

    }else{
        header("location: ../login.php");
    }


    //import database
    include("../connection.php");
    $sqlmain= "select * from patient where pemail=?";
    $stmt = $database->prepare($sqlmain);
    $stmt->bind_param("s",$useremail);
    $stmt->execute();
    $userrow = $stmt->get_result();
    $userfetch=$userrow->fetch_assoc();
    $userid= $userfetch["pid"];
    $username=$userfetch["pname"];


    //Auto update appointment status based on date
    date_default_timezone_set('Asia/Singapore');
    $today = date('Y-m-d H:i:s');

    $statusUpdateSQL = "
        UPDATE appointment 
        INNER JOIN schedule ON appointment.scheduleid = schedule.scheduleid
        SET appointment.status = 
            CASE
                WHEN appointment.status = 'Completed' THEN 'Completed'
                WHEN appointment.status = 'Cancelled' THEN 'Cancelled'
                WHEN CONCAT(schedule.scheduledate, ' ', schedule.scheduletime) < ? THEN 'Missed'
                ELSE 'Pending'
            END
        WHERE appointment.pid = ?
    ";

    $stmtStatus = $database->prepare($statusUpdateSQL);
    $stmtStatus->bind_param("si", $today, $userid);
    $stmtStatus->execute();

    // 1. Get current missed appointments from the appointment table
    $getTotalMissedSQL = "SELECT COUNT(*) AS total_missed FROM appointment WHERE pid = ? AND status = 'Missed'";
    $stmt = $database->prepare($getTotalMissedSQL);
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $totalMissed = $row['total_missed'];

    // 2. Get patient's already recorded missed_count and ban_until
    $getPatientSQL = "SELECT missed_count, ban_until FROM patient WHERE pid = ?";
    $stmt = $database->prepare($getPatientSQL);
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $recordedMissed = $row['missed_count'];
    $banUntil = $row['ban_until'];
    $now = date('Y-m-d H:i:s');

    // 3. Calculate new misses since last ban check
    $newMisses = $totalMissed - $recordedMissed;

    // 4. If new misses >= 3, apply ban for 7 more days and reset missed_count
    if ($newMisses >= 3) {
        $nextBanDate = max($banUntil, $now); // extend if already banned
        $newBanUntil = date('Y-m-d H:i:s', strtotime($nextBanDate . ' +7 days'));

        // Update missed_count to totalMissed and apply new ban
        $updateSQL = "UPDATE patient SET missed_count = ?, ban_until = ? WHERE pid = ?";
        $stmt = $database->prepare($updateSQL);
        $stmt->bind_param("ssi", $totalMissed, $newBanUntil, $userid);
        $stmt->execute();
    } else {
        // Just update missed_count if no ban needed
        $updateSQL = "UPDATE patient SET missed_count = ? WHERE pid = ?";
        $stmt = $database->prepare($updateSQL);
        $stmt->bind_param("ii", $totalMissed, $userid);
        $stmt->execute();
    }




    $sqlmain= "select appointment.appoid, schedule.scheduleid, schedule.title, doctor.docname, patient.pname, schedule.scheduledate, schedule.scheduletime, appointment.apponum, appointment.appodate, appointment.status 
            from schedule 
            inner join appointment on schedule.scheduleid=appointment.scheduleid 
            inner join patient on patient.pid=appointment.pid 
            inner join doctor on schedule.docid=doctor.docid  
            where  patient.pid=$userid ";

    if($_POST){
        if(!empty($_POST["sheduledate"])){
            $sheduledate=$_POST["sheduledate"];
            $sqlmain.=" and schedule.scheduledate='$sheduledate' ";
        };
    }

    $sqlmain.="order by appointment.appodate asc";
    $result= $database->query($sqlmain);
    ?>

    <div class="container">
        <?php include(__DIR__ . '/patientMenu.php'); ?>
        <div class="dash-body">
            <table border="0" width="100%" style=" border-spacing: 0;margin:0;padding:0;margin-top:25px; ">
                <tr >
                    <td width="13%" >
                    <a href="index.php" ><button  class="login-btn btn-primary-soft btn btn-icon-back"  style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px"><font class="tn-in-text">Back</font></button></a>
                    </td>
                    <td>
                        <p style="font-size: 23px;padding-left:12px;font-weight: 600;">My Bookings history</p>
                                           
                    </td>
                    <td width="15%">
                        <p style="font-size: 14px;color: rgb(119, 119, 119);padding: 0;margin: 0;text-align: right;">
                            Today's Date
                        </p>
                        <p class="heading-sub12" style="padding: 0;margin: 0;">
                            <?php 

                        date_default_timezone_set('Asia/Singapore');

                        $today = date('Y-m-d');
                        echo $today;

                        
                        ?>
                        </p>
                    </td>
                    <td width="10%">
                        <button  class="btn-label"  style="display: flex;justify-content: center;align-items: center;"><img src="../img/calendar.svg" width="100%"></button>
                    </td>


                </tr>
               
                <tr>
                    <td colspan="4" style="padding-top:10px;width: 100%;" >
                    
                        <p class="heading-main12" style="margin-left: 45px;font-size:18px;color:rgb(49, 49, 49)">My Bookings (<?php echo $result->num_rows; ?>)</p>
                    </td>
                    
                </tr>
                <tr>
                    <td colspan="4" style="padding-top:0px;width: 100%;" >
                        <center>
                        <table class="filter-container" border="0" >
                        <tr>
                           <td width="10%">

                           </td> 
                        <td width="5%" style="text-align: center;">
                        Date:
                        </td>
                        <td width="30%">
                        <form action="" method="post">
                            
                            <input type="date" name="sheduledate" id="date" class="input-text filter-container-items" style="margin: 0;width: 95%;">

                        </td>
                        
                    <td width="12%">
                        <input type="submit"  name="filter" value=" Filter" class=" btn-primary-soft btn button-icon btn-filter"  style="padding: 15px; margin :0;width:100%">
                        </form>
                    </td>

                    </tr>
                            </table>

                        </center>
                    </td>
                    
                </tr>
                
               
                  
                <tr>
                   <td colspan="4">
                       <center>
                        <div class="abc scroll">
                        <table width="93%" class="sub-table scrolldown" border="0" style="border:none">
                        
                        <tbody>
                        
                            <?php

                                if($result->num_rows==0){
                                    echo '<tr>
                                    <td colspan="7">
                                    <br><br><br><br>
                                    <center>
                                    <img src="../img/notfound.svg" width="25%">
                                    
                                    <br>
                                    <p class="heading-main12" style="margin-left: 45px;font-size:20px;color:rgb(49, 49, 49)">We  couldnt find anything related to your keywords !</p>
                                    <a class="non-style-link" href="appointment.php"><button  class="login-btn btn-primary-soft btn"  style="display: flex;justify-content: center;align-items: center;margin-left:20px;">&nbsp; Show all Appointments &nbsp;</font></button>
                                    </a>
                                    </center>
                                    <br><br><br><br>
                                    </td>
                                    </tr>';
                                    
                                }
                                else{

                                    for ( $x=0; $x<($result->num_rows);$x++){
                                        echo "<tr>";
                                        for($q=0;$q<3;$q++){
                                            $row=$result->fetch_assoc();
                                            if (!isset($row)){
                                            break;
                                            };
                                            $scheduleid=$row["scheduleid"];
                                            $title=$row["title"];
                                            $docname=$row["docname"];
                                            $scheduledate=$row["scheduledate"];
                                            $scheduletime=$row["scheduletime"];
                                            $apponum=$row["apponum"];
                                            $appodate=$row["appodate"];
                                            $appoid=$row["appoid"];
                                            $status=$row["status"]; 
    
                                            if($scheduleid==""){
                                                break;
                                            }
    
                                            echo '
                                            <td style="width: 25%;">
                                                <div class="dashboard-items search-items">
                                                    <div style="width:100%;">
                                                        <div class="h3-search">
                                                            Booking Date: '.substr($appodate,0,30).'<br>
                                                            Reference Number: OC-000-'.$appoid.'
                                                        </div>
                                                        <div class="h1-search">'.substr($title,0,21).'<br></div>
                                                        <div class="h3-search">Appointment Number:<div class="h1-search">0'.$apponum.'</div></div>
                                                        <div class="h3-search">'.substr($docname,0,30).'</div>
                                                        <div class="h4-search">
                                                            Scheduled Date: '.$scheduledate.'<br>Starts: <b>@'.substr($scheduletime,0,5).'</b> (24h)
                                                        </div>
                                                        <div class="h4-search">
                                                            Status: <b style="color:'.
                                                                ($status=="Completed" ? "green" : 
                                                                ($status=="Missed" ? "Gray" :
                                                                ($status=="Cancelled" ? "red" : "orange")))
                                                            .'">'.$status.'</b>
                                                        </div>
                                                        <br>';
                                                        
                                            if ($status == "Pending") {
                                                echo '
                                                <a href="?action=drop&id='.$appoid.'&title='.$title.'&doc='.$docname.'">
                                                    <button class="login-btn btn-primary-soft btn" style="padding-top:11px;padding-bottom:11px;width:100%">
                                                        <font class="tn-in-text">Cancel Booking</font>
                                                    </button>
                                                </a>';
                                            } else {
                                                echo '
                                                <a href="?action=reschedule&id='.$appoid.'&title='.$title.'&doc='.$docname.'&scheduleid='.$scheduleid.'">
                                                    <button class="login-btn btn-primary-soft btn" style="padding-top:11px;padding-bottom:11px;width:100%">
                                                        <font class="tn-in-text">Reschedule</font>
                                                    </button>
                                                </a>';
                                            }
                                            
                                            echo '
                                                    </div>
                                                </div>
                                            </td>';
                                            
    
                                        }
                                        echo "</tr>";
                                    
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
    
    if($_GET){
        $id=$_GET["id"];
        $action=$_GET["action"];
        if($action=='booking-added'){
            
            echo '
            <div id="popup1" class="overlay">
                    <div class="popup">
                    <center>
                    <br><br>
                        <h2>Booking Successfully.</h2>
                        <a class="close" href="appointment.php">&times;</a>
                        <div class="content">
                        Your Appointment number is '.$id.'.<br><br>
                            
                        </div>
                        <div style="display: flex;justify-content: center;">
                        
                        <a href="appointment.php" class="non-style-link"><button  class="btn-primary btn"  style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;"><font class="tn-in-text">&nbsp;&nbsp;OK&nbsp;&nbsp;</font></button></a>
                        <br><br><br><br>
                        </div>
                    </center>
            </div>
            </div>
            ';
        }elseif($action=='drop'){
            $title=$_GET["title"];
            $docname=$_GET["doc"];
            
            echo '
            <div id="popup1" class="overlay">
                    <div class="popup">
                    <center>
                        <h2>Are you sure?</h2>
                        <a class="close" href="appointment.php">&times;</a>
                        <div class="content">
                            You want to Cancel this Appointment?<br><br>
                            Session Name: &nbsp;<b>'.substr($title,0,40).'</b><br>
                            Doctor name&nbsp; : <b>'.substr($docname,0,40).'</b><br><br>
                            
                        </div>
                        <div style="display: flex;justify-content: center;">
                        <a href="delete-appointment.php?id='.$id.'" class="non-style-link"><button  class="btn-primary btn"  style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;"<font class="tn-in-text">&nbsp;Yes&nbsp;</font></button></a>&nbsp;&nbsp;&nbsp;
                        <a href="appointment.php" class="non-style-link"><button  class="btn-primary btn"  style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;"><font class="tn-in-text">&nbsp;&nbsp;No&nbsp;&nbsp;</font></button></a>

                        </div>
                    </center>
            </div>
            </div>
            '; 
        }elseif($action=='view'){
            $sqlmain= "select * from doctor where docid=?";
            $stmt = $database->prepare($sqlmain);
            $stmt->bind_param("i",$id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row=$result->fetch_assoc();
            $name=$row["docname"];
            $email=$row["docemail"];
            $spe=$row["specialties"];
            
            $sqlmain= "select sname from specialties where id=?";
            $stmt = $database->prepare($sqlmain);
            $stmt->bind_param("s",$spe);
            $stmt->execute();
            $spcil_res = $stmt->get_result();
            $spcil_array= $spcil_res->fetch_assoc();
            $spcil_name=$spcil_array["sname"];
            $nic=$row['docnic'];
            $tele=$row['doctel'];
            echo '
            <div id="popup1" class="overlay">
                    <div class="popup">
                    <center>
                        <h2></h2>
                        <a class="close" href="doctors.php">&times;</a>
                        <div class="content">
                            eDoc Web App<br>
                            
                        </div>
                        <div style="display: flex;justify-content: center;">
                        <table width="80%" class="sub-table scrolldown add-doc-form-container" border="0">
                        
                            <tr>
                                <td>
                                    <p style="padding: 0;margin: 0;text-align: left;font-size: 25px;font-weight: 500;">View Details.</p><br><br>
                                </td>
                            </tr>
                            
                            <tr>
                                
                                <td class="label-td" colspan="2">
                                    <label for="name" class="form-label">Name: </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    '.$name.'<br><br>
                                </td>
                                
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <label for="Email" class="form-label">Email: </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                '.$email.'<br><br>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <label for="nic" class="form-label">NIC: </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                '.$nic.'<br><br>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <label for="Tele" class="form-label">Telephone: </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                '.$tele.'<br><br>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <label for="spec" class="form-label">Specialties: </label>
                                    
                                </td>
                            </tr>
                            <tr>
                            <td class="label-td" colspan="2">
                            '.$spcil_name.'<br><br>
                            </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <a href="doctors.php"><input type="button" value="OK" class="login-btn btn-primary-soft btn" ></a>
                                
                                    
                                </td>
                
                            </tr>
                           

                        </table>
                        </div>
                    </center>
                    <br><br>
            </div>
            </div>
            ';  
    }elseif($action=='reschedule'){
        $appoid = $_GET["id"];
        $title = $_GET["title"];
        $docname = $_GET["doc"];
        $oldScheduleId = $_GET["scheduleid"];
    
        // Get available schedules
        $today = date('Y-m-d');
        $sql = "
            SELECT s.scheduleid, s.title, d.docname, s.scheduledate, s.scheduletime
            FROM schedule s
            INNER JOIN doctor d ON s.docid = d.docid
            WHERE (
                    s.scheduledate > CURDATE() 
                    OR (s.scheduledate = CURDATE() AND s.scheduletime > CURTIME())
                )
            AND s.docid = (SELECT docid FROM schedule WHERE scheduleid = ?)
            AND (
                SELECT COUNT(*) 
                FROM appointment 
                WHERE scheduleid = s.scheduleid 
                AND status NOT IN ('Cancelled')
            ) < s.nop
            AND s.scheduleid NOT IN (
                SELECT scheduleid 
                FROM appointment 
                WHERE pid = ?
                AND status NOT IN ('Cancelled')
            )
            ORDER BY s.scheduledate ASC, s.scheduletime ASC
        ";
        
        $stmt = $database->prepare($sql);
        $stmt->bind_param("ii", $oldScheduleId, $userid);
        $stmt->execute();
        $options = $stmt->get_result();
    
        echo '
        <div id="popup1" class="overlay">
            <div class="popup">
            <center>
                <h2>Reschedule Appointment</h2>
                <a class="close" href="appointment.php">&times;</a>
                <div class="content">
                    You are rescheduling the following session:<br><br>
                    <b>Title:</b> '.substr($title, 0, 40).'<br>
                    <b>Doctor:</b> '.substr($docname, 0, 40).'<br><br>
                    <form action="reschedule-appointment.php" method="POST">
                        <input type="hidden" name="appoid" value="'.$appoid.'">
                        <label>Select New Session:</label><br>
                        <select name="new_scheduleid" required style="width:90%; padding: 10px; margin-top: 10px;">';
    
                        if ($options->num_rows > 0) {
                            while ($row = $options->fetch_assoc()) {
                                echo '<option value="'.$row["scheduleid"].'">'
                                    .$row["title"].' - '.$row["docname"].' ('.$row["scheduledate"].' @ '.substr($row["scheduletime"], 0, 5).')</option>';
                            }
                        } else {
                            echo '<option disabled>No sessions available</option>';
                        }
    
        echo '
                        </select><br><br>
                        <button type="submit" class="btn-primary btn">Confirm Reschedule</button>
                    </form>
                </div>
            </center>
            </div>
        </div>';
    }    
}

    ?>
    </div>

</body>
</html>
