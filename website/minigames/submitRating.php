<?php
include(__DIR__ . '../../connection.php');
include(__DIR__ . '/gamedb.php');

session_start();
date_default_timezone_set('Asia/Kuala_Lumpur');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $gameId = $_POST["gameId"];
    $ratingValue = $_POST["ratingValue"];
    $userEmail = $_SESSION["user"];
    // var_dump($_SESSION["user"]); // Shows detailed info
    // echo $_SESSION["user"]; // Just prints the email
    
    // Prevent invalid ratings
    if ($ratingValue < 1 || $ratingValue > 5) {
        die("Invalid rating value");
    }

    // Insert or update the user's rating for the game
    if(checkUserGameRatingExists($userEmail, $gameId)){
        $success = updateUserGameRating($userEmail, $gameId, $ratingValue);
    }
    else{
        $success = insertUserGameRating($userEmail, $gameId, $ratingValue);
    }
    

    // Execute and check success
    try {
        if ($success) {
            // Redirect to the corresponding game page based on gameId
            $redirectPage = "";
            switch ($gameId) {
                case 1:
                    $redirectPage = "cardMatching.php";
                    break;
                case 2:
                    $redirectPage = "mindfulCounting.php";
                    break;
                case 3:
                    $redirectPage = "SubmitQuiz.php";
                    break;
                case 5:
                    $redirectPage = "spaceshipShooter.php";
                    break;
                default:
                    $redirectPage = "gameLists.php"; // Default fallback page
                    break;
            }
            
            echo "<script>window.location.href='$redirectPage?successRating=1';</script>";
        }
    } catch (Exception $e) {
        echo "<script>alert('Error submitting rating: " . addslashes($e->getMessage()) . "');</script>";
    }
}
?>