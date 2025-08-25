<?php
session_start();
require_once(__DIR__ . '/../includes/database/commentDA.php');
require_once(__DIR__ . '/../includes/database/config.php');
require_once(__DIR__ . '/../includes/utilities/generateCommentID.php');
// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Validate and sanitize input
$post_id = filter_input(INPUT_POST, 'post_id', FILTER_SANITIZE_STRING);
$user_id = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_STRING);
$comment_content = filter_input(INPUT_POST, 'comment_content', FILTER_SANITIZE_STRING);

// Validate inputs
if (empty($post_id) || empty($user_id) || empty($comment_content)) {
    http_response_code(400); // Bad Request
    echo json_encode([
        'success' => false, 
        'message' => 'Missing required fields'
    ]);
    exit;
}

// Validate comment length
if (strlen($comment_content) > 1000) {
    http_response_code(400); // Bad Request
    echo json_encode([
        'success' => false, 
        'message' => 'Comment exceeds maximum length of 1000 characters'
    ]);
    exit;
}

try {
    // Generate a unique comment ID
    $comment_id = generateCommentId();

    // Create CommentDA instance
    $commentDA = new CommentDA();

    // Add comment to database
    $result = $commentDA->addComment($comment_id, $post_id, $user_id, $comment_content);

    if ($result) {
        // Redirect back to the post page with success message
        $_SESSION['comment_success'] = 'Comment added successfully!';
        header("Location: ../views/postDetail.php?id=" . urlencode($post_id));
        exit;
    } else {
        // If comment creation fails
        $_SESSION['comment_error'] = 'Failed to add comment. Please try again.';
        header("Location: ../views/postDetail.php?id=" . urlencode($post_id));
        exit;
    }
} catch (Exception $e) {
    // Log the error
    error_log("Comment Creation Error: " . $e->getMessage());

    // Redirect with error
    $_SESSION['comment_error'] = 'An unexpected error occurred.';
    header("Location: ../pages/postDetail.php?id=" . urlencode($post_id));
    exit;
}


?>