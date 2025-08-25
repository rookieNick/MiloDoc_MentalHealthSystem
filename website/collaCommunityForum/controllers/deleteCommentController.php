<?php
session_start();
require_once(__DIR__ . '/../includes/database/commentDA.php');
require_once(__DIR__ . '/../includes/database/userDA.php');
// Set the content type to JSON
header('Content-Type: application/json');

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get the comment ID from the query parameter
$commentId = isset($_GET['comment_id']) ? $_GET['comment_id'] : null;

if (!$commentId) {
    echo json_encode(['success' => false, 'message' => 'Comment ID is required']);
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
    $commentDA = new CommentDA();

    // Fetch the comment to verify the author
    $comment = $commentDA->getCommentById($commentId);

    if (!$comment) {
        echo json_encode(['success' => false, 'message' => 'Comment not found']);
        exit;
    }

    // Verify that the user is the author of the comment
    if ($comment['author_id'] !== $userId) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized action: You can only delete your own comments']);
        exit;
    }

    // Perform the hard delete
    $result = $commentDA->deleteComment($commentId);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Comment deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete comment']);
    }
} catch (Exception $e) {
    error_log("Delete Comment Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while deleting the comment']);
}
?>