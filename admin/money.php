<?php
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

// Query ข้อมูลสินค้าที่มี admin_pay = 0 และ Order_Status = 'shipped'
$query = "SELECT o.Order_ID, o.Order_Quantity, o.Order_Total, p.product_name, p.product_picture, 
                 u.name AS seller_name, u.bankname, u.banknumber, o.admin_pay, o.Order_Status
          FROM orders o
          JOIN products p ON o.product_id = p.product_id
          JOIN users u ON p.user_id = u.id
          WHERE o.Order_Status = 'shipped' AND o.admin_pay = 0";

$stmt = $conn->prepare($query);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// คำนวณราคาสุดท้ายและส่วนลด
foreach ($orders as &$order) {
    if (!empty($order['promotion_code']) && !empty($order['promotion_discount'])) {
        $order['discount_amount'] = $order['Order_Total'] * ($order['promotion_discount'] / 100); // คำนวณส่วนลด
    } else {
        $order['discount_amount'] = 0;
    }
    $order['final_price'] = $order['Order_Total'] - $order['discount_amount']; // ราคาหลังหักส่วนลด
    $order['commission_amount'] = $order['final_price'] * 0.1; // คำนวณค่าคอมมิชชั่น 10%
    $order['final_price_after_commission'] = $order['final_price'] - $order['commission_amount']; // ราคาหลังหักค่าคอมมิชชั่น
}

// ตรวจสอบและอัปโหลดรูปภาพการโอนเงิน
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['payment_image'])) {
    $orderID = $_POST['order_id'];
    $upload_dir = '../uploads/';
    $image_name = basename($_FILES['payment_image']['name']);
    $upload_file = $upload_dir . $image_name;

    // ตรวจสอบว่ามีไฟล์ชื่อเดียวกันอยู่ในโฟลเดอร์หรือไม่
    $i = 1;
    $file_info = pathinfo($upload_file);
    while (file_exists($upload_file)) {
        $image_name = $file_info['filename'] . '_' . $i . '.' . $file_info['extension'];
        $upload_file = $upload_dir . $image_name;
        $i++;
    }

    if (move_uploaded_file($_FILES['payment_image']['tmp_name'], $upload_file)) {
        // เมื่ออัปโหลดสลิปโอนเงิน อัปเดตสถานะการชำระเงินในตาราง payments
        $query = "INSERT INTO payments (order_id, payment_image, confirmed) 
        VALUES (:order_id, :payment_image, 1) 
        ON DUPLICATE KEY UPDATE payment_image = :payment_image, confirmed = 1";

        $stmt = $conn->prepare($query);
        $stmt->bindParam(':payment_image', $image_name);
        $stmt->bindParam(':order_id', $orderID);
        $stmt->execute();

        $_SESSION['upload_success'] = $orderID;

        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        echo "<script>alert('เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการสินค้าเพื่อชำระเงิน</title>
    <style>
        .container {
            background-color: #34495e;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.4);
            width: 100%;
            max-width: 800px;
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
            width: 100px;
            height: 100px;
            margin-right: 15px;
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
            font-size: 28px;
            margin-bottom: 20px;
        }

        label {
            font-size: 16px;
            font-weight: bold;
            color: #ecf0f1;
        }

        .back-button {
            margin-left: 300px;
        }

        .pay {
            color: green;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>รายการสินค้าที่ต้องชำระเงิน</h1>

        <?php if (!empty($orders)) : ?>
            <?php foreach ($orders as $order) : ?>
                <div id="product-<?= $order['Order_ID'] ?>" class="product-container">
                    <div class="image-container">
                        <?php
                        $images = explode(',', str_replace(['[', ']', '"'], '', $order['product_picture']));
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

                        <?php if (!empty($order['promotion_code'])) : ?>
                            <p><strong>ใช้โปรโมชั่น:</strong> <?= htmlspecialchars($order['promotion_code']) ?></p>
                            <p><strong>ส่วนลด:</strong> <?= htmlspecialchars($order['promotion_discount']) ?>% (<?= number_format($order['discount_amount'], 2) ?>฿)</p>

                        <?php endif; ?>

                        <p><strong>ค่าคอมมิชชั่นที่ถูกหัก (10%):</strong> ฿<?= number_format($order['commission_amount'], 2) ?></p>
                        <p class="pay"><strong>ราคาที่ต้องโอนจ่ายหลังหักค่าคอมมิชชั่น:</strong> ฿<?= number_format($order['final_price_after_commission'], 2) ?></p>
                        <p><strong>ผู้ขาย:</strong> <?= htmlspecialchars($order['seller_name']) ?></p>
                        <p><strong>ธนาคาร:</strong> <?= htmlspecialchars($order['bankname']) ?></p>
                        <p><strong>เลขที่บัญชี:</strong> <?= htmlspecialchars($order['banknumber']) ?></p>
                    </div>

                    <div class="payment-form">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['Order_ID']) ?>">
                            <label for="payment_image">อัปโหลดรูปภาพการโอนเงิน:</label>
                            <input type="file" name="payment_image" required>
                            <button type="submit">ยืนยันการชำระเงิน</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <p>ไม่มีสินค้าที่ต้องชำระเงิน</p>
        <?php endif; ?>

        <a href="admin_dashboard.php" class="back-button btn btn-danger">กลับหน้าแรก</a>
    </div>
</body>

</html>