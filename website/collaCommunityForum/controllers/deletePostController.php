<?php
session_start();
require_once(__DIR__ . '/../includes/database/postDA.php');
require_once(__DIR__ . '/../includes/database/commentDA.php');
require_once(__DIR__ . '/../includes/database/postVoteDA.php');
require_once(__DIR__ . '/../includes/database/postMediaDA.php');
require_once(__DIR__ . '/../includes/database/userDA.php');
// Set the content type to JSON
header('Content-Type: application/json');

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get the post ID from the query parameter
$postId = isset($_GET['post_id']) ? $_GET['post_id'] : null;

if (!$postId) {
    echo json_encode(['success' => false, 'message' => 'Post ID is required']);
    exit;
}


if (isset($_SESSION["user"])) {
    if ($_SESSION["user"] == "" && $_SESSION['usertype'] != 'p'  && $_SESSION['usertype'] != 'a'  && $_SESSION['usertype'] != 'd') {
        header("location: ../../login.php");
        exit;
    } else {
        $useremail = $_SESSION["user"];
    }
} else {
    header("location: ../../login.php");
    exit;
}
$userDA = new UserDA();
$userData = $userDA->getUserByEmail($useremail);
$userId = $userData['user_id'];

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

try {
    $postDA = new PostDA();
    $commentDA = new CommentDA();
    $postVoteDA = new PostVoteDA();
    $postMediaDA = new PostMediaDA();

    // Fetch the post to verify the author
    $post = $postDA->getPostById($postId);
    error_log("Post fetched for deletion: " . json_encode($post));

    if (!$post) {
        echo json_encode(['success' => false, 'message' => 'Post not found']);
        exit;
    }

    // Verify that the user is the author of the post
    if ($post['author_id'] !== $userId) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized action: You can only delete your own posts']);
        exit;
    }

    // Step 1: Delete all comments associated with the post
    $comments = $commentDA->getCommentsByPostId($postId);
    error_log("Comments fetched for Post ID $postId: " . json_encode($comments));
    if ($comments !== false) {
        foreach ($comments as $comment) {
            if (!$commentDA->deleteComment($comment['comment_id'])) {
                throw new Exception('Failed to delete comment ID: ' . $comment['comment_id']);
            }
            error_log("Successfully deleted comment ID: " . $comment['comment_id'] . " for post ID: $postId");
        }
    } else {
        throw new Exception('Failed to fetch comments for deletion');
    }

    // Step 2: Delete all votes associated with the post using PostVoteDA
    $votes = $postVoteDA->getUserVote(null, $postId); // Fetch all votes for this post
    error_log("Votes fetched for Post ID $postId: " . json_encode($votes));
    if ($votes !== false && !empty($votes)) {
        foreach ($votes as $vote) {
            $deleteVoteResult = $postVoteDA->removeVote($vote['user_id'], $postId);
            if (!$deleteVoteResult) {
                throw new Exception('Failed to delete vote for post ID: ' . $postId . ' by user ID: ' . $vote['user_id']);
            }
            error_log("Successfully deleted vote for post ID: $postId by user ID: " . $vote['user_id']);
        }
    }

    // Step 3: Delete all media associated with the post
    $mediaFiles = $postMediaDA->getMediaByPostId($postId);
    error_log("Media files fetched for Post ID $postId: " . json_encode($mediaFiles));
    if (!empty($mediaFiles)) {
        foreach ($mediaFiles as $media) {
            if (!$postMediaDA->deletePostMedia($media['media_id'])) {
                throw new Exception('Failed to delete media ID: ' . $media['media_id'] . ' for post ID: ' . $postId);
            }
            error_log("Successfully deleted media ID: " . $media['media_id'] . " for post ID: $postId");
        }
    }

    // Step 3.5: Verify no related records remain
    $remainingComments = $commentDA->getCommentsByPostId($postId);
    $remainingVotes = $postVoteDA->getUserVote(null, $postId);
    $remainingMedia = $postMediaDA->getMediaByPostId($postId);
    error_log("Remaining comments for Post ID $postId: " . json_encode($remainingComments));
    error_log("Remaining votes for Post ID $postId: " . json_encode($remainingVotes));
    error_log("Remaining media for Post ID $postId: " . json_encode($remainingMedia));
    if (!empty($remainingComments) || !empty($remainingVotes) || !empty($remainingMedia)) {
        throw new Exception('Related records still exist for post ID: ' . $postId);
    }

    // Step 4: Delete the post itself
    if (!$postDA->deletePost($postId)) {
        throw new Exception('Failed to delete post ID: ' . $postId);
    }

    echo json_encode(['success' => true, 'message' => 'Post and associated data deleted successfully']);
} catch (Exception $e) {
    error_log("Delete Post Error for Post ID $postId: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while deleting the post: ' . $e->getMessage()]);
}
?>