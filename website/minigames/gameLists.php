<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/animations.css">  
    <link rel="stylesheet" href="../css/main.css">  
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/gameLists.css">
    <title>Game Lists</title>
    
    
    
</head>
<body>
    <?php
    session_start();
    //import database
    include("../connection.php");
    include(__DIR__ . '/gamedb.php');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $search = $_POST['search'] ?? null;
        
        // Redirect to avoid resubmission warning
        header('Location: gameLists.php?search=' . urlencode($search));
        exit();
    } else {
        // Get the search parameter from the query string if it's a GET request
        $search = $_GET['search'] ?? null;
    }
    
    

    if(isset($_SESSION["user"])){
        if(($_SESSION["user"])=="" or $_SESSION['usertype']!='p'){
            header("location: ../login.php");
        }else{
            $useremail=$_SESSION["user"];
        }

    }else{
        header("location: ../login.php");
    }
    




    $sqlmain= "select * from patient where pemail=?";
    $stmt = $database->prepare($sqlmain);
    $stmt->bind_param("s",$useremail);
    $stmt->execute();
    $userrow = $stmt->get_result();
    $userfetch=$userrow->fetch_assoc();

    $userid= $userfetch["pid"];
    $username=$userfetch["pname"];

    $averageRating1 = getAverageGameRating(1);
    $averageRating2 = getAverageGameRating(2);
    $averageRating3 = getAverageGameRating(3);
    $averageRating5 = getAverageGameRating(5);

    $shooterGameRanking = getShooterGameRanking();
    $quizGameRanking = getQuizGameRanking();
    $mindfulCountingGameRanking = getMindfulCountingRanking();
    $cardMatchingGameRanking = getCardMatchingGameRanking();

    // Check if there is at least one top player
    $shooterGametopPlayer = (!empty($shooterGameRanking)) ? $shooterGameRanking[0] : ["name" => "-", "score" => "-"];
    $quizGametopPlayer = (!empty($quizGameRanking)) ? $quizGameRanking[0] : ["name" => "-", "score" => "-"];
    $countingGametopPlayer = (!empty($mindfulCountingGameRanking)) ? $mindfulCountingGameRanking[0] : ["name" => "-", "score" => "-"];
    $cardMatchingGametopPlayer = (!empty($cardMatchingGameRanking)) ? $cardMatchingGameRanking[0] : ["name" => "-", "score" => "-"];


    $shooterGamePersonalRanking = getShooterGameThisWeekHighestScore($useremail);
    $quizGamePersonalRanking = getQuizGameThisWeekHighestScore($useremail);
    $mindfulCountingGamePersonalRanking = getMindfulCountingThisWeekHighestScore($useremail);
    $cardMatchingGamePersonalRanking = getCardMatchingThisWeekHighestScore($useremail);


    $missionStatuses = [];
    $completedCount = 0;

    // Fetch bingo missions from the database
    $playId = getOrCreateBingoPlayId($useremail); // get playId is set in db, if not create it
    $bingoMissions = getBingoMissionsByPlayId($playId);

  foreach ($bingoMissions as $mission) {
    $i = $mission['missionNumber']; // This is the missionNumber from DB
    $target = $mission['target'];
    $completed = false;
  
    switch ($i) {
        case 1:
            $completed = hasPlayedDifferentGamesThisWeek($useremail, $target+1);
            break;
        case 2:
            $completed = hasReachedTotalFromWeeklyHighScores($useremail, $target);
            break;
        case 3:
            $completed = hasSurvivedXSecondsInGame5ThisWeek($useremail, $target);
            break;
        case 4:
            $completed = hasScoredAtLeastXPercentInGame3ThisWeek($useremail, $target);
            break;
        case 5:
            $completed = hasDestroyedXEnemiesInGame5ThisWeek($useremail, $target);
            break;
        case 6:
            $completed = hasScoredXInGame4ThisWeek($useremail, $target);
            break;
        case 7:
            $completed = hasPlayedMindfulCountingWithHighDifficulty($useremail, $target);
            break;
        case 8:
            $completed = hasUserConversatedThisWeek($useremail, $target);
            break;
        case 9:
            $completed = hasPostedInForum($useremail, $target);
            break;
        case 10:
            $completed = hasUserScheduledAppointmentsAtLeast($useremail, $target);
            break;
        case 11:
            $completed = hasCheckedInAtLeast($useremail, $target);
            break;
        case 12:
            $completed = hasUserVotedAtLeast($useremail, $target);
            break;
    }
  
    if ($completed) {
        $completedCount++;
    }
  }

    // Make completed string available for File B
    $completedProgress = "$completedCount/9";
    
    $games = [];
    if ($search != null) {
        $searchGames = getGamesBySearch($search);
    
        foreach ($searchGames as $game) {
            $id = $game['gameId'];
            $name = $game['gameName'];
            $status = $game['status'];
        
            // Defaults
            $rating = null;
            $completed = null;
            $url = '/minigames/' . strtolower(str_replace(' ', '', $name)) . '.php';
            $background = strtolower(str_replace(' ', '', $name)) . '-background';
        
            
            // Customize for known IDs
            if ($id == 1) {
                $rating = null;
                $completed = $completedProgress;
                $url = '/minigames/bingo.php';
                $background = 'bingo-background';
            } elseif ($id == 2) {
                $rating = $averageRating2;
                $url = '/minigames/mindfulCounting.php';
                $background = 'mindfulCounting-background';
            } elseif ($id == 3) {
                $rating = $averageRating3;
                $url = '/minigames/positivityQuiz.php';
                $background = 'positiveQuiz-background';
            } elseif ($id == 4) {
                $rating = $averageRating1;
                $url = '/minigames/cardMatching.php';
                $background = 'memoryMatching-background';
            } elseif ($id == 5) {
                $rating = $averageRating5;
                $url = '/minigames/spaceshipShooter.php';
                $background = 'spaceshipShooting-background';
            }
        
            $gameItem = [
                'id' => $id,
                'name' => $name,
                'rating' => $rating,
                'url' => $url,
                'background' => $background,
                'status' => $status,
            ];
        
            // Add completed if applicable
            if ($completed !== null) {
                $gameItem['completed'] = $completed;
            }
        
            $games[] = $gameItem;
        }
    }
    else{
        $favorites = getFavoriteGameIds($useremail);
        $games = [
            [
                'id' => 1,
                'name' => 'Wellness Quest Bingo',
                'rating' => null, // custom display later
                'completed' => $completedProgress,
                'url' => '/minigames/bingo.php',
                'background' => 'bingo-background',
                'status' => getGameStatusById(1),
            ],
            [
                'id' => 2,
                'name' => 'Mindful Counting',
                'rating' => $averageRating2,
                'url' => '/minigames/mindfulCounting.php',
                'background' => 'mindfulCounting-background',
                'status' => getGameStatusById(2),
            ],
            [
                'id' => 3,
                'name' => 'Positivity Quiz',
                'rating' => $averageRating3,
                'url' => '/minigames/positivityQuiz.php',
                'background' => 'positiveQuiz-background',
                'status' => getGameStatusById(3),
            ],
            [
                'id' => 4,
                'name' => 'Card Matching',
                'rating' => $averageRating1,
                'url' => '/minigames/cardMatching.php',
                'background' => 'memoryMatching-background',
                'status' => getGameStatusById(4),
            ],
            [
                'id' => 5,
                'name' => 'Spaceship Shooter',
                'rating' => $averageRating5,
                'url' => '/minigames/spaceshipShooter.php',
                'background' => 'spaceshipShooting-background',
                'status' => getGameStatusById(5),
            ],
        ];
    
        foreach ($games as &$game) {
            $game['isFavorite'] = in_array($game['id'], $favorites);
        }
        unset($game); // break reference
        
        // Sort: favorites first
        usort($games, function ($a, $b) {
            return $b['isFavorite'] <=> $a['isFavorite']; // Descending (true first)
        });
    }
    

    ?>
    <div class="container">
    <?php include(__DIR__ . '/../patient/patientMenu.php'); ?>
        <div class="dash-body" style="margin-top: 15px">
            <table border="0" width="100%" style=" border-spacing: 0;margin:0;padding:0;" >
                        
                        <tr >
                            
                            <td colspan="1" class="nav-bar" >
                            <div style="display: flex; align-items: center; gap: 20px;">
                                <p style="font-size: 23px;padding-left:12px;font-weight: 600;margin-left:20px;">Game Lists</p>
                                <form action="gameLists.php" method="post" style="display: flex; align-items: center; justify-content: center;">
                                    <input type="search" name="search" value="<?php echo htmlspecialchars($search); ?>" class="input-text" 
                                        placeholder="Search Games Name" list="searchGames" style="width: 450px;">
                                    &nbsp;&nbsp;
                                    <input type="Submit" value="Search" class="login-btn btn-primary btn" style="padding-left: 25px;padding-right: 25px;padding-top: 10px;padding-bottom: 10px;">
                                </form>
                            </div>
                            </td>
                            
                            <td width="25%">
                                <?php $userPoints = getPatientPointsByEmail($useremail); ?>
                                <p style="font-size: 23px;font-weight: 600; text-align: right; padding-right: 30px;">
                                    Your Points:
                                    <span style="background-color: #f0f0f0; color: #4CAF50; font-weight: bold; font-size: 16px; padding: 2px 10px; border-radius: 10px; margin-left: 6px;">
                                        <?php echo $userPoints; ?> ⭐
                                    </span>
                                </p>
                            </td>


        
        
                        </tr>
                <tr>
                    <td colspan="4" >
                        
                    <center>
                    <table class="filter-container doctor-header gameLists-background" style="border: none;width:95%" border="0">
                        <tr>
                            <!-- Title Row -->
                            <td width="52%"><b>Mini Games Name</b></td>
                            <td width="28%"><b>This Week's Highest Player Score</b></td>
                            <td width="20%"><b>My Score</b></td>
                        </tr>
                        <tr>
                            <td>Card Matching</td>
                            <td>
                                <?php 
                                if ($cardMatchingGametopPlayer['score'] == '-') {
                                    echo "-";
                                } else {
                                    echo $cardMatchingGametopPlayer['score'] . " (Player :" . $cardMatchingGametopPlayer['name'] . ")";
                                }
                                ?>
                            </td>
                            <td><?php echo $cardMatchingGamePersonalRanking; ?></td>
                        </tr>
                        <tr>
                            <td>Mindful Counting</td>
                            <td>
                                <?php 
                                if ($countingGametopPlayer['score'] == '-') {
                                    echo "-";
                                } else {
                                    echo $countingGametopPlayer['score'] . " (Player :" . $countingGametopPlayer['name'] . ")";
                                }
                                ?>
                            </td>
                            <td><?php echo $mindfulCountingGamePersonalRanking; ?></td>
                        </tr>
                        <tr>
                            <td>Positivity Quiz</td>
                            <td>
                                <?php 
                                if ($quizGametopPlayer['score'] == '-') {
                                    echo "-";
                                } else {
                                    echo $quizGametopPlayer['score'] . " (Player :" . $quizGametopPlayer['name'] . ")";
                                }
                                ?>
                            </td>
                            <td><?php echo $quizGamePersonalRanking; ?></td>
                        </tr>
                        <tr>
                            <td>Spaceship Shooter</td>
                            <td>
                                <?php 
                                if ($shooterGametopPlayer['score'] == '-') {
                                    echo "-";
                                } else {
                                    echo $shooterGametopPlayer['score'] . " (Player :" . $shooterGametopPlayer['name'] . ")";
                                }
                                ?>
                            </td>
                            <td><?php echo $shooterGamePersonalRanking; ?></td>
                        </tr>
                    </table>
                    
                    <?php foreach ($games as $game): ?>
                        <center>
                        <div class="game-container">
                            <span class="favorite-icon" data-game-id="<?php echo $game['id']; ?>"
                                onclick="toggleFavorite(this, <?php echo $game['id']; ?>)">
                                <?php echo isset($game['isFavorite']) ? ($game['isFavorite'] ? '❤️' : '🤍') : ''; ?>
                            </span>
                            <table class="filter-container doctor-header bingo-background <?php echo $game['background']; ?>" 
                                style="border: none;width:95%" border="0">
                                <tr>
                                    <td width="57%" rowspan="2"><b><?php echo $game['name']; ?></b></td>
                                    <td width="28%">
                                        <b>
                                        <?php 
                                            if ($game['id'] == 1) {
                                                echo "Completed Tasks: " . $game['completed'];
                                            } else {
                                                echo "Rating: " . $game['rating'] . " / 5.0";
                                            }
                                        ?>
                                        </b>
                                    </td>
                                    <td width="15%" rowspan="2">
                                    <?php if ($game['status'] == 0): ?>
                                        <div class="button-wrapper">
                                            <a href="javascript:void(0);" class="play-now-btn disabled-btn"><?php echo ($game['id'] == 1) ? "View Quest" : "Play Now"; ?></a>
                                            <div class="tooltip">This game is currently unavailable.</div>
                                        </div>
                                    <?php else: ?>
                                        <a href="<?php echo $game['url']; ?>" class="play-now-btn">
                                            <?php echo ($game['id'] == 1) ? "View Quest" : "Play Now"; ?>
                                        </a>
                                    <?php endif; ?>

                                    </td>
                                </tr>
                            </table>
                        </div>
                    <?php endforeach; ?>
        </div>
    </div>

    <script>
        const favoriteGameIds = <?php echo json_encode($favorites); ?>;

        function toggleFavorite(icon, gameId) {
            const isFavoriting = icon.innerHTML === "🤍";
            icon.innerHTML = isFavoriting ? "❤️" : "🤍";

            fetch("updateFavoriteList.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `gameId=${gameId}&action=${isFavoriting ? 'add' : 'remove'}`
            })
            .then(response => response.json())
            .then(data => {
                console.log("Favorite updated:", data.message);
            })
            .catch(error => {
                console.error("Error:", error);
                // Revert icon if error
                icon.innerHTML = isFavoriting ? "🤍" : "❤️";
            });
        }

        function markFavoritesOnLoad() {
            document.querySelectorAll('.favorite-icon').forEach(icon => {
                const gameId = parseInt(icon.dataset.gameId);
                if (favoriteGameIds.includes(gameId)) {
                    icon.innerHTML = "❤️";
                } else {
                    icon.innerHTML = "🤍";
                }
            });
        }

        window.onload = markFavoritesOnLoad;
    </script>
</body>
</html>