<?php
session_start();
date_default_timezone_set('Asia/Kuala_Lumpur');
include(__DIR__ . '../../connection.php');
include(__DIR__ . '/gamedb.php');

$gameId = 2;
$gameStatus = getGameStatusById($gameId);
if ($gameStatus == 0) {
    $gameName = getGameNameById($gameId);
    $gameNameUrl = urlencode($gameName);
    header("Location: /minigames/underMaintenance.php?game=" . $gameNameUrl);
    exit;
}

$_SESSION["currentGame"] = 2;

// Fetch level data
$levelData = getAllLevelData();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mindful Counting - Flying Birds</title>
    <style>
        body {
            text-align: center;
            overflow: hidden;
            margin: 0;
        }
        video {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1;
        }
        .bird, .apple, .banana {
            position: absolute;
            width: 80px;
            animation: fly 5s linear forwards, flap 0.3s infinite alternate;
        }
        @keyframes fly {
            from { left: -60px; }
            to { left: 100vw; }
        }
        @keyframes flap {
            from { transform: rotate(-10deg); }
            to { transform: rotate(10deg); }
        }
        .popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            text-align: center;
            display: none;
            z-index: 10;
        }
        #sound-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: none;
            border: none;
            font-size: 30px;
            cursor: pointer;
            transition: transform 0.2s;
        }
        #sound-btn:hover {
            transform: scale(1.1);
        }
        .popup, .level-popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            text-align: center;
            display: none;
            z-index: 10;
        }
        .top-left-btns {
            position: fixed;
            top: 10px;
            left: 10px;
        }
        button {
            margin: 5px;
            padding: 10px;
            font-size: 16px;
            cursor: pointer;
        }
    </style>
</head>
<?php include(__DIR__ . '/ratingBox.php'); ?>
<body>
    <video id="background-video" autoplay loop muted></video>
    <div class="top-left-btns">
        <button id="backBtn">Back</button>
        <button id="levelBtn" onclick="showLevelPopup()">Set Level</button>
    </div>

    <div id="instruction-box" class="popup">
        <h2>Mindful Counting - Count the Flying Birds</h2>
        <p>Count how many birds fly across the screen! Ignore the others!</p>
        <button onclick="closePopup()">Start</button>
    </div>

    <div id="level-popup" class="level-popup" style="background-color:aqua">
        <h3>Select Difficulty</h3>
        <button onclick="setLevel('easy')">Easy</button>
        <button onclick="setLevel('medium')">Medium</button>
        <button onclick="setLevel('hard')">Hard</button>
        <button onclick="setLevel('extreme')">Extreme</button>
        <button onclick="setLevel('survival')">Survival</button>
    </div>

    <div class="popup" id="setTime">
        <p>Select Survival Time:</p>
        <input type="range" id="timeSlider" min="20" max="60" value="20" step="1" oninput="updateTimeValue()">
        <p>Selected Time: <span id="timeValue">20</span> seconds</p>
        <button onclick="timeSelected()">Confirm</button>
    </div>

    <div id="game-over-box" class="popup">
        <p id="gameoverline1">Game Over! Please enter the number of birds you counted:</p>
        <input type="number" id="userGuess">
        <button style="background-color: #28a745; color: white;" onclick="checkAnswer()">Submit</button>
        <p id="result"></p>

        <!-- Buttons in one line, initially hidden -->
        <div id="buttonContainer" style="display: none; margin-top: 10px; gap: 10px; justify-content: center;">
            <button class="gameoverbtn" onclick="restartGame()">Restart</button>
            <button class="gameoverbtn" onclick="rateGame(2)">Rate The Game</button>
            <button class="gameoverbtn" onclick="goBack()">Exit</button>
        </div>
    </div>

    <button id="sound-btn" onclick="toggleSound()">🔊</button>
    <p style="display: none;">Bird Count: <span id="count" style="display: none;">0</span></p>
    <p id="currentLevel">Select The Level...</p> <!-- Default displayed level -->

    <!-- Sound Effects -->
    <audio id="gameWinSound" src="/audio/gameWin.mp3"></audio>
    <audio id="gameLostSound" src="/audio/gameLost.mp3"></audio>

    <script>
        const levelData = <?php echo json_encode($levelData); ?>;
        let birdCount = 0;
        let gameRunning = false;
        let gameEnd = false;

        let birdSpeed = 1000;  // Default generate bird (easy speed)
        let birdSpeedGap = 1000;  // Speed of random generate bird
        let selectedLevel = "easy"; // Default level
        let flySpeed = 7; // Default speed for easy mode

        //flying speed (default easy)
        let range = 0;
        let min = 7;

        let gameTimeout = 12000; // Default game time (12 seconds)
        let finalScore = 0; // Final score to be calculated

        const gameWinSound = document.getElementById('gameWinSound');
        const gameLostSound = document.getElementById('gameLostSound');

        function gameSetup() {
            
            showLevelPopup();

            const video = document.getElementById("background-video");
            const rand = Math.random();
            let src = "";

            if (rand < 0.55) {
                src = "/video/countingBackground1.mp4";
            } else if (rand < 0.88) {
                src = "/video/countingBackground2.mp4";
            } else {
                src = "/video/countingBackground3.mp4";
            }

            video.src = src;
        }

        function closePopup() {
            document.getElementById("instruction-box").style.display = "none";
            startGame();
        }

        function showLevelPopup() {
            document.getElementById("instruction-box").style.display = "none";
            document.getElementById("level-popup").style.display = "block";
            document.getElementById("setTime").style.display = "none";
        }
        
        function timeSelected(){
            document.getElementById("setTime").style.display = "none";
            document.getElementById("instruction-box").style.display = "block";
            let timeSlider = document.getElementById("timeSlider");
            document.getElementById("currentLevel").textContent = `Current Level: ${selectedLevel} (${timeSlider.value} seconds)`;
        }

        function updateTimeValue() {
            let timeSlider = document.getElementById("timeSlider");
            document.getElementById("timeValue").textContent = timeSlider.value;
            gameTimeout = timeSlider.value * 1000; // Convert to milliseconds
        }

        function setLevel(level) {
            selectedLevel = level;

            const levelConfig = levelData[level]; // No need to fetch individually, it's all in one object

            if (levelConfig) {
                min = parseFloat(levelConfig.minimum_flight_time);
                range = parseFloat(levelConfig.speed_variation);
                birdSpeed = parseInt(levelConfig.base_bird_speed);
                birdSpeedGap = parseInt(levelConfig.bird_speed_random_gap);
                gameTimeout = parseInt(levelConfig.game_timeout);

                // Handle the new "survival" mode separately if needed
                if (level === "survival") {
                    document.getElementById("level-popup").style.display = "none";
                    document.getElementById("setTime").style.display = "block";

                    const timeSlider = document.getElementById("timeSlider");
                    const timeValue = document.getElementById("timeValue");

                    const minTime = gameTimeout / 1000;

                    // Set dynamic minimum and reset values
                    timeSlider.min = minTime;
                    timeSlider.value = minTime;
                    timeValue.textContent = minTime;
    
                    return; // Exit function to prevent showing the level popup again
                }
            }
            
            document.getElementById("level-popup").style.display = "none";
            document.getElementById("instruction-box").style.display = "block";
            // Update the displayed level
            document.getElementById("currentLevel").textContent = `Current Level: ${selectedLevel}`;
        }

        function generateFlySpeed(range, min) {
            return Math.random() * range  + min; // Random between 3 - 7
        }

        function startGame() {
            if (!gameRunning) {
                gameRunning = true;
                birdCount = 0;
                document.getElementById("count").textContent = birdCount;
                createFlyingObject();
                document.getElementById("backBtn").style.display = "none";
                document.getElementById("levelBtn").style.display = "none";
                setTimeout(stopGame, gameTimeout);  // Stop generating objects after 12 sec(default)
            }
        }

        function createFlyingObject() {
            if (!gameRunning || gameEnd) return;

            let isBird = Math.random() > 0.35;   // 65% chance to create bird, 35% other
            let object = document.createElement("img");

            if (isBird) {
                object.src = "/img/bird.png";
                object.className = "bird";
                birdCount++;   // Only count birds
                document.getElementById("count").textContent = birdCount;
            } else {
                object.src = Math.random() > 0.5 ? "/img/apple.png" : "/img/banana.png";
                object.className = object.src.includes("apple") ? "apple" : "banana";
            }

            object.style.top = Math.random() * 80 + "vh";   // Random height
            flySpeed = generateFlySpeed(range, min);   // Random speed between 1 - 3 etc
            object.style.animation = `fly ${flySpeed}s linear forwards, flap 0.3s infinite alternate`;

            document.body.appendChild(object);
            setTimeout(() => object.remove(), flySpeed * 1000);   // Remove after flySpeed seconds

            setTimeout(createFlyingObject, Math.random() * birdSpeedGap + birdSpeed);   // Random delay 0.1-0.3etc sec
        }

        function stopGame() {
            gameRunning = false;

            setTimeout(() => {
                document.getElementById("game-over-box").style.display = "block";
                gameEnd = true;
            }, 5000);  // Ensure all objects finish flying before showing end screen
        }

        function calculateFinalScore() {
            let timeUsed = gameTimeout / 1000; // Convert to seconds
            let levelMultiplier = (selectedLevel === "easy") ? 1 :
                                (selectedLevel === "medium") ? 2 :
                                (selectedLevel === "hard") ? 3 :
                                4;  // Extreme and Survival both use 4

            let finalScore = (levelMultiplier * timeUsed * 250) + (birdCount * 10);
            return finalScore;
        }

        function checkAnswer() {
            let userGuess = parseInt(document.getElementById("userGuess").value);
            let resultText = document.getElementById("result");
            let inputField = document.getElementById("userGuess");
            let submitButton = inputField.nextElementSibling; // The Submit button

            // Check if input is empty or not a valid number
            if (isNaN(userGuess) || inputField.value.trim() === "") {
                    resultText.textContent = "Please enter a valid number!";
                    return;
                }

            if (userGuess === birdCount) {
                if(music.muted == false){
                    gameWinSound.play(); // Play game win sound
                }
                finalScore = calculateFinalScore();
                resultText.innerHTML = `Congratulations! You are Correct 🎉<br>Final Score: ${finalScore}`;

                // Insert game data into the database

                let timeUsed = gameTimeout / 1000; // Convert to seconds

                // Call PHP to generate a new play ID and save game data
                fetch("saveMindfulCountingData.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `gameId=2&difficultyLevel=${selectedLevel}&gameTimeOut=${timeUsed}&birdCount=${birdCount}&finalScore=${finalScore}`
                })
                .then(response => response.json())
                .then(data => {
                    console.log("Game data saved:", data);
                })
                .catch(error => console.error("Error:", error));

            } else {
                if(music.muted == false){
                    gameLostSound.play(); // Play game lost sound
                }
                resultText.textContent = `Wrong answer! The correct number of birds is ${birdCount}.`;
            }

            // Replace input field with a text display
            let userGuessDisplay = document.createElement("p");
            userGuessDisplay.textContent = `Your Answer: ${userGuess}`;
            userGuessDisplay.style.fontWeight = "bold";
            inputField.replaceWith(userGuessDisplay);

            // Remove Submit button
            submitButton.remove();

            document.getElementById("gameoverline1").style.display = "none";

            //show back btn
            document.getElementById("backBtn").style.display = "block";

            // Show button container
            document.getElementById("buttonContainer").style.display = "flex";
        }

        const music = new Audio("/audio/BirdsChirping.mp3");
        music.loop = true;
        document.addEventListener("click", () => music.play(), { once: true });

        function toggleSound() {
            const isMuted = music.muted; // Get current state
            const newMutedState = !isMuted;

            music.muted = newMutedState;

            // Save the preference
            localStorage.setItem("soundMuted", newMutedState);
            
            // Update button icon based on new state
            document.getElementById("sound-btn").innerHTML = isMuted ? "🔊" : "🔇";
        }
        
        window.onload = function () {
            const savedMutedState = localStorage.getItem("soundMuted") === "true"; // Convert string to boolean

            music.muted = savedMutedState;

            // Set button icon correctly
            document.getElementById("sound-btn").innerHTML = savedMutedState ? "🔇" : "🔊";
        };

        document.getElementById("backBtn").addEventListener("click", function() {
            window.location.href = "/minigames/gameLists.php";
        });

        function goBack() {
            window.location.href = "/minigames/gameLists.php";
        }

        function restartGame() {
            location.reload(); // Reload page to restart game
        }

        gameSetup();
    </script>
</body>
</html>
