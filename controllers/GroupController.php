<?php
// controllers/GroupController.php
require_once dirname(__DIR__) . '/config/session.php';
header('Content-Type: application/json');

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/models/Group.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$db = (new Database())->getConnection();
$groupModel = new Group($db);

$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'create_group':
        $name = $_POST['name'] ?? '';
        $members = $_POST['members'] ?? []; // Array of user IDs
        
        if (empty($name)) {
            echo json_encode(['status' => 'error', 'message' => 'Group name required']);
            exit();
        }

        $groupModel->name = $name;
        $groupModel->admin_id = $_SESSION['user_id'];
        
        if ($groupModel->create()) {
            // Add creator
            $groupModel->addMember($_SESSION['user_id']);
            
            // Add selected members
            if (is_array($members)) {
                foreach ($members as $user_id) {
                    $groupModel->addMember($user_id);
                }
            }
            
            echo json_encode(['status' => 'success', 'message' => 'Group created', 'group_id' => $groupModel->id]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to create group']);
        }
        break;

    case 'get_groups':
        $stmt = $groupModel->getUserGroups($_SESSION['user_id']);
        $groups = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $groups[] = $row;
        }
        echo json_encode(['status' => 'success', 'data' => $groups]);
        break;

    case 'get_group_info':
        $group_id = $_REQUEST['group_id'] ?? null;
        if (!$group_id) {
            echo json_encode(['status' => 'error', 'message' => 'Group ID required']);
            exit;
        }
        
        // Fetch Group Metadata separately for reliability
        $stmtMeta = $db->prepare("SELECT name, group_image FROM groups WHERE id = :id");
        $stmtMeta->execute(['id' => $group_id]);
        $groupMeta = $stmtMeta->fetch(PDO::FETCH_ASSOC);
        
        $group_name = $groupMeta['name'] ?? 'Unknown Group';
        $group_image = $groupMeta['group_image'] ?? 'assets/images/default.png';

        $stmt = $groupModel->getGroupInfo($group_id);
        $members = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $members[] = $row;
        }
        echo json_encode(['status' => 'success', 'data' => [
            'members' => $members,
            'group_name' => $group_name,
            'group_image' => $group_image
        ]]);
        break;

    case 'update_group_name':
        $group_id = $_POST['group_id'] ?? null;
        $name = $_POST['name'] ?? '';
        
        if (!$group_id || empty($name)) {
            echo json_encode(['status' => 'error', 'message' => 'Group ID and Name are required']);
            exit;
        }
        
        if ($groupModel->updateName($group_id, $name)) {
            echo json_encode(['status' => 'success', 'message' => 'Group name updated']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update name']);
        }
        break;

    case 'update_group_image':
        $group_id = $_POST['group_id'] ?? null;
        if (!$group_id || !isset($_FILES['group_image'])) {
            echo json_encode(['status' => 'error', 'message' => 'Group ID and Image are required']);
            exit;
        }

        $file = $_FILES['group_image'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        $maxSize = 2097152; // 2MB

        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['status' => 'error', 'message' => 'Upload error: ' . $file['error']]);
            exit;
        }

        if ($file['size'] > $maxSize) {
            echo json_encode(['status' => 'error', 'message' => 'File too large (Max 2MB)']);
            exit;
        }

        if (!in_array($file['type'], $allowedTypes)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Only JPG, PNG allowed.']);
            exit;
        }

        $fileData = file_get_contents($file['tmp_name']);
        if ($fileData === false) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to read file']);
            exit;
        }

        $base64 = base64_encode($fileData);
        $db_path = "data:" . $file['type'] . ";base64," . $base64;

        if ($groupModel->updateImage($group_id, $db_path)) {
            echo json_encode(['status' => 'success', 'message' => 'Image updated', 'path' => $db_path]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update Database record']);
        }
        break;

    case 'remove_group_image':
        $group_id = $_POST['group_id'] ?? null;
        if (!$group_id) {
            echo json_encode(['status' => 'error', 'message' => 'Group ID required']);
            exit;
        }
        if ($groupModel->updateImage($group_id, null)) {
            echo json_encode(['status' => 'success', 'message' => 'Group image removed']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to remove group image']);
        }
        break;

    case 'set_nickname':
        $group_id = $_POST['group_id'] ?? null;
        $user_id = $_POST['user_id'] ?? null;
        $nickname = $_POST['nickname'] ?? '';

        if (!$group_id || !$user_id) {
            echo json_encode(['status' => 'error', 'message' => 'Group and User required']);
            exit;
        }

        if (!$groupModel->isOwner($group_id, $_SESSION['user_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'Only the Group Owner can set nicknames']);
            exit;
        }

        if ($groupModel->setNickname($group_id, $user_id, $nickname)) {
            echo json_encode(['status' => 'success', 'message' => 'Nickname updated']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update nickname']);
        }
        break;

    case 'add_member':
        $group_id = $_POST['group_id'] ?? null;
        $user_input = $_POST['user_id'] ?? null;

        if (!$group_id || !$user_input) {
            echo json_encode(['status' => 'error', 'message' => 'Group and Username/ID required']);
            exit;
        }

        if (!$groupModel->isOwner($group_id, $_SESSION['user_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'Only the Group Owner can add members']);
            exit;
        }

        // Resolve user_id from string input
        if (is_numeric($user_input)) {
            $stmtCheck = $db->prepare("SELECT id, username FROM users WHERE id = ?");
            $stmtCheck->execute([$user_input]);
        } else {
            $stmtCheck = $db->prepare("SELECT id, username FROM users WHERE username = ?");
            $stmtCheck->execute([$user_input]);
        }
        $targetUser = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$targetUser) {
            echo json_encode(['status' => 'error', 'message' => 'User not found across global records']);
            exit;
        }
        $resolved_id = $targetUser['id'];
        $resolved_username = $targetUser['username'];

        $groupModel->id = $group_id; 
        if ($groupModel->addMember($resolved_id)) {
            echo json_encode(['status' => 'success', 'message' => "@{$resolved_username} was verified and added successfully!"]);
        } else {
            echo json_encode(['status' => 'error', 'message' => "User @{$resolved_username} is already actively participating in this group"]);
        }
        break;

    case 'remove_member':
        $group_id = $_POST['group_id'] ?? null;
        $user_id = $_POST['user_id'] ?? null;

        if (!$group_id || !$user_id) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid parameters for removal']);
            exit;
        }

        if (!$groupModel->isOwner($group_id, $_SESSION['user_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'Only the Group Owner can execute ejections']);
            exit;
        }
        
        $stmtCheck = $db->prepare("SELECT username FROM users WHERE id = ?");
        $stmtCheck->execute([$user_id]);
        $targetUser = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        $removed_uname = $targetUser ? $targetUser['username'] : "Unknown";

        if ($groupModel->removeMember($group_id, $user_id)) {
            echo json_encode(['status' => 'success', 'message' => "@{$removed_uname} was gracefully removed from the group"]);
        } else {
            echo json_encode(['status' => 'error', 'message' => "Failed to remove @{$removed_uname} from the database cluster"]);
        }
        break;

    case 'get_groups_in_common':
        $other_user_id = $_GET['other_user_id'] ?? null;
        if (!$other_user_id) {
            echo json_encode(['status' => 'error', 'message' => 'Other user ID required']);
            exit;
        }
        $stmt = $groupModel->getGroupsInCommon($_SESSION['user_id'], $other_user_id);
        $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $groups]);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        break;
}
?>
