<?php
session_start();
include_once('../config/Database.php');

include_once("header.php");
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// ดึงข้อมูลสมาชิกทั้งหมด
$query = "SELECT id, name, email, address, phone, banknumber, bankname FROM users";
$stmt = $conn->prepare($query);
$stmt->execute();
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลธนาคารจากตาราง banks
$banks_query = "SELECT id, name FROM banks";
$banks_stmt = $conn->prepare($banks_query);
$banks_stmt->execute();
$banks = $banks_stmt->fetchAll(PDO::FETCH_ASSOC);

// ตรวจสอบว่าตัวแปร $members ได้ถูกตั้งค่าแล้วหรือยัง
$members = $members ?? [];

$selected_member = null;
$id = isset($_GET['id']) ? $_GET['id'] : null;

if ($id) {
    $query = "SELECT * FROM users WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $selected_member = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$selected_member) {
        die('Member not found.');
    }
}

// อัปเดตข้อมูลสมาชิกเมื่อมีการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_member'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    $banknumber = $_POST['banknumber'];
    $bankname = $_POST['bankname'];
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_BCRYPT) : $selected_member['password'];

    // การตรวจสอบการป้อนข้อมูล
    $errors = [];
    if (!preg_match('/^[a-zA-Z0-9ก-ฮ ]+$/u', $name)) {
        $errors[] = "Name should contain only letters, numbers, and spaces.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if (strlen($phone) !== 10) {
        $errors[] = "Phone number must be exactly 10 digits.";
    } elseif (!preg_match('/^[0-9]+$/', $phone)) {
        $errors[] = "Phone should contain only numbers.";
    }
    if (!preg_match('/^[0-9]+$/', $banknumber)) {
        $errors[] = "Bank number should contain only numbers.";
    }

    if (empty($errors)) {
        // อัปเดตข้อมูลเมื่อไม่มีข้อผิดพลาด
        $update_query = "UPDATE users SET 
                            name = :name, 
                            email = :email, 
                            password = :password, 
                            address = :address, 
                            phone = :phone, 
                            banknumber = :banknumber, 
                            bankname = :bankname 
                         WHERE id = :id";

        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bindParam(':name', $name);
        $update_stmt->bindParam(':email', $email);
        $update_stmt->bindParam(':password', $password);
        $update_stmt->bindParam(':address', $address);
        $update_stmt->bindParam(':phone', $phone);
        $update_stmt->bindParam(':banknumber', $banknumber);
        $update_stmt->bindParam(':bankname', $bankname);
        $update_stmt->bindParam(':id', $id);

        if ($update_stmt->execute()) {
            $success = "Member updated successfully!";
            header("Location: edit_member.php"); // Redirect to prevent resubmission
            exit();
        } else {
            $errors[] = "Failed to update member.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Member and View All Members</title>
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
            padding: 20px;
        }

        .container {
            background-color: #34495e;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.4);
            width: 100%;
            max-width: 1200px;
            overflow-x: auto;
            margin-top: 50px;
            
        }

        h1,
        h2 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 28px;
        }

        h2 {
            margin-top: 10px;
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
            background-color: #27ae60;
            color: white;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: #34495e;
            color: white;
        }

        th,
        td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #ddd;
            /* ใช้สีที่เข้มขึ้นสำหรับขอบ */

        }

        th {
            background-color: #2980b9;
            color: white;
        }

        a {
            color: #1abc9c;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
            color: white;
        }

        .alert-success {
            background-color: #27ae60;
        }

        .alert-danger {
            background-color: #e74c3c;
        }

        .btnall{
            /* border: 1px solid red; */
            width: 100%;
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <div class="container">
        <?php if ($selected_member) { ?>
            <form method="POST" action="">
                <input type="hidden" name="update_member" value="1">
                <input type="text" name="name" placeholder="ใส่ชื่อ" value="<?php echo htmlspecialchars($selected_member['name']); ?>" required>
                <input type="email" name="email" placeholder="ใส่อีเมลล์" value="<?php echo htmlspecialchars($selected_member['email']); ?>" required>
                <input type="text" name="address" placeholder="ใส่ที่อยู่" value="<?php echo htmlspecialchars($selected_member['address']); ?>" required>
                <input type="text" name="phone" placeholder="ใส่เบอร์โทรศัพท์" value="<?php echo htmlspecialchars($selected_member['phone']); ?>" required>
                <input type="text" name="banknumber" placeholder="ใส่เลขบัญชี" value="<?php echo htmlspecialchars($selected_member['banknumber']); ?>" required>

                <!-- เพิ่มการแสดงผลธนาคารที่ดึงจากฐานข้อมูล -->
                <select name="bankname" required>
                    <option value="" disabled selected>เลือกธนาคาร</option>
                    <?php foreach ($banks as $bank) { ?>
                        <option value="<?php echo htmlspecialchars($bank['name']); ?>" <?php if ($selected_member['bankname'] == $bank['name']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($bank['name']); ?>
                        </option>
                    <?php } ?>
                </select>

                <input type="password" name="password" placeholder="รหัสใหม่ (ถ้าไม่ใส่อะไรจะเป็นรหัสเดิม)">
                <button type="submit">Update Member</button>
            </form>
            <?php if (isset($success)) {
                echo "<div class='alert alert-success'>$success</div>";
            } ?>
            <?php if (isset($errors)) {
                foreach ($errors as $error) {
                    echo "<div class='alert alert-danger'>$error</div>";
                }
            } ?>
        <?php } ?>

        <h2>สมาชิกทั้งหมด</h2>
        <table border="0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ชื่อ</th>
                    <th>อีเมลล์</th>
                    <th>ที่อยู่</th>
                    <th>เบอร์</th>
                    <th>เลขบัญชี</th>
                    <th>ชื่อธนาคาร</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($members as $member) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($member['id']); ?></td>
                        <td><?php echo htmlspecialchars($member['name']); ?></td>
                        <td><?php echo htmlspecialchars($member['email']); ?></td>
                        <td><?php echo htmlspecialchars($member['address']); ?></td>
                        <td><?php echo htmlspecialchars($member['phone']); ?></td>
                        <td><?php echo htmlspecialchars($member['banknumber']); ?></td>
                        <td><?php echo htmlspecialchars($member['bankname']); ?></td>
                        <td>
                            <a href="edit_member.php?id=<?php echo $member['id']; ?>" class="btn btn-success edit">แก้ไข</a>
                            <a href="delete_user.php?id=<?php echo $member['id']; ?>" class="btn btn-danger delete" onclick="return confirm('ต้องการลบผู้ใช้ใช่หรือไม่?');">ลบ</a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        <div class="btnall">
            <button type="button" class="btn btn-danger " onclick="window.location.href='admin_dashboard.php'">กลับหน้าแรก</button>
            <button type="button" class="btn btn-success" onclick="window.location.href='add_member.php'">เพิ่มสมาชิก</button>
        </div>
    </div>
</body>

</html>