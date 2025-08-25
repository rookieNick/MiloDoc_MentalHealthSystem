<?php

//learn from w3schools.com

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
        .popup{
            animation: transitionIn-Y-bottom 0.5s;
        }
        .sub-table{
            animation: transitionIn-Y-bottom 0.5s;
        }
        .dashbord-tables{
            animation: transitionIn-Y-over 0.5s;
        }
        .filter-container{
            animation: transitionIn-X  0.5s;
        }
        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #307bb1;
            margin-bottom: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .profile-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 20px;
        }
        .upload-btn {
            margin-top: 10px;
            padding: 8px 16px;
            background-color: #307bb1;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .upload-btn:hover {
            background-color: #245d84;
        }
        #file-input, #camera-input {
            display: none;
        }
        .profile-image-section {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 20px;
        }
        .change-photo-btn, .camera-btn {
            margin-top: 10px;
            padding: 8px 16px;
            background-color: #307bb1;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            font-size: 14px;
            margin-right: 10px;
        }
        .change-photo-btn:hover, .camera-btn:hover {
            background-color: #245d84;
        }
        .profile-image-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #307bb1;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .camera-container {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            z-index: 1000;
        }
        .camera-container video {
            width: 100%;
            max-width: 300px;
            border-radius: 8px;
        }
        .camera-actions {
            display: flex;
            justify-content: center;
            margin-top: 10px;
        }
        .camera-actions button {
            margin: 0 5px;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .capture-btn {
            background-color: #307bb1;
            color: white;
        }
        .capture-btn:hover {
            background-color: #245d84;
        }
        .close-camera-btn {
            background-color: #ff5050;
            color: white;
        }
        .close-camera-btn:hover {
            background-color: #cc4040;
        }
        .error-message {
            color: rgb(255, 62, 62);
            text-align: center;
            margin-bottom: 10px;
        }
        .success-message {
            color: rgb(62, 255, 62);
            text-align: center;
            margin-bottom: 10px;
        }
</style>
</head>
<script>
    function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                
                reader.onload = function(e) {
                    document.getElementById('profileImagePreview').src = e.target.result;
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }

        let stream = null;

        async function openCamera() {
            const cameraContainer = document.getElementById('cameraContainer');
            const video = document.getElementById('cameraFeed');
            
            try {
                stream = await navigator.mediaDevices.getUserMedia({ video: true });
                video.srcObject = stream;
                cameraContainer.style.display = 'block';
            } catch (err) {
                alert('Error accessing camera: ' + err.message);
            }
        }

        function closeCamera() {
            const cameraContainer = document.getElementById('cameraContainer');
            const video = document.getElementById('cameraFeed');
            
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
            }
            video.srcObject = null;
            cameraContainer.style.display = 'none';
        }

        function captureImage() {
            const video = document.getElementById('cameraFeed');
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            const imageDataUrl = canvas.toDataURL('image/png');
            document.getElementById('profileImagePreview').src = imageDataUrl;

            // Convert the captured image to a file for form submission
            canvas.toBlob(function(blob) {
                const file = new File([blob], "captured-image.png", { type: "image/png" });
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                document.getElementById('file-input').files = dataTransfer.files;
            }, 'image/png');

            closeCamera();
        }

    document.addEventListener('DOMContentLoaded', function () {
        const rows = document.querySelectorAll('.patient-row');

        rows.forEach(row => {
            const status = row.dataset.status; // "active" or "suspend"
            const unbanBtn = row.querySelector('.unban-btn');

            if (status === 'active' && unbanBtn) {
                unbanBtn.disabled = true;
                unbanBtn.title = "User is already active";
                unbanBtn.classList.add('disabled-lock');
            }
        });
    });
</script>

<style>
    .disabled-lock {
        opacity: 0.5;
        cursor: not-allowed;
    }
</style>

<body>
    <div class="container">
        <?php include(__DIR__ . '/adminMenu.php'); ?>
        <div class="dash-body">
            <table border="0" width="100%" style=" border-spacing: 0;margin:0;padding:0;margin-top:25px; ">
                <tr >
                    <td width="13%">

                    <a href="patient.php" ><button  class="login-btn btn-primary-soft btn btn-icon-back"  style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px"><font class="tn-in-text">Back</font></button></a>
                        
                    </td>
                    <td>
                        
                        <form action="" method="post" class="header-search">

                            <input type="search" name="search" class="input-text header-searchbar" placeholder="Search Patient name or Email" list="patient">&nbsp;&nbsp;
                            
                            <?php
                                echo '<datalist id="patient">';
                                $list11 = $database->query("select  pname,pemail from patient;");

                                for ($y=0;$y<$list11->num_rows;$y++){
                                    $row00=$list11->fetch_assoc();
                                    $d=$row00["pname"];
                                    $c=$row00["pemail"];
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
                        <p class="heading-main12" style="margin-left: 45px;font-size:20px;color:rgb(49, 49, 49)">Add New Patient</p>
                    </td>
                    <td colspan="2">
                        <a href="?action=add&id=none&error=0" class="non-style-link"><button  class="login-btn btn-primary btn button-icon"  style="display: flex;justify-content: center;align-items: center;margin-left:75px;background-image: url('../img/icons/add.svg');">Add New</font></button>
                            </a></td>
                </tr>
                
                <tr>
                    <td colspan="4" style="padding-top:10px;">
                        <p class="heading-main12" style="margin-left: 45px;font-size:18px;color:rgb(49, 49, 49)">All Patients (<?php echo $list11->num_rows; ?>)</p>
                    </td>
                    
                </tr>
                <?php
                    if($_POST){
                        $keyword=$_POST["search"];
                        
                        $sqlmain= "select * from patient where pemail='$keyword' or pname='$keyword' or pname like '$keyword%' or pname like '%$keyword' or pname like '%$keyword%' ";
                    }else{
                        $sqlmain= "select * from patient order by pid desc";

                    }



                ?>
                  
                <tr>
                   <td colspan="4">
                       <center>
                        <div class="abc scroll">
                        <table width="93%" class="sub-table scrolldown"  style="border-spacing:0;">
                        <thead>
                        <tr>
                                <th class="table-headin">
                                    
                                Name
                                
                                </th>
                                <th class="table-headin">
                                    Email
                                </th>
                                <th class="table-headin">
                                    Ban Status
                                </th>
                                <th class="table-headin">
                                    Ban Until
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
                                    <a class="non-style-link" href="patient.php"><button  class="login-btn btn-primary-soft btn"  style="display: flex;justify-content: center;align-items: center;margin-left:20px;">&nbsp; Show all Patients &nbsp;</font></button>
                                    </a>
                                    </center>
                                    <br><br><br><br>
                                    </td>
                                    </tr>';
                                    
                                }
                                else{
                                for ( $x=0; $x<$result->num_rows;$x++){
                                    $row=$result->fetch_assoc();
                                    $pid=$row["pid"];
                                    $name=$row["pname"];
                                    $email=$row["pemail"];
                                    $nic=$row["pnic"];
                                    $dob=$row["pdob"];
                                    $tel=$row["ptel"];
                                    $status = ($row['ban_until']) ? 'Suspended' : 'Active';
                                    $statusColor = ($status === 'Suspended') ? 'red' : 'green';
                                    $banUntilDisplay = ($row['ban_until']) ? $row['ban_until'] : '-';     
                                    
                                    echo '<tr>
                                        <td> &nbsp;'.
                                        substr($name,0,35)
                                        .'</td>
                                        <td>
                                        '.substr($email,0,30).'
                                         </td>
                                        <td style="color: ' . $statusColor . '; font-weight: bold;">' . $status . '</td>
                                        <td>' . $banUntilDisplay . '</td>
                                        <td >
                                        <div style="display:flex;justify-content: center;">
                                        ' . (
                                            $status === 'Suspended'
                                            ? '<a href="unban-user.php?id=' . $pid . '" class="non-style-link unban-link">
                                                <button class="btn-primary-soft btn" style="padding-left: 40px;padding-top: 12px;padding-bottom: 12px;margin-top: 10px;">
                                                    Unban
                                                </button>
                                            </a>'
                                            : ''
                                        ) . '
                                        <a href="?action=edit&id='.$pid.'&error=0" class="non-style-link"><button  class="btn-primary-soft btn button-icon btn-edit"  style="padding-left: 40px;padding-top: 12px;padding-bottom: 12px;margin-top: 10px;"><font class="tn-in-text">Edit</font></button></a>
                                        <a href="?action=view&id='.$pid.'" class="non-style-link"><button  class="btn-primary-soft btn button-icon btn-view"  style="padding-left: 40px;padding-top: 12px;padding-bottom: 12px;margin-top: 10px;"><font class="tn-in-text">View</font></button></a>
                                       <a href="?action=drop&id='.$pid.'&name='.$name.'" class="non-style-link"><button  class="btn-primary-soft btn button-icon btn-delete"  style="padding-left: 40px;padding-top: 12px;padding-bottom: 12px;margin-top: 10px;"><font class="tn-in-text">Remove</font></button></a>
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
        $id = isset($_GET["id"]) ? $_GET["id"] : null;
        $action = isset($_GET["action"]) ? $_GET["action"] : null;
        
        if ($action == 'drop') {
            // Delete account popup
            $nameget = $_GET["name"];
            echo '
            <div id="popup1" class="overlay">
                    <div class="popup">
                    <center>
                        <h2>Are you sure?</h2>
                        <a class="close" href="patient.php">&times;</a>
                        <div class="content">
                            You want to delete Your Account<br>('.substr($nameget,0,40).').
                        </div>
                        <div style="display: flex;justify-content: center;">
                        <a href="delete-patient.php?id='.$id.'" class="non-style-link"><button class="btn-primary btn" style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;"><font class="tn-in-text">&nbsp;Yes&nbsp;</font></button></a>&nbsp;&nbsp;&nbsp;
                        <a href="patient.php" class="non-style-link"><button class="btn-primary btn" style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;"><font class="tn-in-text">&nbsp;&nbsp;No&nbsp;&nbsp;</font></button></a>
                        </div>
                    </center>
            </div>
            </div>
            ';
        } elseif ($action == 'add') {
            $error_1 = $_GET["error"] ?? '0';
            $class = ($error_1 === '0' || $error_1 === '4') ? 'success-msg' : 'error-msg';
        
            switch ($error_1) {
                case '0': $text = ''; break;
                case '1': $text = 'This email already exists.'; break;
                case '2': $text = 'Password Confirmation Error! Please reconfirm your password.'; break;
                case '3': $text = 'Please fill in all required fields.'; break;
                case '4': $text = 'Patient added successfully!'; break;
                case '5': $text = 'Invalid NIC format.'; break;
                case '6': $text = 'Invalid phone number format.'; break;
                case '7': $text = 'Invalid email format.'; break;
                case '8': $text = 'Password must be at least 6 characters.'; break;
                default: $text = '';
            }
        
            $message = ($text !== '') ? "<div class='$class' style='margin:10px 0;'>$text</div>" : '';
        
            if ($error_1 !== '4') {
                echo '
                <div id="popup1" class="overlay">
                    <div class="popup">
                        <center>
                            <a class="close" href="patient.php">&times;</a> 
                            <div class="abc" style="max-height: 90vh; overflow-y: auto;">
                            <form action="add-patient.php" name="addPatientForm" method="POST" class="add-new-form">
                                <table width="80%" class="sub-table scrolldown add-doc-form-container" border="0">
                                    <tr>
                                        <td colspan="2">
                                            <p style="padding: 0;margin: 0;text-align: left;font-size: 25px;font-weight: 500;">Add New Patient</p>
                                            '.$message.'
                                        </td>
                                    </tr>
                                    <tr><td class="label-td" colspan="2"><label>First Name:</label></td></tr>
                                    <tr><td class="label-td" colspan="2"><input type="text" name="fname" class="input-text" required></td></tr>
                                    <tr><td class="label-td" colspan="2"><label>Last Name:</label></td></tr>
                                    <tr><td class="label-td" colspan="2"><input type="text" name="lname" class="input-text" required></td></tr>
                                    <tr><td class="label-td" colspan="2"><label>NIC:</label></td></tr>
                                    <tr><td class="label-td" colspan="2"><input type="text" name="nic" class="input-text" required></td></tr>
                                    <tr><td class="label-td" colspan="2"><label>Date of Birth:</label></td></tr>
                                    <tr><td class="label-td" colspan="2"><input type="date" name="dob" class="input-text" required></td></tr>
                                    <tr><td class="label-td" colspan="2"><label>Address:</label></td></tr>
                                    <tr><td class="label-td" colspan="2"><input type="text" name="address" class="input-text" required></td></tr>
                                    <tr><td class="label-td" colspan="2"><label>Email:</label></td></tr>
                                    <tr><td class="label-td" colspan="2"><input type="email" name="email" class="input-text" required></td></tr>
                                    <tr><td class="label-td" colspan="2"><label>Parent Email:</label></td></tr>
                                    <tr><td class="label-td" colspan="2"><input type="email" name="parentemail" class="input-text" required></td></tr>
                                    <tr><td class="label-td" colspan="2"><label>Phone:</label></td></tr>
                                    <tr><td class="label-td" colspan="2"><input type="tel" name="tele" class="input-text" required></td></tr>
                                    <tr><td class="label-td" colspan="2"><label>Password:</label></td></tr>
                                    <tr><td class="label-td" colspan="2"><input type="password" name="password" class="input-text" required></td></tr>
                                    <tr><td class="label-td" colspan="2"><label>Confirm Password:</label></td></tr>
                                    <tr><td class="label-td" colspan="2"><input type="password" name="cpassword" class="input-text" required></td></tr>
                                    <tr>
                                        <td colspan="2">
                                            <input type="reset" value="Reset" class="login-btn btn-primary-soft btn">
                                            <input type="submit" value="Add Patient" class="login-btn btn-primary btn">
                                        </td>
                                    </tr>
                                </table>
                            </form>
                            </div>
                        </center>
                    </div>
                </div>';
            } else {
                // Success popup after add
                echo '
                <div id="popup1" class="overlay">
                    <div class="popup">
                        <center>
                            <br><br><br><br>
                            <h2>Patient Added Successfully!</h2>
                            <a class="close" href="patient.php">&times;</a>
                            <div class="content">
                                You have successfully added a new patient.
                            </div>
                            <div style="display: flex;justify-content: center;">
                                <a href="patient.php" class="non-style-link">
                                    <button class="btn-primary btn" style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;">
                                        <font class="tn-in-text">&nbsp;&nbsp;OK&nbsp;&nbsp;</font>
                                    </button>
                                </a>
                            </div>
                            <br><br>
                        </center>
                    </div>
                </div>';
            }
        } elseif ($action == 'view'){
            $sqlmain= "select * from patient where pid='$id'";
            $result= $database->query($sqlmain);
            $row=$result->fetch_assoc();
            $name=$row["pname"];
            $email=$row["pemail"];
            $nic=$row["pnic"];
            $dob=$row["pdob"];
            $tele=$row["ptel"];
            $address=$row["paddress"];
            echo '
            <div id="popup1" class="overlay">
                    <div class="popup">
                    <center>
                        <a class="close" href="patient.php">&times;</a>
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
                                    <label for="name" class="form-label">Patient ID: </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    P-'.$id.'<br><br>
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
                                    <label for="spec" class="form-label">Address: </label>
                                    
                                </td>
                            </tr>
                            <tr>
                            <td class="label-td" colspan="2">
                            '.$address.'<br><br>
                            </td>
                            </tr>
                            <tr>
                                
                                <td class="label-td" colspan="2">
                                    <label for="name" class="form-label">Date of Birth: </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    '.$dob.'<br><br>
                                </td>
                                
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <a href="patient.php"><input type="button" value="OK" class="login-btn btn-primary-soft btn" ></a>
                                
                                    
                                </td>
                
                            </tr>
                           

                        </table>
                        </div>
                    </center>
                    <br><br>
            </div>
            </div>
            ';
        
        } elseif ($action == 'edit') {
            // Edit user details popup
            $sqlmain = "select * from patient where pid=?";
            $stmt = $database->prepare($sqlmain);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            if (!$row) {
                die("Error: Patient not found.");
            }
            $name = $row["pname"];
            $email = $row["pemail"];
            $address = $row["paddress"];
            $nic = $row['pnic'];
            $tele = $row['ptel'];
            $pemail = $row['parentemail'];
            
            // Get profile image from webuser table
            $sqlProfile = "SELECT profile_image FROM webuser WHERE email=?";
            $stmtProfile = $database->prepare($sqlProfile);
            $stmtProfile->bind_param("s", $email);
            $stmtProfile->execute();
            $resultProfile = $stmtProfile->get_result();
            $profileData = $resultProfile->fetch_assoc();
            $profileImage = $profileData ? $profileData['profile_image'] : null;
            $profileImagePath = $profileImage ? "../patient/profileImage/".$profileImage : "../img/user.png";

            $error_1 = $_GET["error"];
            $errorlist = array(
                '1' => '<label for="promter" class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Already have an account for this Email address.</label>',
                '2' => '<label for="promter" class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Password Conformation Error! Reconform Password</label>',
                '3' => '<label for="promter" class="form-label" style="color:rgb(255, 62, 62);text-align:center;"></label>',
                '4' => "",
                '0' => '',
            );

            // Check for image upload messages
            $upload_error = isset($_GET['upload_error']) ? urldecode($_GET['upload_error']) : '';
            $upload_success = isset($_GET['upload_success']) ? urldecode($_GET['upload_success']) : '';

            if ($error_1 != '4') {
                echo '
                <div id="popup1" class="overlay">
                        <div class="popup">
                        <center>
                            <a class="close" href="patient.php">&times;</a> 
                            <div style="display: flex;justify-content: center;">
                            <div class="abc">
                            <table width="80%" class="sub-table scrolldown add-doc-form-container" border="0">
                            <tr>
                                    <td class="label-td" colspan="2">'.$errorlist[$error_1].'</td>
                                </tr>';
                if (!empty($upload_error)) {
                    echo '<tr><td class="label-td" colspan="2"><label class="error-message">'.$upload_error.'</label></td></tr>';
                }
                if (!empty($upload_success)) {
                    echo '<tr><td class="label-td" colspan="2"><label class="success-message">'.$upload_success.'</label></td></tr>';
                }
                echo '
                                <tr>
                                    <td>
                                        <p style="padding: 0;margin: 0;text-align: left;font-size: 25px;font-weight: 500;">Edit User Account Details.</p>
                                    User ID : '.$id.'<br><br>
                                    </td>
                                </tr>
                                <!-- Profile Image Section with Upload and Camera Options -->
                                <tr>
                                    <td class="label-td" colspan="2">
                                        <form action="updateProfileImage.php" method="POST" enctype="multipart/form-data">
                                            <div class="profile-image-section">
                                                <img src="'.htmlspecialchars($profileImagePath).'" alt="Profile Image" id="profileImagePreview" class="profile-image-preview">
                                                <input type="file" name="profile_image" id="file-input" accept="image/*" onchange="previewImage(this)">
                                                <input type="file" id="camera-input" accept="image/*" capture="environment" onchange="previewImage(this)" style="display: none;">
                                                <input type="hidden" name="email" value="'.$email.'">
                                                <div style="display: flex; justify-content: center; margin-top: 10px;">
                                                    <label for="file-input" class="change-photo-btn">Upload from Storage</label>
                                                    <button type="button" class="camera-btn" onclick="openCamera()">Take a Snapshot</button>
                                                </div>
                                                <div style="margin-top: 10px;">
                                                    <input type="submit" value="Update Profile Image" class="login-btn btn-primary btn">
                                                </div>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                        <form name="editForm" action="edit-user.php" method="POST" class="add-new-form" enctype="multipart/form-data">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                        <label for="Email" class="form-label">Email: </label>
                                        <input type="hidden" value="'.$id.'" name="id00">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                    <input type="hidden" name="oldemail" value="'.$email.'">
                                    <input type="email" name="email" class="input-text" placeholder="Email Address" value="'.$email.'" required><br>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                        <label for="name" class="form-label">Name: </label>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                        <input type="text" name="name" class="input-text" placeholder="Doctor Name" value="'.$name.'" required><br>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                        <label for="nic" class="form-label">NIC: </label>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                        <input type="text" name="nic" class="input-text" placeholder="NIC Number" value="'.$nic.'" required><br>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                        <label for="Tele" class="form-label">Telephone: </label>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                        <input type="tel" name="Tele" class="input-text" placeholder="Telephone Number" value="'.$tele.'" required><br>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                        <label for="spec" class="form-label">Address</label>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                    <input type="text" name="address" class="input-text" placeholder="Address" value="'.$address.'" required><br>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                        <label for="spec" class="form-label">Parent/Guardian Email: </label>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                    <input type="text" name="parentemail" class="input-text" placeholder="Parent Email" value="'.$pemail.'" required><br>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                        <label for="password" class="form-label">Password: </label>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                        <input type="password" name="password" class="input-text" placeholder="Define a Password" ><br>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                        <label for="cpassword" class="form-label">Confirm Password: </label>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                        <input type="password" name="cpassword" class="input-text" placeholder="Confirm Password" ><br>
                                    </td>
                                </tr>  
                                <tr>
                                    <td colspan="2">
                                        <input type="reset" value="Reset" class="login-btn btn-primary-soft btn" >&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                        <input type="submit" value="Save" class="login-btn btn-primary btn">
                                    </td>
                                </tr>
                            </form>
                            </table>
                            </div>
                            </div>
                        </center>
                        <br><br>
                </div>
                </div>
                ';
            } 
            else {
                echo '
                <div id="popup1" class="overlay">
                        <div class="popup">
                        <center>
                        <br><br><br><br>
                            <h2>Edit Successfully!</h2>
                            <a class="close" href="patient.php">&times;</a>
                            <div class="content">
                                If You change your email also Please logout and login again with your new email
                            </div>
                            <div style="display: flex;justify-content: center;">
                            <a href="patient.php" class="non-style-link"><button class="btn-primary btn" style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;"><font class="tn-in-text">&nbsp;&nbsp;OK&nbsp;&nbsp;</font></button></a>
                            <a href="../logout.php" class="non-style-link"><button class="btn-primary-soft btn" style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;"><font class="tn-in-text">&nbsp;&nbsp;Log out&nbsp;&nbsp;</font></button></a>
                            </div>
                            <br><br>
                        </center>
                </div>
                </div>
                ';
            }
        }
        ;
    }

?>
</div>

</body>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const addForm = document.forms['addPatientForm'];
        if (!addForm) return;

        const passwordField = addForm['password'];
        const confirmPasswordField = addForm['cpassword'];

        const fields = [
            { name: 'fname', regex: /^[A-Za-z\s]{2,50}$/, message: 'First name must be 2-50 letters only.' },
            { name: 'lname', regex: /^[A-Za-z\s]{2,50}$/, message: 'Last name must be 2-50 letters only.' },
            { name: 'nic', regex: /^[0-9]{12}$/, message: 'NIC must be exactly 12 digits.' },
            { name: 'tele', regex: /^01\d{8,9}$/, message: 'Phone must start with 01 and be 10 or 11 digits.' },
            { name: 'email', regex: /^[a-zA-Z0-9._%+-]+@gmail\.com$/, message: 'Email must be a valid Gmail address.' },
            { name: 'parentemail', regex: /^[a-zA-Z0-9._%+-]+@gmail\.com$/, message: 'Parent email must be a valid Gmail address.' }
        ];

        // Real-time validation for each field
        fields.forEach(field => {
            const input = addForm[field.name];
            if (input) {
                input.addEventListener('input', function () {
                    validateField(input, field.regex, field.message);
                });
            }
        });

        // Password field logic
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

        // Form submission validation
        addForm.addEventListener('submit', function (e) {
            let valid = true;

            fields.forEach(field => {
                const input = addForm[field.name];
                if (input && !validateField(input, field.regex, field.message)) valid = false;
            });

            if (!validatePasswordStrength(passwordField)) valid = false;
            if (!validatePasswords()) valid = false;

            if (!valid) {
                e.preventDefault();
                alert('❌ Please fix the errors before submitting the form.');
            }
        });

        // Disable confirm password initially
        confirmPasswordField.disabled = true;

        // Validation helper functions
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
            if (!strongPasswordRegex.test(input.value.trim())) {
                showError(input, 'Password must be at least 8 characters, include uppercase, lowercase, number, and special character.');
                return false;
            } else {
                clearError(input);
                return true;
            }
        }

        function validatePasswords() {
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
                error.style.fontSize = '13px';
                error.style.marginTop = '3px';
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
    });

    document.addEventListener('DOMContentLoaded', function () {
        const editForm = document.forms['editForm'];
        if (!editForm) return;

        const passwordField = editForm['password'];
        const confirmPasswordField = editForm['cpassword'];

        const fields = [
            { name: 'name', regex: /^[A-Za-z\s]{2,50}$/, message: 'Name must be 2-50 letters only.' },
            { name: 'nic', regex: /^[0-9]{12}$/, message: 'NIC must be exactly 12 digits.' },
            { name: 'Tele', regex: /^01\d{8,9}$/, message: 'Phone number must start with 01 and be 10 or 11 digits.' },
            { name: 'email', regex: /^[a-zA-Z0-9._%+-]+@gmail\.com$/, message: 'Email must be a valid Gmail address.' },
            { name: 'parentemail', regex: /^[a-zA-Z0-9._%+-]+@gmail\.com$/, message: 'Parent email must be a valid Gmail address.' }
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

        editForm.addEventListener('submit', function (e) {
            let valid = true;

            fields.forEach(field => {
                const input = editForm[field.name];
                if (input && !validateField(input, field.regex, field.message)) valid = false;
            });

            if (passwordField.value.length > 0) {
                if (!validatePasswordStrength(passwordField)) valid = false;
                if (!validatePasswords()) valid = false;
            }

            if (!valid) {
                e.preventDefault();
                alert('❌ Please fix all errors before submitting.');
            }
        });

        confirmPasswordField.disabled = true;

        // Helpers
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
            if (!strongPasswordRegex.test(input.value.trim())) {
                showError(input, 'Password must be at least 8 characters with upper, lower, digit, and special char.');
                return false;
            } else {
                clearError(input);
                return true;
            }
        }

        function validatePasswords() {
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
                error.style.fontSize = '13px';
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
    });
    
    document.addEventListener("DOMContentLoaded", function () {
        const unbanLinks = document.querySelectorAll(".unban-link");

        unbanLinks.forEach(link => {
            link.addEventListener("click", function (e) {
                const confirmed = confirm("Are you sure you want to unban this user?");
                if (!confirmed) {
                    e.preventDefault(); // Stop link from navigating
                }
            });
        });
    });
</script>
</html>