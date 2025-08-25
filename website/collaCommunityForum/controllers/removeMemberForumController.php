<?php
// File: controllers/removeMemberForumController.php
session_start();
header('Content-Type: application/json');

// Include necessary files
require_once(__DIR__ . '/../includes/database/communityMemberDA.php');
require_once(__DIR__ . '/../includes/database/communityDA.php');
require_once(__DIR__ . '/../includes/database/userDA.php');

// Get JSON data from request
$data = json_decode(file_get_contents('php://input'), true);

// Check if all required data is present
if (!isset($data['membership_id']) || !isset($data['community_id'])) {
    error_log("Missing required parameters: " . print_r($data, true));
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters'
    ]);
    exit;
}

$membershipId = $data['membership_id'];
$communityId = $data['community_id'];

// Validate membershipId
if (empty($membershipId) || !is_string($membershipId)) {
    error_log("Invalid membership_id: $membershipId");
    echo json_encode([
        'success' => false,
        'message' => 'Invalid membership ID'
    ]);
    exit;
}

// Validate session
if (!isset($_SESSION["user"])) {
    error_log("No user session found");
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized: User not logged in'
    ]);
    exit;
}

if ($_SESSION["user"] == "" || !in_array($_SESSION['usertype'], ['p', 'a', 'd'])) {
    error_log("Unauthorized access attempt: user not logged in or invalid usertype");
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized: Invalid user type'
    ]);
    exit;
}

$useremail = $_SESSION["user"];
$userDA = new UserDA();
$userData = $userDA->getUserByEmail($useremail);
if (!$userData) {
    error_log("User not found for email: $useremail");
    echo json_encode([
        'success' => false,
        'message' => 'User not found'
    ]);
    exit;
}
$userId = $userData['user_id'];

// Initialize data access objects
$communityDA = new CommunityDA();
$communityMemberDA = new CommunityMemberDA();

// Get community details to check if user is admin
$community = $communityDA->getCommunityById($communityId);
if (!$community || $community['creator_id'] !== $userId) {
    error_log("Unauthorized: User $userId is not admin of community $communityId");
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized: Only forum admins can remove members'
    ]);
    exit;
}

// Check if the member exists and is not the creator
$memberToRemove = $communityMemberDA->getCommunityMemberById($membershipId);
if ($memberToRemove && $memberToRemove['user_id'] === $community['creator_id']) {
    error_log("Attempt to remove creator: user_id {$memberToRemove['user_id']} for community $communityId");
    echo json_encode([
        'success' => false,
        'message' => 'Cannot remove the forum creator'
    ]);
    exit;
}

// Remove the member
try {
    error_log("Attempting to delete member with membership_id: $membershipId");
    $result = $communityMemberDA->deleteCommunityMember($membershipId);
    error_log("Delete result for membership_id $membershipId: " . ($result ? 'true' : 'false'));

    // Check if the member still exists to confirm deletion
    $memberAfterDeletion = $communityMemberDA->getCommunityMemberById($membershipId);
    if ($memberAfterDeletion) {
        error_log("Member still exists after deletion attempt: membership_id $membershipId");
        echo json_encode([
            'success' => false,
            'message' => 'Failed to remove member: Member still exists'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Member removed successfully'
        ]);
    }
} catch (Exception $e) {
    error_log("Error deleting member with membership_id $membershipId: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to remove member: ' . $e->getMessage()
    ]);
}
?>