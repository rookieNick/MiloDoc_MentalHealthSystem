
<?php
require_once(__DIR__ . '/config.php');

class PostDA
{
    private $conn;
    private $tableName = "post";

    public function __construct()
    {
        $this->conn = $this->getDatabaseConnection();
    }

    private function getDatabaseConnection()
    {
        return getDatabaseConnection();
    }

    public function getAllPosts($communityId = null)
    {
        $query = "SELECT * FROM {$this->tableName}";
        $params = [];

        if ($communityId) {
            $query .= " WHERE community_id = :community_id";
            $params[':community_id'] = $communityId;
        }

        try {
            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $ex) {
            error_log("Database Error: " . $ex->getMessage());
            return [];
        }
    }

    public function getPostById($postId)
    {
        // MODIFIED: Ensure is_flagged is included in the query
        $query = "SELECT post_id, community_id, author_id, content, upvotes, downvotes, is_deleted, created_at, updated_at 
                  FROM {$this->tableName} WHERE post_id = :post_id";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':post_id', $postId, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $ex) {
            error_log("Database Error: " . $ex->getMessage());
            return null;
        }
    }

    public function getPostByCommunityID($communityID, $userId = null)
    {
        try {
            // MODIFIED: Added is_flagged to the selected columns
            $query = "SELECT 
                        p.post_id, p.community_id, p.author_id, p.content, p.upvotes, p.downvotes, 
                        p.is_deleted, p.created_at, p.updated_at,
                        (SELECT COUNT(*) FROM post_vote WHERE post_id = p.post_id AND vote_type = 'up') AS upvotes,
                        (SELECT COUNT(*) FROM post_vote WHERE post_id = p.post_id AND vote_type = 'down') AS downvotes,
                        uv.vote_type AS user_vote
                    FROM post p
                    LEFT JOIN post_vote uv ON p.post_id = uv.post_id AND uv.user_id = :user_id
                    WHERE p.community_id = :community_id
                    ORDER BY p.created_at DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':community_id', $communityID, PDO::PARAM_STR);
            
            if ($userId) {
                $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
            } else {
                $stmt->bindValue(':user_id', null, PDO::PARAM_NULL);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $ex) {
            error_log("Database Error: " . $ex->getMessage());
            return null;
        }
    }

    public function addPost($postData) {
        try {
            // MODIFIED: Added is_flagged to the insert query
            $sql = "INSERT INTO post (post_id, community_id, author_id, content, upvotes, downvotes, is_deleted) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                $postData['post_id'],
                $postData['community_id'],
                $postData['author_id'],
                $postData['content'],
                0, 0, 0,
            ]);
    
            if (!$result) {
                error_log("Insert Failed: " . implode(" | ", $stmt->errorInfo()));
            }
    
            return $result;
        } catch (PDOException $e) {
            error_log("Database Error in addPost: " . $e->getMessage());
            return false;
        }
    }

    public function updatePost($post)
    {
        // MODIFIED: Added is_flagged to the update query
        $query = "UPDATE {$this->tableName} 
                  SET content = :content, image_url = :image_url, updated_at = NOW()
                  WHERE post_id = :post_id";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':post_id', $post['post_id']);
            $stmt->bindParam(':content', $post['content']);
            $stmt->bindParam(':image_url', $post['image_url']);
            
            return $stmt->execute();
        } catch (PDOException $ex) {
            error_log("Database Error: " . $ex->getMessage());
            return false;
        }
    }

    public function deletePost($postId)
    {
        try {
            $deleteQuery = "DELETE FROM {$this->tableName} WHERE post_id = :post_id";
            $stmt = $this->conn->prepare($deleteQuery);
            $stmt->bindParam(':post_id', $postId, PDO::PARAM_STR);
            $stmt->execute();
            $rowCount = $stmt->rowCount();
            error_log("Delete query executed for Post ID $postId, rows affected: $rowCount");
            return $rowCount > 0;
        } catch (PDOException $ex) {
            error_log("Database Error in deletePost for Post ID $postId: " . $ex->getMessage());
            return false;
        }
    }

    public function getLatestPostID()
    {
        $query = "SELECT post_id FROM post 
                  WHERE post_id LIKE :id_prefix 
                  ORDER BY post_id DESC LIMIT 1";
        try {
            $stmt = $this->conn->prepare($query);
            $currentDate = new DateTime();
            $year = $currentDate->format('Y');
            $month = $currentDate->format('m');
            $idPrefix = "PST{$year}{$month}%";
            
            $stmt->bindParam(':id_prefix', $idPrefix, PDO::PARAM_STR);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $row['post_id'] : null;
        } catch (PDOException $ex) {
            error_log("Database Error: " . $ex->getMessage());
            return null;
        }
    }

    public function getPostWithUserVote($postId, $userId = null)
    {
        try {
            // MODIFIED: Added is_flagged to the selected columns
            $query = "SELECT 
                        p.post_id, p.community_id, p.author_id, p.content, p.upvotes, p.downvotes, 
                        p.is_deleted, p.created_at, p.updated_at,
                        (SELECT COUNT(*) FROM post_vote WHERE post_id = p.post_id AND vote_type = 'up') AS upvotes,
                        (SELECT COUNT(*) FROM post_vote WHERE post_id = p.post_id AND vote_type = 'down') AS downvotes,
                        (SELECT vote_type FROM post_vote WHERE post_id = p.post_id AND user_id = :user_id) AS user_vote
                    FROM post p
                    WHERE p.post_id = :post_id";
        
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':post_id', $postId, PDO::PARAM_STR);
            
            if ($userId) {
                $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
            } else {
                $stmt->bindValue(':user_id', null, PDO::PARAM_NULL);
            }
        
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $ex) {
            error_log("Database Error: " . $ex->getMessage());
            return null;
        }
    }


}
