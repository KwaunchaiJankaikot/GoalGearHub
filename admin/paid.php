<?php
ob_start();  // เริ่มการเก็บข้อมูลบัฟเฟอร์

include_once('../config/Database.php');
include_once('header.php');

session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Query เพื่อดึงข้อมูลสินค้าที่จัดส่งแล้วแต่ยังไม่ได้จ่ายเงิน
$query = "SELECT o.Order_ID, o.Order_Total, o.final_price, o.Order_Quantity, 
                 o.commission_amount, o.discount_amount, p.product_name, 
                 p.product_picture, u.name AS seller_name, u.bankname, u.banknumber
          FROM orders o
          JOIN products p ON o.product_id = p.product_id
          JOIN users u ON p.user_id = u.id
          WHERE o.Order_Status = 'shipped' AND o.admin_pay = 0";

$stmt = $conn->prepare($query);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ฟังก์ชันสำหรับอัปโหลดรูปภาพการจ่ายเงิน
function uploadPaidImage($files, $upload_dir = '../uploads') {
    $image_name = basename($files['name']);
    $upload_file = $upload_dir . '/' . $image_name;
    
    // ตรวจสอบว่ามีไฟล์ชื่อเดียวกันอยู่ในโฟลเดอร์หรือไม่
    $i = 1;
    $file_info = pathinfo($upload_file);
    while (file_exists($upload_file)) {
        $image_name = $file_info['filename'] . '_' . $i . '.' . $file_info['extension'];
        $upload_file = $upload_dir . '/' . $image_name;
        $i++;
    }

    // ย้ายไฟล์ไปยังโฟลเดอร์ที่กำหนด
    if (move_uploaded_file($files['tmp_name'], $upload_file)) {
        return $image_name;
    }
    return null;
}

// ถ้ามีการกดปุ่มจ่ายเงิน (การปรับ admin_pay เป็น 1 พร้อมอัปโหลดสลิป)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pay_order']) && isset($_FILES['paid_image'])) {
    $orderID = $_POST['order_id'];
    $paid_image = uploadPaidImage($_FILES['paid_image']);

    if ($paid_image) {
        $query = "UPDATE orders SET admin_pay = 1, paid_image = :paid_image WHERE Order_ID = :order_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':paid_image', $paid_image);
        $stmt->bindParam(':order_id', $orderID);
        $stmt->execute();

        $_SESSION['pay_success'] = "จ่ายเงินสำเร็จสำหรับ Order ID: $orderID พร้อมอัปโหลดรูปภาพสลิปการจ่ายเงิน";
    } else {
        $_SESSION['pay_error'] = "เกิดข้อผิดพลาดในการอัปโหลดสลิป";
    }

    header("Location: paid.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการจ่ายเงินให้ผู้ขาย</title>
    <style>
        body {
            background-color: #2c3e50;
            font-family: Arial, sans-serif;
            color: #ecf0f1;
        }

        .container {
            background-color: #34495e;
            padding: 40px;
            border-radius: 8px;
            width: 100%;
            max-width: 1100px;
            margin: 30px auto;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
        }

        .product-container {
            background-color: #3b3e44;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        .image-container {
            width: 150px;
            height: 150px;
            margin-right: 20px;
        }

        img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }

        .product-info {
            text-align: left;
            flex-grow: 1;
        }

        .product-info p {
            margin: 5px 0;
            line-height: 1.6;
        }

        .payment-form {
            margin-top: 20px;
            text-align: left;
            /* border: 1px solid red; */
            margin-left: 170px;
        }

        .payment-form input[type="file"] {
            margin-bottom: 10px;
        }

        .payment-form button {
            background-color: #27ae60;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        .payment-form button:hover {
            background-color: #219150;
        }

        h1 {
            color: #fff;
            text-align: center;
            margin-bottom: 30px;
        }

        .pay {
            color: green;
            
        }

        label {
            color: white;
        }
    </style>

    <script>
        // ตรวจสอบข้อความแจ้งเตือนแล้วแสดง alert
        window.onload = function() {
            <?php if (isset($_SESSION['pay_success'])): ?>
                alert("<?= $_SESSION['pay_success'] ?>");
                <?php unset($_SESSION['pay_success']); ?>
            <?php elseif (isset($_SESSION['pay_error'])): ?>
                alert("<?= $_SESSION['pay_error'] ?>");
                <?php unset($_SESSION['pay_error']); ?>
            <?php endif; ?>
        };
    </script>
</head>
<body>
    <div class="container">
        <h1>รายการสินค้าที่ต้องจ่ายเงินให้ผู้ขาย</h1>

        <?php if (!empty($orders)): ?>
            <?php foreach ($orders as $order): ?>
                <div class="product-container">
                    <div class="image-container">
                        <?php 
                        // ดึงรูปภาพแรกของสินค้า
                        $images = explode(',', $order['product_picture']);
                        if (!empty($images[0])) : ?>
                            <img src="../uploads/<?= htmlspecialchars(trim($images[0])) ?>" alt="Product Image">
                        <?php else : ?>
                            <p>No image available.</p>
                        <?php endif; ?>
                    </div>

                    <div class="product-info">
                        <p><strong>ชื่อสินค้า:</strong> <?= htmlspecialchars($order['product_name']) ?></p>
                        <p><strong>จำนวน:</strong> <?= htmlspecialchars($order['Order_Quantity']) ?></p>
                        <p><strong>ราคา:</strong> ฿<?= number_format($order['Order_Total'], 2) ?></p>
                        <p><strong>ส่วนลด:</strong> ฿<?= number_format($order['discount_amount'], 2) ?></p>
                        <p><strong>ราคาหลังหักส่วนลด:</strong> ฿<?= number_format($order['final_price'], 2) ?></p>
                        <p><strong>ค่าคอมมิชชั่นที่ถูกหัก (10%):</strong> ฿<?= number_format($order['commission_amount'], 2) ?></p>
                        <p class="pay"><strong>ยอดเงินที่ต้องจ่าย:</strong> ฿<?= number_format($order['final_price'] - $order['commission_amount'], 2) ?></p>
                        <p><strong>ผู้ขาย:</strong> <?= htmlspecialchars($order['seller_name']) ?></p>
                        <p><strong>ธนาคาร:</strong> <?= htmlspecialchars($order['bankname']) ?></p>
                        <p><strong>เลขที่บัญชี:</strong> <?= htmlspecialchars($order['banknumber']) ?></p>
                    </div>

                    <div class="payment-form">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['Order_ID']) ?>">
                            <label for="paid_image">อัปโหลดรูปภาพการโอนเงิน:</label>
                            <input type="file" name="paid_image" required>
                            <button type="submit" name="pay_order">ยืนยันการจ่ายเงิน</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align:center;">ไม่มีรายการที่ต้องจ่ายเงิน</p>
        <?php endif; ?>
        <a href="admin_dashboard.php" class="back-button btn btn-danger" style=" margin-left: 470px;">กลับหน้าแรก</a>
    </div>
</body>
</html>
