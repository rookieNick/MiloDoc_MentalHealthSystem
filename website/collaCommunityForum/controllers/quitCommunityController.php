<?php
require_once(__DIR__ . '/../includes/database/communityMemberDA.php');

header('Content-Type: application/json');

$communityMemberDA = new CommunityMemberDA();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }

    $communityId = isset($_POST['community_id']) ? $_POST['community_id'] : null;
    $userId = isset($_POST['user_id']) ? $_POST['user_id'] : null;

    if (!$communityId || !$userId) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    // Remove 1. Check if the user is a member of the community
    $members = $communityMemberDA->getCommunityMembersById($communityId);
    $isMember = false;
    foreach ($members as $member) {
        if ($member['user_id'] === $userId) {
            $isMember = true;
            break;
        }
    }

    if (!$isMember) {
        echo json_encode(['success' => false, 'message' => 'You are not a member of this community']);
        exit;
    }

    // Remove the member from the community
    $result = $communityMemberDA->removeMemberByCommunityAndUser($communityId, $userId);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'You have successfully quit the community']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to quit the community. Please try again.']);
    }
} catch (Exception $e) {
    error_log("Error in quitCommunityController: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
?>