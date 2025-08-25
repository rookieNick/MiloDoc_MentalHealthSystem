<?php
include("../connection.php");
include(__DIR__ . '/../minigames/gamedb.php');

session_start();

if ($_POST) {
    // Get the form data
    $tipId = $_POST['tipId'];
    $tipText = $_POST['tipText'];
    $status = $_POST['status'];

    // Error code guide:
    // error=1 → missing field
    // error=2 → update failed
    // error=3 → success

    // Validate empty fields
    if (empty($tipText)) {
        $error = '1'; // Missing tip text
    } else {
        // Try to update the tip status and text
        $updateSuccess = updateMemoryGameTip($tipId, $tipText, $status);

        if ($updateSuccess) {
            $error = '3'; // Success
        } else {
            $error = '2'; // Failed to update
        }
    }

    // Redirect with error or success
    header("Location: manageCardMatching.php?action=edit&error=$error&id=$tipId");
    exit();

} else {
    // If form not submitted properly
    header("Location: manageCardMatching.php?action=edit&error=1&id=0");
    exit();
}
?>