<?php
require_once(__DIR__.'/../database/postVoteDA.php');


// Function to generate user ID
function generatePostVoteId() {
  $postVoteDA = new PostVoteDA();// Create an instance of UserDA to interact with the database

    $latestPostVoteID = $postVoteDA ->getLatestPostVoteID();

    // Get the current year and month
    $currentDate = new DateTime();
    $year = $currentDate->format('Y');
    $month = str_pad($currentDate->format('n'), 2, '0', STR_PAD_LEFT);

    if ( $latestPostVoteID ) {
        // Extract the numeric part of the latest user ID (last 4 digits)
        $index = (int)substr( $latestPostVoteID , -4);
        $index++; // Increment the index
        $newId = "PVOTE{$year}{$month}" . str_pad($index, 4, '0', STR_PAD_LEFT);  // Format the new user ID
    } else {
        // If no user ID found, start from 0001
        $newId = "PVOTE{$year}{$month}0001";
    }

    return $newId; // Return the generated user ID
}
?>
