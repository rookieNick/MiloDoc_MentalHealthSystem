<?php


require_once(__DIR__.'/../database/communityMemberDA.php');
// Function to generate user ID
function generateCommunityMemberId() {
    $communityMemberDA = new CommunityMemberDA();  // Create an instance of UserDA to interact with the database

    // Get the latest user ID from the database
    $latestCommuintyMemberID = $communityMemberDA->getLatestCommunityMemberID();

    // Get the current year and month
    $currentDate = new DateTime();
    $year = $currentDate->format('Y');
    $month = $currentDate->format('m');

    if ($latestCommuintyMemberID) {
        // Extract the numeric part of the latest user ID (last 4 digits)
        $index = (int)substr($latestCommuintyMemberID, -4);
        $index++; // Increment the index
        $newId = "MEM{$year}{$month}" . str_pad($index, 4, '0', STR_PAD_LEFT);  // Format the new user ID
    } else {
        // If no user ID found, start from 0001
        $newId = "MEM{$year}{$month}0001";
    }

    return $newId; // Return the generated user ID
}
?>
