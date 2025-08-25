<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/animations.css">  
    <link rel="stylesheet" href="../css/main.css">  
    <link rel="stylesheet" href="../css/admin.css">
     
    <title>Settings</title>
    <style>
        .dashbord-tables{
            animation: transitionIn-Y-over 0.5s;
        }
        .filter-container{
            animation: transitionIn-X  0.5s;
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

    //learn from w3schools.com

    session_start();

    if(isset($_SESSION["user"])){
        if(($_SESSION["user"])=="" or $_SESSION['usertype']!='d'){
            header("location: ../login.php");
        }else{
            $useremail=$_SESSION["user"];
        }

    }else{
        header("location: ../login.php");
    }
    

    //import database
    include("../connection.php");
    $userrow = $database->query("select * from doctor where docemail='$useremail'");
    $userfetch=$userrow->fetch_assoc();
    $userid= $userfetch["docid"];
    $username=$userfetch["docname"];

    
    ?>
    <div class="container">
    <?php include(__DIR__ . '/doctorMenu.php'); ?>
        <div class="dash-body" style="margin-top: 15px">
            <table border="0" width="100%" style=" border-spacing: 0;margin:0;padding:0;" >
                        
                        <tr >
                            
                        <td width="13%" >
                    <a href="index.php" ><button  class="login-btn btn-primary-soft btn btn-icon-back"  style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px"><font class="tn-in-text">Back</font></button></a>
                    </td>
                    <td>
                        <p style="font-size: 23px;padding-left:12px;font-weight: 600;">Settings</p>
                                           
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


                                $patientrow = $database->query("select  * from  patient;");
                                $doctorrow = $database->query("select  * from  doctor;");
                                $appointmentrow = $database->query("select  * from  appointment where appodate>='$today';");
                                $schedulerow = $database->query("select  * from  schedule where scheduledate='$today';");


                                ?>
                                </p>
                            </td>
                            <td width="10%">
                                <button  class="btn-label"  style="display: flex;justify-content: center;align-items: center;"><img src="../img/calendar.svg" width="100%"></button>
                            </td>
        
        
                        </tr>
                <tr>
                    <td colspan="4">
                        
                        <center>
                        <table class="filter-container" style="border: none;" border="0">
                            <tr>
                                <td colspan="4">
                                    <p style="font-size: 20px">&nbsp;</p>
                                </td>
                            </tr>
                            <tr>
                                <td style="width: 25%;">
                                    <a href="?action=edit&id=<?php echo $userid ?>&error=0" class="non-style-link">
                                    <div  class="dashboard-items setting-tabs"  style="padding:20px;margin:auto;width:95%;display: flex">
                                        <div class="btn-icon-back dashboard-icons-setting" style="background-image: url('../img/icons/doctors-hover.svg');"></div>
                                        <div>
                                                <div class="h1-dashboard">
                                                    Account Settings  &nbsp;

                                                </div><br>
                                                <div class="h3-dashboard" style="font-size: 15px;">
                                                    Edit your Account Details & Change Password
                                                </div>
                                        </div>
                                                
                                    </div>
                                    </a>
                                </td>
                                
                                
                            </tr>
                            <tr>
                                <td colspan="4">
                                    <p style="font-size: 5px">&nbsp;</p>
                                </td>
                            </tr>
                            <tr>
                            <td style="width: 25%;">
                                    <a href="?action=view&id=<?php echo $userid ?>" class="non-style-link">
                                    <div  class="dashboard-items setting-tabs"  style="padding:20px;margin:auto;width:95%;display: flex;">
                                        <div class="btn-icon-back dashboard-icons-setting " style="background-image: url('../img/icons/view-iceblue.svg');"></div>
                                        <div>
                                                <div class="h1-dashboard" >
                                                    View Account Details
                                                    
                                                </div><br>
                                                <div class="h3-dashboard"  style="font-size: 15px;">
                                                    View Personal information About Your Account
                                                </div>
                                        </div>
                                                
                                    </div>
                                    </a>
                                </td>
                                
                            </tr>
                            <tr>
                                <td colspan="4">
                                    <p style="font-size: 5px">&nbsp;</p>
                                </td>
                            </tr>
                            <tr>
                            <td style="width: 25%;">
                                    <a href="?action=drop&id=<?php echo $userid.'&name='.$username ?>" class="non-style-link">
                                    <div  class="dashboard-items setting-tabs"  style="padding:20px;margin:auto;width:95%;display: flex;">
                                        <div class="btn-icon-back dashboard-icons-setting" style="background-image: url('../img/icons/patients-hover.svg');"></div>
                                        <div>
                                                <div class="h1-dashboard" style="color: #ff5050;">
                                                    Delete Account
                                                    
                                                </div><br>
                                                <div class="h3-dashboard"  style="font-size: 15px;">
                                                    Will Permanently Remove your Account
                                                </div>
                                        </div>
                                                
                                    </div>
                                    </a>
                                </td>
                                
                            </tr>
                        </table>
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
                        <a class="close" href="settings.php">&times;</a>
                        <div class="content">
                            You want to delete this record<br>('.substr($nameget,0,40).').
                            
                        </div>
                        <div style="display: flex;justify-content: center;">
                        <a href="delete-doctor.php?id='.$id.'" class="non-style-link"><button  class="btn-primary btn"  style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;"<font class="tn-in-text">&nbsp;Yes&nbsp;</font></button></a>&nbsp;&nbsp;&nbsp;
                        <a href="settings.php" class="non-style-link"><button  class="btn-primary btn"  style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;"><font class="tn-in-text">&nbsp;&nbsp;No&nbsp;&nbsp;</font></button></a>

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
                        <a class="close" href="settings.php">&times;</a>
                        <div class="content">
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
                                    <a href="settings.php"><input type="button" value="OK" class="login-btn btn-primary-soft btn" ></a>
                                
                                    
                                </td>
                
                            </tr>
                           

                        </table>
                        </div>
                    </center>
                    <br><br>
            </div>
            </div>
            ';
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
                        <a class="close" href="settings.php">&times;</a> 
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
                                                <input type="email" name="email" class="input-text" value="'.$email.'" readonly><br>
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
                            <a class="close" href="settings.php">&times;</a>
                            <div class="content">
                                If You change your email also Please logout and login again with your new email
                                
                            </div>
                            <div style="display: flex;justify-content: center;">
                            
                            <a href="settings.php" class="non-style-link"><button  class="btn-primary btn"  style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;"><font class="tn-in-text">&nbsp;&nbsp;OK&nbsp;&nbsp;</font></button></a>
                            <a href="../logout.php" class="non-style-link"><button  class="btn-primary-soft btn"  style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;"><font class="tn-in-text">&nbsp;&nbsp;Log out&nbsp;&nbsp;</font></button></a>

                            </div>
                            <br><br>
                        </center>
                </div>
                </div>
    ';

        }; }
        ?>

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
            { name: 'Tele', regex: /^01\d{8,9}$/, message: 'Phone number must start with 01 and be 10 or 11 digits.' }
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

</script>

</html>