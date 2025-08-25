<?php
session_start();
require_once(__DIR__ . '/../includes/database/postVoteDA.php');

header('Content-Type: application/json');

// Validate input
$postId = $_POST['post_id'] ?? null;
$userId = $_POST['user_id'] ?? null;
$voteType = $_POST['vote_type'] ?? null;

// Input validation
if (!$postId || !$userId || !$voteType || !in_array($voteType, ['up', 'down'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid input parameters'
    ]);
    exit;
}

try {
    $postVoteDA = new PostVoteDA();
    
    // Check if user has already voted
    $existingVote = $postVoteDA->voteExists($userId, $postId);
    
    if ($existingVote) {
        // If vote exists, update or remove the vote
        if ($existingVote['vote_type'] === $voteType) {
            // Same vote type - remove the vote
            $result = $postVoteDA->removeVote($userId, $postId);
            $message = 'Vote removed';
            $currentVote = null;
        } else {
            // Different vote type - update the vote
            $result = $postVoteDA->updateVote($userId, $postId, $voteType);
            $message = 'Vote changed';
            $currentVote = $voteType;
        }
    } else {
        // No existing vote - add new vote
        $result = $postVoteDA->addVote($userId, $postId, $voteType);
        $message = 'Vote added';
        $currentVote = $voteType;
    }
    
    // Get updated vote counts
    $voteCounts = $postVoteDA->getVoteCount($postId);
    
    // Prepare response
    $response = [
        'success' => $result,
        'message' => $message,
        'upvotes' => $voteCounts['upvotes'],
        'downvotes' => $voteCounts['downvotes'],
        'currentVote' => $currentVote
    ];
    
    echo json_encode($response);
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'An unexpected error occurred: ' . $e->getMessage()
    ]);
}
?>