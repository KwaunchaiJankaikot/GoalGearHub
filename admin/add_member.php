<?php
session_start();
include_once('Database.php'); 

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// ดึงข้อมูลธนาคารจากตาราง banks
$banks_query = "SELECT id, name FROM banks";
$banks_stmt = $conn->prepare($banks_query);
$banks_stmt->execute();
$banks = $banks_stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    $banknumber = $_POST['banknumber'];
    $bankname = $_POST['bankname'];

    // ตรวจสอบว่า email มีอยู่แล้วหรือไม่
    $check_query = "SELECT COUNT(*) FROM users WHERE email = :email";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bindParam(':email', $email);
    $check_stmt->execute();
    $email_exists = $check_stmt->fetchColumn();

    if ($email_exists) {
        $error = "This email is already in use. Please use a different email.";
    } else {
        $query = "INSERT INTO users (name, email, password, address, phone, banknumber, bankname) 
                  VALUES (:name, :email, :password, :address, :phone, :banknumber, :bankname)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $password);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':banknumber', $banknumber);
        $stmt->bindParam(':bankname', $bankname);

        if ($stmt->execute()) {
            $success = "Member added successfully.";
            header("Location: add_member.php"); // เปลี่ยนเส้นทางเพื่อป้องกันการรีเฟรชฟอร์มซ้ำ
        } else {
            $error = "Failed to add member.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Member</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #2c3e50;
            color: #ecf0f1;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            background-color: #34495e;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.4);
            width: 100%;
            max-width: 600px;
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 28px;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            padding: 10px;
            border-radius: 5px;
            border: none;
            margin-bottom: 10px;
            font-size: 14px;
            width: 100%;
            box-sizing: border-box;
        }

        button {
            padding: 10px;
            border-radius: 5px;
            border: none;
            color: white;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btnadd{
            background-color: #27ae60;
        }
        .btngoback{
            background-color: red;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            width: 95%;
            text-align: center;
            color: white;
        }

        .alert-success {
            background-color: #27ae60;
            margin-top: 10px;
        }

        .alert-danger {
            background-color: #e74c3c;
            margin-top: 10px;
        }
    </style>
</head>
<body>

    <div class="container">
    <?php if (isset($success)) { echo "<div class='alert alert-success '>$success</div>"; } ?>
    <?php if (isset($error)) { echo "<div class='alert alert-danger'>$error</div>"; } ?>
        <h1>เพิ่มสมาชิก</h1>
        <form method="POST" action="">
            <input type="text" name="name" placeholder="ชื่อ" required>
            <input type="email" name="email" placeholder="อีเมลล์" required>
            <input type="password" name="password" placeholder="รหัสผ่าน" required>
            <input type="text" name="address" placeholder="ที่อยู่" required>
            <input type="text" name="phone" placeholder="เบอร์" required>
            <input type="text" name="banknumber" placeholder="เลขบัญชี (ใช้ในกรณีขายสินค้า)" required>
            
            <!-- ส่วนนี้จะสร้างตัวเลือกธนาคารจากข้อมูลในฐานข้อมูล -->
            <select name="bankname" required>
                <option value="" disabled selected>เลือกธนาคาร</option>
                <?php foreach ($banks as $bank) { ?>
                    <option value="<?php echo htmlspecialchars($bank['name']); ?>">
                        <?php echo htmlspecialchars($bank['name']); ?>
                    </option>
                <?php } ?>
            </select>

            <button type="submit" class="btn-secondary btnadd">เพิ่มสมาขิก</button>
            <button type="button" class="btn-secondary btngoback" onclick="window.location.href='admin_dashboard.php'">กลับหน้าแรก</button>
        </form>

    </div>
</body>
</html>
