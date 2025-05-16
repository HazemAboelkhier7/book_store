<?php
class Auth {
    private static $instance = null;
    private $db;
    private $user = null;
    
    private function __construct() {
        $this->db = Database::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function login($username, $password) {
        try {
            $stmt = $this->db->getConnection()->prepare("SELECT * FROM admins WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                if (password_verify($password, $user['password'])) {
                    // Set session
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['admin_username'] = $user['username'];
                    $_SESSION['admin_name'] = $user['name'];
                    $_SESSION['last_activity'] = time();
                    
                    // Update last login
                    $stmt = $this->db->getConnection()->prepare("UPDATE admins SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
                    $stmt->bind_param("i", $user['id']);
                    $stmt->execute();
                    
                    $this->user = $user;
                    return true;
                }
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Login Error: " . $e->getMessage());
            return false;
        }
    }
    
    public function logout() {
        $_SESSION = array();
        session_destroy();
        $this->user = null;
    }
    
    public function isLoggedIn() {
        if (!isset($_SESSION['admin_id'])) {
            return false;
        }
        
        // Check session timeout (30 minutes)
        if (time() - $_SESSION['last_activity'] > 1800) {
            $this->logout();
            return false;
        }
        
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: index.php');
            exit;
        }
    }
    
    public function getCurrentUser() {
        if ($this->user === null && $this->isLoggedIn()) {
            $stmt = $this->db->getConnection()->prepare("SELECT * FROM admins WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['admin_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows === 1) {
                $this->user = $result->fetch_assoc();
            }
        }
        
        return $this->user;
    }
}

// Function to get auth instance
function get_auth() {
    return Auth::getInstance();
} 