<?php
include("../connection.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id00'];
    $email = trim($_POST['email']); // still needed to check foreign key, just not editable
    $name = trim($_POST['name']);
    $nic = trim($_POST['nic']);
    $tele = trim($_POST['Tele']);
    $spec = $_POST['spec'];
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $cpassword = isset($_POST['cpassword']) ? trim($_POST['cpassword']) : '';

    $error = '0';

    // ==== Validation ====
    if (empty($name) || empty($nic) || empty($tele) || empty($spec) || empty($email)) {
        $error = '1';
    } elseif (!preg_match("/^[a-zA-Z\s]{2,50}$/", $name)) {
        $error = '4';
    } elseif (!preg_match("/^[0-9]{6,14}$/", $nic)) {
        $error = '5';
    } elseif (!preg_match("/^01\d{8,9}$/", $tele)) {
        $error = '6';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '7';
    } elseif (!preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/', $email)) {
        $error = '10';
    } elseif (!empty($password) && strlen($password) < 6) {
        $error = '8';
    } elseif (!empty($password) && $password !== $cpassword) {
        $error = '2';
    }

    // ==== Proceed with update ====
    if ($error === '0') {
        $database->begin_transaction();

        try {
            // 1. Update doctor details (email is unchanged)
            $stmt = $database->prepare("UPDATE doctor SET docname=?, docnic=?, doctel=?, specialties=? WHERE docid=?");
            if (!$stmt) throw new Exception("Prepare failed: " . $database->error);
            $stmt->bind_param("ssssi", $name, $nic, $tele, $spec, $id);
            if (!$stmt->execute()) throw new Exception("Doctor update failed: " . $stmt->error);

            // 2. Update password if provided
            if (!empty($password)) {
                $hashedPassword = md5($_POST['password']);
                $stmt_pw = $database->prepare("UPDATE doctor SET docpassword=? WHERE docid=?");
                if (!$stmt_pw) throw new Exception("Prepare failed: " . $database->error);
                $stmt_pw->bind_param("si", $hashedPassword, $id);
                if (!$stmt_pw->execute()) throw new Exception("Password update failed: " . $stmt_pw->error);
            }

            $database->commit();

        } catch (Exception $e) {
            $database->rollback();
            die("Update failed: " . $e->getMessage());
        }
    }

} else {
    $error = '3'; // No POST
}

header("Location: doctors.php?action=edit&error=$error&id=$id&updated=true");
exit();
?>
