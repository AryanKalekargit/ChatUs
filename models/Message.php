<?php
// models/Message.php
require_once dirname(__DIR__) . '/encryption/crypto.php';

class Message {
    private $conn;
    private $table_name = "messages";

    public $id;
    public $sender_id;
    public $receiver_id;
    public $group_id;
    public $encrypted_message;
    public $image_path;
    public $audio_path;
    public $once_view;
    public $viewed;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Process encryption before creation
    public function setAndEncryptMessage($raw_message) {
        $this->encrypted_message = Crypto::encrypt($raw_message);
    }

    // Create message (Direct message)
    public function createDirectMessage() {
        $query = "INSERT INTO " . $this->table_name . "
                  (sender_id, receiver_id, encrypted_message, image_path, audio_path, once_view)
                  VALUES (:sender_id, :receiver_id, :encrypted_message, :image_path, :audio_path, :once_view)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":sender_id", $this->sender_id);
        $stmt->bindParam(":receiver_id", $this->receiver_id);
        $stmt->bindParam(":encrypted_message", $this->encrypted_message);
        
        // Handle Nullable file paths
        if ($this->image_path === null) {
            $stmt->bindValue(":image_path", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(":image_path", $this->image_path);
        }
        
        if ($this->audio_path === null) {
            $stmt->bindValue(":audio_path", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(":audio_path", $this->audio_path);
        }

        $stmt->bindParam(":once_view", $this->once_view, PDO::PARAM_INT);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Create message (Group message)
    public function createGroupMessage() {
        $query = "INSERT INTO " . $this->table_name . "
                  (sender_id, group_id, encrypted_message, image_path, audio_path, once_view)
                  VALUES (:sender_id, :group_id, :encrypted_message, :image_path, :audio_path, :once_view)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":sender_id", $this->sender_id);
        $stmt->bindParam(":group_id", $this->group_id);
        $stmt->bindParam(":encrypted_message", $this->encrypted_message);
        
        // Handle Nullable file paths
        if ($this->image_path === null) {
            $stmt->bindValue(":image_path", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(":image_path", $this->image_path);
        }
        
        if ($this->audio_path === null) {
            $stmt->bindValue(":audio_path", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(":audio_path", $this->audio_path);
        }

        $stmt->bindParam(":once_view", $this->once_view, PDO::PARAM_INT);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Get chat history between two users Let's get last 200
    public function getConversation($user1, $user2) {
        $query = "SELECT * FROM (
                    SELECT m.id, m.sender_id, m.receiver_id, m.encrypted_message, 
                           m.image_path, m.audio_path, m.once_view, m.viewed, m.status, m.created_at,
                           u.username as sender_name
                    FROM " . $this->table_name . " m
                    LEFT JOIN users u ON m.sender_id = u.id
                    WHERE (m.sender_id = :u1 AND m.receiver_id = :u1_msg2) 
                       OR (m.sender_id = :u2 AND m.receiver_id = :u2_msg1)
                    ORDER BY m.created_at DESC
                    LIMIT 200
                  ) as sub
                  ORDER BY created_at ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":u1", $user1);
        $stmt->bindParam(":u1_msg2", $user2);
        $stmt->bindParam(":u2", $user2);
        $stmt->bindParam(":u2_msg1", $user1);
        
        $stmt->execute();
        return $stmt;
    }

    // Get Group Conversation
    public function getGroupConversation($group_id) {
        $query = "SELECT * FROM (
                    SELECT m.id, m.sender_id, m.group_id, m.encrypted_message, 
                           m.image_path, m.audio_path, m.once_view, m.viewed, m.status, m.created_at,
                           u.username as sender_name, u.profile_image
                    FROM " . $this->table_name . " m
                    JOIN users u ON m.sender_id = u.id
                    LEFT JOIN group_members gm ON m.group_id = gm.group_id AND m.sender_id = gm.user_id
                    WHERE m.group_id = :group_id
                    ORDER BY m.created_at DESC
                    LIMIT 200
                  ) as sub
                  ORDER BY created_at ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":group_id", $group_id);
        $stmt->execute();
        return $stmt;
    }

    // Mark message as viewed (used for once_view images)
    public function markAsViewed($message_id) {
        $query = "UPDATE " . $this->table_name . " SET viewed = 1, status = 'viewed' WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $message_id);
        return $stmt->execute();
    }

    // Mark all direct messages from a sender as viewed
    public function markDirectMessagesViewed($sender_id, $receiver_id) {
        $query = "UPDATE " . $this->table_name . " SET status = 'viewed' WHERE sender_id = :sender_id AND receiver_id = :receiver_id AND status != 'viewed'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':sender_id', $sender_id);
        $stmt->bindParam(':receiver_id', $receiver_id);
        return $stmt->execute();
    }

    // Get recent conversations for sidebar
    public function getRecentConversations($user_id) {
        $query = "
            SELECT
                c.type,
                c.target_id,
                m.id AS message_id,
                m.encrypted_message,
                m.image_path,
                m.audio_path,
                m.created_at,
                m.sender_id,
                m.status,
                u.username AS user_name,
                u.profile_image AS user_image,
                u.about AS user_about,
                u.last_active,
                u.created_at AS user_created_at,
                g.name AS group_name,
                g.group_image,
                g.created_at AS group_created_at,
                (SELECT COUNT(*) FROM messages WHERE sender_id = c.target_id AND receiver_id = :u1 AND status != 'viewed') AS unread_count,
                CASE 
                    WHEN c.type = 'group' THEN (
                        SELECT COUNT(*) 
                        FROM group_members gm 
                        JOIN users u_on ON gm.user_id = u_on.id 
                        WHERE gm.group_id = c.target_id 
                        AND u_on.last_active > (NOW() - INTERVAL '5 minutes')
                    )
                    ELSE 0
                END AS online_count
            FROM (
                SELECT 
                    'user' AS type,
                    CASE WHEN sender_id = :u2 THEN receiver_id ELSE sender_id END AS target_id,
                    MAX(id) AS last_message_id
                FROM messages
                WHERE (sender_id = :u3 AND receiver_id IS NOT NULL) 
                   OR (receiver_id = :u4 AND sender_id IS NOT NULL)
                GROUP BY target_id
                
                UNION
                
                SELECT
                    'group' AS type,
                    g_all.id AS target_id,
                    (SELECT MAX(id) FROM messages WHERE group_id = g_all.id) AS last_message_id
                FROM (
                    SELECT group_id as id FROM group_members WHERE user_id = :u5
                    UNION SELECT id FROM groups WHERE admin_id = :u6
                ) g_all
            ) c
            LEFT JOIN messages m ON m.id = c.last_message_id
            LEFT JOIN users u ON (c.type = 'user' AND c.target_id = u.id)
            LEFT JOIN groups g ON (c.type = 'group' AND c.target_id = g.id)
            ORDER BY COALESCE(m.created_at, '1970-01-01') DESC
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':u1', $user_id);
        $stmt->bindParam(':u2', $user_id);
        $stmt->bindParam(':u3', $user_id);
        $stmt->bindParam(':u4', $user_id);
        $stmt->bindParam(':u5', $user_id);
        $stmt->bindParam(':u6', $user_id);
        $stmt->execute();
        return $stmt;
    }

    // Get Shared Media (Images only)
    public function getSharedMedia($user1 = null, $user2 = null, $group_id = null) {
        if ($group_id) {
            $query = "SELECT id, image_path, created_at, sender_id 
                      FROM " . $this->table_name . " 
                      WHERE group_id = :group_id AND image_path IS NOT NULL AND once_view = 0
                      ORDER BY created_at DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':group_id', $group_id);
        } else {
            $query = "SELECT id, image_path, created_at, sender_id 
                      FROM " . $this->table_name . " 
                      WHERE ((sender_id = :u1 AND receiver_id = :u2) OR (sender_id = :u2 AND receiver_id = :u1))
                        AND image_path IS NOT NULL AND once_view = 0
                      ORDER BY created_at DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':u1', $user1);
            $stmt->bindParam(':u2', $user2);
        }
        
        $stmt->execute();
        return $stmt;
    }
}
?>
