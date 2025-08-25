<?php
// Start the session at the very top, before any output
session_start();

// Check session and redirect if necessary
if (isset($_SESSION["user"])) {
    if (($_SESSION["user"]) == "" || $_SESSION['usertype'] != 'p') {
        header("location: ../login.php");
        exit(); // Ensure no further code is executed after redirect
    } else {
        $useremail = $_SESSION["user"];
    }
} else {
    header("location: ../login.php");
    exit();
}

// Import database
include("../connection.php");

// Fetch user data from patient table
$sqlmain = "select * from patient where pemail=?";
$stmt = $database->prepare($sqlmain);
$stmt->bind_param("s", $useremail);
$stmt->execute();
$result = $stmt->get_result();
$userfetch = $result->fetch_assoc();

// Check if user data was found
if (!$userfetch) {
    die("Error: User not found in the patient table.");
}
$userid = $userfetch["pid"];
$username = $userfetch["pname"];

// Get profile image from webuser table
$sqlProfile = "SELECT profile_image FROM webuser WHERE email=?";
$stmtProfile = $database->prepare($sqlProfile);
$stmtProfile->bind_param("s", $useremail);
$stmtProfile->execute();
$resultProfile = $stmtProfile->get_result();
$profileData = $resultProfile->fetch_assoc();

// Check if profile data was found
$profileImage = $profileData ? $profileData['profile_image'] : null;
$profileImagePath = $profileImage ? "/patient/profileImage/" . $profileImage : "../img/user.png";
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
    <!-- JavaScript for image preview and camera functionality -->
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
    </script>
</head>
<body>
    <!-- Main container and menu section -->
    <div class="container">
        <?php include(__DIR__ . '/patientMenu.php'); ?>
        <div class="dash-body" style="margin-top: 15px">
            <table border="0" width="100%" style="border-spacing: 0;margin:0;padding:0;">
                <tr>
                    <td width="13%">
                        <a href="index.php"><button class="login-btn btn-primary-soft btn btn-icon-back" style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px"><font class="tn-in-text">Back</font></button></a>
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
                            date_default_timezone_set('Asia/Kolkata');
                            $today = date('Y-m-d');
                            echo $today;
                            $patientrow = $database->query("select * from patient;");
                            $doctorrow = $database->query("select * from doctor;");
                            $appointmentrow = $database->query("select * from appointment where appodate>='$today';");
                            $schedulerow = $database->query("select * from schedule where scheduledate='$today';");
                            ?>
                        </p>
                    </td>
                    <td width="10%">
                        <button class="btn-label" style="display: flex;justify-content: center;align-items: center;"><img src="../img/calendar.svg" width="100%"></button>
                    </td>
                </tr>
                
                <tr>
                    <td colspan="4">
                        <center>
                            <!-- Filter container section -->
                            <table class="filter-container" style="border: none;" border="0">
                                <tr>
                                    <td colspan="4">
                                        <p style="font-size: 20px">&nbsp;</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="width: 25%;">
                                        <a href="?action=edit&id=<?php echo $userid ?>&error=0" class="non-style-link">
                                        <div class="dashboard-items setting-tabs" style="padding:20px;margin:auto;width:95%;display: flex">
                                        <div class="btn-icon-back dashboard-icons-setting" style="background-image: url('../img/icons/doctors-hover.svg');"></div>
                                            <div>
                                                <div class="h1-dashboard">
                                                    Account Settings &nbsp;
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
                                        <div class="dashboard-items setting-tabs" style="padding:20px;margin:auto;width:95%;display: flex;">
                                            <div class="btn-icon-back dashboard-icons-setting" style="background-image: url('../img/icons/view-iceblue.svg');"></div>
                                            <div>
                                                <div class="h1-dashboard">
                                                    View Account Details
                                                </div><br>
                                                <div class="h3-dashboard" style="font-size: 15px;">
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
                                        <div class="dashboard-items setting-tabs" style="padding:20px;margin:auto;width:95%;display: flex;">
                                            <div class="btn-icon-back dashboard-icons-setting" style="background-image: url('../img/icons/patients-hover.svg');"></div>
                                            <div>
                                                <div class="h1-dashboard" style="color: #ff5050;">
                                                    Delete Account
                                                </div><br>
                                                <div class="h3-dashboard" style="font-size: 15px;">
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
    <!-- Camera Popup Container -->
    <div id="cameraContainer" class="camera-container">
        <video id="cameraFeed" autoplay></video>
        <div class="camera-actions">
            <button class="capture-btn" onclick="captureImage()">Capture</button>
            <button class="close-camera-btn" onclick="closeCamera()">Close</button>
        </div>
    </div>
    <?php 
    if ($_GET) {
        $id = $_GET["id"];
        $action = $_GET["action"];
        if ($action == 'drop') {
            // Delete account popup
            $nameget = $_GET["name"];
            echo '
            <div id="popup1" class="overlay">
                    <div class="popup">
                    <center>
                        <h2>Are you sure?</h2>
                        <a class="close" href="settings.php">&times;</a>
                        <div class="content">
                            You want to delete Your Account<br>('.substr($nameget,0,40).').
                        </div>
                        <div style="display: flex;justify-content: center;">
                        <a href="delete-account.php?id='.$id.'" class="non-style-link"><button class="btn-primary btn" style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;"><font class="tn-in-text">&nbsp;Yes&nbsp;</font></button></a>&nbsp;&nbsp;&nbsp;
                        <a href="settings.php" class="non-style-link"><button class="btn-primary btn" style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;"><font class="tn-in-text">&nbsp;&nbsp;No&nbsp;&nbsp;</font></button></a>
                        </div>
                    </center>
            </div>
            </div>
            ';
        } elseif ($action == 'view') {
            // View details popup
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
            $dob = $row["pdob"];
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
            $profileImagePath = $profileImage ? "/patient/profileImage/".$profileImage : "../img/user.png";
            
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
                                <td style="text-align: center;">
                                    <img src="'.htmlspecialchars($profileImagePath) .'" alt="Profile Image" class="profile-image">
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
                                    <label for="spec" class="form-label">Date of Birth: </label>
                                </td>
                            </tr>
                            <tr>
                            <td class="label-td" colspan="2">
                            '.$dob.'<br><br>
                            </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <label for="spec" class="form-label">Parent/Guardian Email: </label>
                                </td>
                            </tr>
                            <tr>
                            <td class="label-td" colspan="2">
                            '.$pemail.'<br><br>
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
            $profileImagePath = $profileImage ? "/patient/profileImage/".$profileImage : "../img/user.png";
            
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
                            <a class="close" href="settings.php">&times;</a> 
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
            } else {
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
                            <a href="settings.php" class="non-style-link"><button class="btn-primary btn" style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;"><font class="tn-in-text">&nbsp;&nbsp;OK&nbsp;&nbsp;</font></button></a>
                            <a href="../logout.php" class="non-style-link"><button class="btn-primary-soft btn" style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;"><font class="tn-in-text">&nbsp;&nbsp;Log out&nbsp;&nbsp;</font></button></a>
                            </div>
                            <br><br>
                        </center>
                </div>
                </div>
                ';
            }
        }
    }
    ?>
</body>
</html>