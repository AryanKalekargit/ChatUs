<?php
// models/Group.php
class Group {
    private $conn;
    private $table_name = "groups";
    private $members_table = "group_members";

    public $id;
    public $name;
    public $admin_id;
    public $group_image;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " (name, admin_id, group_image) VALUES (:name, :admin_id, :group_image)";
        $stmt = $this->conn->prepare($query);

        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->group_image = $this->group_image ?: 'assets/images/default.png';
        
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":admin_id", $this->admin_id);
        $stmt->bindParam(":group_image", $this->group_image);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function addMember($user_id) {
        $query = "INSERT INTO " . $this->members_table . " (group_id, user_id) VALUES (:group_id, :user_id)";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":group_id", $this->id);
        $stmt->bindParam(":user_id", $user_id);

        try {
            return $stmt->execute();
        } catch(PDOException $e) {
            return false; // Already a member
        }
    }

    public function getUserGroups($user_id) {
        $query = "SELECT g.id, g.name, g.admin_id, g.group_image, g.created_at 
                  FROM " . $this->table_name . " g
                  JOIN " . $this->members_table . " gm ON g.id = gm.group_id
                  WHERE gm.user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        return $stmt;
    }

    public function getGroupInfo($group_id) {
        $query = "SELECT u.id, u.username, u.profile_image, 
                         CASE WHEN g.admin_id = u.id THEN 'owner' ELSE 'member' END as role,
                         g.name as group_name, g.group_image, u.last_active, gm.nickname
                  FROM (
                      SELECT user_id, group_id, nickname FROM group_members WHERE group_id = :group_id
                      UNION
                      SELECT admin_id as user_id, id as group_id, NULL as nickname FROM " . $this->table_name . " WHERE id = :group_id
                  ) gm
                  JOIN users u ON gm.user_id = u.id
                  JOIN " . $this->table_name . " g ON gm.group_id = g.id
                  WHERE gm.group_id = :group_id
                  GROUP BY u.id, g.name, g.group_image, g.admin_id, u.username, u.profile_image, u.last_active, gm.nickname
                  ORDER BY role DESC, u.username ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":group_id", $group_id);
        $stmt->execute();
        
        return $stmt;
    }
    public function updateName($group_id, $name) {
        $query = "UPDATE " . $this->table_name . " SET name = :name WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $name = htmlspecialchars(strip_tags($name));
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':id', $group_id);
        return $stmt->execute();
    }

    public function updateImage($group_id, $path) {
        $query = "UPDATE " . $this->table_name . " SET group_image = :path WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        if ($path === null) {
            $stmt->bindValue(':path', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':path', $path, PDO::PARAM_STR);
        }
        $stmt->bindParam(':id', $group_id);
        return $stmt->execute();
    }

    public function isOwner($group_id, $user_id) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE id = :group_id AND admin_id = :user_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':group_id', $group_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function setNickname($group_id, $user_id, $nickname) {
        $query = "UPDATE " . $this->members_table . " SET nickname = :nickname WHERE group_id = :group_id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        if (empty(trim($nickname))) {
            $stmt->bindValue(':nickname', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':nickname', htmlspecialchars(strip_tags($nickname)), PDO::PARAM_STR);
        }
        $stmt->bindParam(':group_id', $group_id);
        $stmt->bindParam(':user_id', $user_id);
        return $stmt->execute();
    }

    public function removeMember($group_id, $user_id) {
        $query = "DELETE FROM " . $this->members_table . " WHERE group_id = :group_id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':group_id', $group_id);
        $stmt->bindParam(':user_id', $user_id);
        return $stmt->execute();
    }

    public function getGroupsInCommon($user_id1, $user_id2) {
        $query = "SELECT g.id, g.name, g.group_image 
                  FROM " . $this->table_name . " g
                  JOIN " . $this->members_table . " gm1 ON g.id = gm1.group_id
                  JOIN " . $this->members_table . " gm2 ON g.id = gm2.group_id
                  WHERE gm1.user_id = :u1 AND gm2.user_id = :u2";
        $stmt = $this->conn->prepare($query);
        $stmt->execute(['u1' => $user_id1, 'u2' => $user_id2]);
        return $stmt;
    }
}
?>
