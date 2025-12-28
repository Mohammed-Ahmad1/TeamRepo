<?php

class Database
{
private $hostname = "localhost";
private $username = "root";
private $password = "";
private $db = "phpteamproject";
public $conn;
 public function __construct()
 {
$this->connectDB();


}
private function connectDB()
{
    $this->conn=new mysqli(
    $this->hostname,
    $this->username,
    $this->password,
    $this->db
    );


if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }

}

}
?>
