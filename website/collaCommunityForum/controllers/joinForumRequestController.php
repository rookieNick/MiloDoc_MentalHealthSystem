<?php
session_start();
header('Content-Type: application/json');

require_once(__DIR__ . '/../includes/database/communityDA.php');
require_once(__DIR__ . '/../includes/database/communityMemberDA.php');
require_once(__DIR__ . '/../includes/database/joinCommunityRequestDA.php');
require_once(__DIR__ . '/../includes/utilities/generateCommunityMemberID.php');
require_once(__DIR__ . '/../includes/database/userDA.php');

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['action']) || !isset($data['request_id']) || !isset($data['community_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters'
    ]);
    exit;
}

$requestId = $data['request_id'];
$action = $data['action'];
$communityId = $data['community_id'];

if (!isset($_SESSION["user"])) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in'
    ]);
    exit;
}

$useremail = $_SESSION["user"];
$userDA = new UserDA();
$userData = $userDA->getUserByEmail($useremail);
if (!$userData) {
    echo json_encode([
        'success' => false,
        'message' => 'User not found'
    ]);
    exit;
}
$userId = $userData['user_id'];

$communityDA = new CommunityDA();
$community = $communityDA->getCommunityById($communityId);
if (!$community || $userId != $community['creator_id']) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized: Only forum admins can perform this action'
    ]);
    exit;
}

$joinRequestDA = new JoinCommunityRequestDA();
$communityMemberDA = new CommunityMemberDA();

if ($action == 'approve') {
    $request = $joinRequestDA->getJoinRequestById($requestId);
    if ($request) {
        $joinRequestDA->updateJoinRequestStatus($requestId, 'Approved', $userId);
        $newMembershipId = generateCommunityMemberId();
        $result = $communityMemberDA->addCommunityMember(
            $newMembershipId,
            $request['community_id'],
            $request['user_id'],
            'Member'
        );
        if ($result) {
            $joinRequestDA->deleteJoinRequest($requestId);
            echo json_encode([
                'success' => true,
                'message' => 'Join request approved successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to add member to community'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Request not found'
        ]);
    }
} elseif ($action == 'reject') {
    $joinRequestDA->updateJoinRequestStatus($requestId, 'Rejected', $userId);
    $joinRequestDA->deleteJoinRequest($requestId);
    echo json_encode([
        'success' => true,
        'message' => 'Join request rejected successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid action'
    ]);
}
exit;
?>