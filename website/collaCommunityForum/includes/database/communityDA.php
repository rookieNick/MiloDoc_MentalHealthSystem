<?php
require_once(__DIR__ . '/config.php');

class CommunityDA
{
    private $conn;
    private $tableName = "community";

    public function __construct()
    {
        $this->conn = $this->getDatabaseConnection();
    }

    private function getDatabaseConnection()
    {
        return getDatabaseConnection();
    }

    public function getAllCommunities($visibility = null)
    {
        $query = "SELECT * FROM {$this->tableName}";
        $params = [];

        if ($visibility) {
            $query .= " WHERE visibility = :visibility";
            $params[':visibility'] = $visibility;
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

    public function getCommunityById($communityId)
    {
        $query = "SELECT * FROM {$this->tableName} WHERE community_id = :communityId";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':communityId', $communityId, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $ex) {
            error_log("Database Error: " . $ex->getMessage());
            return null;
        }
    }
    public function addCommunity($community)
    {
        $query = "INSERT INTO {$this->tableName} 
                  (community_id, name, description, picture_url, creator_id, visibility, category, created_at, updated_at) 
                  VALUES (:community_id, :name, :description, :picture_url, :creator_id, :visibility, :category, NOW(), NOW())";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':community_id', $community['community_id']);
            $stmt->bindParam(':name',         $community['name']);
            $stmt->bindParam(':description',  $community['description']);
            $stmt->bindParam(':picture_url',  $community['picture_url']);
            $stmt->bindParam(':creator_id',   $community['creator_id']);
            $stmt->bindParam(':visibility',   $community['visibility']);
            $stmt->bindParam(':category',     $community['category']);
    
            if ($stmt->execute()) {
                return true;
            } else {
                return $stmt->errorInfo()[2];
            }
        } catch (PDOException $ex) {
            error_log("Database Error: " . $ex->getMessage());
            return $ex->getMessage();
        }
    }
    
    
    public function updateCommunity($community) {
        $query = "
            UPDATE {$this->tableName}
            SET
              name        = :name,
              description = :description,
              picture_url = :picture_url,
              visibility  = :visibility,
              category    = :category,      /* ← added */
              updated_at  = NOW()
            WHERE community_id = :community_id
        ";
    
        try {
            $stmt = $this->conn->prepare($query);
    
            // bind existing fields
            $stmt->bindParam(':community_id', $community['community_id']);
            $stmt->bindParam(':name',         $community['name']);
            $stmt->bindParam(':description',  $community['description']);
            $stmt->bindParam(':picture_url',  $community['picture_url']);
            $stmt->bindParam(':visibility',   $community['visibility']);
    
            // bind new category field
            $stmt->bindParam(':category',     $community['category']);
    
            return $stmt->execute(); // Returns true if successful
        } catch (PDOException $ex) {
            error_log("Database Error: " . $ex->getMessage());
            return false;
        }
    }
    

    public function deleteCommunity($communityId)
    {
        try {
            // Retrieve the image path before deleting the forum
            $query = "SELECT picture_url FROM {$this->tableName} WHERE community_id = :community_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':community_id', $communityId, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if ($result && !empty($result['picture_url'])) {
                $imagePath = __DIR__ . "/../" . ltrim($result['picture_url'], '/'); // Normalize path
                $defaultImage = "default_image.jpg"; // Only use filename for comparison
    
                // Check if it's NOT the default image before deleting
                if (file_exists($imagePath) && basename($result['picture_url']) !== $defaultImage) {
                    unlink($imagePath);
                }
            }
    
          
    
    
            // Now delete the forum
            $deleteForumQuery = "DELETE FROM {$this->tableName} WHERE community_id = :community_id";
            $stmt = $this->conn->prepare($deleteForumQuery);
            $stmt->bindParam(':community_id', $communityId, PDO::PARAM_STR);
            $stmt->execute();
    
          
    
            return true;
        } catch (PDOException $ex) {
            error_log("Database Error: " . $ex->getMessage());
            return false;
        }
    }
    

    public function getLatestCommunityID() {
        $query = "SELECT community_id FROM community 
                  WHERE community_id LIKE :id_prefix 
                  ORDER BY community_id DESC LIMIT 1";
        try {
            $stmt = $this->conn->prepare($query);
            $currentDate = new DateTime();
            $year = $currentDate->format('Y');
            $month = $currentDate->format('m');
            $idPrefix = "COM{$year}{$month}%";
            
            $stmt->bindParam(':id_prefix', $idPrefix, PDO::PARAM_STR);
            $stmt->execute();
    
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $row['community_id'] : null;
        } catch (PDOException $ex) {
            error_log("Database Error: " . $ex->getMessage());
            return null;
        }
    }

/**
 * Fetch communities with optional visibility, created date, and category filters.
 *
 * @param string|null $visibility  One of 'Public','Private','Anonymous'
 * @param string|null $createdAt   Date string in 'YYYY-MM-DD' format
 * @param string|null $category    One of 'anxiety','stress','depression','trauma support','mindfulness and meditation'
 * @return array                   List of matching communities (assoc arrays, including 'category')
 */
public function getFilteredCommunities($visibility = null, $createdAt = null, $category = null, $search = null)
    {
        $query = "SELECT * FROM community WHERE 1=1";

        if (!empty($visibility)) {
            $query .= " AND visibility = :visibility";
        }
        if (!empty($createdAt)) {
            $query .= " AND DATE(created_at) = :createdAt";
        }
        if (!empty($category)) {
            $query .= " AND category = :category";
        }
        if (!empty($search)) {
            $query .= " AND (name LIKE :search OR description LIKE :search)";
        }

        $query .= " ORDER BY created_at DESC";

        try {
            $stmt = $this->conn->prepare($query);

            if (!empty($visibility)) {
                $stmt->bindParam(':visibility', $visibility, PDO::PARAM_STR);
            }
            if (!empty($createdAt)) {
                $stmt->bindParam(':createdAt', $createdAt, PDO::PARAM_STR);
            }
            if (!empty($category)) {
                $stmt->bindParam(':category', $category, PDO::PARAM_STR);
            }
            if (!empty($search)) {
                $searchTerm = "%{$search}%";
                $stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
            }

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
            return [];
        }
    }

public function communityExists($communityId) {
    try {
        $query = "SELECT COUNT(*) FROM {$this->tableName} WHERE community_id = :community_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':community_id', $communityId, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $ex) {
        error_log("Error checking if community exists: " . $ex->getMessage());
        return false;
    }
}
}
