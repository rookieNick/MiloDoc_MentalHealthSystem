<?php
include("../connection.php");
include(__DIR__ . '/../minigames/gamedb.php');

session_start();

if ($_POST) {
    // Get POSTed form data
    $id = $_POST['id'];
    $shooting_speed_ms = $_POST['shooting_speed_ms'];
    $enemy_spawn_speed_ms = $_POST['enemy_spawn_speed_ms'];
    $base_enemy_speed = $_POST['base_enemy_speed'];
    $speed_increment_per_kill = $_POST['speed_increment_per_kill'];
    $updated_by = $_SESSION['user'] ?? 'admin'; // fallback if session is corrupted

    // Error guide:
    // error=1 → missing field
    // error=2 → update failed
    // error=3 → success

    // Check if all fields are present
    if (
        $shooting_speed_ms === '' || $enemy_spawn_speed_ms === '' ||
        $base_enemy_speed === '' || $speed_increment_per_kill === ''
    ) {
        $error = '1'; // missing data
    } else {
        // Try to update
        $updateSuccess = updateShooterGameLevel($id, $shooting_speed_ms, $enemy_spawn_speed_ms, $base_enemy_speed, $speed_increment_per_kill, $updated_by);

        if ($updateSuccess) {
            $error = '3'; // success
        } else {
            $error = '2'; // update failed
        }
    }

    // Redirect back to management page with error code
    header("Location: manageSpaceshipShooter.php?action=edit&error=$error");
    exit();
} else {
    header("Location: manageSpaceshipShooter.php?action=edit&error=1");
    exit();
}
