<?php

    session_start();

    if(isset($_SESSION["user"])){
        if(($_SESSION["user"])=="" or $_SESSION['usertype']!='a'){
            header("location: ../login.php");
        }

    }else{
        header("location: ../login.php");
    }
    
    
    if($_GET){
        //import database
        include("../connection.php");
        include(__DIR__ . '/../minigames/gamedb.php');

        $id = $_GET["id"];
        $activate = isset($_GET["activate"]) ? $_GET["activate"] : false;
        if($activate) {
            setQuizQuestionStatusDisabled($id, 'enabled'); // Set the question status to disabled
        } else {
            setQuizQuestionStatusDisabled($id); // Set the question status to disabled
        }
        

        header("location: manageQuiz.php");
    }


?>