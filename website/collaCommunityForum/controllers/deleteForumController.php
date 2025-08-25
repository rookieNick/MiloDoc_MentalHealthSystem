<?php
session_start();
require_once(__DIR__ . '/../includes/database/communityDA.php');
require_once(__DIR__ . '/../includes/database/postDA.php');
require_once(__DIR__ . '/../includes/database/postMediaDA.php');
require_once(__DIR__ . '/../includes/database/commentDA.php');
require_once(__DIR__ . '/../includes/database/postVoteDA.php');
require_once(__DIR__ . '/../includes/database/communityMemberDA.php');
require_once(__DIR__ . '/../includes/database/joinCommunityRequestDA.php');
require_once(__DIR__ . '/../includes/database/userDA.php');

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 0); // Ensure errors don't output to the response
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Set the content type to JSON
header('Content-Type: application/json');

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get the community ID from the query parameter
$communityId = isset($_GET['id']) ? $_GET['id'] : null;

if (!$communityId) {
    echo json_encode(['success' => false, 'message' => 'Community ID is required']);
    exit;
}

// Check user authentication and return JSON instead of redirecting
if (!isset($_SESSION["user"])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated', 'redirect' => '../../login.php']);
    exit;
}

if ($_SESSION["user"] == "" || !in_array($_SESSION['usertype'], ['p', 'a', 'd'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid user session or user type', 'redirect' => '../../login.php']);
    exit;
}

$useremail = $_SESSION["user"];
$userDA = new UserDA();
$userData = $userDA->getUserByEmail($useremail);
$userId = $userData['user_id'] ?? null;

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

try {
    $communityDA = new CommunityDA();
    $postDA = new PostDA();
    $postMediaDA = new PostMediaDA();
    $commentDA = new CommentDA();
    $postVoteDA = new PostVoteDA();
    $communityMemberDA = new CommunityMemberDA();
    $joinCommunityRequestDA = new JoinCommunityRequestDA();

    // Fetch the community to verify it exists and the user is the admin
    $community = $communityDA->getCommunityById($communityId);
    if (!$community) {
        echo json_encode(['success' => false, 'message' => 'Forum not found']);
        exit;
    }

    // Verify that the user is the admin (creator) of the community
    if ($community['creator_id'] !== $userId) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized action: Only the forum admin can delete this forum']);
        exit;
    }

    // Step 1: Fetch all posts in the community
    $posts = $postDA->getPostByCommunityID($communityId);

    // Step 2: Delete all related data for each post
    foreach ($posts as $post) {
        $postId = $post['post_id'];

        // Delete comments associated with the post
        $comments = $commentDA->getCommentsByPostId($postId);
        if ($comments === false) {
            throw new Exception('Failed to fetch comments for post ID: ' . $postId);
        }
        foreach ($comments as $comment) {
            if (!$commentDA->deleteComment($comment['comment_id'])) {
                throw new Exception('Failed to delete comment ID: ' . $comment['comment_id']);
            }
            error_log("Successfully deleted comment ID: {$comment['comment_id']} for post ID: $postId");
        }

        // Delete votes associated with the post using PostVoteDA
        if (!$postVoteDA->removeVote(null, $postId)) {
            throw new Exception('Failed to delete votes for post ID: ' . $postId);
        }
        error_log("Successfully deleted votes for post ID: $postId");

        // Delete media associated with the post using PostMediaDA
        if (!$postMediaDA->deleteMediaByPostId($postId)) {
            throw new Exception('Failed to delete media for post ID: ' . $postId);
        }
        error_log("Successfully deleted media for post ID: $postId");

        // Delete the post itself using PostDA
        if (!$postDA->deletePost($postId)) {
            throw new Exception('Failed to delete post ID: ' . $postId);
        }
        error_log("Successfully deleted post ID: $postId");
    }

    // Step 3: Delete community membership records using CommunityMemberDA
    $members = $communityMemberDA->getCommunityMembersById($communityId);
    if ($members === null) {
        throw new Exception('Failed to fetch community members for deletion');
    }
    foreach ($members as $member) {
        if (!$communityMemberDA->deleteCommunityMember($member['membership_id'])) {
            throw new Exception('Failed to delete community member ID: ' . $member['membership_id']);
        }
        error_log("Successfully deleted community member ID: {$member['membership_id']} for community ID: $communityId");
    }

    // Step 4: Delete join community requests using JoinCommunityRequestDA
    $requests = $joinCommunityRequestDA->getAllJoinRequests();
    foreach ($requests as $request) {
        if ($request['community_id'] === $communityId) {
            if (!$joinCommunityRequestDA->deleteJoinRequest($request['request_id'])) {
                throw new Exception('Failed to delete join community request ID: ' . $request['request_id']);
            }
            error_log("Successfully deleted join community request ID: {$request['request_id']} for community ID: $communityId");
        }
    }

    // Step 5: Delete the community itself using CommunityDA
    // CommunityDA::deleteCommunity already handles picture deletion
    if (!$communityDA->deleteCommunity($communityId)) {
        throw new Exception('Failed to delete community ID: ' . $communityId);
    }
    error_log("Successfully deleted community ID: $communityId");

    echo json_encode(['success' => true, 'message' => 'Forum and all associated data deleted successfully']);
} catch (Exception $e) {
    error_log("Delete Forum Error for Community ID $communityId: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while deleting the forum: ' . $e->getMessage()]);
}
?>