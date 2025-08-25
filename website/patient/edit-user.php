<?php
include("../connection.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST["id00"];
    $oldemail = $_POST["oldemail"];
    $email = trim($_POST["email"]);
    $name = trim($_POST["name"]);
    $nic = trim($_POST["nic"]);
    $tele = trim($_POST["Tele"]);
    $address = trim($_POST["address"]);
    $parentemail = trim($_POST["parentemail"]);
    $password = trim($_POST["password"]);
    $cpassword = trim($_POST["cpassword"]);

    $emailRegex = '/^[a-zA-Z0-9._%+-]+@gmail\.com$/';
    $nameRegex = '/^[A-Za-z\s]+$/';
    $nicRegex = '/^[0-9]{12}$/';

    // Validation checks
    if (!preg_match($emailRegex, $email)) {
        header("Location: settings.php?action=edit&id=$id&error=1");
        exit();
    }
    if (!preg_match($nameRegex, $name)) {
        header("Location: settings.php?action=edit&id=$id&error=5");
        exit();
    }
    if (!preg_match($nicRegex, $nic)) {
        header("Location: settings.php?action=edit&id=$id&error=6");
        exit();
    }
    if (empty($address)) {
        header("Location: settings.php?action=edit&id=$id&error=7");
        exit();
    }
    if (!preg_match($emailRegex, $parentemail)) {
        header("Location: settings.php?action=edit&id=$id&error=8");
        exit();
    }

    // Update query
    if (!empty($password)) {
        if ($password != $cpassword) {
            header("Location: settings.php?action=edit&id=$id&error=2");
            exit();
        }

        // Hash password before saving
        $hashed_password = md5($password);

        $sql = "UPDATE patient SET pemail=?, pname=?, pnic=?, ptel=?, paddress=?, parentemail=?, ppassword=? WHERE pid=?";
        $stmt = $database->prepare($sql);
        $stmt->bind_param("sssssssi", $email, $name, $nic, $tele, $address, $parentemail, $hashed_password, $id);
    } else {
        $sql = "UPDATE patient SET pemail=?, pname=?, pnic=?, ptel=?, paddress=?, parentemail=? WHERE pid=?";
        $stmt = $database->prepare($sql);
        $stmt->bind_param("ssssssi", $email, $name, $nic, $tele, $address, $parentemail, $id);
    }

    if ($stmt->execute()) {
        header("Location: settings.php?action=edit&id=$id&error=4&upload_success=" . urlencode('Account updated successfully!'));
        exit();
    } else {
        header("Location: settings.php?action=edit&id=$id&error=3");
        exit();
    }
}
?>
