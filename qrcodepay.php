<?php
session_start();
include_once('header.php');
include_once('config/Database.php');
include_once('nav.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$user_id = $_SESSION['user_id'];

// ดึงรายการสินค้าในตะกร้าของผู้ใช้
$query = "SELECT p.product_name, p.product_price, c.quantity 
          FROM cart c 
          JOIN products p ON c.product_id = p.product_id 
          WHERE c.user_id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($cartItems)) {
    echo "<script>alert('ตะกร้าของคุณว่างเปล่า'); window.location.href='welcome.php';</script>";
    exit();
}

// ดึงข้อมูล QR Code ปัจจุบันจากฐานข้อมูล
$query = "SELECT * FROM payment_options WHERE option_name = 'QR Code' AND enabled = 1 LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->execute();
$qrPaymentOption = $stmt->fetch(PDO::FETCH_ASSOC);

// คำนวณยอดรวมของสินค้าในตะกร้า
$totalPrice = 0;
foreach ($cartItems as $item) {
    $totalPrice += $item['product_price'] * $item['quantity'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>QR Code Payment</title>
    <link rel="stylesheet" href="styles.css">
</head>
<style>
    .qrcodepay-container {
        display: flex;
        justify-content: space-between;
        max-width: 1200px;
        margin: 20px auto;
        padding: 20px;
        background-color: #2b2b2b;
        color: white;
        border-radius: 10px;
    }

    .qrcodepay-left,
    .qrcodepay-right {
        width: 48%;
    }

    .qrcodepay-left {
        background-color: #444;
        padding: 20px;
        border-radius: 10px;
    }

    .qrcodepay-right {
        background-color: #333;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    .qrcodepay-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
        border-radius: 10px;
        overflow: hidden;
    }

    .qrcodepay-table th,
    .qrcodepay-table td {
        border: 1px solid #444;
        padding: 12px 10px;
        text-align: left;
        background-color: #555;
    }

    .qrcodepay-table th {
        background-color: #666;
        color: #ffffff;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .qrcodepay-total-container {
        margin-top: 10px;
        padding: 10px;
        text-align: left;
    }

    .qrcodepay-total {
        font-weight: bold;
        font-size: 18px;
        color: #ffa500;
    }

    .imgqr {
        width: 250px;
        height: auto;
        margin-top: 10px;
        border: 2px solid #444;
    }
</style>

<body>
    <div class="qrcodepay-container">
        <!-- Left Section: QR Code -->
        <div class="qrcodepay-left">
            <h2>ชำระเงินด้วย QR Code</h2>
            <?php if (!empty($qrPaymentOption['qr_code_url'])): ?>
                <img src="uploads/<?php echo htmlspecialchars($qrPaymentOption['qr_code_url']); ?>" alt="QR Code" class="imgqr">
            <?php else: ?>
                <p>ไม่มี QR Code สำหรับการชำระเงิน</p>
            <?php endif; ?>
        </div>

        <!-- Right Section: Order Details -->
        <div class="qrcodepay-right">
            <h2>รายละเอียดการสั่งซื้อ</h2>

            <table class="qrcodepay-table">
                <tr>
                    <th>ชื่อสินค้า</th>
                    <th>ราคาต่อหน่วย</th>
                    <th>จำนวน</th>
                    <th>ราคารวม</th>
                </tr>
                <?php foreach ($cartItems as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td>฿<?php echo number_format($item['product_price'], 2); ?></td>
                        <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                        <td>฿<?php echo number_format($item['product_price'] * $item['quantity'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <div class="qrcodepay-total-container">
                <p class="qrcodepay-total">ราคารวมทั้งหมด: ฿<?php echo number_format($totalPrice, 2); ?></p>
            </div>
        </div>
    </div>
</body>

</html>
