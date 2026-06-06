<?php
// controllers/ProfileController.php
require_once dirname(__DIR__) . '/config/session.php';
header('Content-Type: application/json');

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/models/User.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$db = (new Database())->getConnection();
$userModel = new User($db);

$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'ping':
        $typing_to = isset($_POST['typing_to']) && is_numeric($_POST['typing_to']) ? $_POST['typing_to'] : null;
        
        $query = "UPDATE users SET last_active = CURRENT_TIMESTAMP, is_typing_to = :typing_to WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindValue(':typing_to', $typing_to, is_null($typing_to) ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':id', $_SESSION['user_id']);
        $stmt->execute();
        
        // Fetch typing status of other user if requested
        $active_user_id = $_POST['active_user_id'] ?? null;
        $active_status = null;
        if ($active_user_id) {
            $query = "SELECT last_active, is_typing_to FROM users WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute([':id' => $active_user_id]);
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $active_status = $row;
            }
        }

        echo json_encode(['status' => 'success', 'data' => $active_status]);
        break;

    case 'update_profile':
        $about = $_POST['about'] ?? null;
        $image_path = null;
        
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $fileObj = $_FILES['profile_image'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            $dir = dirname(__DIR__) . "/uploads/profiles/";
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            
            if ($fileObj['size'] <= 5242880 && in_array($fileObj['type'], $allowedTypes)) { // 5MB
                $ext = pathinfo($fileObj['name'], PATHINFO_EXTENSION);
                $filename = 'user_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
                if (move_uploaded_file($fileObj['tmp_name'], $dir . $filename)) {
                    $image_path = "uploads/profiles/" . $filename;
                }
            }
        }
        
        if ($userModel->updateProfile($_SESSION['user_id'], $about, $image_path)) {
            echo json_encode(['status' => 'success', 'message' => 'Profile updated successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update profile or no changes made']);
        }
        break;

    case 'update_theme':
        $theme = $_POST['theme'] ?? 'dark';
        if ($userModel->updateTheme($_SESSION['user_id'], $theme)) {
            echo json_encode(['status' => 'success', 'message' => 'Theme updated']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update theme']);
        }
        break;

    case 'remove_pfp':
        if ($userModel->updateProfile($_SESSION['user_id'], null, "")) {
            echo json_encode(['status' => 'success', 'message' => 'Profile picture removed']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to remove profile picture']);
        }
        break;
        
    case 'get_my_profile':
        $myUser = $userModel->getUserById($_SESSION['user_id']);
        echo json_encode(['status' => 'success', 'data' => $myUser]);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        break;
}
?>
