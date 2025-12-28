<?php

require_once "./connect.php";

class User {
    private $db;
    private $table = "users";



public function __construct() {
$database = new Database();
$this->db = $database->conn;

    }

 

 public function login($email, $password) {
        $stmt = $this->db->prepare("SELECT user_id,  email, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
           
            if ($password === $user['password']) {
                
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                return true;
            }
        }
        return false;
    }
}