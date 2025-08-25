<?php
session_start();
date_default_timezone_set('Asia/Kuala_Lumpur');
include(__DIR__ . '../../connection.php');
include(__DIR__ . '/gamedb.php');


$_SESSION["currentGame"] = 4;
$gameId = 4;
$gameStatus = getGameStatusById($gameId);
if ($gameStatus == 0) {
    $gameName = getGameNameById($gameId);
    $gameNameUrl = urlencode($gameName);
    header("Location: /minigames/underMaintenance.php?game=" . $gameNameUrl);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Memory Matching - Self-Care Edition</title>
    <style>
        /* Reset default margin & padding */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            width: 100%;
            overflow-x: hidden;  /* prevent horizontal scroll */
        }
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            background-color: #f3f3f3;
        }
        .game-board {
            display: grid;
            grid-template-columns: repeat(4, 120px);
            gap: 10px;
            justify-content: center;
            padding: 50px;
            border-radius: 10px;
        }
        .card {
            width: 120px;
            height: 150px;
            perspective: 1000px;
        }
        .card-inner {
            width: 100%;
            height: 100%;
            position: relative;
            transform-style: preserve-3d;
            transition: transform 0.6s;
        }
        .card.flipped .card-inner {
            transform: rotateY(180deg);
        }
        .card-front, .card-back {
            width: 100%;
            height: 100%;
            position: absolute;
            backface-visibility: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            border-radius: 8px;
        }
        .card-back {
            transform: rotateY(180deg);
            border: black solid 2px;
            border-radius: 10px;
            background-color:rgb(221, 221, 221, 0.9);
        }
        /* .matched .card-inner {
            border-radius: 12px;
            box-shadow: 0 0 0 2px #2e597d;
        } */
        .main-container {
            display: flex;
            height: 100vh;  /* Full height of screen */
            width: 100%;
        }

        .left-panel {
            flex: 0 0 60%;  /* Fixed 60% */
            background-image: url('../img/card-background.jpg'); /* your image path */
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        /* set left background darker */
        .left-panel::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 60%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.3); /* darkness level: 0.4 */
            z-index: 0;
        }
        .card-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 10px;
            border: black solid 2px;
            opacity: 0.9;
        }
        .right-panel {
            width: 40%;
            background-color: #2E597D;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 20px;
            height: 100%;
        }

        .top-bar {
        display: flex;
        justify-content: flex-end;
        }

        .top-bar button {
        padding: 10px 20px;
        background-color: #1976D2;
        color: white;
        border: none;
        cursor: pointer;
        border-radius: 8px;
        }

        .game-info h1 {
        font-size: 24px;
        margin-bottom: 10px;
        }

        .result {
            font-size: 18px;
            line-height: 1.6;
        }

        .score-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px; /* Add some space between rows */
        }

        .score-row span:first-child {
            font-weight: bold;
            text-align: right;
            width: 180px;
        }

        .score-row span:last-child {
            text-align: left;
        }



        .top-bar button:hover {
        background-color: #1E88E5;
        }

        .button-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 15px;
        }

        .back-btn, .restart-btn, .rating-btn {
            padding: 10px 20px;
            font-weight: bold;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        /* Back Button Style */
        .back-btn {
            width: 50%;
            background-color: #0D47A1;
            color: white;
        }

        .back-btn:hover {
            background-color: #42A5F5;
            transform: scale(1.05);
        }

        /* Restart Button Style */
        .restart-btn {
            width: 50%;
            background-color:rgb(101, 179, 255); /* Orange */
            color: white; /* Dark Green Text */
        }

        .restart-btn:hover {
            background-color:rgb(76, 163, 255);
            transform: scale(1.05);
        }

        .rating-btn {
            width: 100%;
            background-color: #1976D2;
            color: white;
        }
        .row-two {
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .rating-box {
            border: solid 2px #8BC34A !important;
            background: #FFF9C4 !important;
        }
        .card-box {
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow: hidden;
            height: 220px;
        }

        .card-box img {
            max-width: 100%;
            max-height: 100px;
            object-fit: contain;
            display: block;
            margin: 0 auto 8px;
            border-radius: 10px;
            border: 2px solid black;
        }

        .card-name {
            font-weight: 500;
            margin-bottom: 8px;
        }

        .select-row {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: auto;
            flex-direction: column; /* Stack vertically */
            align-items: center;    /* Center horizontally */
            gap: 5px;               /* Space between points & button */
        }

        /* Hide the default radio */
        .select-radio {
            display: none;
        }

        .select-label {
            border-radius: 4px;
            padding: 3px 8px;
            font-size: 12px;
            cursor: pointer;
            user-select: none;
            color: white;
            transition: 0.2s;
        }

        /* Selected (Green) */
        .selected-style {
            background-color: #1565C0;
            color: white;
            border-color: #1565C0;
            cursor: default;
        }

        .select-style {
            background-color: #2196F3;
            color: white;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.1s ease;
        }

        .select-style:hover {
            background-color: #1E88E5; 
        }

        .select-style:active {
            background-color: #1976D2; 
        }

        /* Redeem (Blue Like Your Theme) */
        .redeem-style {
            border-color: #2196F3;
            color: #2196F3;
        }

        .redeem-style:hover {
            background-color: #2196F3;
            color: white;
        }
        /* Not Enough (Gray Background, No Click) */
        .disabled-style {
            background-color: #e0e0e0;
            color: #9e9e9e;
            cursor: not-allowed;
        }

        /* Disable Card Click Effect */
        .card.disabled {
            pointer-events: none;
        }
        #changeCardImgPanel {
            width: 100%;
            background-color: #2E597D;
            color: white;
            display: flex;
            flex-direction: column;
            padding: 20px;
            height: 100%;
            display: none;
            justify-content: flex-start;
        }
        #points-display {
            top: 10px;
            font-weight: bold;
            text-align: left;
        }
        #ratingSuccessBox {
            position: fixed;
            top: 50%;
            left: 80%;
            transform: translate(-50%, -50%);
            z-index: 999; /* above everything */
            display: none;
        }
    </style>
</head>
<?php include(__DIR__ . '/ratingBox.php'); 
$selectedTips = getRandomActiveSelfCareTips();
$userEmail = $_SESSION['user']; // Ensure user is logged in

//0000000000000000000000000000000 TEMP: create fake self-care tips with the same text for testing
// $fakeTipText = 'Drink water';
// $fakeTipId = 999; // arbitrary ID for testing

// $selectedTips = [];
// for ($i = 0; $i < 8; $i++) {
//     $selectedTips[] = [
//         'tipId' => $fakeTipId + $i, // still unique IDs
//         'tipText' => $fakeTipText
//     ];
// }
//000000000000000000000000000000 to delete

// get random active self-care tips from the database
$self_care_tips = array_map(function($tip) {
    return $tip['tipText'];
}, $selectedTips);

// get IDs
$selectedTipIds = array_map(function($tip) {
    return $tip['tipId'];
}, $selectedTips);

// Duplicate and shuffle tips for matching pairs
$cards = array_merge($self_care_tips, $self_care_tips);
shuffle($cards);


$cardOptions = getAllMemoryGameCards();
$userCards = getUserCardsByEmail($userEmail);

if (empty($userCards)) {
    insertDefaultUserCardByEmail($userEmail, 7);   //free gold dog card for free

    $userCards = getUserCardsByEmail($userEmail);
}

// user's current points
$userPoints = getPatientPointsByEmail($userEmail);

$path = getOnUsedCardPathByEmail($userEmail);

// Merge user cards with card options and add status flags
$finalCards = [];

foreach ($cardOptions as $card) {
    $status = 'not_owned'; // default
    $onUsed = 0;

    foreach ($userCards as $uc) {
        if ($uc['cardId'] == $card['cardId']) {
            $status = ($uc['onUsed'] == 1) ? 'owned_selected' : 'owned';
            $onUsed = $uc['onUsed'];
            break;
        }
    }

    $finalCards[] = [
        "cardId" => $card["cardId"],
        "name" => $card["name"],
        "path" => $card["path"],
        "points" => $card["points"],
        "status" => $status,
        "onUsed" => $onUsed
    ];
}

// Sort finalCards array
usort($finalCards, function ($a, $b) {
    $order = ["owned_selected" => 0, "owned" => 1, "not_owned" => 2];

    if ($order[$a['status']] !== $order[$b['status']]) {
        return $order[$a['status']] - $order[$b['status']];
    } else {
        return $a['points'] - $b['points']; // sort by points if same status
    }
});

?>
<body>

<div class="main-container">
        <!-- Left: Game Board -->
        <div class="left-panel">
            <div class="game-board" id="gameBoard">
                <?php foreach ($cards as $index => $tip): ?>
                    <div class="card" data-index="<?= $index ?>" data-tip="<?= $tip ?>">
                        <div class="card-inner">
                        <div class="card-front">
                            <img src="<?php echo $path; ?>" alt="Card Back" class="card-image">
                        </div>
                            <div class="card-back"><?= $tip ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Change Card Image Panel (Hidden by default) -->
        <div id="changeCardImgPanel">
        <div class="top-bar">
        <button id="changeCardBtnBack" onclick="changeCardImgBack()">Back</button>
        </div>
            <!-- Top Right User Points -->
            <div id="points-display">
                Points: <?php echo $userPoints; ?>
            </div>

            <!-- Card List -->
            <div style="margin-top: 40px; display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 15px; align-content: start;">
                <?php foreach ($finalCards as $card) { 
                    $ownedCard = null;
                    foreach ($userCards as $uc) {
                        if ($uc['cardId'] == $card['cardId']) {
                            $ownedCard = $uc;
                            break;
                        }
                    }
                ?>
                    <div class="card-box">
                        <img src="<?php echo $card['path']; ?>" alt="<?php echo $card['name']; ?>">
                        <div class="card-name"><?php echo $card['name']; ?></div>

                        <div class="select-row">
                            <!-- Points Display Always On Top -->
                            <div class="select-label" style="margin-bottom: 5px; cursor: default;">
                                <?php echo $card['points']; ?> pts
                            </div>

                            <?php if ($ownedCard) { ?>
                                <?php if ($ownedCard['onUsed'] == 1) { ?>
                                    <!-- Currently Selected -->
                                    <div class="select-label selected-style"
                                        data-cardid="<?php echo $card['cardId']; ?>"
                                        data-path="<?php echo $card['path']; ?>">
                                        Selected
                                    </div>
                                <?php } else { ?>
                                    <!-- Can Select -->
                                    <button class="select-label select-style" id="card<?php echo $card['cardId']; ?>" onclick="changeCardImage('<?php echo $card['cardId']; ?>', '<?php echo $card['path']; ?>')">
                                    Select
                                    </button>
                                <?php } ?>
                            <?php } else { ?>
                                <?php if ($userPoints >= $card['points']) { ?>
                                    <!-- Can Redeem -->
                                    <button class="select-label redeem-style" onclick="openRedeemModal('<?php echo $card['cardId']; ?>', '<?php echo $card['path']; ?>', '<?php echo $card['points']; ?>', '<?php echo $userPoints; ?>')">
                                        Redeem
                                    </button>
                                <?php } else { ?>
                                    <!-- Not Enough -->
                                    <div class="select-label disabled-style">
                                        Redeem
                                    </div>
                                <?php } ?>
                            <?php } ?>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>

        <!-- Right: Game Info -->
        <div class="right-panel" id="right-panel">
        <div class="top-bar" id="top-bar">
            <button id="changeCardBtn" onclick="changeCardImg()">Change Card Image</button>
        </div>




        <div class="game-info" id="gameInfo">
            <div id="intro-box">
                <h1>Memory Matching - Self-Care Edition</h1>
                <p>Click the cards to reveal self-care tips and find matching pairs!</p>
            </div>

            <div class="result" id="result" style="display: none;">
                <h2>Congratulations!</h2>
                <br>
                <p id="result-text">You matched all the cards</p>
                <div class="score-row">
                    <span>Time (seconds):</span> <span id="timer">0</span>
                </div>
                <div class="score-row">
                    <span>Flip Count:</span> <span id="attempts">0</span>
                </div>
                <div class="score-row">
                    <span>Final Score:</span> <span id="finalScore">0</span>
                </div>
            </div>
        </div>

        <div class="game-info" id="ratingSuccessBox" style="display: none;">
            <div id="intro-box">
                <h2>Rating Submitted!</h2>
                <p>Thank you for your feedback.</p>
                <br>
                <button class="restart-btn" onclick="closeSuccessPopup()">OK</button>
            </div>
        </div>

        
        <div class="button-group" id="bottomBtns">
            <button id="rating-btn" class="rating-btn" style="display: none;" onclick="rateGame(1)">Rate This Game</button>

            <div class="row-two">
                <button class="back-btn" onclick="goBack()">Back to Game List</button>
                <button class="restart-btn" onclick="restartGame()">Restart</button>
            </div>
        </div>
    </div>
    </div>

    <!-- Redeem Confirmation Modal -->
    <div id="redeemConfirmModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:1000; justify-content:center; align-items:center;">
        <div style="background:#6fbaf8; padding:20px; border-radius:10px; max-width:400px; text-align:center;">
            <h3>Confirm Redemption</h3>
            <img id="confirmCardImg" src="" alt="Card" style="max-width:100px; margin:10px auto; border-radius:4px; border: 2px solid black;">
            <p><strong>Points Required:</strong> <span id="confirmPoints"></span> pts</p>
            <p><strong>Your Points:</strong> <span id="currentUserPoints"></span> pts</p>
            <div style="display:flex; justify-content:space-between; margin-top:20px;">
                <button onclick="closeRedeemModal()" style="padding:8px 16px; background:#ccc; border:none; border-radius:5px;">Cancel</button>
                <button id="confirmRedeemBtn" style="padding:8px 16px; background:#4CAF50; color:white; border:none; border-radius:5px;">Redeem</button>
            </div>
        </div>
    </div>

<!-- Sound Effects -->
<audio id="flipSound" src="/audio/cardFlipOpen.mp3"></audio>
<audio id="closeSound" src="/audio/cardFlipClose.mp3"></audio>
<audio id="matchSound" src="/audio/cardMatch.mp3"></audio>
<audio id="gameWinSound" src="/audio/gameWin.mp3"></audio>

<script>
    let flippedCards = [];
    let matchedCards = 0;
    let attempts = 0;
    let timer = 0;
    let timerInterval;
    let gameStarted = false;
    let timePenalty = 0;
    let flipPenalty = 0;
    let finalscore = 0;

    let selectedRedeemCardId = null;

    const flipSound = document.getElementById('flipSound');
    const closeSound = document.getElementById('closeSound');
    const matchSound = document.getElementById('matchSound');
    const gameWinSound = document.getElementById('gameWinSound');

    document.querySelectorAll('.card').forEach(card => {
        card.addEventListener('click', function () {
            if (!gameStarted) {
                startTimer();
                gameStarted = true;
                document.getElementById("intro-box").style.display = "none";
                document.getElementById("changeCardBtn").style.display = "none";
            }
            
            if (flippedCards.length < 2 && !this.classList.contains('flipped')) {
                this.classList.add('flipped');
                flipSound.play(); // Play flip sound

                flippedCards.push(this);

                if (flippedCards.length === 2) {
                    attempts++;
                    document.getElementById('attempts').innerText = attempts;
                    checkMatch();
                }
            }
        });
    });

    function changeCardImg() {
        var gameBoard = document.getElementById("gameBoard");
        var gameInfo = document.getElementById("gameInfo");
        var bottomBtns = document.getElementById("bottomBtns");
        var rightpanel = document.getElementById('right-panel');

            // Hide game board
            gameInfo.style.display = "none";
            bottomBtns.style.display = "none";
            disableAllCards();
            document.getElementById("changeCardImgPanel").style.display = "block";
            rightpanel.style.display = "none";
        
    }

    function changeCardImgBack() {
        var gameBoard = document.getElementById("gameBoard");
        var gameInfo = document.getElementById("gameInfo");
        var bottomBtns = document.getElementById("bottomBtns");
        var rightpanel = document.getElementById('right-panel');

            // Show game board
            gameInfo.style.display = "block";
            bottomBtns.style.display = "flex";
            enableAllCards();
            rightpanel.style.display = "flex";
            document.getElementById("changeCardImgPanel").style.display = "none";

    }

    // Change Card Image Function
    function changeCardImage(cardid, newImgPath) {
        if (!newImgPath) {
            console.error("Missing image path.");
            return;
        }

        let cardFronts = document.querySelectorAll('.card-front img');
        cardFronts.forEach(function (img) {
            img.src = newImgPath;
        });

        const userEmail = "<?php echo $_SESSION['user']; ?>";
        const prevSelected = document.querySelector('.selected-style');

        if (prevSelected) {
            const prevCardId = prevSelected.dataset.cardid;
            const prevPath = prevSelected.dataset.path;

            if (prevSelected.tagName.toLowerCase() === 'div') {
                const newBtn = document.createElement('button');
                newBtn.className = 'select-label select-style';
                newBtn.id = 'card' + prevCardId;
                newBtn.innerText = 'Select';

                newBtn.dataset.cardid = prevCardId;
                newBtn.dataset.path = prevPath;

                newBtn.onclick = function () {
                    changeCardImage(prevCardId, prevPath);
                };
                prevSelected.replaceWith(newBtn);
            } else {
                prevSelected.classList.remove('selected-style');
                prevSelected.classList.add('select-style');
                prevSelected.textContent = 'Select';

                prevSelected.dataset.cardid = prevCardId;
                prevSelected.dataset.path = prevPath;

                prevSelected.onclick = function () {
                    changeCardImage(prevCardId, prevPath);
                };
            }
        }

        const newSelectedBtn = document.getElementById('card' + cardid);
        if (!newSelectedBtn) {
            console.error("New selected button not found.");
            return;
        }

        newSelectedBtn.classList.remove('select-style');
        newSelectedBtn.classList.add('selected-style');
        newSelectedBtn.textContent = 'Selected';

        newSelectedBtn.dataset.cardid = cardid;
        newSelectedBtn.dataset.path = newImgPath;

        newSelectedBtn.onclick = null;

        fetch("cardMatchingCardUpdate.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `cardId=${cardid}&updateType=1`
        })
            .then(response => response.json())
            .then(data => {
                console.log("card updated:", data);
            })
            .catch(error => console.error("Error:", error));
    }

    // Redeem Card Function
    function openRedeemModal(cardId, cardPath, pointsRequired, currentPoints) {
        selectedRedeemCardId = cardId;

        document.getElementById("confirmCardImg").src = cardPath;
        document.getElementById("confirmPoints").textContent = pointsRequired;
        document.getElementById("currentUserPoints").textContent = currentPoints;

        document.getElementById("redeemConfirmModal").style.display = "flex";
    }

    function closeRedeemModal() {
        selectedRedeemCardId = null;
        document.getElementById("redeemConfirmModal").style.display = "none";
    }

    document.getElementById("confirmRedeemBtn").onclick = function() {
        if (selectedRedeemCardId) {
            redeemCard(selectedRedeemCardId);
            closeRedeemModal();
        }
    };

    function redeemCard(selectedRedeemCardId) {
        fetch("cardMatchingCardUpdate.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `cardId=${selectedRedeemCardId}&updateType=2`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Redeem Success!");
                window.location.href = "/minigames/cardMatching.php";
            } else {
                alert(data.error || "Redeem Failed!");
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("An error occurred. Please try again.");
        });
    }

    // Disable All Cards
    function disableAllCards() {
        const cards = document.querySelectorAll('.card');
        cards.forEach(card => {
            card.classList.add('disabled');
        });
    }

    // Enable All Cards
    function enableAllCards() {
        const cards = document.querySelectorAll('.card');
        cards.forEach(card => {
            card.classList.remove('disabled');
        });
    }

    function checkMatch() {
        let [card1, card2] = flippedCards;
        if (card1.dataset.tip === card2.dataset.tip) {
            card1.classList.add('matched');
            card2.classList.add('matched');
            matchedCards += 2;
            flippedCards = [];

            // win the game
            if (matchedCards === document.querySelectorAll('.card').length) {
                clearInterval(timerInterval);
                gameWinSound.play(); // Play game win sound
                document.getElementById("rating-btn").style.display = "block";
                document.getElementById("result").style.display = "block";
                document.getElementById("changeCardBtn").style.display = "block";

                if(timer >= 10)
                timePenalty = (timer - 10) * 200; // 10 seconds penalty for each second over 10 seconds
                if (attempts >= 8)
                flipPenalty = (attempts - 8) * 100; // 8 flips penalty for each flip over 8 flips
            
                finalscore = 20000 - (timePenalty + attempts); // 20,000 points - penalties
                document.getElementById('finalScore').innerText = finalscore;

                fetch("cardMatchingCardUpdate.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `updateType=3&timeUsed=${timer}&flips=${attempts}&finalScore=${finalscore}`
                })
                    .then(response => response.json())
                    .then(data => {
                        console.log("Game result saved:", data);
                    })
                    .catch(error => console.error("Error:", error));

            }
            else {
                matchSound.play(); // Play match sound
            }
        } else {
            setTimeout(() => {
                card1.classList.remove('flipped');
                card2.classList.remove('flipped');
                closeSound.play(); // Play close sound
                flippedCards = [];
            }, 1000);
        }
    }

    function startTimer() {
        timerInterval = setInterval(() => {
            timer++;
            document.getElementById('timer').innerText = timer;
        }, 1000);
    }

    function restartGame() {
        clearInterval(timerInterval);
        timer = 0;
        document.getElementById('timer').innerText = "0";
        document.getElementById('attempts').innerText = "0";
        flippedCards = [];
        matchedCards = 0;
        attempts = 0;
        gameStarted = false;
        
        document.querySelectorAll('.card').forEach(card => {
            card.classList.remove('flipped', 'matched');
        });

        setTimeout(() => location.reload(), 500); 
    }

    function goBack() {
        window.location.href = "/minigames/gameLists.php";
    }
</script>

</body>
</html>
