<?php


require_once(__DIR__.'/../database/userDA.php');


// Function to generate user ID
function generateUserId() {
   $userDA = new UserDA();// Create an instance of UserDA to interact with the database

    // Get the latest user ID from the database
    $latestUserID = $userDA->getLatestUserID();

    // Get the current year and month
    $currentDate = new DateTime();
    $year = $currentDate->format('Y');
    $month = $currentDate->format('m');

    if ( $latestUserID) {
        // Extract the numeric part of the latest user ID (last 4 digits)
        $index = (int)substr( $latestUserID, -4);
        $index++; // Increment the index
        $newId = "USR{$year}{$month}" . str_pad($index, 4, '0', STR_PAD_LEFT);  // Format the new user ID
    } else {
        // If no user ID found, start from 0001
        $newId = "COM{$year}{$month}0001";
    }

    return $newId; // Return the generated user ID
}
?>
