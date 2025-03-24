<?php
// รวมไฟล์ Database.php และ header.php
include_once('../config/Database.php');
include_once('header.php');

// เริ่มต้นเซสชัน
session_start();
$error = null;



// ตรวจสอบว่ามีการส่งข้อมูลจากฟอร์มหรือไม่
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email']; // รับค่าอีเมลจากฟอร์ม
    $password = $_POST['password']; // รับค่ารหัสผ่านจากฟอร์ม

    // สร้างอินสแตนซ์ของคลาส Database และเชื่อมต่อกับฐานข้อมูล
    $db = new Database();
    $conn = $db->getConnection();


    // ค้นหาผู้ใช้ตามอีเมล
    $query = "SELECT * FROM admins WHERE email = :email";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':email', $email); // คือการผูกค่าพารามิเตอร์ :email กับตัวแปร $email
    $stmt->execute();

    // ตรวจสอบว่ามีผู้ใช้อยู่ในฐานข้อมูลหรือไม่
    if ($stmt->rowCount() > 0) {
        $admin = $stmt->fetch(PDO::FETCH_ASSOC); // ดึงข้อมูลผู้ใช้
        // ตรวจสอบรหัสผ่าน
        if (password_verify($password, $admin['password'])) {
            // ตั้งค่าข้อมูลเซสชันสำหรับผู้ดูแลระบบ
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_email'] = $admin['email'];


            header("Location: admin_dashboard.php");
            exit();
        } else {
            $error = "Invalid password."; // รหัสผ่านไม่ถูกต้อง
        }
    } else {
        $error = "Email does not exist."; // ไม่พบอีเมล
    }

    // ถ้ามีข้อผิดพลาด ให้นำข้อผิดพลาดไปเก็บในเซสชันและเปลี่ยนเส้นทางกลับไปที่หน้าเดิม

    if ($error) {
        $_SESSION['error'] = $error;
        header("Location: " . $_SERVER['PHP_SELF']); // เปลี่ยนเส้นทางกลับมายังหน้าปัจจุบัน
        exit();
    }
}

// ตรวจสอบว่ามีข้อความแจ้งเตือนในเซสชันหรือไม่
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error']; // ดึงข้อความแจ้งเตือนจากเซสชัน
    unset($_SESSION['error']); // ลบข้อความแจ้งเตือนออกจากเซสชันเพื่อไม่ให้แสดงอีกเมื่อรีเฟรชหน้า
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <style>


        .container {
            background-color: #34495e;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
            margin-top: 160px;
        }

        h1 {
            color: white;
            text-align: center;
            margin-bottom: 30px;
        }

        input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: none;
            border-radius: 5px;
        }

        input[type="email"],
        input[type="password"] {
            background-color: #ecf0f1;
        }

        button {
            width: 100%;
            padding: 10px;
            background-color: #2980b9;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
        }

        button:hover {
            background-color: #3498db;
        }

        .alert {
            background-color: #e74c3c;
            color: white;
            padding: 10px;
            margin-top: 20px;
            border-radius: 5px;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Admin Login</h1>
        <?php if ($error) { ?>
            <div class="alert">
                <?php echo $error; ?>
            </div>
        <?php } ?>
        <form method="POST" action="">
            <input type="email" name="email" placeholder="Email" required><br>
            <input type="password" name="password" placeholder="Password" required><br>
            <button type="submit">Login</button>
        </form>
        <!-- แสดงข้อความแจ้งเตือนหากมีข้อผิดพลาด -->

    </div>
</body>

</html>
