<?php
session_start();

// Set timezone
date_default_timezone_set('Asia/Singapore');
$date = date('Y-m-d');

// Import dependencies
require_once("../connection.php");
require_once("../collaCommunityForum/includes/database/userDA.php");
require_once("../collaCommunityForum/includes/utilities/generateUserID.php");

$userDA = new UserDA();

if ($_POST) {
    // Sanitize fields
    $fname = filter_var($_POST['fname'], FILTER_SANITIZE_STRING);
    $lname = filter_var($_POST['lname'], FILTER_SANITIZE_STRING);
    $name = $fname . " " . $lname;
    $address = filter_var($_POST['address'], FILTER_SANITIZE_STRING);
    $nic = filter_var($_POST['nic'], FILTER_SANITIZE_STRING);
    $dob = filter_var($_POST['dob'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $parentemail = filter_var($_POST['parentemail'], FILTER_SANITIZE_EMAIL);
    $tele = filter_var($_POST['tele'], FILTER_SANITIZE_STRING);
    $password = md5($_POST['password']);
    $cpassword = md5($_POST['cpassword']);

    // Error codes: 0 = success, 1 = email exists, 2 = password mismatch, 3 = ID generation fail, 4 = user insert fail, 5 = DB error

    // 1. Check password confirmation
    if ($password !== $cpassword) {
        header("Location: patient.php?action=add&error=2");
        exit;
    }

    // 2. Check if email exists
    if ($userDA->userExistsByEmail($email)) {
        header("Location: patient.php?action=add&error=1");
        exit;
    }

    // 3. Generate user ID
    $userId = generateUserId();
    if (!$userId) {
        header("Location: patient.php?action=add&error=3");
        exit;
    }

    // 4. Add user
    $user = [
        'user_id' => $userId,
        'email' => $email,
        'username' => null,
        'is_anonymous' => 0
    ];
    $userAddResult = $userDA->addUser($user);
    if ($userAddResult !== true) {
        header("Location: patient.php?action=add&error=4");
        exit;
    }

    // 5. Add to webuser and patient tables
    try {
        $usertype = 'p';

        $stmt = $database->prepare("INSERT INTO webuser (email, usertype) VALUES (?, ?)");
        $stmt->bind_param("ss", $email, $usertype);
        $stmt->execute();
        $stmt->close();

        $hashed_password = md5($password);

        $stmt = $database->prepare("INSERT INTO patient (pemail, pname, ppassword, paddress, pnic, pdob, ptel, parentemail)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $email, $name, $hashed_password, $address, $nic, $dob, $tele, $parentemail);
        $stmt->execute();
        $stmt->close();

        //Success
        header("Location: patient.php?action=add&error=4");
        exit;

    } catch (mysqli_sql_exception $e) {
        // 6. DB error fallback
        header("Location: patient.php?action=add&error=5");
        exit;
    }
} else {
    header("Location: patient.php");
    exit;
}
?>
