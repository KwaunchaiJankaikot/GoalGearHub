<?php
class Database {
    private $host = "localhost";
    private $port = "3307"; // เพิ่มพอร์ตที่คุณใช้อยู่
    private $db_name = "final"; // ชื่อฐานข้อมูลของคุณ
    private $username = "root"; // username ของ MySQL
    private $password = ""; // password ของ MySQL
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            // ระบุพอร์ตในสตริงการเชื่อมต่อ PDO
            $this->conn = new PDO("mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }
}

?>
