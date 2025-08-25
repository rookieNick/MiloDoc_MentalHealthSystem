<?php
session_start();
date_default_timezone_set('Asia/Kuala_Lumpur');
if (isset($_SESSION["user"])) {
    if (($_SESSION["user"]) == "" or $_SESSION['usertype'] != 'a') {
        header("location: ../login.php");
    } else {
        $useremail = $_SESSION["user"];
    }
} else {
    header("location: ../login.php");
}




include("../connection.php");

$message = "Invalid Request";
$success = 0;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $keyword = $_POST['keyword'];
    $description = $_POST['description'];
    $flag = $_POST['flag'];
    $status = $_POST['status'];
    $created_by = $useremail;
    $created_date = date('Y-m-d H:i:s');


    // Check for duplicate keyword
    $check = $database->query("SELECT * FROM sensitive_keywords WHERE keyword = '$keyword'");
    if ($check->num_rows > 0) {
        $message = "Keyword already exists!";
    } else {
        $new_id = uniqid("SK");

        $sql = "INSERT INTO sensitive_keywords 
                (sensitive_keyword_id, keyword, description, flag, status, created_date, created_by) 
                VALUES 
                ('$new_id', '$keyword', '$description', '$flag', '$status', '$created_date', '$created_by')";

        if ($database->query($sql)) {
            $message = "Keyword added successfully!";
            $success = 1;
        } else {
            $message = "Failed to add keyword. Please try again.";
        }
    }
}

header("location: adminChatbot.php?action=add&success=". $success . "&message=" . urlencode($message));
exit;
?>
