<?php


require_once(__DIR__.'/../database/joinCommunityRequestDA.php');


// Function to generate user ID
function generateRequestId() {
  $joinCommunityRequest = new JoinCommunityRequestDA();

    // Get the latest user ID from the database
    $latestRequestID = $joinCommunityRequest->getLatestRequestID();

    // Get the current year and month
    $currentDate = new DateTime();
    $year = $currentDate->format('Y');
    $month = $currentDate->format('m');

    if ($latestRequestID) {
        // Extract the numeric part of the latest user ID (last 4 digits)
        $index = (int)substr($latestRequestID, -4);
        $index++; // Increment the index
        $newId = "REQ{$year}{$month}" . str_pad($index, 4, '0', STR_PAD_LEFT);  // Format the new user ID
    } else {
        // If no user ID found, start from 0001
        $newId = "REQ{$year}{$month}0001";
    }

    return $newId; // Return the generated user ID
}
?>
