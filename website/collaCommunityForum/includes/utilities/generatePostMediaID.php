<?php

require_once(__DIR__.'/../database/postMediaDA.php');

// Function to generate media ID
function generatePostMediaId() {
    $postMediaDA = new PostMediaDA();  // Create an instance of PostMediaDA to interact with the database

    // Get the latest media ID from the database
    $latestPostMediaID = $postMediaDA->getLatestPostMediaID();

    // Get the current year and month
    $currentDate = new DateTime();
    $year = $currentDate->format('Y');
    $month = $currentDate->format('m');

    if ($latestPostMediaID) {
        // Extract the numeric part of the latest media ID (last 4 digits)
        $index = (int)substr($latestPostMediaID, -4);
        $index++; // Increment the index
        $newId = "PSTMD{$year}{$month}" . str_pad($index, 4, '0', STR_PAD_LEFT);  // Format the new media ID
    } else {
        // If no media ID found, start from 0001
        $newId = "PSTMD{$year}{$month}0001";
    }

    return $newId; // Return the generated media ID
}
?>
