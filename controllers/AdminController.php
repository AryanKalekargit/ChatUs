<?php
// controllers/AdminController.php
session_start();
header('Content-Type: application/json');

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/encryption/crypto.php';

// Auth Check specifically for Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized Access']);
    exit();
}

$db = (new Database())->getConnection();
$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'get_stats':
        $stats = [];
        $stats['users'] = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $stats['messages'] = $db->query("SELECT COUNT(*) FROM messages")->fetchColumn();
        $stats['groups'] = $db->query("SELECT COUNT(*) FROM groups")->fetchColumn();
        echo json_encode(['status' => 'success', 'data' => $stats]);
        break;

    case 'get_users':
        $stmt = $db->query("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $users]);
        break;

    case 'delete_user':
        $user_id = $_POST['user_id'] ?? null;
        if ($user_id && $user_id != $_SESSION['user_id']) { // Don't delete self
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt->execute([$user_id])) {
                echo json_encode(['status' => 'success']);
                break;
            }
        }
        echo json_encode(['status' => 'error']);
        break;

    case 'get_messages':
        $stmt = $db->query("SELECT m.id, m.sender_id, m.receiver_id, m.group_id, m.encrypted_message, m.image_path, m.created_at, u.username as sender_name 
                            FROM messages m 
                            JOIN users u ON m.sender_id = u.id
                            ORDER BY m.created_at DESC LIMIT 100");
        $messages = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $decrypted = Crypto::decrypt($row['encrypted_message']);
            $row['decrypted_message'] = $decrypted ? $decrypted : '[Decryption Failed]';
            $messages[] = $row;
        }
        echo json_encode(['status' => 'success', 'data' => $messages]);
        break;
        
    case 'delete_message':
        $message_id = $_POST['message_id'] ?? null;
        if ($message_id) {
            $stmt = $db->prepare("DELETE FROM messages WHERE id = ?");
            if ($stmt->execute([$message_id])) {
                echo json_encode(['status' => 'success']);
                break;
            }
        }
        echo json_encode(['status' => 'error']);
        break;

    case 'get_groups':
        $stmt = $db->query("SELECT g.id, g.name, g.created_at, u.username as admin_name 
                            FROM groups g 
                            JOIN users u ON g.admin_id = u.id 
                            ORDER BY g.created_at DESC");
        $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $groups]);
        break;
        
    case 'delete_group':
        $group_id = $_POST['group_id'] ?? null;
        if ($group_id) {
            $stmt = $db->prepare("DELETE FROM groups WHERE id = ?");
            if ($stmt->execute([$group_id])) {
                echo json_encode(['status' => 'success']);
                break;
            }
        }
        echo json_encode(['status' => 'error']);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        break;
}
?>
