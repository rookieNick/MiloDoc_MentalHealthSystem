<?php
session_start();
date_default_timezone_set('Asia/Kuala_Lumpur');
include(__DIR__ . '../../connection.php');
include(__DIR__ . '/gamedb.php');

$_SESSION["currentGame"] = 5;

$gameId = 5;
$gameStatus = getGameStatusById($gameId);
if ($gameStatus == 0) {
    $gameName = getGameNameById($gameId);
    $gameNameUrl = urlencode($gameName);
    header("Location: /minigames/underMaintenance.php?game=" . $gameNameUrl);
    exit;
}

$level = getShooterGameLevel();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Space Shooter</title>
    <style>
        body {
            margin: 0;
            overflow: hidden;
            background-color: black;
        }
        canvas {
            display: block;
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
        .popup, .power-shop {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px 70px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            text-align: center;
            display: none;
            z-index: 10;
        }
        #instruction-box {
            display: block; 
        }
        .explosion {
            width: 50px;
            height: 50px;
            animation: explode 0.5s ease-out;
        }

        @keyframes explode {
            0% { transform: scale(0.5); opacity: 1; }
            100% { transform: scale(1.5); opacity: 0; }
        }
    </style>
</head>
<?php include(__DIR__ . '/ratingBox.php'); ?>
<body>
    <!-- Background Video -->
    <video id="background-video" autoplay loop muted>
        <source src="/video/spaceLoopVideo.mp4" type="video/mp4">
        Your browser does not support the video tag.
    </video>

    <!-- Canvas for Game -->
    <canvas id="gameCanvas"></canvas>

    <!-- Top Left Buttons (Visible Before Game Starts) -->
    <div class="top-left-btns">
        <button id="backBtn" onclick="goBack()">Back</button>
        <!-- <button id="powerShopBtn" onclick="showPowerShop()">Power</button> -->
    </div>

    <!-- Instruction Box (Visible Before Game Starts) -->
    <div id="instruction-box" class="popup">
        <h2>Spaceship Shooter</h2>
        <p>Shoot the Space Ship Without Touching Them!</p>
        <button onclick="startGame()">Start</button>
    </div>

    <!-- Power Shop (Hidden Initially) -->
    <!-- <div id="power-shop" class="power-shop" style="background-color:aqua;">
        <h3>Buy the Power</h3>
        <button onclick="speedUpShooting()">Speed Up Shooting</button>
        <button onclick="closePowerShop()">Close</button>
    </div> -->

    <!-- Game Over Box (Hidden Initially) -->
    <div id="game-over-box" class="popup" style="display: none;">
        <h2>Game Over!</h2>
        <p>Your Score: <span id="scoreDisplay"></span></p>
        <button onclick="restartGame()">Restart</button>
        <button onclick="rateGame(5)">Rate The Game</button>  <!-- gameId 5 -->
        <button onclick="goBack()">Exit</button>
    </div>

    

    <!-- Sound Toggle Button -->
    <button id="sound-btn" onclick="toggleSound()">🔇</button>

    <!-- Sound Effects -->
    <audio id="shootingSound" src="/audio/shooting.mp3"></audio>
    <audio id="shipExploreSound" src="/audio/shipExplosion.mp3"></audio>

    
    


    <script>
        const canvas = document.getElementById("gameCanvas");
        const ctx = canvas.getContext("2d");
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;

        let shipImg = new Image();
        shipImg.src = "/img/spaceship.png";
        let enemyImg = new Image();
        enemyImg.src = "/img/enemyShip.png";

        const shootingSound = document.getElementById('shootingSound');
        const shipExploreSound = document.getElementById('shipExploreSound');

        let ship = { x: canvas.width / 2, y: canvas.height - 50, width: 50, height: 50 };
        let bullets = [];
        let enemies = [];
        let gameRunning = true;
        let enemiesDestroyed = 0;
        let enemySpawnInterval;
        let bulletsFired = 0;
        let startTime;

        let shootingInterval;
        const baseShootingSpeed = <?= $level['shooting_speed_ms'] ?>;
        const enemySpawnSpeed = <?= $level['enemy_spawn_speed_ms'] ?>;
        const baseEnemySpeed = parseFloat(<?= $level['base_enemy_speed'] ?>);
        const speedIncrementPerKill = parseFloat(<?= $level['speed_increment_per_kill'] ?>);
        const baseEnemySpeedX = (baseEnemySpeed / 4) * 3;
        const baseEnemySpeedY = baseEnemySpeed;
        const enemySpeedXIncrement = speedIncrementPerKill / 2;
        const enemySpeedYIncrement = speedIncrementPerKill;
        const maxEnemySpeedX = 4.5;
        const maxEnemySpeedY = 6;
        
        // Update ship position based on mouse movement
        document.addEventListener("mousemove", (event) => {
            ship.x = event.clientX - ship.width / 2;
        });
        
        // Automatically shoot bullets at intervals
        function shoot() {
            if (!gameRunning) return;
            bulletsFired++; // Increase bullets fired
            bullets.push({ x: ship.x + ship.width / 2 - 5, y: ship.y, width: 5, height: 25 }); // Bullet width can be adjusted here
            shootingSound.play(); // Play shootingSound
        }
        
        
        // Spawn enemies at random positions
        function spawnEnemy() {
            if (!gameRunning) return;

            // multiple enemies spawn based on enemies destroyed
            let spawnCount = 1;
            if (enemiesDestroyed >= 20) {
                spawnCount = 3;
            } else if (enemiesDestroyed >= 10) {
                spawnCount = 2;
            }
            for (let i = 0; i < spawnCount; i++) {
                let x = Math.random() * (canvas.width - 50);
                enemies.push({ x, y: 0, width: 50, height: 50, speed: baseEnemySpeed + enemiesDestroyed * speedIncrementPerKill});
            }
        }

        function updateShootingInterval() {
            clearInterval(shootingInterval);
            if(baseShootingSpeed < 400){
                shootingInterval = setInterval(shoot, baseShootingSpeed);
            } else {
                const newInterval = Math.max(400, baseShootingSpeed - enemiesDestroyed * 10); // Min 400ms cap to prevent too fast
                shootingInterval = setInterval(shoot, newInterval);
            }
        }
        
        
        function update() {
            if (!gameRunning) return;
            
            // Move bullets
            bullets.forEach((bullet, index) => {
                bullet.y -= 5;
                if (bullet.y < 0) bullets.splice(index, 1);
            });

            // Move enemies
            enemies.forEach((enemy, index) => {
                let speedX = Math.min(baseEnemySpeedX + enemiesDestroyed * enemySpeedXIncrement, maxEnemySpeedX);
                let speedY = Math.min(baseEnemySpeedY + enemiesDestroyed * enemySpeedYIncrement, maxEnemySpeedY);


                // Move enemy towards the ship horizontally
                if (enemy.x < ship.x) {
                    enemy.x += speedX;
                } else if (enemy.x > ship.x) {
                    enemy.x -= speedX;
                }

                // Move enemy downward
                enemy.y += speedY;

                // Remove enemy if it reaches bottom
                if (enemy.y > canvas.height) enemies.splice(index, 1);

                // Check collision with player
                if (
                    ship.x < enemy.x + enemy.width &&
                    ship.x + ship.width > enemy.x &&
                    ship.y < enemy.y + enemy.height &&
                    ship.y + ship.height > enemy.y
                ) {
                    gameOver();
                }
            });

            // Check collision between bullets and enemies
            bullets.forEach((bullet, bulletIndex) => {
                enemies.forEach((enemy, enemyIndex) => {
                    if (
                        bullet.x < enemy.x + enemy.width &&
                        bullet.x + bullet.width > enemy.x &&
                        bullet.y < enemy.y + enemy.height &&
                        bullet.y + bullet.height > enemy.y
                    ) {
                        bullets.splice(bulletIndex, 1);
                        destroyEnemy(enemy);
                        enemies.splice(enemyIndex, 1);
                    }
                });
            });

            draw();
            requestAnimationFrame(update);
        }


        function destroyEnemy(enemy) {
            const explosion = document.createElement("img");
            explosion.src = "/img/explosion.png";
            explosion.classList.add("explosion");

            // Position explosion correctly (use `enemy.x` and `enemy.y` from canvas)
            explosion.style.position = "absolute";
            explosion.style.left = enemy.x + "px";
            explosion.style.top = enemy.y + "px";
            explosion.style.width = enemy.width + "px";
            explosion.style.height = enemy.height + "px";

            document.body.appendChild(explosion);

            // Remove explosion after 0.5 seconds
            setTimeout(() => explosion.remove(), 500);
            enemiesDestroyed++; // Increase count of destroyed enemies
        }
        
        function draw() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            // Draw ship image
            ctx.drawImage(shipImg, ship.x, ship.y, ship.width, ship.height);
                    
            // Draw bullets
            ctx.fillStyle = "red";
            bullets.forEach((bullet) => ctx.fillRect(bullet.x, bullet.y, bullet.width, bullet.height));
            
            // Draw enemy images
            enemies.forEach((enemy) => ctx.drawImage(enemyImg, enemy.x, enemy.y, enemy.width, enemy.height));
        }
        
        function restartGame() {
            location.reload(); // Reload page to restart game
        }

        function startGame() {
            document.getElementById("instruction-box").style.display = "none";
            // document.getElementById("powerShopBtn").style.display = "none";
            document.getElementById("backBtn").style.display = "none";
            gameRunning = true;
            startTime = Date.now(); // Start tracking survival time
            canvas.style.cursor = "none"; // Hide cursor when game starts
            // setInterval(shoot, shootingSpeed); // adjust shooting speed
            updateShootingInterval(); // set initial shooting speed
            enemySpawnInterval = setInterval(spawnEnemy, enemySpawnSpeed);
            update(); // Start the game loop
        }

        function showPowerShop() {
            // document.getElementById("power-shop").style.display = "block";
            document.getElementById("instruction-box").style.display = "none";
        }

        function closePowerShop() {
            document.getElementById("power-shop").style.display = "none";
            document.getElementById("instruction-box").style.display = "block";
        }

        function gameOver() {
            const wasMutedBefore = music.muted; // Save current state before muting
            
            if(music.muted == false){
                shipExploreSound.play(); // Play shipExploreSound
            }
            canvas.style.cursor = "default"; // Show cursor again

            music.muted = true; //muted
            shootingSound.muted = true; //muted

            localStorage.setItem("soundMuted", wasMutedBefore); // Save the preference

            let survivalTime = Math.floor((Date.now() - startTime) / 1000); // Convert to seconds
            let accuracy = bulletsFired > 0 ? ((enemiesDestroyed / bulletsFired) * 100).toFixed(1) : 0;
            let finalScore = (enemiesDestroyed * 100) + (accuracy * 50) + (survivalTime * 5); // Formula

            // Call PHP to generate a new play ID and save game data
            fetch("saveSpaceshipShooterData.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `gameId=5&enemiesDestroyed=${enemiesDestroyed}&bulletsFired=${bulletsFired}&accuracy=${accuracy}&survivalTime=${survivalTime}&finalScore=${finalScore}`
            })
            .then(response => response.json())
            .then(data => {
                console.log("Game data saved:", data);
            })
            .catch(error => console.error("Error:", error));
            
            // Remove ship and show explosion
            const explosion = document.createElement("img");
            explosion.src = "/img/explosion.png"; // Explosion image
            explosion.classList.add("explosion");

            explosion.style.position = "absolute";
            explosion.style.left = ship.x + "px";
            explosion.style.top = ship.y + "px";
            explosion.style.width = ship.width + "px";
            explosion.style.height = ship.height + "px";

            document.body.appendChild(explosion);
            
            // Move the ship outside the visible screen
            ship.x = -9999;
            ship.y = -9999;

            clearInterval(enemySpawnInterval); // Stop enemy generation

            // Remove explosion after 0.5s
            setTimeout(() => explosion.remove(), 500);

            // Wait 1.5 seconds before showing game-over screen
            setTimeout(() => {
                document.getElementById("scoreDisplay").innerText = finalScore;
                document.getElementById("game-over-box").style.display = "block";
                document.getElementById("backBtn").style.display = "block";
            }, 1500);
        }

        function goBack() {
            window.location.href = "/minigames/gameLists.php";
        }

        // Initialize audio elements
        const music = new Audio("/audio/shootingBackgroundMusic.mp3");

        music.loop = true;
        music.muted = true; // Start muted
        shootingSound.muted = true; // Start muted

        // Ensure music plays after user interaction
        document.addEventListener("click", () => {
            music.play().catch(error => console.log("Autoplay blocked:", error));
        }, { once: true });

        // Function to toggle sound
        function toggleSound() {
            const isMuted = music.muted; // Get current state
            const newMutedState = !isMuted;

            music.muted = newMutedState;
            shootingSound.muted = newMutedState;

            // Save the preference
            localStorage.setItem("soundMuted", newMutedState);
            
            // Update button icon based on new state
            document.getElementById("sound-btn").innerHTML = isMuted ? "🔊" : "🔇";
        }

        window.onload = function () {
            const savedMutedState = localStorage.getItem("soundMuted") === "true"; // Convert string to boolean

            music.muted = savedMutedState;
            shootingSound.muted = savedMutedState;

            // Set button icon correctly
            document.getElementById("sound-btn").innerHTML = savedMutedState ? "🔇" : "🔊";
        };
        
        


    </script>
</body>
</html>
