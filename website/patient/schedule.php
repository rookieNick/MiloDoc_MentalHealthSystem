<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/animations.css">  
    <link rel="stylesheet" href="../css/main.css">  
    <link rel="stylesheet" href="../css/admin.css">
        
    <title>Sessions</title>
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
    $result = $stmt->get_result();
    $userfetch=$result->fetch_assoc();
    $userid= $userfetch["pid"];
    $username=$userfetch["pname"];

    // Check if banned before allowing to book
    $banCheckSQL = "SELECT ban_until FROM patient WHERE pid = ?";
    $stmt = $database->prepare($banCheckSQL);
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $banRes = $stmt->get_result();
    $ban = $banRes->fetch_assoc();

    date_default_timezone_set('Asia/Singapore');
    $now = date('Y-m-d H:i:s');
    if ($ban && $ban['ban_until'] && $ban['ban_until'] > $now) {
        $banTime = date("d M Y, h:i A", strtotime($ban['ban_until']));
        echo "<script>
                alert('You have missed 3 or more appointments.\\nYou are banned from booking new sessions until:\\n$banTime');
                window.location.href = 'index.php'
            </script>";
                exit();
            }
    
    $today = date('Y-m-d');

 ?>
 <div class="container">
    <?php include(__DIR__ . '/patientMenu.php'); ?>
        <?php
            $insertkey = "";
            $q = '';
            $searchtype = "All";

            if ($_POST && !empty($_POST["search"])) {
                $keyword = $_POST["search"];
                $now = date('Y-m-d H:i:s');
                $sqlmain = "
                SELECT schedule.*, doctor.docname 
                FROM schedule 
                INNER JOIN doctor ON schedule.docid = doctor.docid
                WHERE CONCAT(schedule.scheduledate, ' ', schedule.scheduletime) >= ?
                AND (
                    doctor.docname LIKE ? 
                    OR schedule.title LIKE ? 
                    OR schedule.scheduledate LIKE ?
                )
                AND (
                    SELECT COUNT(*) 
                    FROM appointment 
                    WHERE scheduleid = schedule.scheduleid 
                    AND status != 'Cancelled'
                ) < schedule.nop
                ORDER BY schedule.scheduledate ASC, schedule.scheduletime ASC";

                $likeKeyword = "%$keyword%";
                $stmt = $database->prepare($sqlmain);
                $stmt->bind_param("ssss", $now, $likeKeyword, $likeKeyword, $likeKeyword);
                $stmt->execute();
                $result = $stmt->get_result();
                $insertkey = $keyword;
                $searchtype = "Search Result : ";
                $q = '"';

            } else {
                $sqlmain = "
                    SELECT schedule.*, doctor.docname FROM schedule 
                    INNER JOIN doctor ON schedule.docid = doctor.docid
                    WHERE schedule.scheduledate >= ?
                    AND schedule.scheduleid NOT IN (
                        SELECT scheduleid FROM appointment WHERE pid = ? AND status != 'Cancelled'
                    )
                    AND (
                        SELECT COUNT(*) FROM appointment WHERE scheduleid = schedule.scheduleid AND status != 'Cancelled'
                    ) < schedule.nop
                    ORDER BY schedule.scheduledate ASC";

                $stmt = $database->prepare($sqlmain);
                $stmt->bind_param("si", $today, $userid);
                $stmt->execute();
                $result = $stmt->get_result();
            }
        ?>
                  
        <div class="dash-body">
            <table border="0" width="100%" style=" border-spacing: 0;margin:0;padding:0;margin-top:25px; ">
                <tr >
                    <td width="13%" >
                    <a href="index.php" ><button  class="login-btn btn-primary-soft btn btn-icon-back"  style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px"><font class="tn-in-text">Back</font></button></a>
                    </td>
                </td>
                    <td >
                            <form action="" method="post" class="header-search">

                                        <input type="search" name="search" class="input-text header-searchbar" placeholder="Search Doctor name" list="doctors" value="<?php  echo $insertkey ?>">&nbsp;&nbsp;
                                        
                                        <?php
                                            echo '<datalist id="doctors">';
                                            $list11 = $database->query("select DISTINCT * from  doctor;");
                                            $list12 = $database->query("select DISTINCT * from  schedule GROUP BY title;");

                                            for ($y=0;$y<$list11->num_rows;$y++){
                                                $row00=$list11->fetch_assoc();
                                                $d=$row00["docname"];
                                               
                                                echo "<option value='$d'><br/>";
                                               
                                            };

                                            for ($y=0;$y<$list12->num_rows;$y++){
                                                $row00=$list12->fetch_assoc();
                                                $d=$row00["title"];
                                               
                                                echo "<option value='$d'><br/>";
                                            };

                                                echo ' </datalist>';
                                                    ?>
                                        
                                
                                        <input type="Submit" value="Search" class="login-btn btn-primary btn" style="padding-left: 25px;padding-right: 25px;padding-top: 10px;padding-bottom: 10px;">
                                        </form>
                    </td>
                    <td width="15%">
                        <p style="font-size: 14px;color: rgb(119, 119, 119);padding: 0;margin: 0;text-align: right;">
                            Today's Date
                        </p>
                        <p class="heading-sub12" style="padding: 0;margin: 0;">
                            <?php 

                                
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
                        <p class="heading-main12" style="margin-left: 45px;font-size:18px;color:rgb(49, 49, 49)"><?php echo $searchtype." Sessions"."(".$result->num_rows.")"; ?> </p>
                        <p class="heading-main12" style="margin-left: 45px;font-size:22px;color:rgb(49, 49, 49)"><?php echo $q.$insertkey.$q ; ?> </p>
                    </td>
                    
                </tr>
                
                
                
                <tr>
                   <td colspan="4">
                       <center>
                        <div class="abc scroll">
                        <table width="100%" class="sub-table scrolldown" border="0" style="padding: 50px;border:none">
                            
                        <tbody>
                        
                            <?php

                                
                                

                                if($result->num_rows==0){
                                    echo '<tr>
                                    <td colspan="4">
                                    <br><br><br><br>
                                    <center>
                                    <img src="../img/notfound.svg" width="25%">
                                    
                                    <br>
                                    <p class="heading-main12" style="margin-left: 45px;font-size:20px;color:rgb(49, 49, 49)">We  couldnt find anything related to your keywords !</p>
                                    <a class="non-style-link" href="schedule.php"><button  class="login-btn btn-primary-soft btn"  style="display: flex;justify-content: center;align-items: center;margin-left:20px;">&nbsp; Show all Sessions &nbsp;</font></button>
                                    </a>
                                    </center>
                                    <br><br><br><br>
                                    </td>
                                    </tr>';
                                    
                                }
                                else{
                                    //echo $result->num_rows;
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

                                        if($scheduleid==""){
                                            break;
                                        }

                                        echo '
                                        <td style="width: 25%;">
                                                <div  class="dashboard-items search-items"  >
                                                
                                                    <div style="width:100%">
                                                            <div class="h1-search">
                                                                '.substr($title,0,21).'
                                                            </div><br>
                                                            <div class="h3-search">
                                                                '.substr($docname,0,30).'
                                                            </div>
                                                            <div class="h4-search">
                                                                '.$scheduledate.'<br>Starts: <b>@'.substr($scheduletime,0,5).'</b> (24h)
                                                            </div>
                                                            <br>
                                                            <a href="booking.php?id='.$scheduleid.'" ><button  class="login-btn btn-primary-soft btn "  style="padding-top:11px;padding-bottom:11px;width:100%"><font class="tn-in-text">Book Now</font></button></a>
                                                    </div>
                                                            
                                                </div>
                                            </td>';

                                    }
                                    echo "</tr>" ;
                                    
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

    </div>

</body>
</html>
