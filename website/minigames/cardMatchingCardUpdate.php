<?php
session_start();
include(__DIR__ . '../../connection.php');
include(__DIR__ . '/gamedb.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userEmail = $_SESSION["user"];
    $updateType = $_POST['updateType']; // Get the update type from the request

    if($updateType == 3){   // Save game data using the function
        $timeUsed = $_POST['timeUsed'];
        $flips = $_POST['flips'];
        $finalScore = $_POST['finalScore'];
        $gameId = 4; // hardcoded for memory game
    
        include_once('gamehelper.php'); // put generateNewPlayId in this file
    
        $playId = generateNewPlayId($userEmail, $gameId);
    
        $stmt = $database->prepare("INSERT INTO memorygame (playId, timeOpen, timeUsed, finalScore) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siii", $playId, $flips, $timeUsed, $finalScore);
        $result = $stmt->execute();
    
        echo json_encode(["success" => $result, "playId" => $playId]);
    }
    else if($updateType == 1){ // Update selected card
        $cardId = $_POST['cardId'];

        // Save game data using the function
        $result = updateSelectedCardByEmail($userEmail, $cardId); // Update the selected card in the database
        echo json_encode($result);
        exit;
    }
    else if($updateType == 2){ // redeem card
        $cardId = $_POST['cardId'];

        // 1. Get points required for the card
        $stmt = $database->prepare("SELECT points FROM memorygamecard WHERE cardId = ?");
        $stmt->bind_param("i", $cardId);
        $stmt->execute();
        $resultCard = $stmt->get_result();
        $card = $resultCard->fetch_assoc();

        if (!$card) {
            echo json_encode(["success" => false, "error" => "Card not found"]);
            exit;
        }

        $requiredPoints = $card['points'];

        // 2. Get current patient points
        $stmt2 = $database->prepare("SELECT points FROM patient WHERE pemail = ?");
        $stmt2->bind_param("s", $userEmail);
        $stmt2->execute();
        $resultPatient = $stmt2->get_result();
        $patient = $resultPatient->fetch_assoc();

        if (!$patient || $patient['points'] < $requiredPoints) {
            echo json_encode(["success" => false, "error" => "Not enough points"]);
            exit;
        }

        // 3. Deduct patient points
        $stmt3 = $database->prepare("UPDATE patient SET points = points - ? WHERE pemail = ?");
        $stmt3->bind_param("is", $requiredPoints, $userEmail);
        $stmt3->execute();

        // 4. Add card to user collection
        $insertResult = insertUserCardByEmail($userEmail, $cardId);

        echo json_encode(["success" => true, "insertResult" => $insertResult]);
        exit;
    }
    

    
}
?>