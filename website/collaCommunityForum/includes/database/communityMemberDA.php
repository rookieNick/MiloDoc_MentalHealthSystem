<?php
require_once(__DIR__ . '/config.php');

class CommunityMemberDA
{
    private $conn;
    private $tableName = "community_member";

    public function __construct()
    {
        $this->conn = $this->getDatabaseConnection();
    }

    private function getDatabaseConnection()
    {
        return getDatabaseConnection();
    }
    public function addCommunityMember($membership_id, $community_id, $user_id, $role = "Member", $joined_at = null) {
        $sql = "INSERT INTO {$this->tableName} (membership_id, community_id, user_id, role, joined_at) 
                VALUES (:membership_id, :community_id, :user_id, :role, :joined_at)";
        
        try {
            $stmt = $this->conn->prepare($sql);
            if ($joined_at === null) {
                $joined_at = date('Y-m-d H:i:s');
            }
            $stmt->bindParam(':membership_id', $membership_id);
            $stmt->bindParam(':community_id', $community_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':role', $role);
            $stmt->bindParam(':joined_at', $joined_at);
            
            if ($stmt->execute()) {
                return true;
            } else {
                error_log("Failed to execute INSERT for membership_id=$membership_id, community_id=$community_id, user_id=$user_id");
                return false;
            }
        } catch (PDOException $e) {
            $errorInfo = $e->errorInfo;
            $sqlState = $errorInfo[0];
            $errorCode = $e->getCode();
            
            if ($sqlState == '23000') {
                if (strpos($e->getMessage(), 'membership_id') !== false) {
                    error_log("Duplicate membership_id detected: membership_id=$membership_id");
                    throw new Exception("Duplicate membership ID: $membership_id");
                } elseif (strpos($e->getMessage(), 'community_id') !== false || strpos($e->getMessage(), 'user_id') !== false) {
                    error_log("Duplicate membership or foreign key violation: user_id=$user_id, community_id=$community_id");
                    throw new Exception("User is already a member or invalid community/user ID");
                }
            } elseif ($sqlState == '23503') { // Foreign key violation
                error_log("Foreign key violation: community_id=$community_id or user_id=$user_id does not exist");
                throw new Exception("Invalid community or user ID");
            }
            
            error_log("Error in addCommunityMember: " . $e->getMessage());
            throw new Exception("Database error: " . $e->getMessage());
        }
    }

    /**
     * Read a community member record by membership_id.
     */
    public function getCommunityMemberById($membership_id) {
        $sql = "SELECT * FROM {$this->tableName} WHERE membership_id = :membership_id";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':membership_id', $membership_id);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getCommunityMemberById: " . $e->getMessage());
            return false;
        }
    }
    public function getCommunityMembersById($communityId)
    {
        $query = "SELECT * FROM {$this->tableName} WHERE community_id = :communityId";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':communityId', $communityId, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $ex) {
            error_log("Database Error: " . $ex->getMessage());
            return null;
        }
    }

    public function getCommunityMemberByUserId($userId) {
        $sql = "SELECT * FROM {$this->tableName} WHERE user_id = :user_id";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getCommunityMemberById: " . $e->getMessage());
            return false;
        }
    }
    /**
     * Get all community member records.
     */
    public function getAllCommunityMembers() {
        $sql = "SELECT * FROM {$this->tableName}";
        try {
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getAllCommunityMembers: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update a community member record.
     * $data should be an associative array with the keys: community_id, user_id, role, joined_at (if needed)
     */
    public function updateCommunityMember($membership_id, $data) {
        // Build dynamic query based on the provided fields in $data.
        $fields = [];
        if (isset($data['community_id'])) {
            $fields[] = "community_id = :community_id";
        }
        if (isset($data['user_id'])) {
            $fields[] = "user_id = :user_id";
        }
        if (isset($data['role'])) {
            $fields[] = "role = :role";
        }
        if (isset($data['joined_at'])) {
            $fields[] = "joined_at = :joined_at";
        }

        if (empty($fields)) {
            // Nothing to update.
            return false;
        }
        
        $sql = "UPDATE {$this->tableName} SET " . implode(', ', $fields) . " WHERE membership_id = :membership_id";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':membership_id', $membership_id);

            if (isset($data['community_id'])) {
                $stmt->bindParam(':community_id', $data['community_id']);
            }
            if (isset($data['user_id'])) {
                $stmt->bindParam(':user_id', $data['user_id']);
            }
            if (isset($data['role'])) {
                $stmt->bindParam(':role', $data['role']);
            }
            if (isset($data['joined_at'])) {
                $stmt->bindParam(':joined_at', $data['joined_at']);
            }

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error in updateCommunityMember: " . $e->getMessage());
            return false;
        }
    }
/**
 * Check if a user is a member of a specific community.
 */
public function isUserMemberOfCommunity($user_id, $community_id) {
    $sql = "SELECT COUNT(*) FROM {$this->tableName} WHERE user_id = :user_id AND community_id = :community_id";
    try {
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':community_id', $community_id);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log("Error in isUserMemberOfCommunity: " . $e->getMessage());
        return false;
    }
}
    /**
     * Delete a community member record by membership_id.
     */
    public function deleteCommunityMember($membership_id) {
        $sql = "DELETE FROM {$this->tableName} WHERE membership_id = :membership_id";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':membership_id', $membership_id);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error in deleteCommunityMember: " . $e->getMessage());
            return false;
        }
    }

    public function removeMemberByCommunityAndUser($community_id, $user_id) {
        $sql = "DELETE FROM {$this->tableName} WHERE community_id = :community_id AND user_id = :user_id";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':community_id', $community_id);
            $stmt->bindParam(':user_id', $user_id);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error in removeMemberByCommunityAndUser: " . $e->getMessage());
            return false;
        }
    }

    
    public function getLatestCommunityMemberID() {
        $query = "SELECT membership_id FROM community_member
                  WHERE membership_id LIKE :id_prefix 
                  ORDER BY membership_id DESC LIMIT 1";
        try {
            $stmt = $this->conn->prepare($query);
            $currentDate = new DateTime();
            $year = $currentDate->format('Y');
            $month = $currentDate->format('m');
            $idPrefix = "MEM{$year}{$month}%";
            
            $stmt->bindParam(':id_prefix', $idPrefix, PDO::PARAM_STR);
            $stmt->execute();
    
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $row['membership_id'] : null;
        } catch (PDOException $ex) {
            error_log("Database Error: " . $ex->getMessage());
            return null;
        }
    }

}
?>