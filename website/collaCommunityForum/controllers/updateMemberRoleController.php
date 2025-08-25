<?php
session_start();
header('Content-Type: application/json');

require_once(__DIR__ . '/../includes/database/communityMemberDA.php');
require_once(__DIR__ . '/../includes/database/communityDA.php');
require_once(__DIR__ . '/../includes/database/userDA.php');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    error_log("Received data: " . print_r($data, true));

    if (!isset($data['membership_id']) || !isset($data['role']) || !isset($data['community_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required parameters'
        ]);
        exit;
    }

    $membershipId = $data['membership_id'];
    $newRole = $data['role'];
    $communityId = $data['community_id'];

    $validRoles = ['admin', 'moderator', 'member'];
    if (!in_array($newRole, $validRoles)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid role specified'
        ]);
        exit;
    }

    if (!isset($_SESSION["user"]) || ($_SESSION["user"] == "" && !in_array($_SESSION['usertype'], ['p', 'a', 'd']))) {
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized: User not logged in or invalid user type'
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
    $communityMemberDA = new CommunityMemberDA();

    $community = $communityDA->getCommunityById($communityId);
    if (!$community || $community['creator_id'] !== $userId) {
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized: Only forum admins can update member roles'
        ]);
        exit;
    }

    $memberToUpdate = $communityMemberDA->getCommunityMemberById($membershipId);
    if (!$memberToUpdate) {
        echo json_encode([
            'success' => false,
            'message' => 'Member not found'
        ]);
        exit;
    }

    if ($memberToUpdate['user_id'] === $community['creator_id']) {
        echo json_encode([
            'success' => false,
            'message' => 'Cannot change the role of the forum creator'
        ]);
        exit;
    }

    $result = $communityMemberDA->updateCommunityMember($membershipId, ['role' => $newRole]);
    if ($result) {
        error_log("Role updated successfully for membership_id: $membershipId");
        echo json_encode([
            'success' => true,
            'message' => 'Member role updated successfully'
        ]);
    } else {
        error_log("Failed to update role for membership_id: $membershipId");
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update member role in database'
        ]);
    }
} catch (Exception $e) {
    error_log("Server error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>