<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');
require_once(__DIR__ . '/../includes/database/postVoteDA.php');
require_once(__DIR__ . '/../includes/database/postDA.php');

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get the post data
$postId = isset($_POST['post_id']) ? $_POST['post_id'] : null;
$userId = isset($_POST['user_id']) ? $_POST['user_id'] : null;
$voteType = isset($_POST['vote_type']) ? $_POST['vote_type'] : null;

// Log received data for debugging
error_log("Vote request - Post ID: $postId, User ID: $userId, Vote Type: $voteType");

// Validate input
if (!$postId || !$userId || !$voteType) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

// Ensure vote type is valid
if ($voteType !== 'up' && $voteType !== 'down') {
    echo json_encode(['success' => false, 'message' => 'Invalid vote type']);
    exit;
}

try {
    $postVoteDA = new PostVoteDA();
    
    // Process the vote - Simplified approach
    $result = $postVoteDA->processVote($userId, $postId, $voteType);
    
    if ($result['success']) {
        // Get the updated vote counts
        $voteCounts = $postVoteDA->getVoteCount($postId);
        $upvotes = $voteCounts['upvotes'] ?: 0;
        $downvotes = $voteCounts['downvotes'] ?: 0;
        
        // Get the user's current vote status
        $userVote = $postVoteDA->getUserVote($userId, $postId);
        $currentVote = $userVote ? $userVote['vote_type'] : null;
        
        echo json_encode([
            'success' => true,
            'message' => $result['message'],
            'upvotes' => $upvotes,
            'downvotes' => $downvotes,
            'currentVote' => $currentVote,
            'postId' => $postId
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => $result['message']]);
    }
} catch (Exception $e) {
    error_log("Vote Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>