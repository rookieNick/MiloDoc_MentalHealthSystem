<?php
require_once(__DIR__ . '/config.php');

class JoinCommunityRequestDA
{
    private $conn;
    private $tableName = "joincommunityrequest";

    public function __construct()
    {
        $this->conn = $this->getDatabaseConnection();
    }

    private function getDatabaseConnection()
    {
        return getDatabaseConnection();
    }

    // Create a join request
    public function createJoinRequest($id, $userId, $communityId, $reason)
    {
        try {
            $requestId = $id;
            $currentDate = date('Y-m-d H:i:s');
            
            $sql = "INSERT INTO {$this->tableName} (request_id, user_id, community_id, reason, request_date) 
                    VALUES (:request_id, :user_id, :community_id, :reason, :request_date)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':request_id', $requestId, PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
            $stmt->bindParam(':community_id', $communityId, PDO::PARAM_STR);
            $stmt->bindParam(':reason', $reason, PDO::PARAM_STR);
            $stmt->bindParam(':request_date', $currentDate, PDO::PARAM_STR);
            
            $result = $stmt->execute();
            
            if (!$result) {
                throw new Exception("Failed to create join request");
            }
            
            return $requestId;
            
        } catch (PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
            throw new Exception("Database error: " . $e->getMessage());
        }
    }
   // Fetch all join requests
   public function getAllJoinRequests()
   {
       try {
           $sql = "SELECT * FROM {$this->tableName} ORDER BY request_date DESC";
           $stmt = $this->conn->prepare($sql);
           $stmt->execute();
           
           return $stmt->fetchAll(PDO::FETCH_ASSOC);
       } catch (PDOException $e) {
           error_log("Database Error: " . $e->getMessage());
           return [];
       }
   }

   // Fetch a single join request by request_id
   public function getJoinRequestById($requestId)
   {
       try {
           $sql = "SELECT * FROM {$this->tableName} WHERE request_id = :request_id";
           $stmt = $this->conn->prepare($sql);
           $stmt->bindParam(':request_id', $requestId, PDO::PARAM_STR);
           $stmt->execute();
           
           return $stmt->fetch(PDO::FETCH_ASSOC);
       } catch (PDOException $e) {
           error_log("Database Error: " . $e->getMessage());
           return null;
       }
   }

   // Update request status (Approve or Reject)
   public function updateJoinRequestStatus($requestId, $status, $reviewedBy)
   {
       try {
           $reviewDate = date('Y-m-d H:i:s');
           
           $sql = "UPDATE {$this->tableName} 
                   SET request_status = :status, 
                       reviewed_by = :reviewed_by,
                       review_date = :review_date
                   WHERE request_id = :request_id";
                   
           $stmt = $this->conn->prepare($sql);
           $stmt->bindParam(':status', $status, PDO::PARAM_STR);
           $stmt->bindParam(':reviewed_by', $reviewedBy, PDO::PARAM_STR);
           $stmt->bindParam(':review_date', $reviewDate, PDO::PARAM_STR);
           $stmt->bindParam(':request_id', $requestId, PDO::PARAM_STR);
           
           return $stmt->execute();
       } catch (PDOException $e) {
           error_log("Database Error: " . $e->getMessage());
           return false;
       }
   }

   public function deleteJoinRequest($requestId)
   {
       try {
           $sql = "DELETE FROM {$this->tableName} WHERE request_id = :request_id";
           $stmt = $this->conn->prepare($sql);
           $stmt->bindParam(':request_id', $requestId, PDO::PARAM_STR);
           
           return $stmt->execute();
       } catch (PDOException $e) {
           error_log("Database Error: " . $e->getMessage());
           return false;
       }
   }

    
    public function getLatestRequestID() {
        $query = "SELECT request_id FROM joincommunityrequest 
                  WHERE request_id LIKE :id_prefix 
                  ORDER BY request_id DESC LIMIT 1";
        try {
            $stmt = $this->conn->prepare($query);
            $currentDate = new DateTime();
            $year = $currentDate->format('Y');
            $month = $currentDate->format('m');
            $idPrefix = "REQ{$year}{$month}%";
            
            $stmt->bindParam(':id_prefix', $idPrefix, PDO::PARAM_STR);
            $stmt->execute();
    
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $row['request_id'] : null;
        } catch (PDOException $ex) {
            error_log("Database Error: " . $ex->getMessage());
            return null;
        }
    }
}
