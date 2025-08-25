<?php
require_once(__DIR__ . '/config.php');
class CommentDA {
    private $conn;
    private $tableName = "comment";

    public function __construct()
    {
        $this->conn = $this->getDatabaseConnection();
    }

    private function getDatabaseConnection()
    {
        return getDatabaseConnection();
    }

    // 🔹 1️⃣ Create a New Comment
    public function addComment($comment_id, $post_id, $author_id, $content) {
        $query = "INSERT INTO $this->tableName (comment_id, post_id, author_id, content, created_at, updated_at) 
                  VALUES (:comment_id, :post_id, :author_id, :content, NOW(), NOW())";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':comment_id', $comment_id);
            $stmt->bindParam(':post_id', $post_id);
            $stmt->bindParam(':author_id', $author_id);
            $stmt->bindParam(':content', $content);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
            return false;
        }
    }

    // 🔹 2️⃣ Read Comments by Post ID
    public function getCommentsByPostId($post_id) {
        $query = "SELECT * FROM $this->tableName WHERE post_id = :post_id AND is_deleted = 0 ORDER BY created_at ASC";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':post_id', $post_id, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
            return false;
        }
    }

    // 🔹 3️⃣ Update a Comment
    public function updateComment($comment_id, $content) {
        $query = "UPDATE $this->tableName 
                  SET content = :content, updated_at = NOW() 
                  WHERE comment_id = :comment_id AND is_deleted = 0";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':comment_id', $comment_id, PDO::PARAM_STR);
            $stmt->bindParam(':content', $content, PDO::PARAM_STR);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
            return false;
        }
    }
    
    public function deleteComment($comment_id) {
        $query = "DELETE FROM $this->tableName WHERE comment_id = :comment_id";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':comment_id', $comment_id, PDO::PARAM_STR);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
            return false;
        }
    }

    // 🔹 5 Get a Single Comment by ID
    public function getCommentById($comment_id) {
        $query = "SELECT * FROM $this->tableName WHERE comment_id = :comment_id AND is_deleted = 0";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':comment_id', $comment_id, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
            return false;
        }
    }

    public function getLatestCommentID() {
        $query = "SELECT comment_id FROM comment 
                  WHERE comment_id LIKE :id_prefix 
                  ORDER BY comment_id DESC LIMIT 1";
        try {
            $stmt = $this->conn->prepare($query);
            $currentDate = new DateTime();
            $year = $currentDate->format('Y');
            $month = $currentDate->format('m');
            $idPrefix = "CMT{$year}{$month}%";
            
            $stmt->bindParam(':id_prefix', $idPrefix, PDO::PARAM_STR);
            $stmt->execute();
    
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $row['comment_id'] : null;
        } catch (PDOException $ex) {
            error_log("Database Error: " . $ex->getMessage());
            return null;
        }
    }
}
?>
