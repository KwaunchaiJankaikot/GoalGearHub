<?php
class UserLogin
{
    private $conn;
    private $email;
    private $password;
    private $userId;  // เพิ่มตัวแปรสำหรับ userId

    public function __construct($db, $email = null, $password = null, $userId = null)
    {
        $this->conn = $db;
        if ($email) $this->setEmail($email);
        if ($password) $this->setPassword($password);
        if ($userId) $this->setUserId($userId);
    }

    // Setter สำหรับ userId
    public function setUserId($id)
    {
        $this->userId = $id;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getPassword()
    {
        return $this->password;
    }

    // ตรวจสอบว่าอีเมลมีอยู่หรือไม่
    public function emailNotExists()
    {
        $query = "SELECT COUNT(*) FROM users WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $this->email);
        $stmt->execute();
        $count = $stmt->fetchColumn();
        return $count == 0;
    }

    // ตรวจสอบรหัสผ่าน
    public function verifyPassword()
    {
        $query = "SELECT password FROM users WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $this->email);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return password_verify($this->password, $row['password']);
    }

    // ดึง userId ตามอีเมล
    public function getUserId()
    {
        $query = "SELECT id FROM users WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $this->email);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['id'];
    }

    // ดึงชื่อผู้ใช้ตามอีเมล
    public function getName()
    {
        $query = "SELECT name FROM users WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $this->email);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['name'];
    }

    // ล็อกเอาท์ผู้ใช้
    public function logOut()
    {
        session_start();
        unset($_SESSION['user_id']);
        unset($_SESSION['name']);
        session_destroy();
        setcookie('email', '', time() - 3600, "/");
        setcookie('password', '', time() - 3600, "/");
        header("Location: signin.php");
        exit();
    }

    // ดึงอีเมลของผู้ใช้ตามอีเมล
    public function getUserEmail()
    {
        $query = "SELECT email FROM users WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $this->email);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['email'];
    }

    // ดึงข้อมูลผู้ใช้ทั้งหมดตาม userId รวมถึง phone, banknumber และ bankname
    public function getUserInfo()
    {
        $query = "SELECT email, address, phone, name, banknumber, bankname FROM users WHERE id = :id LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->userId);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // อัปเดตที่อยู่ผู้ใช้
    public function updateAddress($newAddress)
    {
        $query = "UPDATE users SET address = :address WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':address', $newAddress);
        $stmt->bindParam(':id', $this->userId);

        return $stmt->execute();
    }

    // อัปเดตรหัสผ่านผู้ใช้
    public function updatePassword($newPassword)
    {
        $query = "UPDATE users SET password = :password WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':password', $newPassword);
        $stmt->bindParam(':id', $this->userId);

        return $stmt->execute();
    }

    // อัปเดตเบอร์โทรศัพท์
    public function updatePhone($newPhone)
    {
        $query = "UPDATE users SET phone = :phone WHERE id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':phone', $newPhone);
        $stmt->bindParam(':user_id', $this->userId);
        return $stmt->execute();
    }

    // อัปเดตข้อมูลธนาคาร
    public function updateBankInfo($bankAccount, $bankName)
    {
        $query = "UPDATE users SET banknumber = :bankAccount, bankname = :bankName WHERE id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':bankAccount', $bankAccount);
        $stmt->bindParam(':bankName', $bankName);
        $stmt->bindParam(':user_id', $this->userId);

        return $stmt->execute();
    }

    // ดึงเบอร์โทรศัพท์ของผู้ใช้
    public function getPhone()
    {
        $query = "SELECT phone FROM users WHERE id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $this->userId);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['phone'] ?? null;
    }

    // ดึงข้อมูลธนาคารของผู้ใช้
    public function getBankInfo()
    {
        $query = "SELECT banknumber, bankname FROM users WHERE id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $this->userId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    function getSellerName($product_id) {
    // สร้างคำสั่ง SQL สำหรับดึงชื่อผู้ขาย
    $query = "SELECT u.name FROM users u JOIN products p ON u.id = p.user_id WHERE p.product_id = :product_id";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':product_id', $product_id);
    $stmt->execute();
    // ดึงข้อมูลชื่อผู้ขาย
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['name'] ?? 'Unknown Seller'; // ถ้าไม่พบชื่อ ให้คืนค่า 'Unknown Seller'
    }   
    public function getSellerNameByUserId($user_id) {
        $query = "SELECT name FROM users WHERE id = :user_id"; // สร้างคำสั่ง SQL เพื่อดึงชื่อผู้ใช้ตาม user_id
        $stmt = $this->conn->prepare($query); // เตรียมคำสั่ง SQL สำหรับการดำเนินการ
        $stmt->bindParam(':user_id', $user_id); // ผูกค่า user_id กับคำสั่ง SQL
        $stmt->execute(); // ดำเนินการคำสั่ง SQL
        $row = $stmt->fetch(PDO::FETCH_ASSOC); // ดึงข้อมูลจากฐานข้อมูล
        return $row['name'] ?? 'Unknown Seller'; // คืนค่าชื่อผู้ใช้ หรือคืนค่า 'Unknown Seller' ถ้าไม่พบชื่อ
    }
}
?>
