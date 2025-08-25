<?php
require_once(__DIR__ . '/config.php');

class UserDA
{
    private $conn;
    private $tableName = "user";

    public function __construct()
    {
        $this->conn = $this->getDatabaseConnection();
    }

    private function getDatabaseConnection()
    {
        return getDatabaseConnection();
    }

    // Create: Add a new user to the table
    public function addUser($user)
    {
        $query = "INSERT INTO {$this->tableName} 
                  (user_id, email, username, is_anonymous) 
                  VALUES (:user_id, :email, :username, :is_anonymous)";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user['user_id'], PDO::PARAM_STR);
            $stmt->bindParam(':email', $user['email'], PDO::PARAM_STR);
            $stmt->bindParam(':username', $user['username'], PDO::PARAM_STR);
            $stmt->bindParam(':is_anonymous', $user['is_anonymous'], PDO::PARAM_INT);

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

    public function getUserById($userId)
    {
        $query = "SELECT * FROM {$this->tableName} WHERE user_id = :user_id";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result !== false ? $result : null; // Return null if no user is found
        } catch (PDOException $ex) {
            error_log("Database Error: " . $ex->getMessage());
            return null;
        }
    }
    public function getUserByEmail($email)
    {
        $query = "SELECT * FROM {$this->tableName} WHERE email = :email";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result !== false ? $result : null; // Return null if no user is found
        } catch (PDOException $ex) {
            error_log("Database Error: " . $ex->getMessage());
            return null;
        }
    }

    // Read: Get all users (with optional filtering by is_anonymous)
    public function getAllUsers($isAnonymous = null)
    {
        $query = "SELECT * FROM {$this->tableName}";
        $params = [];

        if (!is_null($isAnonymous)) {
            $query .= " WHERE is_anonymous = :is_anonymous";
            $params[':is_anonymous'] = $isAnonymous;
        }

        try {
            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $ex) {
            error_log("Database Error: " . $ex->getMessage());
            return [];
        }
    }

    // Update: Update an existing user
    public function updateUser($user)
    {
        $query = "UPDATE {$this->tableName} 
                  SET email = :email, 
                      username = :username, 
                      is_anonymous = :is_anonymous 
                  WHERE user_id = :user_id";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user['user_id'], PDO::PARAM_STR);
            $stmt->bindParam(':email', $user['email'], PDO::PARAM_STR);
            $stmt->bindParam(':username', $user['username'], PDO::PARAM_STR);
            $stmt->bindParam(':is_anonymous', $user['is_anonymous'], PDO::PARAM_INT);

            return $stmt->execute(); // Returns true if successful
        } catch (PDOException $ex) {
            error_log("Database Error: " . $ex->getMessage());
            return false;
        }
    }

    // Delete: Delete a user by user_id
    public function deleteUser($userId)
    {
        $query = "DELETE FROM {$this->tableName} WHERE user_id = :user_id";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
            $stmt->execute();
            return true;
        } catch (PDOException $ex) {
            error_log("Database Error: " . $ex->getMessage());
            return false;
        }
    }

    // Utility: Check if a user exists by user_id
    public function userExistsById($userId)
    {
        try {
            $query = "SELECT COUNT(*) FROM {$this->tableName} WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $ex) {
            error_log("Error checking if user exists: " . $ex->getMessage());
            return false;
        }
    }

    // Utility: Check if a user exists by email
    public function userExistsByEmail($email)
    {
        try {
            $query = "SELECT COUNT(*) FROM {$this->tableName} WHERE email = :email";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $ex) {
            error_log("Error checking if user exists: " . $ex->getMessage());
            return false;
        }
    }

    // Utility: Generate the next user_id based on the current date
    public function getLatestUserId()
    {
        $query = "SELECT user_id FROM {$this->tableName} 
                  WHERE user_id LIKE :id_prefix 
                  ORDER BY user_id DESC LIMIT 1";
        try {
            $stmt = $this->conn->prepare($query);
            $currentDate = new DateTime();
            $year = $currentDate->format('Y');
            $month = $currentDate->format('m');
            $idPrefix = "USR{$year}{$month}%";
            
            $stmt->bindParam(':id_prefix', $idPrefix, PDO::PARAM_STR);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return $row['user_id'];
            } else {
                // If no user exists for this prefix, return the first ID for the current year/month
                return "USR{$year}{$month}0001";
            }
        } catch (PDOException $ex) {
            error_log("Database Error: " . $ex->getMessage());
            return null;
        }
    }

}