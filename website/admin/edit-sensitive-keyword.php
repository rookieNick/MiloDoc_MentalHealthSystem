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

$message = "Invalid request";
$success = 0;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $keyword_id = $_POST['keyword_id'];
    $keyword = $_POST['keyword'];
    $description = $_POST['description'];
    $flag = $_POST['flag'];
    $status = $_POST['status'];
    $updated_by = $useremail;
    $updated_date = date('Y-m-d H:i:s');

    // Check if this keyword already exists (excluding this one)
    $check = $database->query("SELECT * FROM sensitive_keywords WHERE keyword = '$keyword' AND sensitive_keyword_id != '$keyword_id'");
    if ($check->num_rows > 0) {
        $message = "Keyword already exists!";
    } else {
        $sql = "UPDATE sensitive_keywords 
                SET keyword = '$keyword', 
                    description = '$description', 
                    flag = '$flag', 
                    status = '$status', 
                    updated_date = '$updated_date', 
                    updated_by = '$updated_by'
                WHERE sensitive_keyword_id = '$keyword_id'";

        if ($database->query($sql)) {
            $message = "Keyword updated successfully!";
            $success = 1;
        } else {
            $message = "Failed to update keyword. Please try again.";
        }
    }
}

header("location: adminChatbot.php?action=edit&success=" . $success . "&message=" . urlencode($message) . "&id=" . urlencode($keyword_id));
exit;
?>
