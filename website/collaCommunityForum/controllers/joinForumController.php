<?php
session_start();
require_once(__DIR__ . '/../includes/database/communityMemberDA.php');
require_once(__DIR__ . '/../includes/database/joinCommunityRequestDA.php');
require_once(__DIR__ . '/../includes/database/communityDA.php');
require_once(__DIR__ . '/../includes/utilities/generateRequestID.php');
require_once(__DIR__ . '/../includes/utilities/generateCommunityMemberID.php');

$communityId = isset($_POST['community_id']) ? $_POST['community_id'] : null;
$userId = isset($_POST['user_id']) ? $_POST['user_id'] : null;
$reason = isset($_POST['reason']) ? $_POST['reason'] : ""; // Get the reason from the form

// Check if required data is provided
if (!$communityId || !$userId) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required data'
    ]);
    exit;
}

// Create instances of the data access objects
$communityMemberDA = new CommunityMemberDA();
$joinRequestDA = new JoinCommunityRequestDA();

// Check if the forum exists
// TODO: Add forum existence check if needed

// Check if user is already a member
$isMember = false;
$communityMembers = $communityMemberDA->getAllCommunityMembers();
if ($communityMembers) {
    foreach ($communityMembers as $member) {
        if ($member['community_id'] === $communityId && $member['user_id'] === $userId) {
            $isMember = true;
            break;
        }
    }
}

if ($isMember) {
    echo json_encode([
        'success' => false,
        'message' => 'You are already a member of this forum'
    ]);
    exit;
}

// For public forums, add the user directly
// For private/anonymous forums, create a join request
$communityDA = new CommunityDA();
$community = $communityDA->getCommunityById($communityId);

if ($community['visibility'] === 'Public') {
    $membershipId = generateCommunityMemberId();
    // Add the user directly for public forums
    $result = $communityMemberDA->addCommunityMember($membershipId,$communityId, $userId, $role="Member");
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'You have successfully joined this forum'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to join the forum'
        ]);
    }
} else {
    // For private/anonymous forums, create a join request
    // Generate a unique request ID
    $requestId = generateRequestId();
    
    try {
        // Create the join request with the reason provided
        $joinRequestDA->createJoinRequest($requestId,$userId, $communityId, $reason);
        
        echo json_encode([
            'success' => true,
            'message' => 'Your request to join this forum has been submitted and is pending approval'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to submit join request: ' . $e->getMessage()
        ]);
    }
}
