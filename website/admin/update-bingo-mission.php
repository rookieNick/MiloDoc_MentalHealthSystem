<?php
include("../connection.php");
include(__DIR__ . '/../minigames/gamedb.php');

session_start();

if ($_POST) {
    $missionNumber = $_POST['missionNumber'];
    $missionName = $_POST['missionName'];
    $missionDescription = $_POST['missionDescription'];
    $missionType = $_POST['missionType'];
    $targetValue = $_POST['targetValue'];
    $status = $_POST['status'];

    // Error code guide:
    // error=1 → missing field
    // error=2 → invalid mission type
    // error=3 → update failed
    // error=4 → success

    // Validate empty fields
    if (
        empty($missionName) || empty($missionDescription) || empty($missionType) || empty($targetValue)
    ) {
        $error = '1';
    } elseif (!in_array($missionType, ['game', 'scoreTarget', 'requiredTime', 'percentage', 'level', 'requiredDay', 'target', 'other'])) {
        $error = '2';
    } elseif ($targetValue < 1) {
        $error = '6'; // Invalid target value
    } elseif ($missionType == 'percentage' && $targetValue > 100) {
        $error = '5'; // Invalid target value
    } elseif ($targetValue > 4 && $missionNumber == 7) {
        $error = '7'; // Invalid target value
    } else {
        // Try to update
        $updateSuccess = updateBingoMission($missionNumber, $missionName, $missionDescription, $missionType, $targetValue, $status);

        if ($updateSuccess) {
            $error = '4'; // Success
        } else {
            $error = '3'; // Failed to update
        }
    }

    // Redirect with error or success
    header("Location: manageBingo.php?action=edit&error=$error&mission=$missionNumber");
    exit();

} else {
    // If form not submitted properly
    header("Location: manageBingo.php?action=edit&error=1&id=0");
    exit();
}
?>
