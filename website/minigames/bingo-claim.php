<?php
require_once(__DIR__ . '/gamedb.php');

session_start();
$playId = $_POST['playId'] ?? null;
$missions = isset($_POST['missions']) ? explode(",", $_POST['missions']) : [];
$pemail = $_SESSION["user"] ?? null;

if (!$playId || empty($missions) || !$pemail) {
    header("Location: bingo.php?claimed=0");
    exit;
}

$totalPoints = 0;

// Calculate points
foreach ($missions as $m) {
    $m = (int)$m;
    if ($m === 48) {
        $totalPoints += 100;
    } elseif (in_array($m, [49, 50])) {
        $totalPoints += 50;
    }
}

// Update bingoprogress rewardStatus to 'claimed'
$stmt = $database->prepare("
    UPDATE bingoprogress 
    SET rewardStatus = 'claimed', claimDate = NOW() 
    WHERE playId = ? AND missionNumber = ?
");
foreach ($missions as $m) {
    $m = (int)$m;
    $stmt->bind_param("ii", $playId, $m);
    $stmt->execute();
}

// Add points to patient
$updatePoints = $database->prepare("
    UPDATE patient 
    SET points = points + ? 
    WHERE pemail = ?
");
$updatePoints->bind_param("is", $totalPoints, $pemail);
$updatePoints->execute();

// Redirect with points claimed
header("Location: bingo.php?claimed=" . $totalPoints);
exit;
?>
