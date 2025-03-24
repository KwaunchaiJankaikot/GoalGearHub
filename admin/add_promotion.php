<?php
session_start();
include_once('../config/Database.php');


if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = isset($_POST['promotion_id']) ? $_POST['promotion_id'] : null;
    $code = $_POST['code'];
    $discount = $_POST['discount'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    $errors = [];

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
        if ($id) {
            $query = "UPDATE promotions SET code = :code, discount = :discount, start_date = :start_date, end_date = :end_date WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':code', $code);
            $stmt->bindParam(':discount', $discount);
            $stmt->bindParam(':start_date', $start_date);
            $stmt->bindParam(':end_date', $end_date);
            $stmt->bindParam(':id', $id);

            if ($stmt->execute()) {
                $_SESSION['success'] = "Promotion updated successfully.";
            } else {
                $_SESSION['errors'][] = "Failed to update promotion.";
            }
        } else {
            $query = "INSERT INTO promotions (code, discount, start_date, end_date) VALUES (:code, :discount, :start_date, :end_date)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':code', $code);
            $stmt->bindParam(':discount', $discount);
            $stmt->bindParam(':start_date', $start_date);
            $stmt->bindParam(':end_date', $end_date);

            if ($stmt->execute()) {
                $_SESSION['success'] = "Promotion added successfully.";
            } else {
                $_SESSION['errors'][] = "Failed to add promotion.";
            }
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $_SESSION['errors'] = $errors;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// ดึงข้อมูลโปรโมชั่นทั้งหมด
$query = "
    SELECT p.*, 
    (SELECT COUNT(*) FROM orders o WHERE o.promotion_id = p.id) as usage_count
    FROM promotions p";
$stmt = $conn->prepare($query);
$stmt->execute();
$promotions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ล้างค่า session ที่ใช้แสดงแจ้งเตือนหลังจากแสดงแล้ว
$success = isset($_SESSION['success']) ? $_SESSION['success'] : null;
$errors = isset($_SESSION['errors']) ? $_SESSION['errors'] : [];

unset($_SESSION['success']);
unset($_SESSION['errors']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Add or Edit Promotion</title>
    <style>
        /* การตั้งค่าพื้นฐาน */
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
            max-width: 800px;
            overflow-x: auto;
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 28px;
        }

        /* การตกแต่งฟอร์ม */
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

        /* button:hover {
            background-color: #2ecc71;
        } */

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
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }

        th {
            background-color: #2980b9;
            color: white;
            text-align: center;
        }

        td {
            /* border: 10px solid red; */
            text-align: center;
        }

        .edit-btn,
        .delete-btn {
            width: 100px;
            /* กำหนดความกว้างให้เท่ากัน */
            padding: 10px;
            border-radius: 5px;
            border: none;
            color: white;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            text-align: center;
            text-decoration: none;
            /* border: 1px solid red; */
        }

        .edit-btn {
            background-color: #27ae60;
        }

        .delete-btn {
            background-color: red;
            /* border: 1px solid red; */
            
        }

        .edit-btn:hover {
            background-color: #3498db;
        }

        .delete-btn:hover {
            background-color: #c0392b;
        }

        /* ข้อความแจ้งเตือน */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            width: 97.5%;
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
            background-color: #dc3545;
        }
    </style>
    <script>
        function editPromotion(id, code, discount, startDate, endDate) {
            document.getElementById('promotion_id').value = id;
            document.getElementById('code').value = code;
            document.getElementById('discount').value = discount;
            document.getElementById('start_date').value = startDate;
            document.getElementById('end_date').value = endDate;
            document.getElementById('submit_button').innerText = "Update Promotion";
        }
    </script>
</head>

<body>
    <div class="container">
        <h1>Add or Edit Promotion</h1>
        <form method="POST" action="">
            <input type="hidden" name="promotion_id" id="promotion_id">
            <input type="text" name="code" id="code" placeholder="Promotion Code" required>
            <input type="number" name="discount" id="discount" placeholder="Discount (%)" required>
            <input type="date" name="start_date" id="start_date" required>
            <input type="date" name="end_date" id="end_date" required>
            <button type="submit" id="submit_button">เพิ่มโปรโมชั่น</button>
        </form>
        <?php if ($success) {
            echo "<div class='alert alert-success'>$success</div>";
        } ?>
        <?php if (!empty($errors)) {
            foreach ($errors as $error) {
                echo "<div class='alert alert-danger'>$error</div>";
            }
        } ?>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>โค้ด</th>
                    <th>ส่วนลด(%)</th>
                    <th>เริ่มวันที่</th>
                    <th>หมดวันที่</th>
                    <th>ถูกใช้(ครั้ง)</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($promotions as $promotion) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($promotion['id']); ?></td>
                        <td><?php echo htmlspecialchars($promotion['code']); ?></td>
                        <td><?php echo htmlspecialchars($promotion['discount']); ?></td>
                        <td><?php echo htmlspecialchars($promotion['start_date']); ?></td>
                        <td><?php echo htmlspecialchars($promotion['end_date']); ?></td>
                        <td><?php echo htmlspecialchars($promotion['usage_count']); ?></td>
                        <td>
                            <button type="button" class="edit-btn" onclick="editPromotion('<?php echo $promotion['id']; ?>', '<?php echo htmlspecialchars($promotion['code']); ?>', '<?php echo htmlspecialchars($promotion['discount']); ?>', '<?php echo htmlspecialchars($promotion['start_date']); ?>', '<?php echo htmlspecialchars($promotion['end_date']); ?>')">แก้ไข</button>
                            <a href="delete_promotion.php?id=<?php echo $promotion['id']; ?>" class="delete-btn" onclick="return confirm('แน่ใจที่ลบโปรโมชั่นใช่หรือไม่?');">Delete</a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        <a href="admin_dashboard.php"><button class="btngoback">กลับหน้าแรก</button></a>
    </div>
</body>

</html>