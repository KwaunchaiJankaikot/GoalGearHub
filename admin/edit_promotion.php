<?php
session_start();
include_once('../config/Database.php');


if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$id) {
    die('ID not provided.');
}

// ดึงข้อมูลสินค้าตาม ID
$query = "SELECT * FROM promotions WHERE id = :id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();
$promotion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$promotion) {
    die('Promotion not found.');
}

// อัปเดตข้อมูลเมื่อผู้ใช้ส่งฟอร์ม
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $code = $_POST['code'];
    $discount = $_POST['discount'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    $errors = [];

    // ตรวจสอบความถูกต้องของข้อมูล
    if (!preg_match('/^[a-zA-Z0-9]+$/', $code)) {
        $errors[] = "Promotion code should only contain letters and numbers.";
    }

    if (!preg_match('/^[0-9]+$/', $discount)) {
        $errors[] = "Discount should only contain numbers.";
    }

    $date_format = 'Y-m-d';
    $start_date_obj = DateTime::createFromFormat($date_format, $start_date);
    $end_date_obj = DateTime::createFromFormat($date_format, $end_date);

    if (!$start_date_obj || !$end_date_obj || $start_date_obj->format($date_format) !== $start_date || $end_date_obj->format($date_format) !== $end_date) {
        $errors[] = "Invalid date format.";
    }

    if (empty($errors)) {
        // อัปเดตข้อมูลโปรโมชั่นในฐานข้อมูล
        $query = "UPDATE promotions SET code = :code, discount = :discount, start_date = :start_date, end_date = :end_date WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':code', $code);
        $stmt->bindParam(':discount', $discount);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->bindParam(':id', $id);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Promotion updated successfully.";
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $errors[] = "Failed to update promotion.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Promotion</title>
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
        input[type="number"],
        input[type="date"],
        button {
            padding: 10px;
            border-radius: 5px;
            border: none;
            margin-bottom: 10px;
            font-size: 14px;
            width: 100%;
            box-sizing: border-box;
        }

        button {
            background-color: #27ae60;
            color: white;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
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

        .btngoback {
            background-color: red;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>แก้ไขโปรโมชั่น</h1>
        <form method="POST" action="">
            <input type="text" name="code" value="<?php echo htmlspecialchars($promotion['code']); ?>" required>
            <input type="number" name="discount" value="<?php echo htmlspecialchars($promotion['discount']); ?>" required>
            <input type="date" name="start_date" value="<?php echo htmlspecialchars($promotion['start_date']); ?>" required>
            <input type="date" name="end_date" value="<?php echo htmlspecialchars($promotion['end_date']); ?>" required>
            <button type="submit">อัปเดตโปรโมชั่น</button>
        </form>
        <?php if (isset($errors)) { 
            foreach ($errors as $error) {
                echo "<div class='alert alert-danger'>$error</div>";
            }
        } ?>
        <a href="admin_dashboard.php"><button class="btngoback">กลับ</button></a>
    </div>
</body>
</html>
