<?php


require_once(__DIR__.'/../database/postDA.php');


// Function to generate user ID
function generatePostId() {
    $postDA = new PostDA();  // Create an instance of UserDA to interact with the database

    // Get the latest user ID from the database
    $latestPostID = $postDA->getLatestPostID();

    // Get the current year and month
    $currentDate = new DateTime();
    $year = $currentDate->format('Y');
    $month = $currentDate->format('m');

    if ($latestPostID) {
        // Extract the numeric part of the latest user ID (last 4 digits)
        $index = (int)substr($latestPostID, -4);
        $index++; // Increment the index
        $newId = "PST{$year}{$month}" . str_pad($index, 4, '0', STR_PAD_LEFT);  // Format the new user ID
    } else {
        // If no user ID found, start from 0001
        $newId = "PST{$year}{$month}0001";
    }

    return $newId; // Return the generated user ID
}
?>
