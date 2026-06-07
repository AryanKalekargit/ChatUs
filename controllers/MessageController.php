<?php
// controllers/MessageController.php
require_once dirname(__DIR__) . '/config/session.php';
header('Content-Type: application/json');

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/models/Message.php';
require_once dirname(__DIR__) . '/models/User.php';

// Auth Check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$db = (new Database())->getConnection();
$messageModel = new Message($db);
$userModel = new User($db);

$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'search_users':
        $term = $_GET['term'] ?? '';
        $stmt = $userModel->searchUsers($_SESSION['user_id'], $term);
        $users = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $users[] = $row;
        }
        echo json_encode(['status' => 'success', 'data' => $users]);
        break;

    case 'get_recent_conversations':
        $stmt = $messageModel->getRecentConversations($_SESSION['user_id']);
        $conversations = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($row['encrypted_message'])) {
                $decrypted = Crypto::decrypt($row['encrypted_message']);
                // Create a snippet
                $snippet = mb_substr($decrypted !== false ? $decrypted : '[Decryption Failed]', 0, 30);
                if ($decrypted !== false && mb_strlen($decrypted) > 30) $snippet .= '...';
                $row['last_message'] = $snippet;
            } elseif ($row['image_path']) {
                $row['last_message'] = '📸 Image';
            } elseif ($row['audio_path']) {
                $row['last_message'] = '🎤 Voice Message';
            } else {
                $row['last_message'] = '';
            }
            unset($row['encrypted_message']);
            $conversations[] = $row;
        }
        echo json_encode(['status' => 'success', 'data' => $conversations]);
        break;

    case 'get_users':
        $stmt = $userModel->getAllUsers($_SESSION['user_id']);
        $users = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $users[] = $row;
        }
        echo json_encode(['status' => 'success', 'data' => $users]);
        break;

    case 'send_message':
        $receiver_id = $_POST['receiver_id'] ?? null;
        $raw_message = $_POST['message'] ?? '';
        $once_view = isset($_POST['once_view']) && $_POST['once_view'] === 'true' ? 1 : 0;
        
        // --- Handle File Uploads ---
        $image_path = null;
        $audio_path = null;
        
        function handleUpload($fileObj, $type) {
            $allowedTypes = $type === 'image'
                ? ['image/jpeg', 'image/png', 'image/jpg']
                : ['audio/webm', 'audio/mp3', 'audio/wav', 'audio/ogg', 'audio/mpeg'];

            $maxSize = $type === 'image' ? 2097152 : 5242880; // 2MB images, 5MB audio

            if ($fileObj['error'] === UPLOAD_ERR_OK && $fileObj['size'] <= $maxSize) {
                $mimeType = $fileObj['type'];
                // Fallback MIME for audio blobs lacking type
                if ($type === 'audio' && empty($mimeType)) $mimeType = 'audio/webm';

                $fileData = file_get_contents($fileObj['tmp_name']);
                if ($fileData !== false) {
                    $base64 = base64_encode($fileData);
                    return "data:{$mimeType};base64,{$base64}";
                }
            }
            return null;
        }

        if (isset($_FILES['image'])) {
            $image_path = handleUpload($_FILES['image'], 'image');
        }
        if (isset($_FILES['audio'])) {
            $audio_path = handleUpload($_FILES['audio'], 'audio');
        }

        $group_id = $_POST['group_id'] ?? null;

        if ((!$receiver_id && !$group_id) && empty($raw_message) && !$image_path && !$audio_path) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
            exit();
        }

        $messageModel->sender_id = $_SESSION['user_id'];
        $messageModel->receiver_id = $receiver_id;
        $messageModel->group_id = $group_id;
        $messageModel->setAndEncryptMessage($raw_message);
        $messageModel->image_path = $image_path;
        $messageModel->audio_path = $audio_path;
        $messageModel->once_view = $once_view;
        
        $success = false;
        if ($group_id) {
            $success = $messageModel->createGroupMessage();
        } else {
            $success = $messageModel->createDirectMessage();
        }
        
        if ($success) {
            echo json_encode(['status' => 'success', 'message' => 'Message sent']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to send']);
        }
        break;

    case 'fetch_messages':
        $receiver_id = $_GET['receiver_id'] ?? null;
        $group_id = $_GET['group_id'] ?? null;
        
        if (!$receiver_id && !$group_id) {
            echo json_encode(['status' => 'error', 'message' => 'No target specified']);
            exit();
        }

        if ($group_id) {
            error_log("DEBUG: Fetching messages for group_id: " . $group_id);
            $stmt = $messageModel->getGroupConversation($group_id);
        } else {
            $messageModel->markDirectMessagesViewed($receiver_id, $_SESSION['user_id']);
            $stmt = $messageModel->getConversation($_SESSION['user_id'], $receiver_id);
        }
        $messages = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (empty($row['encrypted_message'])) {
                $row['message'] = '';
            } else {
                $decrypted = Crypto::decrypt($row['encrypted_message']);
                $row['message'] = $decrypted !== false ? $decrypted : '[Decryption Failed]';
            }
            unset($row['encrypted_message']); // Do not send encrypted string to JS
            $messages[] = $row;
        }
        error_log("DEBUG: Found " . count($messages) . " messages for target.");
        
        echo json_encode(['status' => 'success', 'data' => $messages]);
        break;

    case 'mark_viewed':
        $message_id = $_POST['message_id'] ?? null;
        if ($message_id) {
            if ($messageModel->markAsViewed($message_id)) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error']);
            }
        }
        break;

    case 'get_shared_media':
        $receiver_id = $_GET['receiver_id'] ?? null;
        $group_id = $_GET['group_id'] ?? null;
        
        $stmt = $messageModel->getSharedMedia($_SESSION['user_id'], $receiver_id, $group_id);
        $media = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $media[] = $row;
        }
        echo json_encode(['status' => 'success', 'data' => $media]);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        break;
}
?>
