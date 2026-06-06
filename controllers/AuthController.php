<?php
// controllers/AuthController.php
session_start();
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/models/User.php';

class AuthController {
    private $db;
    private $user;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->user = new User($this->db);
    }

    public function register($username, $email, $password) {
        // Set properties
        $this->user->username = htmlspecialchars(strip_tags($username));
        $this->user->email = htmlspecialchars(strip_tags($email));

        // Proactive Uniqueness Checks
        if ($this->user->usernameExists()) {
            return ['status' => 'error', 'message' => 'This username is already taken.'];
        }

        if ($this->user->emailExists($email)) {
            return ['status' => 'error', 'message' => 'An account with this email already exists.'];
        }

        $this->user->password_hash = password_hash($password, PASSWORD_BCRYPT);

        if ($this->user->create()) {
            return ['status' => 'success', 'message' => 'Registration successful! Welcome to ChatUs.'];
        } else {
            return ['status' => 'error', 'message' => 'Something went wrong. Please try again.'];
        }
    }

    public function login($username, $password) {
        // Implement Basic Rate Limiting Check Here
        
        $this->user->username = $username;

        if ($this->user->usernameExists()) {
            if (password_verify($password, $this->user->password_hash)) {
                // Regenerate session id to prevent session fixation attacks
                session_regenerate_id(true);

                $_SESSION['user_id'] = $this->user->id;
                $_SESSION['username'] = $this->user->username;
                $_SESSION['role'] = $this->user->role;
                $_SESSION['theme'] = $this->user->theme_preference;
                
                return ['status' => 'success', 'message' => 'Login successful.'];
            }
        }
        
        // Basic Log Failed Attempt
        $this->logFailedAttempt($username);
        return ['status' => 'error', 'message' => 'Invalid username or password.'];
    }

    public function logout() {
        if(isset($_SESSION['user_id'])) {
            $this->user->setOffline($_SESSION['user_id']);
        }
        session_unset();
        session_destroy();
    }

    private function logFailedAttempt($username) {
        // Logic for rate limiting attempts
        // Fetch user id from username if exists
        $query = "SELECT id FROM users WHERE username = ? LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$username]);
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $userId = $row['id'];
            
            // Upsert into login_attempts (Updated for PostgreSQL)
            $attemptQuery = "INSERT INTO login_attempts (user_id, attempts) VALUES (?, 1) 
                             ON CONFLICT (user_id) DO UPDATE 
                             SET attempts = login_attempts.attempts + 1, last_attempt = CURRENT_TIMESTAMP";
            $attemptStmt = $this->db->prepare($attemptQuery);
            $attemptStmt->execute([$userId]);
        }
    }
}
?>
