<?php
include(__DIR__ . '../../connection.php');
date_default_timezone_set('Asia/Kuala_Lumpur');

function getPatientPointsByEmail($email) {
    global $database;

    $stmt = $database->prepare("SELECT points FROM patient WHERE pemail = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return $row['points'];
    } else {
        return 0;
    }
}

function getAllGames() {
    global $database;
    $games = [];
    $query = "SELECT * FROM gamelist";
    $result = $database->query($query);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $games[] = $row;
        }
    }
    return $games;
}

function getGamePlayCounts() {
    global $database;
    $counts = [];
    $query = "SELECT gameId, COUNT(*) AS playCount FROM gameplay GROUP BY gameId";
    $result = $database->query($query);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $counts[$row["gameId"]] = $row["playCount"];
        }
    }
    return $counts;
}

function setGameStatus($id, $status) {
    global $database;
    $statusValue = ($status === 'enabled') ? 1 : 0;

    $stmt = $database->prepare("UPDATE gamelist SET status = ? WHERE gameId = ?");
    $stmt->bind_param("is", $statusValue, $id);
    $stmt->execute();
    $stmt->close();
}

function getGamesBySearch($search) {
    global $database;

    $games = [];

    if (empty($search)) {
        // If no search input, return all active games
        $query = "SELECT * FROM gamelist WHERE status = 1";
        $stmt = $database->prepare($query);
    } else {
        // Search with LIKE and status = 1
        $query = "SELECT * FROM gamelist WHERE gameName LIKE ? AND status = 1";
        $stmt = $database->prepare($query);

        if ($stmt === false) {
            die("Prepare failed: " . $database->error);
        }

        $searchTerm = "%" . $search . "%";
        $stmt->bind_param("s", $searchTerm);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $games[] = $row;
        }
    }

    return $games;
}

function getGameStatusById($gameId) {
    global $database;

    $stmt = $database->prepare("SELECT status FROM gamelist WHERE gameId = ?");
    $stmt->bind_param("s", $gameId);
    $stmt->execute();
    $stmt->bind_result($status);
    $stmt->fetch();
    $stmt->close();

    return $status;
}

function getAllMemoryGameTips() {
    global $database;

    $tips = array();

    $stmt = $database->prepare("SELECT tipId, tipText, status FROM memorygame_tips ORDER BY tipId ASC");
    $stmt->execute();
    $stmt->bind_result($tipId, $tipText, $status);

    while ($stmt->fetch()) {
        $tips[] = array(
            "tipId" => $tipId,
            "tipText" => $tipText,
            "status" => $status
        );
    }

    $stmt->close();

    return $tips;
}

function getMemoryGameTipById($tipId) {
    global $database;

    // Prepare the SQL query to fetch tip details by ID
    $stmt = $database->prepare("SELECT tipText, status FROM memorygame_tips WHERE tipId = ?");
    $stmt->bind_param("i", $tipId);  // "i" for integer type
    $stmt->execute();
    
    // Bind the result to variables
    $stmt->bind_result($tipText, $status);
    
    // Fetch the result
    if ($stmt->fetch()) {
        // Return an associative array with tip details
        return array(
            'tipText' => $tipText,
            'status' => $status
        );
    } else {
        // Return false if no record found
        return false;
    }

    // Close the statement
    $stmt->close();
}

function updateMemoryGameTip($tipId, $tipText, $status) {
    global $database;
    
    // Prepare the SQL query to update the tip
    $sql = "UPDATE memorygame_tips SET tipText = ?, status = ? WHERE tipId = ?";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("ssi", $tipText, $status, $tipId); // "ssi" means string, string, integer
    
    // Execute the query
    if ($stmt->execute()) {
        $stmt->close();
        return true;
    } else {
        $stmt->close();
        return false;
    }
}

function insertTip($tipText, $status) {
    global $database;

    $stmt = $database->prepare("INSERT INTO memorygame_tips (tipText, status) VALUES (?, ?)");
    $stmt->bind_param("si", $tipText, $status);

    if ($stmt->execute()) {
        return ["success" => true];
    } else {
        return ["success" => false, "message" => $stmt->error];
    }
}

function getGameNameById($id) {
    global $database;
    $stmt = $database->prepare("SELECT gameName FROM gamelist WHERE gameId = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $stmt->bind_result($gameName);
    $stmt->fetch();
    $stmt->close();
    return $gameName;
}

function getFavoriteGameIds($userEmail) {
    global $database;

    $stmt = $database->prepare("
        SELECT gameId FROM favoritegames fg
        JOIN patient p ON fg.pid = p.pid
        WHERE p.pemail = ?
    ");
    $stmt->bind_param("s", $userEmail);
    $stmt->execute();
    $result = $stmt->get_result();

    $favoriteGameIds = [];
    while ($row = $result->fetch_assoc()) {
        $favoriteGameIds[] = $row['gameId'];
    }

    return $favoriteGameIds;
}

function generateNewPlayId($userEmail, $gameId) {
    global $database;

    $playDate = date('Y-m-d H:i:s'); // Current timestamp

    // Insert into the gameplay table
    $stmt = $database->prepare("INSERT INTO gameplay (userEmail, gameId, playDate) VALUES (?, ?, ?)");
    $stmt->bind_param("sis", $userEmail, $gameId, $playDate);

    if ($stmt->execute()) {
        return $database->insert_id; // Returns the newly generated playId
    } else {
        return 0; // Insert failed
    }
}

// ---------------------------------------------------------------------------------------------------------
//Bingo functions
function hasPlayedDifferentGamesThisWeek($userEmail, $target = 4) {
    global $database;

    $startOfWeek = date("Y-m-d", strtotime("monday this week"));
    $endOfWeek = date("Y-m-d", strtotime("sunday this week"));

    $query = "
        SELECT COUNT(DISTINCT gameId) as gameCount
        FROM gameplay
        WHERE userEmail = ?
        AND playDate BETWEEN ? AND ?
    ";

    $stmt = $database->prepare($query);
    if (!$stmt) {
        die("Prepare failed: " . $database->error);
    }

    $stmt->bind_param("sss", $userEmail, $startOfWeek, $endOfWeek);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return $row['gameCount'] >= $target;
    }

    return false;
}

function hasSurvivedXSecondsInGame5ThisWeek($userEmail, $target = 20) {
    global $database;

    $startOfWeek = date("Y-m-d", strtotime("monday this week"));
    $endOfWeek = date("Y-m-d", strtotime("sunday this week"));

    $query = "
        SELECT 1
        FROM gameplay g
        JOIN spaceshipshooter s ON g.playId = s.playId
        WHERE g.userEmail = ?
        AND g.gameId = 5
        AND g.playDate BETWEEN ? AND ?
        AND s.survivalTime >= ?
        LIMIT 1
    ";

    $stmt = $database->prepare($query);
    if (!$stmt) {
        die("Prepare failed: " . $database->error);
    }

    $stmt->bind_param("sssi", $userEmail, $startOfWeek, $endOfWeek, $target);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->num_rows > 0;
}

function hasScoredAtLeastXPercentInGame3ThisWeek($userEmail, $target = 60) {
    global $database;

    $startOfWeek = date("Y-m-d", strtotime("monday this week"));
    $endOfWeek = date("Y-m-d", strtotime("sunday this week"));

    $query = "
        SELECT 1
        FROM gameplay g
        JOIN quizgame q ON g.playId = q.playId
        WHERE g.userEmail = ?
        AND g.gameId = 3
        AND g.playDate BETWEEN ? AND ?
        AND q.percentageScore >= ?
        LIMIT 1
    ";

    $stmt = $database->prepare($query);
    if (!$stmt) {
        die("Prepare failed: " . $database->error);
    }

    $stmt->bind_param("sssi", $userEmail, $startOfWeek, $endOfWeek, $target);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->num_rows > 0;
}


function hasDestroyedXEnemiesInGame5ThisWeek($userEmail, $target = 20) {
    global $database;

    $startOfWeek = date("Y-m-d", strtotime("monday this week"));
    $endOfWeek = date("Y-m-d", strtotime("sunday this week"));

    $query = "
        SELECT 1
        FROM gameplay g
        JOIN spaceshipshooter s ON g.playId = s.playId
        WHERE g.userEmail = ?
        AND g.gameId = 5
        AND g.playDate BETWEEN ? AND ?
        AND s.enemiesDestroyed >= ?
        LIMIT 1
    ";

    $stmt = $database->prepare($query);
    if (!$stmt) {
        die("Prepare failed: " . $database->error);
    }

    $stmt->bind_param("sssi", $userEmail, $startOfWeek, $endOfWeek, $target);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->num_rows > 0;
}

function hasScoredXInGame4ThisWeek($userEmail, $target = 12000) {
    global $database;

    $startOfWeek = date("Y-m-d", strtotime("monday this week"));
    $endOfWeek = date("Y-m-d", strtotime("sunday this week"));

    $query = "
        SELECT 1
        FROM gameplay g
        JOIN memorygame m ON g.playId = m.playId
        WHERE g.userEmail = ?
        AND g.gameId = 4
        AND g.playDate BETWEEN ? AND ?
        AND m.finalScore >= ?
        LIMIT 1
    ";

    $stmt = $database->prepare($query);
    if (!$stmt) {
        die("Prepare failed: " . $database->error);
    }

    $stmt->bind_param("sssi", $userEmail, $startOfWeek, $endOfWeek, $target);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->num_rows > 0;
}


function hasReachedTotalFromWeeklyHighScores($userEmail, $target = 33000) {
    $shooterGamePersonalRanking = getShooterGameThisWeekHighestScore($userEmail);
    $quizGamePersonalRanking = getQuizGameThisWeekHighestScore($userEmail);
    $mindfulCountingGamePersonalRanking = getMindfulCountingThisWeekHighestScore($userEmail);
    $cardMatchingGamePersonalRanking = getCardMatchingThisWeekHighestScore($userEmail);

    $totalScore = 
        intval($shooterGamePersonalRanking) +
        intval($quizGamePersonalRanking) +
        intval($mindfulCountingGamePersonalRanking) +
        intval($cardMatchingGamePersonalRanking);

    return $totalScore >= $target;
}

function hasPlayedMindfulCountingWithHighDifficulty($userEmail, $target = 1) {
    global $database;

    $startOfWeek = date("Y-m-d", strtotime("monday this week"));
    $endOfWeek = date("Y-m-d", strtotime("sunday this week"));

    // Difficulty mapping
    switch ($target) {
        case 1:
            $difficulties = ['Easy', 'Medium', 'Hard', 'Extreme', 'Survival']; // All
            break;
        case 2:
            $difficulties = ['Medium', 'Hard', 'Extreme', 'Survival']; // Exclude Easy
            break;
        case 3:
            $difficulties = ['Hard', 'Extreme', 'Survival']; // Only High
            break;
        case 4:
            $difficulties = ['Extreme', 'Survival']; // Only Very High
            break;
        default:
            $difficulties = ['Hard', 'Extreme', 'Survival'];
            break;
    }

    // Generate placeholders for SQL
    $placeholders = implode(',', array_fill(0, count($difficulties), '?'));

    $query = "
        SELECT 1
        FROM gameplay g
        JOIN mindfulcounting m ON g.playId = m.playId
        WHERE g.userEmail = ?
        AND g.gameId = 2
        AND g.playDate BETWEEN ? AND ?
        AND m.difficultyLevel IN ($placeholders)
        LIMIT 1
    ";

    $stmt = $database->prepare($query);
    if (!$stmt) {
        die("Prepare failed: " . $database->error);
    }

    // Build parameters
    $types = str_repeat('s', 3 + count($difficulties)); // sss...s
    $params = array_merge([$userEmail, $startOfWeek, $endOfWeek], $difficulties);

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->num_rows > 0;
}


function hasUserConversatedThisWeek($userEmail, $target = 1) {
    global $database;

    // Step 1: Get user ID
    $query = "SELECT pid FROM patient WHERE pemail = ?";
    $stmt = $database->prepare($query);
    if (!$stmt) die("Prepare failed: " . $database->error);
    $stmt->bind_param("s", $userEmail);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $userId = $row['pid'];
    } else {
        return false;
    }

    // Step 2: Define week range
    $startOfWeek = date("Y-m-d 00:00:00", strtotime("monday this week"));
    $endOfWeek = date("Y-m-d 23:59:59", strtotime("sunday this week"));

    // Step 3: Count conversations
    $query = "
        SELECT COUNT(*) AS total
        FROM chatbot_conversation
        WHERE user_id = ?
        AND datetime BETWEEN ? AND ?
    ";
    $stmt = $database->prepare($query);
    if (!$stmt) die("Prepare failed: " . $database->error);
    $stmt->bind_param("sss", $userId, $startOfWeek, $endOfWeek);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return $row['total'] >= $target;
    }

    return false;
}

function hasUserScheduledAppointmentsAtLeast($userEmail, $target = 1) {
    global $database;

    // Step 1: Get the pid from the patient table using the email
    $query = "
        SELECT pid
        FROM patient
        WHERE pemail = ?;
    ";
    $stmt = $database->prepare($query);
    if (!$stmt) {
        die("Prepare failed: " . $database->error);
    }

    $stmt->bind_param("s", $userEmail);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $patientId = $row['pid'];
    } else {
        return false; // No user found with the given email
    }

    // Step 2: Define the start and end of the current week (Monday to Sunday)
    $startOfWeek = date("Y-m-d 00:00:00", strtotime("monday this week"));
    $endOfWeek = date("Y-m-d 23:59:59", strtotime("sunday this week"));

    // Step 3: Check for appointments this week
    $query = "
        SELECT COUNT(*) AS appointmentCount
        FROM appointment
        WHERE pid = ?
        AND appodate BETWEEN ? AND ?;
    ";
    $stmt = $database->prepare($query);
    if (!$stmt) {
        die("Prepare failed: " . $database->error);
    }

    $stmt->bind_param("iss", $patientId, $startOfWeek, $endOfWeek);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $appointmentCount = $row['appointmentCount'];
        return $appointmentCount >= $target;
    }

    return false;
}


function hasPostedInForum($userEmail, $target = 1) {
    global $database;

    // Step 1: Get user ID
    $query = "SELECT user_id FROM user WHERE email = ?";
    $stmt = $database->prepare($query);
    if (!$stmt) die("Prepare failed: " . $database->error);
    $stmt->bind_param("s", $userEmail);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $userId = $row['user_id'];
    } else {
        return false;
    }

    // Step 2: Define week range
    $startOfWeek = date("Y-m-d 00:00:00", strtotime("monday this week"));
    $endOfWeek = date("Y-m-d 23:59:59", strtotime("sunday this week"));

    // Step 3: Count posts
    $query = "
        SELECT COUNT(*) AS total
        FROM post
        WHERE author_id = ?
        AND created_at BETWEEN ? AND ?
    ";
    $stmt = $database->prepare($query);
    if (!$stmt) die("Prepare failed: " . $database->error);
    $stmt->bind_param("iss", $userId, $startOfWeek, $endOfWeek);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return $row['total'] >= $target;
    }

    return false;
}


function hasCheckedInAtLeast($userEmail, $target = 3) {
    global $database;

    // Step 1: Get pid from the patient table using the email
    $query = "
        SELECT pid
        FROM patient
        WHERE pemail = ?;
    ";
    $stmt = $database->prepare($query);
    if (!$stmt) {
        die("Prepare failed: " . $database->error);
    }

    $stmt->bind_param("s", $userEmail);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $patientId = $row['pid'];
    } else {
        return false; // No user found with the given email
    }

    // Step 2: Define the start and end of the current week (Monday to Sunday)
    $startOfWeek = date("Y-m-d 00:00:00", strtotime("monday this week"));
    $endOfWeek = date("Y-m-d 23:59:59", strtotime("sunday this week"));

    // Step 3: Check if the user has checked in at least $target times this week
    $query = "
        SELECT COUNT(*) AS checkInCount
        FROM mood
        WHERE pid = ?
        AND dateCreated BETWEEN ? AND ?;
    ";
    $stmt = $database->prepare($query);
    if (!$stmt) {
        die("Prepare failed: " . $database->error);
    }

    $stmt->bind_param("iss", $patientId, $startOfWeek, $endOfWeek);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $checkInCount = $row['checkInCount'];
        return $checkInCount >= $target;
    }

    return false;
}

function hasUserVotedAtLeast($userEmail, $target = 1) {
    global $database;

    // Step 1: Get user_id from the user table
    $query = "
        SELECT user_id
        FROM user
        WHERE email = ?;
    ";
    $stmt = $database->prepare($query);
    if (!$stmt) {
        die("Prepare failed: " . $database->error);
    }

    $stmt->bind_param("s", $userEmail);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $userId = $row['user_id'];
    } else {
        return false; // No user found with the given email
    }

    // Step 2: Check for votes this week
    $startOfWeek = date("Y-m-d 00:00:00", strtotime("monday this week"));
    $endOfWeek = date("Y-m-d 23:59:59", strtotime("sunday this week"));

    $query = "
        SELECT COUNT(*) AS voteCount
        FROM post_vote
        WHERE user_id = ?
        AND created_at BETWEEN ? AND ?;
    ";
    $stmt = $database->prepare($query);
    if (!$stmt) {
        die("Prepare failed: " . $database->error);
    }

    $stmt->bind_param("iss", $userId, $startOfWeek, $endOfWeek);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $voteCount = $row['voteCount'];
        return $voteCount >= $target;
    }

    return false;
}

function hasClaimedMission($missionNumber, $playId) {
    global $database;

    $query = "SELECT claimDate FROM bingoprogress WHERE missionNumber = ? AND playId = ? LIMIT 1";
    $stmt = $database->prepare($query);
    $stmt->bind_param("ii", $missionNumber, $playId);
    $stmt->execute();

    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return !is_null($row['claimDate']); // true if claimDate is NOT null
    }

    return false; // No record found, treat as not claimed
}

function getOrCreateBingoPlayId($userEmail) {
    global $database;

    $gameId = 1; // Bingo Game ID
    $startOfWeek = date('Y-m-d 00:00:00', strtotime('monday this week'));

    // Check if current week playId exists
    $stmt = $database->prepare("
        SELECT playId FROM gameplay 
        WHERE userEmail = ? AND gameId = ? AND playDate >= ?
        ORDER BY playDate DESC LIMIT 1
    ");
    $stmt->bind_param("sis", $userEmail, $gameId, $startOfWeek);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return $row['playId']; // Found valid playId for this week
    } else {
        // Not found, generate new playId
        $newPlayId = generateNewPlayId($userEmail, $gameId);

        // Fetch all enabled missionNumbers from DB, excluding 48, 49, 50
        $missionQuery = "SELECT missionNumber FROM bingomission WHERE status = 'enabled' AND missionNumber NOT IN (48, 49, 50)";
        $missionResult = $database->query($missionQuery);

        $availableMissions = [];
        while ($row = $missionResult->fetch_assoc()) {
            $availableMissions[] = (int)$row['missionNumber'];
        }

        shuffle($availableMissions);
        $selectedMissions = array_slice($availableMissions, 0, 9);

        // Insert default 9 missions with rewardStatus false
        if ($newPlayId > 0) {
            $insertStmt = $database->prepare("
                INSERT INTO bingoprogress (missionNumber, playId, rewardStatus) 
                VALUES (?, ?, 0)
            ");
            foreach ($selectedMissions as $missionNumber) {
                error_log("Selected Missions: " . implode(", ", $selectedMissions));
                $insertStmt->bind_param("ii", $missionNumber, $newPlayId);
                if (!$insertStmt->execute()) {
                    error_log("Failed to insert missionNumber: $missionNumber for playId: $newPlayId. Error: " . $insertStmt->error);
                } else {
                    error_log("Inserted missionNumber: $missionNumber for playId: $newPlayId successfully.");
                }
            }

            // for rewards use
            $fixedMissions = [48, 49, 50];
            foreach ($fixedMissions as $missionNumber) {
                $insertStmt->bind_param("ii", $missionNumber, $newPlayId);
                if (!$insertStmt->execute()) {
                    error_log("Failed to insert fixed missionNumber: $missionNumber for playId: $newPlayId. Error: " . $insertStmt->error);
                } else {
                    error_log("Inserted fixed missionNumber: $missionNumber for playId: $newPlayId successfully.");
                }
            }
        }

        return $newPlayId;
    }
}

function getBingoMissionsWithProgress($playId) {
    global $database;

    $stmt = $database->prepare("
        SELECT bm.missionNumber, bm.missionName, bm.missionDescription,
               COALESCE(bp.rewardStatus, 0) AS rewardStatus
        FROM bingomission bm
        LEFT JOIN bingoprogress bp ON bm.missionNumber = bp.missionNumber AND bp.playId = ?
        ORDER BY bm.missionNumber ASC
        LIMIT 9
    ");
    $stmt->bind_param("i", $playId);
    $stmt->execute();
    $result = $stmt->get_result();

    $missions = [];
    while ($row = $result->fetch_assoc()) {
        $missions[] = $row;
    }

    return $missions;
}

function getBingoMissionStatus($playId) {
    global $database;

    $stmt = $database->prepare("
        SELECT missionNumber, rewardStatus 
        FROM bingoprogress 
        WHERE playId = ? AND missionNumber IN (1, 2, 3)
        ORDER BY missionNumber
    ");
    $stmt->bind_param("i", $playId);
    $stmt->execute();
    $result = $stmt->get_result();

    $missions = [];
    while ($row = $result->fetch_assoc()) {
        $missions[] = $row;
    }

    return $missions;
}

function getAllBingoMissionData() {
    global $database;

    $query = "SELECT missionNumber, missionName, missionDescription, missionType, targetValue, status 
              FROM bingomission";
    $result = $database->query($query);

    $missions = [];
    while ($row = $result->fetch_assoc()) {
        $missions[$row['missionNumber']] = [
            'missionName' => $row['missionName'],
            'missionDescription' => $row['missionDescription'],
            'missionType' => $row['missionType'],
            'targetValue' => $row['targetValue'],
            'status' => $row['status']
        ];
    }

    return $missions;
}

function setBingoMissionStatus($missionNumber, $status = 'disabled') {
    global $database;

    $stmt = $database->prepare("
        UPDATE bingomission
        SET status = ?
        WHERE missionNumber = ?
    ");
    $stmt->bind_param("si", $status, $missionNumber);

    return $stmt->execute();
}

function countEnabledBingoMissionsExcluding($excluded = [48, 49, 50]) {
    global $database;

    // Build placeholders (?, ?, ?) based on number of exclusions
    $placeholders = implode(',', array_fill(0, count($excluded), '?'));

    // Prepare query with dynamic exclusions
    $query = "SELECT COUNT(*) AS count FROM bingomission WHERE status = 'enabled' AND missionNumber NOT IN ($placeholders)";
    $stmt = $database->prepare($query);

    // Bind values dynamically
    $types = str_repeat('i', count($excluded)); // all missionNumbers are integers
    $stmt->bind_param($types, ...$excluded);

    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    return (int)$row['count'];
}

function getBingoMissionsByPlayId($playId) {
    global $database;

    $missions = [];

    $stmt = $database->prepare("
        SELECT 
            bp.missionNumber,
            bm.missionName,
            bm.missionDescription,
            bm.missionType,
            bm.targetValue,
            bp.rewardStatus,
            bp.claimDate
        FROM bingoprogress bp
        JOIN bingomission bm ON bp.missionNumber = bm.missionNumber
        WHERE bp.playId = ?
    ");
    
    if (!$stmt) {
        die("Prepare failed: " . $database->error);
    }

    $stmt->bind_param("i", $playId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $missions[] = [
            'missionNumber' => $row['missionNumber'],
            'name' => $row['missionName'],
            'desc' => $row['missionDescription'],
            'type' => $row['missionType'],
            'target' => $row['targetValue'],
            'rewardStatus' => $row['rewardStatus'],
            'claimed' => $row['claimDate']
        ];
    }

    return $missions;
}

function updateBingoMission($missionNumber, $missionName, $missionDescription, $missionType, $targetValue, $status) {
    global $database;

    $stmt = $database->prepare("
        UPDATE bingomission 
        SET missionName = ?, missionDescription = ?, missionType = ?, targetValue = ?, status = ?
        WHERE missionNumber = ?
    ");

    $stmt->bind_param('sssssi', $missionName, $missionDescription, $missionType, $targetValue, $status, $missionNumber);

    return $stmt->execute();
}

function getBingoMissionById($missionNumber) {
    global $database;

    // Prepare the query to fetch the mission by its number
    $stmt = $database->prepare("SELECT * FROM bingomission WHERE missionNumber = ?");
    $stmt->bind_param('i', $missionNumber); // 'i' is for integer

    // Execute the query
    $stmt->execute();

    // Get the result of the query
    $result = $stmt->get_result();

    // Check if a mission was found
    if ($result->num_rows > 0) {
        // Fetch the mission data as an associative array
        return $result->fetch_assoc();
    } else {
        // Return null if no mission is found
        return null;
    }
}



// ---------------------------------------------------------------------------------------------------------
//Rating functions
// Function to insert or update user rating for a game
function insertUserGameRating($userEmail, $gameId, $ratingValue) {
    global $database;

    $stmt = $database->prepare("INSERT INTO usergameratings (userEmail, gameId, ratingValue) VALUES (?, ?, ?) 
    ON DUPLICATE KEY UPDATE ratingValue = VALUES(ratingValue)");
    $stmt->bind_param("sii", $userEmail, $gameId, $ratingValue);

    return $stmt->execute();
}

// Function to check if a user has already rated a game
function checkUserGameRatingExists($userEmail, $gameId) {
    global $database;

    $stmt = $database->prepare("SELECT 1 FROM usergameratings WHERE userEmail = ? AND gameId = ?");
    $stmt->bind_param("si", $userEmail, $gameId);
    $stmt->execute();
    $stmt->store_result();

    return $stmt->num_rows > 0; // Returns true if rating exists, false otherwise
}

// Function to delete a user's rating for a specific game
function deleteUserGameRating($userEmail, $gameId) {
    global $database;

    $stmt = $database->prepare("DELETE FROM usergameratings WHERE userEmail = ? AND gameId = ?");
    $stmt->bind_param("si", $userEmail, $gameId);

    return $stmt->execute();
}

// Function to get a user's rating for a specific game
function getUserGameRating($userEmail, $gameId) {
    global $database;

    $stmt = $database->prepare("SELECT ratingValue FROM usergameratings WHERE userEmail = ? AND gameId = ?");
    $stmt->bind_param("si", $userEmail, $gameId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return $row['ratingValue']; // Return the rating value
    } else {
        return null; // No rating found
    }
}

function updateUserGameRating($userEmail, $gameId, $newRatingValue) {
    global $database;

    $ratingDate = date('Y-m-d H:i:s'); // Get the current timestamp

    // Prepare the UPDATE query
    $stmt = $database->prepare("UPDATE usergameratings SET ratingValue = ?, ratingDate = ? WHERE userEmail = ? AND gameId = ?");
    $stmt->bind_param("issi", $newRatingValue, $ratingDate, $userEmail, $gameId);

    // Execute the query and return success status
    return $stmt->execute();
}

function getAverageGameRating($gameId) {
    global $database;

    // Query to calculate the total rating and count
    $stmt = $database->prepare("SELECT SUM(ratingValue) AS totalRating, COUNT(*) AS numRows FROM usergameratings WHERE gameId = ?");
    $stmt->bind_param("i", $gameId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $totalRating = $row["totalRating"];
        $numRows = $row["numRows"];

        // Calculate average and format to 1 decimal place
        $average = ($numRows > 0) ? number_format($totalRating / $numRows, 1) : "-";
        return $average;
    }

    return "-"; // Return "-" if no ratings found
}

// ---------------------------------------------------------------------------------------------------------
//Shooting Game functions
function saveShooterGameData($playId, $enemiesDestroyed, $bulletsFired, $accuracy, $survivalTime, $finalScore) {
    global $database;

    $stmt = $database->prepare("INSERT INTO spaceshipshooter (playId, enemiesDestroyed, bulletsFired, accuracy, survivalTime, finalScore) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("siiddi", $playId, $enemiesDestroyed, $bulletsFired, $accuracy, $survivalTime, $finalScore);

    if ($stmt->execute()) {
        return ["success" => true, "playId" => $playId];
    } else {
        return ["success" => false, "error" => $stmt->error];
    }

    $stmt->close();
}

// ---------------------------------------------------------------------------------------------------------
//Quiz functions
function getQuizGameData($playId) {
    global $database;

    $stmt = $database->prepare("
        SELECT playId, timeUsed, percentageScore, finalScore 
        FROM quizgame 
        WHERE playId = ?
    ");
    $stmt->bind_param("i", $playId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return [
            "playId" => $row["playId"],
            "timeUsed" => $row["timeUsed"],
            "percentageScore" => $row["percentageScore"],
            "finalScore" => $row["finalScore"]
        ];
    }

    return null; // Return null if no data is found
}

function getAllQuizQuestions() {
    global $database;

    $stmt = $database->prepare("
        SELECT questionId, questionText, optionA, optionB, optionC, optionD, correctAnswer, status 
        FROM quizquestion
    ");
    $stmt->execute();
    $result = $stmt->get_result();

    $quizData = [];

    while ($row = $result->fetch_assoc()) {
        $correctAnswerText = '';
        switch ($row['correctAnswer']) {
            case 'A':
                $correctAnswerText = $row['optionA'];
                break;
            case 'B':
                $correctAnswerText = $row['optionB'];
                break;
            case 'C':
                $correctAnswerText = $row['optionC'];
                break;
            case 'D':
                $correctAnswerText = $row['optionD'];
                break;
        }

        $quizData[] = [
            "questionId" => $row["questionId"],
            "questionText" => $row["questionText"],
            "optionA" => $row["optionA"],
            "optionB" => $row["optionB"],
            "optionC" => $row["optionC"],
            "optionD" => $row["optionD"],
            "correctAnswerText" => $correctAnswerText,
            "status" => $row["status"]
        ];
    }

    return $quizData;
}

function insertQuizQuestion($questionText, $optionA, $optionB, $optionC, $optionD, $correctAnswer) {
    global $database;

    // Ensure correct answer is valid
    $validAnswers = ['A', 'B', 'C', 'D'];
    if (!in_array($correctAnswer, $validAnswers)) {
        return [
            "success" => false,
            "message" => "Invalid correct answer. Must be A, B, C, or D."
        ];
    }

    $status = 'enabled';

    $stmt = $database->prepare("
        INSERT INTO quizquestion (questionText, optionA, optionB, optionC, optionD, correctAnswer, status)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param("sssssss", $questionText, $optionA, $optionB, $optionC, $optionD, $correctAnswer, $status);

    if ($stmt->execute()) {
        return [
            "success" => true,
            "message" => "Quiz question inserted successfully."
        ];
    } else {
        return [
            "success" => false,
            "message" => "Error inserting question: " . $stmt->error
        ];
    }
}

function getQuizQuestionById($id) {
    global $database;

    $stmt = $database->prepare("
        SELECT * FROM quizquestion WHERE questionId = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_assoc();
}

function updateQuizQuestion($questionId, $questionText, $optionA, $optionB, $optionC, $optionD, $correctAnswer, $status) {
    global $database;

    $stmt = $database->prepare("
        UPDATE quizquestion
        SET questionText = ?, optionA = ?, optionB = ?, optionC = ?, optionD = ?, correctAnswer = ?, status = ?
        WHERE questionId = ?
    ");
    $stmt->bind_param("sssssssi", $questionText, $optionA, $optionB, $optionC, $optionD, $correctAnswer, $status, $questionId);

    return $stmt->execute();
}

function setQuizQuestionStatusDisabled($id, $status = 'disabled') {
    global $database;

    $stmt = $database->prepare("
        UPDATE quizquestion
        SET status = ?
        WHERE questionId = ?
    ");
    $stmt->bind_param("si", $status, $id);

    return $stmt->execute();
}

function deleteQuizQuestion($id) {
    global $database;

    $stmt = $database->prepare("
        DELETE FROM quizquestion WHERE questionId = ?
    ");
    $stmt->bind_param("i", $id);

    return $stmt->execute();
}

// ---------------------------------------------------------------------------------------------------------
//MindfulCounting functions
function saveMindfulCountingData($playId, $difficultyLevel, $gameTimeOut, $birdCount, $finalScore) {
    global $database;

    $stmt = $database->prepare("INSERT INTO mindfulcounting (playId, difficultyLevel, gameTimeOut, birdCount, finalScore) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiii", $playId, $difficultyLevel, $gameTimeOut, $birdCount, $finalScore);

    if ($stmt->execute()) {
        return ["success" => true, "playId" => $playId];
    } else {
        return ["success" => false, "error" => $stmt->error];
    }

    $stmt->close();
}

function updateMindfulCountingLevel($id, $level_name, $minimum_flight_time, $speed_variation, $base_bird_speed, $bird_speed_random_gap, $game_timeout, $updated_by) {
    global $database;
    $query = "UPDATE mindfulCountingLevel SET 
                level_name = ?, 
                minimum_flight_time = ?, 
                speed_variation = ?, 
                base_bird_speed = ?, 
                bird_speed_random_gap = ?, 
                game_timeout = ?, 
                updated_by = ?, 
                updated_at = CURRENT_TIMESTAMP 
              WHERE id = ?";
    $stmt = $database->prepare($query);
    $stmt->bind_param("sdiddisi", $level_name, $minimum_flight_time, $speed_variation, $base_bird_speed, $bird_speed_random_gap, $game_timeout, $updated_by, $id);
    $success = $stmt->execute();
    $stmt->close();

    return $success;
}

function getMindfulCountingLevel($level_name) {
    global $database;
    $query = "SELECT * FROM mindfulCountingLevel WHERE level_name = ?";
    $stmt = $database->prepare($query);
    $stmt->bind_param("s", $level_name); // Bind the level name parameter
    $stmt->execute();
    $result = $stmt->get_result();
    $level = $result->fetch_assoc();
    $stmt->close();
    return $level;
}

function getAllLevelData() {
    global $database; // Assuming you have a $database connection already set up
    $query = "SELECT * FROM mindfulCountingLevel";
    $result = $database->query($query);
    
    $levels = [];
    while ($level = $result->fetch_assoc()) {
        $levels[$level['level_name']] = [
            'minimum_flight_time' => $level['minimum_flight_time'],
            'speed_variation' => $level['speed_variation'],
            'base_bird_speed' => $level['base_bird_speed'],
            'bird_speed_random_gap' => $level['bird_speed_random_gap'],
            'game_timeout' => $level['game_timeout']
        ];
    }

    return $levels;
}

// ---------------------------------------------------------------------------------------------------------
//cardMatching functions
// Function to get all active self-care tips from the database
function getActiveSelfCareTips() {
    global $database;

    $stmt = $database->prepare("SELECT tipDescription FROM memorygame_tips WHERE status = 1");
    $stmt->execute();
    $result = $stmt->get_result();

    $tips = [];
    while ($row = $result->fetch_assoc()) {
        $tips[] = $row['tipDescription'];
    }

    return $tips; // Return array of active tips
}

// Function to get 8 random active self-care tips (with tipId and description)
function getRandomActiveSelfCareTips() {
    global $database;

    $stmt = $database->prepare("SELECT tipId, tipText FROM memorygame_tips WHERE status = 1");
    $stmt->execute();
    $result = $stmt->get_result();

    $tips = [];
    while ($row = $result->fetch_assoc()) {
        $tips[] = $row; // Each row has 'tipId' and 'tipDescription'
    }

    // Shuffle and pick 8 random tips
    shuffle($tips);
    return array_slice($tips, 0, 8);
}

function getAllMemoryGameCards() {
    global $database;

    $stmt = $database->prepare("SELECT cardId, name, path, points FROM memorygamecard");
    $stmt->execute();
    $result = $stmt->get_result();

    $cardOptions = [];
    while ($row = $result->fetch_assoc()) {
        $cardOptions[] = [
            "cardId" => $row["cardId"],
            "name" => $row["name"],
            "path" => $row["path"],
            "points" => $row["points"]
        ];
    }

    return $cardOptions;
}

function insertMemoryGameCard($name, $path, $points) {
    global $database;

    $stmt = $database->prepare("INSERT INTO memorygamecard (name, path, points) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $name, $path, $points);
    return $stmt->execute();
}

function getOnUsedCardPathByEmail($userEmail) {
    global $database;

    $stmt = $database->prepare("
        SELECT mc.path
        FROM memorygameusercard muc
        JOIN memorygamecard mc ON muc.cardId = mc.cardId
        WHERE muc.userEmail = ? AND muc.onUsed = 1
        LIMIT 1
    ");
    $stmt->bind_param("s", $userEmail);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return $row["path"]; // return single path string
    }

    return null; // return null if nothing found
}

function getUserCardsByEmail($userEmail) {
    global $database;

    $stmt = $database->prepare("
        SELECT mc.cardId, mc.name, mc.path, mc.points, muc.onUsed
        FROM memorygameusercard muc
        JOIN memorygamecard mc ON muc.cardId = mc.cardId
        WHERE muc.userEmail = ?
    ");
    $stmt->bind_param("s", $userEmail);
    $stmt->execute();
    $result = $stmt->get_result();

    $userCards = [];
    while ($row = $result->fetch_assoc()) {
        $userCards[] = [
            "cardId" => $row["cardId"],
            "name" => $row["name"],
            "path" => $row["path"],
            "points" => $row["points"],
            "onUsed" => $row["onUsed"]
        ];
    }

    return $userCards;
}

function insertUserCardByEmail($userEmail, $cardId) {
    global $database;

    $stmt = $database->prepare("
        INSERT IGNORE INTO memorygameusercard (userEmail, cardId)
        VALUES (?, ?)
    ");
    $stmt->bind_param("si", $userEmail, $cardId);
    $stmt->execute();
}

function insertDefaultUserCardByEmail($userEmail, $cardId) {
    global $database;

    $stmt = $database->prepare("
        INSERT INTO memorygameusercard (userEmail, cardId, onUsed)
        VALUES (?, ?, 1)
        ON DUPLICATE KEY UPDATE onUsed = 1
    ");
    $stmt->bind_param("si", $userEmail, $cardId);
    $stmt->execute();
}

function updateSelectedCardByEmail($userEmail, $cardId) {
    global $database;

    // Step 1: Set all user's cards to onUsed = 0
    $stmt = $database->prepare("UPDATE memorygameusercard SET onUsed = 0 WHERE userEmail = ?");
    $stmt->bind_param("s", $userEmail);
    if (!$stmt->execute()) {
        return false;
    }
    $stmt->close();

    // Step 2: Set selected card to onUsed = 1
    $stmt = $database->prepare("UPDATE memorygameusercard SET onUsed = 1 WHERE userEmail = ? AND cardId = ?");
    $stmt->bind_param("si", $userEmail, $cardId);
    $success = $stmt->execute();
    $stmt->close();

    return $success;
}

// ---------------------------------------------------------------------------------------------------------
//Ranking functions
//Shooting Game
function getShooterGameRanking() {
    global $database;

    // Query to get top players with highest scores for this week
    $stmt = $database->prepare("
        SELECT p.pname, MAX(s.finalScore) AS highestScore
        FROM spaceshipshooter s
        JOIN gameplay g ON s.playId = g.playId
        JOIN patient p ON g.userEmail = p.pemail
        WHERE g.playDate >= DATE_SUB(NOW(), INTERVAL (WEEKDAY(NOW()) + 1) DAY)
        GROUP BY p.pname
        ORDER BY highestScore DESC
        LIMIT 10
    ");

    $stmt->execute();
    $result = $stmt->get_result();

    $leaderboard = [];
    while ($row = $result->fetch_assoc()) {
        $leaderboard[] = [
            "name" => $row["pname"],
            "score" => ($row["highestScore"] !== null) ? $row["highestScore"] : "-"
        ];
    }

    return $leaderboard;
}

function getShooterGameThisWeekHighestScore($email) {
    global $database;

    // Query to get the highest score for this user in the current week
    $stmt = $database->prepare("
        SELECT MAX(s.finalScore) AS highestScore
        FROM spaceshipshooter s
        JOIN gameplay g ON s.playId = g.playId
        WHERE g.userEmail = ? 
        AND g.playDate >= DATE_SUB(NOW(), INTERVAL (WEEKDAY(NOW()) + 1) DAY)
    ");

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return ($row["highestScore"] !== null) ? $row["highestScore"] : "-"; // Return highest score or "-"
    }

    return "-"; // Return "-" if no score found
}

function getShooterGameLevel() {
    global $database;
    $query = "SELECT * FROM shooterGameLevel ORDER BY id DESC LIMIT 1";
    $result = $database->query($query);
    return $result->fetch_assoc();
}

function updateShooterGameLevel($id, $shooting_speed_ms, $enemy_spawn_speed_ms, $base_enemy_speed, $speed_increment_per_kill, $updated_by) {
    global $database;
    $query = "UPDATE shooterGameLevel SET 
                shooting_speed_ms = ?, 
                enemy_spawn_speed_ms = ?, 
                base_enemy_speed = ?, 
                speed_increment_per_kill = ?, 
                updated_by = ?, 
                updated_at = CURRENT_TIMESTAMP 
              WHERE id = ?";
    $stmt = $database->prepare($query);
    $stmt->bind_param("iiddsi", $shooting_speed_ms, $enemy_spawn_speed_ms, $base_enemy_speed, $speed_increment_per_kill, $updated_by, $id);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}


//QUIZ GAME
function getQuizGameRanking() {
    global $database;

    // Query to get top players with highest scores for this week
    $stmt = $database->prepare("
        SELECT p.pname, MAX(q.finalScore) AS highestScore
        FROM quizgame q
        JOIN gameplay g ON q.playId = g.playId
        JOIN patient p ON g.userEmail = p.pemail
        WHERE g.playDate >= DATE_SUB(NOW(), INTERVAL (WEEKDAY(NOW()) + 1) DAY)
        GROUP BY p.pname
        ORDER BY highestScore DESC
        LIMIT 10
    ");

    $stmt->execute();
    $result = $stmt->get_result();

    $leaderboard = [];
    while ($row = $result->fetch_assoc()) {
        $leaderboard[] = [
            "name" => $row["pname"],
            "score" => ($row["highestScore"] !== null) ? $row["highestScore"] : "-"
        ];
    }

    return $leaderboard;
}

function getQuizGameThisWeekHighestScore($email) {
    global $database;

    // Query to get the highest score for this user in the current week
    $stmt = $database->prepare("
        SELECT MAX(q.finalScore) AS highestScore
        FROM quizgame q
        JOIN gameplay g ON q.playId = g.playId
        WHERE g.userEmail = ? 
        AND g.playDate >= DATE_SUB(NOW(), INTERVAL (WEEKDAY(NOW()) + 1) DAY)
    ");

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return ($row["highestScore"] !== null) ? $row["highestScore"] : "-"; // Return highest score or "-"
    }

    return "-"; // Return "-" if no score found
}


// Mindful Counting Game
function getMindfulCountingRanking() {
    global $database;

    // Query to get top players with the highest scores for this week
    $stmt = $database->prepare("
        SELECT p.pname, MAX(m.finalScore) AS highestScore
        FROM mindfulcounting m
        JOIN gameplay g ON m.playId = g.playId
        JOIN patient p ON g.userEmail = p.pemail
        WHERE g.playDate >= DATE_SUB(NOW(), INTERVAL (WEEKDAY(NOW()) + 1) DAY)
        GROUP BY p.pname
        ORDER BY highestScore DESC
        LIMIT 10
    ");

    $stmt->execute();
    $result = $stmt->get_result();

    $leaderboard = [];
    while ($row = $result->fetch_assoc()) {
        $leaderboard[] = [
            "name" => $row["pname"],
            "score" => ($row["highestScore"] !== null) ? $row["highestScore"] : "-"
        ];
    }

    return $leaderboard;
}

function getMindfulCountingThisWeekHighestScore($email) {
    global $database;

    // Query to get the highest score for this user in the current week
    $stmt = $database->prepare("
        SELECT MAX(m.finalScore) AS highestScore
        FROM mindfulcounting m
        JOIN gameplay g ON m.playId = g.playId
        WHERE g.userEmail = ? 
        AND g.playDate >= DATE_SUB(NOW(), INTERVAL (WEEKDAY(NOW()) + 1) DAY)
    ");

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return ($row["highestScore"] !== null) ? $row["highestScore"] : "-"; // Return highest score or "-"
    }

    return "-"; // Return "-" if no score found
}

// Card Matching Game
function getCardMatchingGameRanking() {
    global $database;

    $stmt = $database->prepare("
        SELECT p.pname, MAX(m.finalScore) AS highestScore
        FROM memorygame m
        JOIN gameplay g ON m.playId = g.playId
        JOIN patient p ON g.userEmail = p.pemail
        WHERE g.playDate >= DATE_SUB(NOW(), INTERVAL (WEEKDAY(NOW()) + 1) DAY)
        GROUP BY p.pname
        ORDER BY highestScore DESC
        LIMIT 10
    ");

    $stmt->execute();
    $result = $stmt->get_result();

    $leaderboard = [];
    while ($row = $result->fetch_assoc()) {
        $leaderboard[] = [
            "name" => $row["pname"],
            "score" => ($row["highestScore"] !== null) ? $row["highestScore"] : "-"
        ];
    }

    return $leaderboard;
}

function getCardMatchingThisWeekHighestScore($email) {
    global $database;

    $stmt = $database->prepare("
        SELECT MAX(m.finalScore) AS highestScore
        FROM memorygame m
        JOIN gameplay g ON m.playId = g.playId
        WHERE g.userEmail = ?
        AND g.playDate >= DATE_SUB(NOW(), INTERVAL (WEEKDAY(NOW()) + 1) DAY)
    ");

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return ($row["highestScore"] !== null) ? $row["highestScore"] : "-";
    }

    return "-";
}


?>