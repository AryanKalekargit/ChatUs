<?php
// models/User.php

class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $username;
    public $email;
    public $password_hash;
    public $profile_image;
    public $theme_preference;
    public $role;
    public $about;
    public $last_active;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create a new user
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (username, email, password_hash)
                  VALUES (:username, :email, :password_hash)";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->password_hash = htmlspecialchars(strip_tags($this->password_hash));

        // Bind data
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password_hash", $this->password_hash);

        try {
            if($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                return true;
            }
        } catch (PDOException $e) {
            // Check for duplicate username
            if ($e->getCode() == 23000) {
                return false;
            }
        }
        return false;
    }

    // Check if username exists
    public function usernameExists() {
        $query = "SELECT id, username, email, password_hash, role, profile_image, theme_preference, about, last_active 
                  FROM " . $this->table_name . " 
                  WHERE username = ? LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $this->username = htmlspecialchars(strip_tags($this->username));
        $stmt->bindParam(1, $this->username);
        $stmt->execute();

        $num = $stmt->rowCount();

        if($num > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->email = $row['email'];
            $this->password_hash = $row['password_hash'];
            $this->role = $row['role'];
            $this->profile_image = $row['profile_image'];
            $this->theme_preference = $row['theme_preference'];
            $this->about = $row['about'];
            $this->last_active = $row['last_active'];
            return true;
        }
        return false;
    }

    // Update Theme Preference
    public function updateTheme($userId, $theme) {
        $query = "UPDATE " . $this->table_name . " SET theme_preference = :theme WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':theme', $theme);
        $stmt->bindParam(':id', $userId);
        return $stmt->execute();
    }
    
    // Get user by ID
    public function getUserById($id) {
        $query = "SELECT id, username, email, profile_image, theme_preference, role, about, last_active, created_at 
                  FROM " . $this->table_name . " 
                  WHERE id = ? LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Check if email exists
    public function emailExists($email) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // Get all users (except current)
    public function getAllUsers($current_user_id) {
        $query = "SELECT id, username, profile_image, about, last_active, created_at 
                  FROM " . $this->table_name . " 
                  WHERE id != :id ORDER BY username ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $current_user_id);
        $stmt->execute();

        return $stmt;
    }

    // Search users by similarity (at least 60% match)
    public function searchUsers($current_user_id, $term) {
        // term% for prefix matching
        $prefix = $term . '%';
        $query = "SELECT u.id, u.username, u.profile_image, u.about, u.last_active, u.created_at,
                         EXISTS(SELECT 1 FROM messages WHERE (sender_id = u.id AND receiver_id = :id1) OR (sender_id = :id2 AND receiver_id = u.id)) as is_friend,
                         similarity(u.username, :term) as sml
                  FROM " . $this->table_name . " u
                  WHERE u.id != :id3
                    AND (
                      (EXISTS(SELECT 1 FROM messages WHERE (sender_id = u.id AND receiver_id = :id4) OR (sender_id = :id5 AND receiver_id = u.id)) AND u.username ILIKE :prefix)
                      OR
                      (similarity(u.username, :term) >= 0.9)
                    )
                  ORDER BY is_friend DESC, sml DESC LIMIT 20";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id1', $current_user_id);
        $stmt->bindParam(':id2', $current_user_id);
        $stmt->bindParam(':id3', $current_user_id);
        $stmt->bindParam(':id4', $current_user_id);
        $stmt->bindParam(':id5', $current_user_id);
        $stmt->bindParam(':term', $term);
        $stmt->bindParam(':prefix', $prefix);
        $stmt->execute();

        return $stmt;
    }

    // Ping Last Active
    public function updateLastActive($current_user_id) {
        $query = "UPDATE " . $this->table_name . " SET last_active = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $current_user_id);
        return $stmt->execute();
    }

    // Update Profile (Avatar & About)
    public function updateProfile($current_user_id, $about = null, $image_path = null) {
        $query = "UPDATE " . $this->table_name . " SET ";
        $updates = [];
        if ($about !== null) {
            $updates[] = "about = :about";
        }
        if ($image_path !== null) {
            $updates[] = "profile_image = :image_path";
        }
        
        if (empty($updates)) return false;

        $query .= implode(", ", $updates) . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':id', $current_user_id);
        if ($about !== null) {
            $stmt->bindParam(':about', $about);
        }
        if ($image_path !== null) {
            // If image_path is empty string, we set it to NULL in the database
            $val = ($image_path === "") ? null : $image_path;
            $stmt->bindValue(':image_path', $val, is_null($val) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        }
        
        return $stmt->execute();
    }

    // Set offline instantly (2 mins in the past to trigger isOnline=false in UI)
    public function setOffline($current_user_id) {
        // We use CURRENT_TIMESTAMP - INTERVAL '3 minutes' for PostgreSQL
        $query = "UPDATE " . $this->table_name . " SET last_active = CURRENT_TIMESTAMP - INTERVAL '3 minutes', is_typing_to = NULL WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $current_user_id);
        return $stmt->execute();
    }
}
