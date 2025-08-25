<?php
// voteDebug.php - Diagnostic tool for the voting system
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html');

require_once(__DIR__ . '/../includes/database/postVoteDA.php');
require_once(__DIR__ . '/../includes/database/postDA.php');

echo "<h1>Vote System Debug</h1>";

// Test database connection
echo "<h2>Testing Database Connection</h2>";
try {
    require_once(__DIR__ . '/../includes/database/config.php');
    $conn = getDatabaseConnection();
    echo "<p style='color: green;'>✅ Database connection successful</p>";
    
    // Check if post_vote table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'post_vote'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ post_vote table exists</p>";
        
        // Check table structure
        $result = $conn->query("DESCRIBE post_vote");
        $columns = $result->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>Columns: " . implode(", ", $columns) . "</p>";
        
        // Check if table has data
        $result = $conn->query("SELECT COUNT(*) as count FROM post_vote");
        $count = $result->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p>Total votes in database: $count</p>";
    } else {
        echo "<p style='color: red;'>❌ post_vote table does not exist!</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

// Diagnose vote process 
echo "<h2>Testing Vote Process</h2>";
echo "<p>Try simulating a vote with test data:</p>";

echo "<form method='post' action=''>
    Post ID: <input type='text' name='post_id' value='POST2025030001'><br>
    User ID: <input type='text' name='user_id' value='USR2025030001'><br>
    Vote Type: 
    <select name='vote_type'>
        <option value='up'>Upvote</option>
        <option value='down'>Downvote</option>
    </select><br>
    <input type='submit' name='test_vote' value='Test Vote'>
</form>";

if (isset($_POST['test_vote'])) {
    $postId = $_POST['post_id'];
    $userId = $_POST['user_id'];
    $voteType = $_POST['vote_type'];
    
    echo "<h3>Test Results</h3>";
    echo "<p>Testing vote with: Post ID: $postId, User ID: $userId, Vote Type: $voteType</p>";
    
    try {
        $postVoteDA = new PostVoteDA();
        
        // Check if vote exists
        $existingVote = $postVoteDA->voteExists($userId, $postId);
        if ($existingVote) {
            echo "<p>Existing vote found: " . json_encode($existingVote) . "</p>";
        } else {
            echo "<p>No existing vote found.</p>";
        }
        
        // Try processing vote
        $result = $postVoteDA->processVote($userId, $postId, $voteType);
        
        echo "<p>Vote process result: " . json_encode($result) . "</p>";
        
        // Get current votes
        $voteCounts = $postVoteDA->getVoteCount($postId);
        echo "<p>Current vote counts: Upvotes: " . ($voteCounts['upvotes'] ?: 0) . 
             ", Downvotes: " . ($voteCounts['downvotes'] ?: 0) . "</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error during vote test: " . $e->getMessage() . "</p>";
    }
}

echo "<h2>Check PHP & Server Configuration</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>PDO Drivers: " . implode(", ", PDO::getAvailableDrivers()) . "</p>";
echo "<p>Server: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
?>