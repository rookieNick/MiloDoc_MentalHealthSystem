<?php
require_once(__DIR__ . '/config.php');

class PostVoteDA
{
    private $conn;
    private $tableName = "post_vote";

    public function __construct()
    {
        $this->conn = $this->getDatabaseConnection();
    }

    private function getDatabaseConnection()
    {
        return getDatabaseConnection();
    }

    // Check if user has already voted on the post
    public function voteExists($userId, $postId)
    {
        try {
            $query = "SELECT vote_id, vote_type FROM {$this->tableName} WHERE user_id = :user_id AND post_id = :post_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
            $stmt->bindParam(':post_id', $postId, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC); // Returns vote type if exists
        } catch (PDOException $ex) {
            error_log("Database Error: " . $ex->getMessage());
            return false;
        }
    }

   // Add a new vote if the user hasn't voted yet
public function addVote($userId, $postId, $voteType)
{
    try {
        // Generate a new vote ID
        $voteId = generatePostVoteId();
        
        $query = "INSERT INTO {$this->tableName} (vote_id, user_id, post_id, vote_type) 
                  VALUES (:vote_id, :user_id, :post_id, :vote_type)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':vote_id', $voteId, PDO::PARAM_STR);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
        $stmt->bindParam(':post_id', $postId, PDO::PARAM_STR);
        $stmt->bindParam(':vote_type', $voteType, PDO::PARAM_STR);
        return $stmt->execute();
    } catch (PDOException $ex) {
        error_log("Database Error in addVote: " . $ex->getMessage());
        return false;
    }
}

    // Update an existing vote
    public function updateVote($userId, $postId, $newVoteType)
    {
        try {
            $existingVote = $this->voteExists($userId, $postId);
    
            if ($existingVote) {
                if ($existingVote['vote_type'] === $newVoteType) {
                    return $this->removeVote($userId, $postId); // Toggle vote removal
                } else {
                    // Update the vote
                    $query = "UPDATE {$this->tableName} SET vote_type = :vote_type WHERE user_id = :user_id AND post_id = :post_id";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':vote_type', $newVoteType, PDO::PARAM_STR);
                    $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
                    $stmt->bindParam(':post_id', $postId, PDO::PARAM_STR);
                    return $stmt->execute();
                }
            }
            return false;
        } catch (PDOException $ex) {
            error_log("Database Error: " . $ex->getMessage());
            return false;
        }
    }

   // Remove vote (toggle if user clicks same vote twice)
   public function removeVote($userId, $postId)
   {
       try {
           $query = "DELETE FROM {$this->tableName}";
           $conditions = [];
           $params = [];

           if ($userId !== null) {
               $conditions[] = "user_id = :user_id";
               $params[':user_id'] = $userId;
           }
           if ($postId !== null) {
               $conditions[] = "post_id = :post_id";
               $params[':post_id'] = $postId;
           }

           if (!empty($conditions)) {
               $query .= " WHERE " . implode(" AND ", $conditions);
           } else {
               return false; // Prevent accidental deletion of all votes
           }

           $stmt = $this->conn->prepare($query);
           foreach ($params as $key => $value) {
               $stmt->bindParam($key, $value, PDO::PARAM_STR);
           }
           return $stmt->execute();
       } catch (PDOException $ex) {
           error_log("Database Error: " . $ex->getMessage());
           return false;
       }
   }

    // Get the vote count for a post
    public function getVoteCount($postId)
    {
        try {
            $query = "SELECT 
                        COALESCE(SUM(CASE WHEN vote_type = 'up' THEN 1 ELSE 0 END), 0) AS upvotes,
                        COALESCE(SUM(CASE WHEN vote_type = 'down' THEN 1 ELSE 0 END), 0) AS downvotes
                      FROM {$this->tableName} WHERE post_id = :post_id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':post_id', $postId, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $ex) {
            error_log("Database Error: " . $ex->getMessage());
            return ['upvotes' => 0, 'downvotes' => 0];
        }
    }

    // Get the vote type of a specific user for a post
    public function getUserVote($userId, $postId)
    {
        try {
            $query = "SELECT vote_type FROM {$this->tableName} WHERE user_id = :user_id AND post_id = :post_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
            $stmt->bindParam(':post_id', $postId, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $ex) {
            error_log("Database Error: " . $ex->getMessage());
            return false;
        }
    }

    // Get the latest post vote ID
    public function getLatestPostVoteID() {
        $query = "SELECT vote_id FROM {$this->tableName} 
                  WHERE vote_id LIKE :id_prefix 
                  ORDER BY vote_id DESC LIMIT 1";
        try {
            $stmt = $this->conn->prepare($query);
            $currentDate = new DateTime();
            $year = $currentDate->format('Y');
            $month = $currentDate->format('m');
            $idPrefix = "PVOTE{$year}{$month}%";
            
            $stmt->bindParam(':id_prefix', $idPrefix, PDO::PARAM_STR);
            $stmt->execute();
    
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $row['vote_id'] : null;
        } catch (PDOException $ex) {
            error_log("Database Error: " . $ex->getMessage());
            return null;
        }
    }
    
    
    public function processVote($userId, $postId, $voteType) {
        try {
            // Begin transaction
            $this->conn->beginTransaction();
            
            // Check if vote exists
            $query = "SELECT vote_id, vote_type FROM {$this->tableName} WHERE user_id = :user_id AND post_id = :post_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
            $stmt->bindParam(':post_id', $postId, PDO::PARAM_STR);
            $stmt->execute();
            $existingVote = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Determine action based on existing vote
            if ($existingVote) {
                if ($existingVote['vote_type'] === $voteType) {
                    // Same vote type - remove the vote
                    $query = "DELETE FROM {$this->tableName} WHERE vote_id = :vote_id";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':vote_id', $existingVote['vote_id'], PDO::PARAM_STR);
                    $stmt->execute();
                    $action = 'removed';
                } else {
                    // Different vote type - update the vote
                    $query = "UPDATE {$this->tableName} SET vote_type = :vote_type WHERE vote_id = :vote_id";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':vote_type', $voteType, PDO::PARAM_STR);
                    $stmt->bindParam(':vote_id', $existingVote['vote_id'], PDO::PARAM_STR);
                    $stmt->execute();
                    $action = 'changed';
                }
            } else {
                // No existing vote - create new vote
                // Generate a new vote ID
                $currentDate = new DateTime();
                $year = $currentDate->format('Y');
                $month = $currentDate->format('m');
                $prefix = "PVOTE{$year}{$month}";
                
                // Get latest ID or start at 0001
                $query = "SELECT MAX(CAST(SUBSTRING(vote_id, -4) AS UNSIGNED)) as max_num 
                         FROM {$this->tableName} 
                         WHERE vote_id LIKE :prefix";
                $stmt = $this->conn->prepare($query);
                $prefixParam = $prefix . '%';
                $stmt->bindParam(':prefix', $prefixParam, PDO::PARAM_STR);
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $nextNum = ($row && $row['max_num']) ? $row['max_num'] + 1 : 1;
                $voteId = $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
                
                // Insert new vote
                $query = "INSERT INTO {$this->tableName} (vote_id, user_id, post_id, vote_type) 
                         VALUES (:vote_id, :user_id, :post_id, :vote_type)";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':vote_id', $voteId, PDO::PARAM_STR);
                $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
                $stmt->bindParam(':post_id', $postId, PDO::PARAM_STR);
                $stmt->bindParam(':vote_type', $voteType, PDO::PARAM_STR);
                $stmt->execute();
                $action = 'added';
            }
            
            $this->conn->commit();
            return ['success' => true, 'message' => "Vote $action successfully"];
        } 
        catch (PDOException $ex) {
            // Roll back the transaction in case of error
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log("Database Error in processVote: " . $ex->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $ex->getMessage()];
        }
        catch (Exception $e) {
            // Roll back the transaction in case of error
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log("General Error in processVote: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}





?>