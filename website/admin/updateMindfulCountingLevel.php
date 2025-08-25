<?php
include("../connection.php");
include(__DIR__ . '/../minigames/gamedb.php');

session_start();

if ($_POST) {
    // Get the form data
    $id = $_POST['id'];
    $level_name = $_POST['level_name'];
    $minimum_flight_time = $_POST['minimum_flight_time'];
    $speed_variation = $_POST['speed_variation'];
    $base_bird_speed = $_POST['base_bird_speed'];
    $bird_speed_random_gap = $_POST['bird_speed_random_gap'];
    $game_timeout = $_POST['game_timeout'];
    $updated_by = $_SESSION['user'] ?? 'admin'; // fallback to 'admin' if somehow missing

    // Error guide:
    // error=1 → missing field
    // error=2 → update failed
    // error=3 → success

    // Validate fields
    if (empty($level_name) || $minimum_flight_time === '' || $speed_variation === '' || $base_bird_speed === '' || $bird_speed_random_gap === '' || $game_timeout === '') {
        $error = '1'; // Missing fields
    } else {
        // Try to update
        $updateSuccess = updateMindfulCountingLevel($id, $level_name, $minimum_flight_time, $speed_variation, $base_bird_speed, $bird_speed_random_gap, $game_timeout, $updated_by);

        if ($updateSuccess) {
            $error = '3'; // Success
        } else {
            $error = '2'; // Failed
        }
    }

    // Redirect back with error code
    header("Location: manageMindfulCounting.php?action=edit&error=$error&level=" . urlencode($level_name));
    exit();
} else {
    header("Location: manageMindfulCounting.php?action=edit&error=1&level=");
    exit();
}
?>
