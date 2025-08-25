<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/animations.css">  
    <link rel="stylesheet" href="../css/main.css">  
    <link rel="stylesheet" href="../css/admin.css">
        
    <title>Doctors</title>
    <style>
        .popup{
            animation: transitionIn-Y-bottom 0.5s;
        }
        .sub-table{
            animation: transitionIn-Y-bottom 0.5s;
        }
        .success-msg {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-left: 5px solid #28a745;
            border-radius: 4px;
        }
        .error-msg {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-left: 5px solid #dc3545;
            border-radius: 4px;
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

    }else{
        header("location: ../login.php");
    }
    
    //import database
    include("../connection.php");

    
    ?>
    <div class="container">
        <?php include(__DIR__ . '/adminMenu.php'); ?>
        <div class="dash-body">
            <table border="0" width="100%" style=" border-spacing: 0;margin:0;padding:0;margin-top:25px; ">
                <tr >
                    <td width="13%">
                        <a href="doctors.php" ><button  class="login-btn btn-primary-soft btn btn-icon-back"  style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px"><font class="tn-in-text">Back</font></button></a>
                    </td>
                    <td>
                        
                        <form action="" method="post" class="header-search">

                            <input type="search" name="search" class="input-text header-searchbar" placeholder="Search Doctor name or Email" list="doctors">&nbsp;&nbsp;
                            
                            <?php
                                echo '<datalist id="doctors">';
                                $list11 = $database->query("select  docname,docemail from  doctor;");

                                for ($y=0;$y<$list11->num_rows;$y++){
                                    $row00=$list11->fetch_assoc();
                                    $d=$row00["docname"];
                                    $c=$row00["docemail"];
                                    echo "<option value='$d'><br/>";
                                    echo "<option value='$c'><br/>";
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
                        date_default_timezone_set('Asia/Singapore');

                        $date = date('Y-m-d');
                        echo $date;
                        ?>
                        </p>
                    </td>
                    <td width="10%">
                        <button  class="btn-label"  style="display: flex;justify-content: center;align-items: center;"><img src="../img/calendar.svg" width="100%"></button>
                    </td>


                </tr>
               
                <tr >
                    <td colspan="2" style="padding-top:30px;">
                        <p class="heading-main12" style="margin-left: 45px;font-size:20px;color:rgb(49, 49, 49)">Add New Doctor</p>
                    </td>
                    <td colspan="2">
                        <a href="?action=add&id=none&error=0" class="non-style-link"><button  class="login-btn btn-primary btn button-icon"  style="display: flex;justify-content: center;align-items: center;margin-left:75px;background-image: url('../img/icons/add.svg');">Add New</font></button>
                            </a></td>
                </tr>
                <tr>
                    <td colspan="4" style="padding-top:10px;">
                        <p class="heading-main12" style="margin-left: 45px;font-size:18px;color:rgb(49, 49, 49)">All Doctors (<?php echo $list11->num_rows; ?>)</p>
                    </td>
                    
                </tr>
                <?php
                    if($_POST){
                        $keyword=$_POST["search"];
                        
                        $sqlmain= "select * from doctor where docemail='$keyword' or docname='$keyword' or docname like '$keyword%' or docname like '%$keyword' or docname like '%$keyword%'";
                    }else{
                        $sqlmain= "select * from doctor order by docid desc";

                    }



                ?>
                  
                <tr>
                   <td colspan="4">
                       <center>
                        <div class="abc scroll">
                        <table width="93%" class="sub-table scrolldown" border="0">
                        <thead>
                        <tr>
                                <th class="table-headin">
                                    
                                
                                Doctor Name
                                
                                </th>
                                <th class="table-headin">
                                    Email
                                </th>
                                <th class="table-headin">
                                    
                                    Specialties
                                    
                                </th>
                                <th class="table-headin">
                                    
                                    Events
                                    
                                </tr>
                        </thead>
                        <tbody>
                        
                            <?php
                                $result= $database->query($sqlmain);

                                if($result->num_rows==0){
                                    echo '<tr>
                                    <td colspan="4">
                                    <br><br><br><br>
                                    <center>
                                    <img src="../img/notfound.svg" width="25%">
                                    
                                    <br>
                                    <p class="heading-main12" style="margin-left: 45px;font-size:20px;color:rgb(49, 49, 49)">We  couldnt find anything related to your keywords !</p>
                                    <a class="non-style-link" href="doctors.php"><button  class="login-btn btn-primary-soft btn"  style="display: flex;justify-content: center;align-items: center;margin-left:20px;">&nbsp; Show all Doctors &nbsp;</font></button>
                                    </a>
                                    </center>
                                    <br><br><br><br>
                                    </td>
                                    </tr>';
                                    
                                }
                                else{
                                for ( $x=0; $x<$result->num_rows;$x++){
                                    $row=$result->fetch_assoc();
                                    $docid=$row["docid"];
                                    $name=$row["docname"];
                                    $email=$row["docemail"];
                                    $spe=$row["specialties"];
                                    $spcil_res= $database->query("select sname from specialties where id='$spe'");
                                    $spcil_array= $spcil_res->fetch_assoc();
                                    $spcil_name=$spcil_array["sname"];
                                    echo '<tr>
                                        <td> &nbsp;'.
                                        substr($name,0,30)
                                        .'</td>
                                        <td>
                                        '.substr($email,0,50).'
                                        </td>
                                        <td>
                                            '.substr($spcil_name,0,40).'
                                        </td>

                                        <td>
                                        <div style="display:flex;justify-content: center;">
                                        <a href="?action=edit&id='.$docid.'&error=0" class="non-style-link"><button  class="btn-primary-soft btn button-icon btn-edit"  style="padding-left: 40px;padding-top: 12px;padding-bottom: 12px;margin-top: 10px;"><font class="tn-in-text">Edit</font></button></a>
                                        &nbsp;&nbsp;&nbsp;
                                        <a href="?action=view&id='.$docid.'" class="non-style-link"><button  class="btn-primary-soft btn button-icon btn-view"  style="padding-left: 40px;padding-top: 12px;padding-bottom: 12px;margin-top: 10px;"><font class="tn-in-text">View</font></button></a>
                                       &nbsp;&nbsp;&nbsp;
                                       <a href="?action=drop&id='.$docid.'&name='.$name.'" class="non-style-link"><button  class="btn-primary-soft btn button-icon btn-delete"  style="padding-left: 40px;padding-top: 12px;padding-bottom: 12px;margin-top: 10px;"><font class="tn-in-text">Remove</font></button></a>
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
    if($_GET){
        
        $id=$_GET["id"];
        $action=$_GET["action"];
        if($action=='drop'){
            $nameget=$_GET["name"];
            echo '
            <div id="popup1" class="overlay">
                    <div class="popup">
                    <center>
                        <h2>Are you sure?</h2>
                        <a class="close" href="doctors.php">&times;</a>
                        <div class="content">
                            You want to delete this record<br>('.substr($nameget,0,40).').
                            
                        </div>
                        <div style="display: flex;justify-content: center;">
                        <a href="delete-doctor.php?id='.$id.'" class="non-style-link"><button  class="btn-primary btn"  style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;"<font class="tn-in-text">&nbsp;Yes&nbsp;</font></button></a>&nbsp;&nbsp;&nbsp;
                        <a href="doctors.php" class="non-style-link"><button  class="btn-primary btn"  style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;"><font class="tn-in-text">&nbsp;&nbsp;No&nbsp;&nbsp;</font></button></a>

                        </div>
                    </center>
            </div>
            </div>
            ';
        }elseif($action=='view'){
            $sqlmain= "select * from doctor where docid='$id'";
            $result= $database->query($sqlmain);
            $row=$result->fetch_assoc();
            $name=$row["docname"];
            $email=$row["docemail"];
            $spe=$row["specialties"];
            
            $spcil_res= $database->query("select sname from specialties where id='$spe'");
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
        }elseif($action=='add'){
                $error_1=$_GET["error"];
                $errorlist= array(
                    '1'=>'<label for="promter" class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Already have an account for this Email address.</label>',
                    '2'=>'<label for="promter" class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Password Conformation Error! Reconform Password</label>',
                    '3'=>'<label for="promter" class="form-label" style="color:rgb(255, 62, 62);text-align:center;"></label>',
                    '4'=>"",
                    '0'=>'',

                );
                if($error_1!='4'){
                echo '
            <div id="popup1" class="overlay">
                    <div class="popup">
                    <center>
                    
                        <a class="close" href="doctors.php">&times;</a> 
                        <div style="display: flex;justify-content: center;">
                        <div class="abc">
                        <table width="80%" class="sub-table scrolldown add-doc-form-container" border="0">
                        <tr>
                                <td class="label-td" colspan="2">'.
                                    $errorlist[$error_1]
                                .'</td>
                            </tr>
                            <tr>
                                <td>
                                    <p style="padding: 0;margin: 0;text-align: left;font-size: 25px;font-weight: 500;">Add New Doctor.</p><br><br>
                                </td>
                            </tr>
                            
                            <tr>
                                <form action="add-new.php" name="addDoctorForm"method="POST" class="add-new-form">
                                <td class="label-td" colspan="2">
                                    <label for="name" class="form-label">Name: </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <input type="text" name="name" class="input-text" placeholder="Doctor Name" required><br>
                                </td>
                                
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <label for="Email" class="form-label">Email: </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <input type="email" name="email" class="input-text" placeholder="Email Address" required><br>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <label for="nic" class="form-label">NIC: </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <input type="text" name="nic" class="input-text" placeholder="NIC Number" required><br>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <label for="Tele" class="form-label">Telephone: </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <input type="tel" name="Tele" class="input-text" placeholder="Telephone Number" required><br>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <label for="spec" class="form-label">Choose specialties: </label>
                                    
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <select name="spec" id="" class="box" >';
                                        
        
                                        $list11 = $database->query("select  * from  specialties order by sname asc;");
        
                                        for ($y=0;$y<$list11->num_rows;$y++){
                                            $row00=$list11->fetch_assoc();
                                            $sn=$row00["sname"];
                                            $id00=$row00["id"];
                                            echo "<option value=".$id00.">$sn</option><br/>";
                                        };
        
        
        
                                        
                        echo     '       </select><br>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <label for="password" class="form-label">Password: </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <input type="password" name="password" class="input-text" placeholder="Defind a Password" required><br>
                                </td>
                            </tr><tr>
                                <td class="label-td" colspan="2">
                                    <label for="cpassword" class="form-label">Conform Password: </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <input type="password" name="cpassword" class="input-text" placeholder="Conform Password" required><br>
                                </td>
                            </tr>
                            
                
                            <tr>
                                <td colspan="2">
                                    <input type="reset" value="Reset" class="login-btn btn-primary-soft btn" >&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                
                                    <input type="submit" value="Add" class="login-btn btn-primary btn">
                                </td>
                
                            </tr>
                           
                            </form>
                            </tr>
                        </table>
                        </div>
                        </div>
                    </center>
                    <br><br>
            </div>
            </div>
            ';

            }else{
                echo '
                    <div id="popup1" class="overlay">
                            <div class="popup">
                            <center>
                            <br><br><br><br>
                                <h2>New Record Added Successfully!</h2>
                                <a class="close" href="doctors.php">&times;</a>
                                <div class="content">
                                    
                                    
                                </div>
                                <div style="display: flex;justify-content: center;">
                                
                                <a href="doctors.php" class="non-style-link"><button  class="btn-primary btn"  style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;"><font class="tn-in-text">&nbsp;&nbsp;OK&nbsp;&nbsp;</font></button></a>

                                </div>
                                <br><br>
                            </center>
                    </div>
                    </div>
            ';
        }
        }elseif($action=='edit'){
            $sqlmain = "SELECT * FROM doctor WHERE docid='$id'";
            $result = $database->query($sqlmain);
            $row = $result->fetch_assoc();
            $name = $row["docname"];
            $email = $row["docemail"];
            $spe = $row["specialties"];
            $spcil_res = $database->query("SELECT sname FROM specialties WHERE id='$spe'");
            $spcil_array = $spcil_res->fetch_assoc();
            $spcil_name = $spcil_array["sname"];
            $nic = $row['docnic'];
            $tele = $row['doctel'];
            
            $message = '';
            if (isset($_GET['error']) && ($_GET['error'] !== '0' || (isset($_GET['updated']) && $_GET['updated'] === 'true'))) {
                $error = $_GET['error'];
                $class = ($error === '0') ? 'success-msg' : 'error-msg';

                switch ($error) {
                    case '0': $text = 'Doctor details updated successfully.'; break;
                    case '1': $text = 'Please fill in all required fields.'; break;
                    case '2': $text = 'Passwords do not match.'; break;
                    case '3': $text = 'No form data received.'; break;
                    case '4': $text = 'Invalid name.'; break;
                    case '5': $text = 'Invalid NIC format.'; break;
                    case '6': $text = 'Invalid telephone number.'; break;
                    case '7': $text = 'Invalid email format.'; break;
                    case '8': $text = 'Password must be at least 6 characters.'; break;
                    case '9': $text = 'New email already exists in the system.'; break;
                    case '10': $text = 'Email must be a valid Gmail address.'; break;
                    default: $text = 'An unknown error occurred.';
                }

                $message = "<div class='$class' style='margin:10px 0;'>$text</div>";
            }

            echo '
            <div id="popup1" class="overlay">
                <div class="popup">
                    <center>
                        <a class="close" href="doctors.php">&times;</a> 
                        <div style="display: flex;justify-content: center;">
                            <div class="abc">
                                <form action="edit-doc.php" name="editDoctorForm" method="POST" class="add-new-form" id="editDoctorForm">
                                    <input type="hidden" name="id00" value="'.$id.'">
                                    <table width="80%" class="sub-table scrolldown add-doc-form-container" border="0">
                                        <tr>
                                            <td>
                                                <p style="padding: 0;margin: 0;text-align: left;font-size: 25px;font-weight: 500;">Edit Doctor Details</p>
                                                Doctor ID : '.$id.'<br><br>
                                                '.$message.'
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="label-td" colspan="2">
                                                <label for="email" class="form-label">Email: </label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="label-td" colspan="2">
                                                <input type="email" name="email" class="input-text" value="'.$email.'" pattern="[a-zA-Z0-9._%+-]+@gmail\.com" readonly><br>
                                            </td>
                                        </tr>
            
                                        <tr>
                                            <td class="label-td" colspan="2">
                                                <label for="name" class="form-label">Name: </label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="label-td" colspan="2">
                                                <input type="text" name="name" class="input-text" value="'.$name.'" required><br>
                                            </td>
                                        </tr>
            
                                        <tr>
                                            <td class="label-td" colspan="2">
                                                <label for="nic" class="form-label">NIC: </label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="label-td" colspan="2">
                                                <input type="text" name="nic" class="input-text" value="'.$nic.'" required><br>
                                            </td>
                                        </tr>
            
                                        <tr>
                                            <td class="label-td" colspan="2">
                                                <label for="Tele" class="form-label">Telephone: </label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="label-td" colspan="2">
                                                <input type="tel" name="Tele" class="input-text" value="'.$tele.'" required><br>
                                            </td>
                                        </tr>
            
                                        <tr>
                                            <td class="label-td" colspan="2">
                                                <label for="spec" class="form-label">Choose Specialties: (Current: '.$spcil_name.')</label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="label-td" colspan="2">
                                                <select name="spec" class="box">
            ';
            $list11 = $database->query("SELECT * FROM specialties");
            while ($row_spec = $list11->fetch_assoc()) {
                $selected = ($row_spec['id'] == $spe) ? "selected" : "";
                echo '<option value="'.$row_spec['id'].'" '.$selected.'>'.$row_spec['sname'].'</option>';
            }
            echo '
                                                </select><br><br>
                                            </td>
                                        </tr>
            
                                        <tr>
                                            <td class="label-td" colspan="2">
                                                <label for="password" class="form-label">New Password (leave unchanged if not updating):</label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="label-td" colspan="2">
                                                <input type="password" name="password" id="passwordField" class="input-text" placeholder="Enter new password">
                                            </td>
                                        </tr>
            
                                        <tr id="confirmPasswordRow" style="display:none;">
                                            <td class="label-td" colspan="2">
                                                <label for="cpassword" class="form-label">Confirm Password:</label>
                                            </td>
                                        </tr>
                                        <tr id="confirmPasswordInputRow" style="display:none;">
                                            <td class="label-td" colspan="2">
                                                <input type="password" name="cpassword" id="confirmPasswordField" class="input-text" placeholder="Confirm new password">
                                            </td>
                                        </tr>
            
                                        <tr>
                                            <td colspan="2">
                                                <input type="reset" value="Reset" class="login-btn btn-primary-soft btn" >&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                <input type="submit" value="Save Changes" class="login-btn btn-primary btn">
                                            </td>
                                        </tr>
                                    </table>
                                </form>
                            </div>
                        </div>
                    </center>
                    <br><br>
                </div>
            </div>
            
            <script>
            // Password logic: show confirm password only if password was changed
            document.getElementById("passwordField").addEventListener("input", function() {
                let pwValue = this.value.trim();
                if (pwValue.length > 0) {
                    document.getElementById("confirmPasswordRow").style.display = "table-row";
                    document.getElementById("confirmPasswordInputRow").style.display = "table-row";
                } else {
                    document.getElementById("confirmPasswordRow").style.display = "none";
                    document.getElementById("confirmPasswordInputRow").style.display = "none";
                    document.getElementById("confirmPasswordField").value = "";
                }
            });
            </script>
            ';
        }else{
            echo '
                <div id="popup1" class="overlay">
                        <div class="popup">
                        <center>
                        <br><br><br><br>
                            <h2>Edit Successfully!</h2>
                            <a class="close" href="doctors.php">&times;</a>
                            <div class="content">
                                
                                
                            </div>
                            <div style="display: flex;justify-content: center;">
                            
                            <a href="doctors.php" class="non-style-link"><button  class="btn-primary btn"  style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;"><font class="tn-in-text">&nbsp;&nbsp;OK&nbsp;&nbsp;</font></button></a>

                            </div>
                            <br><br>
                        </center>
                </div>
                </div>
    ';



        }; 
    };

?>
</div>

</body>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const editForm = document.forms['editDoctorForm']; 
        if (!editForm) return;

        const passwordField = editForm['password'];
        const confirmPasswordField = editForm['cpassword'];

        const fields = [
            { name: 'name', regex: /^[A-Za-z\s]{2,50}$/, message: 'Name must be 2-50 letters only.' },
            { name: 'nic', regex: /^[0-9]{12}$/, message: 'NIC must be exactly 12 digits.' },
            { name: 'Tele', regex: /^01\d{8,9}$/, message: 'Phone number must start with 01 and be 10 or 11 digits.' },
            { name: 'email', regex: /^[a-zA-Z0-9._%+-]+@gmail\.com$/, message: 'Email must be a valid Gmail address (e.g., user@gmail.com).' }
        ];

        fields.forEach(field => {
            const input = editForm[field.name];
            if (input) {
                input.addEventListener('input', function () {
                    validateField(input, field.regex, field.message);
                });
            }
        });

        passwordField.addEventListener('input', function () {
            if (passwordField.value.length > 0) {
                confirmPasswordField.disabled = false;
            } else {
                confirmPasswordField.disabled = true;
                confirmPasswordField.value = '';
                clearError(confirmPasswordField);
            }
            validatePasswordStrength(passwordField);
            validatePasswords();
        });

        confirmPasswordField.addEventListener('input', validatePasswords);

        function validateField(input, regex, errorMessage) {
            const errorContainer = getErrorContainer(input);
            if (!regex.test(input.value.trim())) {
                showError(input, errorMessage);
                return false;
            } else {
                clearError(input);
                return true;
            }
        }

        function validatePasswordStrength(input) {
            const strongPasswordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
            const errorContainer = getErrorContainer(input);

            if (!strongPasswordRegex.test(input.value.trim())) {
                showError(input, 'Password must be at least 8 characters, include uppercase, lowercase, number, and special character.');
                return false;
            } else {
                clearError(input);
                return true;
            }
        }

        function validatePasswords() {
            const errorContainer = getErrorContainer(confirmPasswordField);
            if (passwordField.value !== confirmPasswordField.value) {
                showError(confirmPasswordField, 'Passwords do not match.');
                return false;
            } else {
                clearError(confirmPasswordField);
                return true;
            }
        }

        function getErrorContainer(input) {
            let error = input.nextElementSibling;
            if (!error || !error.classList.contains('error-message')) {
                error = document.createElement('div');
                error.className = 'error-message';
                error.style.color = 'red';
                input.parentNode.insertBefore(error, input.nextSibling);
            }
            return error;
        }

        function showError(input, message) {
            const errorContainer = getErrorContainer(input);
            errorContainer.textContent = message;
            input.style.borderColor = 'red';
        }

        function clearError(input) {
            const errorContainer = getErrorContainer(input);
            errorContainer.textContent = '';
            input.style.borderColor = '';
        }

        editForm.addEventListener('submit', function (e) {
            let valid = true;
            
            // validate all normal fields
            fields.forEach(field => {
                const input = editForm[field.name];
                if (input && !validateField(input, field.regex, field.message)) valid = false;
            });

            // validate password strength and matching
            if (passwordField.value.length > 0) {
                if (!validatePasswordStrength(passwordField)) valid = false;
                if (!validatePasswords()) valid = false;
            }

            if (!valid) {
                e.preventDefault();
                alert('Please fix all errors before submitting.');
            }
        });

        // Initially disable Confirm Password
        confirmPasswordField.disabled = true;
    });

    function showEditDoctorMessage(message, type = 'success') {
        const messageContainer = document.getElementById('editDoctorMessage');
        if (!messageContainer) return;

        messageContainer.innerHTML = `
            <div style="
                padding: 10px;
                margin-bottom: 15px;
                border-left: 5px solid ${type === 'success' ? '#28a745' : '#dc3545'};
                background-color: ${type === 'success' ? '#d4edda' : '#f8d7da'};
                color: ${type === 'success' ? '#155724' : '#721c24'};
                border-radius: 5px;
            ">
                ${message}
            </div>
        `;
    }

    document.addEventListener('DOMContentLoaded', function () {
        const addForm = document.forms['addDoctorForm'];
        if (!addForm) return;

        const passwordField = addForm['password'];
        const confirmPasswordField = addForm['cpassword'];

        const fields = [
            { name: 'name', regex: /^[A-Za-z\s]{2,50}$/, message: 'Name must be 2-50 letters only.' },
            { name: 'nic', regex: /^[0-9]{12}$/, message: 'NIC must be exactly 12 digits.' },
            { name: 'Tele', regex: /^01\d{8,9}$/, message: 'Phone number must start with 01 and be 10 or 11 digits.' },
            { name: 'email', regex: /^[a-zA-Z0-9._%+-]+@gmail\.com$/, message: 'Email must be a valid Gmail address (e.g., user@gmail.com).' }
        ];

        fields.forEach(field => {
            const input = addForm[field.name];
            if (input) {
                input.addEventListener('input', function () {
                    validateField(input, field.regex, field.message);
                });
            }
        });

        passwordField.addEventListener('input', function () {
            if (passwordField.value.length > 0) {
                confirmPasswordField.disabled = false;
            } else {
                confirmPasswordField.disabled = true;
                confirmPasswordField.value = '';
                clearError(confirmPasswordField);
            }
            validatePasswordStrength(passwordField);
            validatePasswords();
        });

        confirmPasswordField.addEventListener('input', validatePasswords);

        function validateField(input, regex, errorMessage) {
            const errorContainer = getErrorContainer(input);
            if (!regex.test(input.value.trim())) {
                showError(input, errorMessage);
                return false;
            } else {
                clearError(input);
                return true;
            }
        }

        function validatePasswordStrength(input) {
            const strongPasswordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
            const errorContainer = getErrorContainer(input);

            if (!strongPasswordRegex.test(input.value.trim())) {
                showError(input, 'Password must be at least 8 characters, include uppercase, lowercase, number, and special character.');
                return false;
            } else {
                clearError(input);
                return true;
            }
        }

        function validatePasswords() {
            const errorContainer = getErrorContainer(confirmPasswordField);
            if (passwordField.value !== confirmPasswordField.value) {
                showError(confirmPasswordField, 'Passwords do not match.');
                return false;
            } else {
                clearError(confirmPasswordField);
                return true;
            }
        }

        function getErrorContainer(input) {
            let error = input.nextElementSibling;
            if (!error || !error.classList.contains('error-message')) {
                error = document.createElement('div');
                error.className = 'error-message';
                error.style.color = 'red';
                input.parentNode.insertBefore(error, input.nextSibling);
            }
            return error;
        }

        function showError(input, message) {
            const errorContainer = getErrorContainer(input);
            errorContainer.textContent = message;
            input.style.borderColor = 'red';
        }

        function clearError(input) {
            const errorContainer = getErrorContainer(input);
            errorContainer.textContent = '';
            input.style.borderColor = '';
        }

        addForm.addEventListener('submit', function (e) {
                let valid = true;

                fields.forEach(field => {
                    const input = addForm[field.name];
                    if (input && !validateField(input, field.regex, field.message)) valid = false;
                });

                if (passwordField.value.length > 0) {
                    if (!validatePasswordStrength(passwordField)) valid = false;
                    if (!validatePasswords()) valid = false;
                }

                if (!valid) {
                    e.preventDefault();
                    alert('Please fix all errors before submitting.');
                }
            });

            // Initially disable Confirm Password
            confirmPasswordField.disabled = true;
        });
</script>
</html>