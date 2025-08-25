<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/animations.css">  
    <link rel="stylesheet" href="css/main.css">  
    <link rel="stylesheet" href="css/signup.css">
        
    <title>Sign Up</title>
    
</head>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form');

    const fname = form['fname'];
    const lname = form['lname'];
    const address = form['address'];
    const nic = form['nic'];
    const dob = form['dob'];

    const fields = [
        { input: fname, regex: /^[A-Za-z\s]{2,50}$/, message: 'First name must be 2–50 letters only.' },
        { input: lname, regex: /^[A-Za-z\s]{2,50}$/, message: 'Last name must be 2–50 letters only.' },
        { input: address, regex: /^.{5,100}$/, message: 'Address must be at least 5 characters.' },
        { input: nic, regex: /^[0-9]{12}$/, message: 'NIC must be exactly 12 digits.' }
    ];

    fields.forEach(({ input, regex, message }) => {
        input.addEventListener('input', () => validateField(input, regex, message));
    });

    dob.addEventListener('change', function () {
        const inputDate = new Date(dob.value);
        const today = new Date();
        const errorContainer = getErrorContainer(dob);

        if (inputDate >= today) {
            showError(dob, 'Date of birth cannot be today or future.');
        } else {
            clearError(dob);
        }
    });

    function validateField(input, regex, errorMessage) {
        const error = getErrorContainer(input);
        if (!regex.test(input.value.trim())) {
            showError(input, errorMessage);
        } else {
            clearError(input);
        }
    }

    function getErrorContainer(input) {
        let error = input.nextElementSibling;
        if (!error || !error.classList.contains('error-text')) {
            error = document.createElement('div');
            error.className = 'error-text';
            error.style.color = 'red';
            error.style.fontSize = '0.9em';
            input.parentNode.appendChild(error);
        }
        return error;
    }

    function showError(input, message) {
        const error = getErrorContainer(input);
        error.textContent = message;
        input.style.borderColor = 'red';
    }

    function clearError(input) {
        const error = getErrorContainer(input);
        error.textContent = '';
        input.style.borderColor = 'green';
    }

    // Final form submission check
    form.addEventListener('submit', function (e) {
        let valid = true;
        fields.forEach(({ input, regex, message }) => {
            if (!regex.test(input.value.trim())) {
                showError(input, message);
                valid = false;
            }
        });

        const inputDate = new Date(dob.value);
        if (inputDate >= new Date()) {
            showError(dob, 'Date of birth must be in the past.');
            valid = false;
        }

        if (!valid) {
            e.preventDefault();
            alert('Please fix the errors before continuing.');
        }
    });
});
</script>

<body>
<?php

//learn from w3schools.com
//Unset all the server side variables

session_start();

$_SESSION["user"]="";
$_SESSION["usertype"]="";

// Set the new timezone
date_default_timezone_set('Asia/Kolkata');
$date = date('Y-m-d');

$_SESSION["date"]=$date;



if($_POST){

    

    $_SESSION["personal"]=array(
        'fname'=>$_POST['fname'],
        'lname'=>$_POST['lname'],
        'address'=>$_POST['address'],
        'nic'=>$_POST['nic'],
        'dob'=>$_POST['dob']
    );


    print_r($_SESSION["personal"]);
    header("location: create-account.php");




}

?>


    <center>
    <div class="container">
        <table border="0">
            <tr>
                <td colspan="2">
                    <p class="header-text">Let's Get Started</p>
                    <p class="sub-text">Add Your Personal Details to Continue</p>
                </td>
            </tr>
            <tr>
                <form action="" method="POST" >
                <td class="label-td" colspan="2">
                    <label for="name" class="form-label">Name: </label>
                </td>
            </tr>
            <tr>
                <td class="label-td">
                    <input type="text" name="fname" class="input-text" placeholder="First Name" required>
                </td>
                <td class="label-td">
                    <input type="text" name="lname" class="input-text" placeholder="Last Name" required>
                </td>
            </tr>
            <tr>
                <td class="label-td" colspan="2">
                    <label for="address" class="form-label">Address: </label>
                </td>
            </tr>
            <tr>
                <td class="label-td" colspan="2">
                    <input type="text" name="address" class="input-text" placeholder="Address" required>
                </td>
            </tr>
            <tr>
                <td class="label-td" colspan="2">
                    <label for="nic" class="form-label">NIC: </label>
                </td>
            </tr>
            <tr>
                <td class="label-td" colspan="2">
                    <input type="text" name="nic" class="input-text" placeholder="NIC Number" required>
                </td>
            </tr>
            <tr>
                <td class="label-td" colspan="2">
                    <label for="dob" class="form-label">Date of Birth: </label>
                </td>
            </tr>
            <tr>
                <td class="label-td" colspan="2">
                    <input type="date" name="dob" class="input-text" required>
                </td>
            </tr>
            <tr>
                <td class="label-td" colspan="2">
                </td>
            </tr>

            <tr>
                <td>
                    <input type="reset" value="Reset" class="login-btn btn-primary-soft btn" >
                </td>
                <td>
                    <input type="submit" value="Next" class="login-btn btn-primary btn">
                </td>

            </tr>
            <tr>
                <td colspan="2">
                    <br>
                    <label for="" class="sub-text" style="font-weight: 280;">Already have an account&#63; </label>
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