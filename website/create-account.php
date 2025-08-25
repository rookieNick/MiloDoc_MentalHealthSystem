<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/animations.css">  
    <link rel="stylesheet" href="css/main.css">  
    <link rel="stylesheet" href="css/signup.css">
        
    <title>Create Account</title>
    <style>
        .container{
            animation: transitionIn-X 0.5s;
        }
    </style>
</head>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form');
    const email = form['newemail'];
    const parentEmail = form['parentnewemail'];
    const phone = form['tele'];
    const password = form['newpassword'];
    const confirmPassword = form['cpassword'];

    // Email format check (Gmail only)
    email.addEventListener('input', function () {
        const pattern = /^[a-zA-Z0-9._%+-]+@gmail\.com$/;
        validateField(email, pattern, 'Must be a valid Gmail address (e.g., example@gmail.com)');
    });

    // Parent email basic format check
    parentEmail.addEventListener('input', function () {
        const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        validateField(parentEmail, pattern, 'Must be a valid email address.');
    });

    // Phone number format
    phone.addEventListener('input', function () {
        const pattern = /^0[0-9]{9}$/;
        validateField(phone, pattern, 'Phone must start with 0 and have 10 digits.');
    });

    // Password strength
    password.addEventListener('input', function () {
        const strongPw = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/;
        validateField(password, strongPw, 'Min 8 characters, with upper, lower, number & symbol.');
        validatePasswordMatch();
    });

    confirmPassword.addEventListener('input', validatePasswordMatch);

    function validateField(input, regex, message) {
        const parent = input.parentNode;
        let error = parent.querySelector('.error-text');
        if (!error) {
            error = document.createElement('div');
            error.className = 'error-text';
            error.style.color = 'red';
            error.style.fontSize = '0.9em';
            parent.appendChild(error);
        }
        if (!regex.test(input.value.trim())) {
            error.textContent = message;
            input.style.borderColor = 'red';
        } else {
            error.textContent = '';
            input.style.borderColor = 'green';
        }
    }

    function validatePasswordMatch() {
        let error = confirmPassword.parentNode.querySelector('.error-text');
        if (!error) {
            error = document.createElement('div');
            error.className = 'error-text';
            error.style.color = 'red';
            error.style.fontSize = '0.9em';
            confirmPassword.parentNode.appendChild(error);
        }

        if (password.value !== confirmPassword.value) {
            error.textContent = 'Passwords do not match.';
            confirmPassword.style.borderColor = 'red';
        } else {
            error.textContent = '';
            confirmPassword.style.borderColor = 'green';
        }
    }
});
</script>

<body>
<?php
session_start();

// Clear session variables
$_SESSION["user"] = "";
$_SESSION["usertype"] = "";
$_SESSION["username"] = "";

// Set timezone
date_default_timezone_set('Asia/Singapore');
$date = date('Y-m-d');
$_SESSION["date"] = $date;

// Import database connection and UserDA
require_once("connection.php");
require_once("../website/collaCommunityForum/includes/database/userDA.php");
require_once("../website/collaCommunityForum/includes/utilities/generateUserID.php");
$userDA = new UserDA();
$error = '<label for="promter" class="form-label"></label>';

if ($_POST) {
    // Sanitize input data
    $fname = filter_var($_SESSION['personal']['fname'], FILTER_SANITIZE_STRING);
    $lname = filter_var($_SESSION['personal']['lname'], FILTER_SANITIZE_STRING);
    $name = $fname . " " . $lname;
    $address = filter_var($_SESSION['personal']['address'], FILTER_SANITIZE_STRING);
    $nic = filter_var($_SESSION['personal']['nic'], FILTER_SANITIZE_STRING);
    $dob = filter_var($_SESSION['personal']['dob'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['newemail'], FILTER_SANITIZE_EMAIL);
    $parentemail = filter_var($_POST['parentnewemail'], FILTER_SANITIZE_EMAIL);
    $tele = filter_var($_POST['tele'], FILTER_SANITIZE_STRING);
    $newpassword = $_POST['newpassword']; 
    $cpassword = $_POST['cpassword'];

    if ($newpassword === $cpassword) {
        // Check if email already exists in user table
        if ($userDA->userExistsByEmail($email)) {
            $error = '<label for="promter" class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Already have an account for this Email address.</label>';
        } else {
            // Generate new user_id
            $userId = generateUserId();

            if ($userId) {
                // Create user array for UserDA
                $user = [
                    'user_id' => $userId,
                    'email' => $email,
                    'username' => null,
                    'is_anonymous' => 0 // Assuming non-anonymous user
                ];

                // Add user to user table
                $userAddResult = $userDA->addUser($user);
                if ($userAddResult === true) {
                    try {
                        // Add to webuser table with explicit columns
                        $stmt = $database->prepare("INSERT INTO webuser (email, usertype) VALUES (?, ?)");
                        $stmt->bind_param("ss", $email, $usertype);
                        $usertype = 'p';
                        $stmt->execute();
                        $stmt->close();
                        $hashed_password = md5($newpassword);
                        // Add to patient table with explicit columns
                        $stmt = $database->prepare("INSERT INTO patient (pemail, pname, ppassword, paddress, pnic, pdob, ptel, parentemail) 
                                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("ssssssss", $email, $name, $hashed_password, $address, $nic, $dob, $tele, $parentemail);
                        $stmt->execute();
                        $stmt->close();

                        // Set session variables
                        $_SESSION["user"] = $email;
                        $_SESSION["usertype"] = "p";
                        $_SESSION["username"] = $fname;

                        // Redirect to patient dashboard
                        header('Location: patient/index.php');
                        exit;
                    } catch (mysqli_sql_exception $e) {
                        $error = '<label for="promter" class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Database Error: ' . htmlspecialchars($e->getMessage()) . '</label>';
                    }
                } else {
                    $error = '<label for="promter" class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Failed to create user: ' . htmlspecialchars($userAddResult) . '</label>';
                }
            }
        }
    } else {
        $error = '<label for="promter" class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Password Confirmation Error! Reconfirm Password</label>';
    }
}
?>

    <center>
    <div class="container">
        <table border="0" style="width: 69%;">
            <tr>
                <td colspan="2">
                    <p class="header-text">Let's Get Started</p>
                    <p class="sub-text">It's Okay, Now Create User Account.</p>
                </td>
            </tr>
            <tr>
                <form action="" method="POST">
                <td class="label-td" colspan="2">
                    <label for="newemail" class="form-label">Email: </label>
                </td>
            </tr>
            <tr>
                <td class="label-td" colspan="2">
                    <input type="email" name="newemail" class="input-text" placeholder="Email Address" required>
                </td>
            </tr>
            <tr>
                <td class="label-td" colspan="2">
                    <label for="tele" class="form-label">Mobile Number: </label>
                </td>
            </tr>
            <tr>
                <td class="label-td" colspan="2">
                    <input type="tel" name="tele" class="input-text" placeholder="ex: 0712345678" pattern="[0]{1}[0-9]{9}">
                </td>
            </tr>
            <tr>
                <td class="label-td" colspan="2">
                    <label for="parentnewemail" class="form-label">Parent Email: </label>
                </td>
            </tr>
            <tr>
                <td class="label-td" colspan="2">
                    <input type="email" name="parentnewemail" class="input-text" placeholder="Parent Email Address" required>
                </td>
            </tr>
            <tr>
                <td class="label-td" colspan="2">
                    <label for="newpassword" class="form-label">Create New Password: </label>
                </td>
            </tr>
            <tr>
                <td class="label-td" colspan="2">
                    <input type="password" name="newpassword" class="input-text" placeholder="New Password" required>
                </td>
            </tr>
            <tr>
                <td class="label-td" colspan="2">
                    <label for="cpassword" class="form-label">Confirm Password: </label>
                </td>
            </tr>
            <tr>
                <td class="label-td" colspan="2">
                    <input type="password" name="cpassword" class="input-text" placeholder="Confirm Password" required>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <?php echo $error ?>
                </td>
            </tr>
            <tr>
                <td>
                    <input type="reset" value="Reset" class="login-btn btn-primary-soft btn">
                </td>
                <td>
                    <input type="submit" value="Sign Up" class="login-btn btn-primary btn">
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <br>
                    <label for="" class="sub-text" style="font-weight: 280;">Already have an account? </label>
                    <a href="login.php" class="hover-link1 non-style-link">Login</a>
                    <br><br><br>
                </td>
            </tr>
                </form>
            </tr>
        </table>
    </div>
    </center>
</body>
</html>