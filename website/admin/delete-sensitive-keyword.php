<?php
    session_start();
    date_default_timezone_set('Asia/Kuala_Lumpur');
    if(isset($_SESSION["user"])){
        if(($_SESSION["user"])=="" or $_SESSION['usertype']!='a'){
            header("location: ../login.php");
        }
    } else {
        header("location: ../login.php");
    }

    if($_GET){
        // Import database connection
        include("../connection.php");
        
        // Retrieve ID from URL
        $id = $_GET["id"];
        
        // SQL query to delete the sensitive keyword
        $sql = $database->query("DELETE FROM sensitive_keywords WHERE sensitive_keyword_id='$id';");
        
        // Redirect back to the sensitive keywords list
        header("location: adminChatbot.php");
    }
?>
