<?php
require_once(__DIR__ . '/config.php');

class PostMediaDA {
    private $conn;
    private $tableName = "post_media";

    public function __construct() {
        $this->conn = $this->getDatabaseConnection();
    }

    private function getDatabaseConnection() {
        return getDatabaseConnection();
    }

        // Create: Add a new media (image or video) to a post
        public function addPostMedia($media) {
            $query = "INSERT INTO {$this->tableName} (media_id, post_id, media_url, media_type, uploaded_at) 
                    VALUES (:media_id, :post_id, :media_url, :media_type, NOW())";
            try {
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':media_id', $media['media_id']);
                $stmt->bindParam(':post_id', $media['post_id']);
                $stmt->bindParam(':media_url', $media['media_url']);
                $stmt->bindParam(':media_type', $media['media_type']); // image or video
                return $stmt->execute();
            } catch (PDOException $ex) {
                error_log("Database Error: " . $ex->getMessage());
                return false;
            }
        }

    // Read: Get all media for a specific post
    public function getMediaByPostId($postId) {
        $query = "SELECT * FROM {$this->tableName} WHERE post_id = :post_id";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':post_id', $postId, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $ex) {
            error_log("Database Error: " . $ex->getMessage());
            return [];
        }
    }

    // Update: Modify a media URL
    public function updatePostMedia($media) {
        $query = "UPDATE {$this->tableName} 
                  SET media_url = :media_url, uploaded_at = NOW() 
                  WHERE media_id = :media_id";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':media_id', $media['media_id']);
            $stmt->bindParam(':media_url', $media['media_url']);
            return $stmt->execute();
        } catch (PDOException $ex) {
            error_log("Database Error: " . $ex->getMessage());
            return false;
        }
    }

    // Delete: Remove media from the database and delete the file
    public function deletePostMedia($mediaId) {
        try {
            // Retrieve media path before deleting the record
            $query = "SELECT media_url FROM {$this->tableName} WHERE media_id = :media_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':media_id', $mediaId, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result && !empty($result['media_url'])) {
                $mediaPath = __DIR__ . '/../' . ltrim($result['media_url'], '/');
                if (file_exists($mediaPath)) {
                    unlink($mediaPath); // Delete the file (image/video)
                }
            }

            // Now delete the record from the database
            $deleteQuery = "DELETE FROM {$this->tableName} WHERE media_id = :media_id";
            $stmt = $this->conn->prepare($deleteQuery);
            $stmt->bindParam(':media_id', $mediaId, PDO::PARAM_STR);
            return $stmt->execute();
        } catch (PDOException $ex) {
            error_log("Database Error: " . $ex->getMessage());
            return false;
        }
    }

    // Delete all media related to a specific post
    public function deleteMediaByPostId($postId) {
        try {
            // Retrieve media URLs before deleting
            $query = "SELECT media_url FROM {$this->tableName} WHERE post_id = :post_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':post_id', $postId, PDO::PARAM_STR);
            $stmt->execute();
            $mediaList = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($mediaList as $media) {
                $mediaPath = __DIR__ . '/../' . ltrim($media['media_url'], '/');
                if (file_exists($mediaPath)) {
                    unlink($mediaPath);
                }
            }

            // Now delete the records from the database
            $deleteQuery = "DELETE FROM {$this->tableName} WHERE post_id = :post_id";
            $stmt = $this->conn->prepare($deleteQuery);
            $stmt->bindParam(':post_id', $postId, PDO::PARAM_STR);
            return $stmt->execute();
        } catch (PDOException $ex) {
            error_log("Database Error: " . $ex->getMessage());
            return false;
        }
    }

    // Get the latest post media ID for auto-generating new IDs
    public function getLatestPostMediaID() {
        $query = "SELECT media_id FROM {$this->tableName}
                  WHERE media_id LIKE :id_prefix 
                  ORDER BY media_id DESC LIMIT 1";
        try {
            $stmt = $this->conn->prepare($query);
            $currentDate = new DateTime();
            $year = $currentDate->format('Y');
            $month = $currentDate->format('m');
            $idPrefix = "PSTMD{$year}{$month}%";
            
            $stmt->bindParam(':id_prefix', $idPrefix, PDO::PARAM_STR);
            $stmt->execute();
    
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $row['media_id'] : null;
        } catch (PDOException $ex) {
            error_log("Database Error: " . $ex->getMessage());
            return null;
        }
    }
}
