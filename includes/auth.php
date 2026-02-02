<?php
require_once __DIR__ . '/../config/database.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public function register($username, $email, $password, $role) {
        // Check if username already exists
        $this->db->query('SELECT id FROM users WHERE username = :username');
        $this->db->bind(':username', $username);
        if($this->db->single()) {
            return false; // Username already exists
        }
        
        // Check if email already exists
        $this->db->query('SELECT id FROM users WHERE email = :email');
        $this->db->bind(':email', $email);
        if($this->db->single()) {
            return false; // Email already exists
        }
        
        $this->db->query('INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, :role)');
        $this->db->bind(':username', $username);
        $this->db->bind(':email', $email);
        $this->db->bind(':password', password_hash($password, PASSWORD_DEFAULT));
        $this->db->bind(':role', $role);
        
        return $this->db->execute();
    }
    
    public function login($email, $password) {
        $this->db->query('SELECT * FROM users WHERE email = :email AND status = "active"');
        $this->db->bind(':email', $email);
        $user = $this->db->single();
        
        if($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            return true;
        }
        return false;
    }
    
    public function logout() {
        session_destroy();
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function getRole() {
        return $_SESSION['role'] ?? null;
    }
}
?>