<?php
session_start();
include_once('../config/Database.php');
include_once('header.php');

// ตรวจสอบว่าแอดมินล็อกอินหรือยัง
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// ดึงข้อมูลยอดขายพร้อมข้อมูลการใช้ส่วนลดและรูปภาพการจ่ายเงิน รวมถึงจำนวนสินค้าที่ซื้อ
$query = "SELECT o.Order_ID, o.Order_Total, o.discount_amount, o.final_price, o.Order_Date, o.payment_method, o.qr_code_image, o.Order_Quantity,o.commission_amount, p.code as promotion_code
          FROM orders o
          LEFT JOIN promotions p ON o.promotion_id = p.id
          WHERE o.Order_Status = 'shipped'
          ORDER BY o.Order_ID ASC";
$stmt = $conn->prepare($query);
$stmt->execute();
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// คำนวณยอดรวมของราคาสุทธิและจำนวนคำสั่งซื้อทั้งหมด
$totalFinalPrice = 0;
$totalOrders = 0;

foreach ($sales as $sale) {
    $totalFinalPrice += ($sale['final_price'] > 0) ? $sale['final_price'] : $sale['Order_Total'];
    $totalOrders++;

}
//ใช้เรียก commission
$query = "SELECT SUM(commission_amount) AS total_commission FROM orders WHERE Order_Status = 'shipped'";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

$totalCommission = $result['total_commission'];

?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดการขาย</title>
    <style>
        body {
            background-color: #2c3e50;
            color: #ecf0f1;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
        }

        table.custom-sales-table {
            width: 100%;
            margin: 0 auto;
            border-collapse: collapse;
            background-color: #34495e;
            border-radius: 8px;
            overflow: hidden;
            color: white;
            box-shadow: 0px 0px 15px rgba(0, 0, 0, 0.5);
        }

        table.custom-sales-table th, table.custom-sales-table td {
            padding: 15px;
            text-align: center;
            border-bottom: 1px solid #444;
        }

        table.custom-sales-table th {
            background-color: #2980b9;
        }

        table.custom-sales-table tr:nth-child(even) {
            background-color: #3e5871;
        }


        .image-container img {
            width: 150px;
            height: auto;
            border-radius: 8px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.5);
        }

        .summary-row th {
            background-color: #1abc9c;
        }

        .goback {
            display: flex;
            justify-content: center;
            width: 100%;
            margin-top: 20px;
        }

        .gogoback {
            width: 10%;
        }
    </style>
</head>

<body>
    <h1>รายละเอียดการขาย</h1>

    <?php if (!empty($sales)): ?>
        <table class="custom-sales-table">
            <thead>
                <tr>
                    <th>รหัสคำสั่งซื้อ</th>
                    <th>ยอดรวม</th>
                    <th>ส่วนลด</th>
                    <th>รหัสส่วนลด</th>
                    <th>ราคาสุทธิ</th>
                    <th>จำนวนสินค้าที่ซื้อ</th>
                    <th>วันที่</th>
                    <th>วิธีการจ่ายเงิน</th>
                    <th>คอมมิชชั่นที่ได้</th>
                    <th>รูปภาพการจ่ายเงิน</th>
    
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sales as $sale): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($sale['Order_ID']); ?></td>
                        <td>฿<?php echo number_format($sale['Order_Total'], 2); ?></td>
                        <td><?php echo $sale['discount_amount'] > 0 ? '฿' . number_format($sale['discount_amount'], 2) : 'ไม่มีส่วนลด'; ?></td>
                        <td><?php echo htmlspecialchars($sale['promotion_code']); ?></td>
                        <td>฿<?php echo number_format($sale['final_price'] > 0 ? $sale['final_price'] : $sale['Order_Total'], 2); ?></td>
                        <td><?php echo htmlspecialchars($sale['Order_Quantity']); ?></td>
                        <td><?php echo htmlspecialchars($sale['Order_Date']); ?></td>
                        <td><?php echo htmlspecialchars($sale['payment_method']); ?></td>
                        <td><?php echo htmlspecialchars($sale['commission_amount'])?></td>
                        <td class="image-container">
                            <?php echo !empty($sale['qr_code_image']) ? '<img src="../uploads/' . htmlspecialchars($sale['qr_code_image']) . '" alt="QR Code">' : 'ไม่มีรูปภาพ'; ?>
                        </td>
                    
                    </tr>
                <?php endforeach; ?>

                <tr class="summary-row">
                    <th colspan="4">ยอดรวมทั้งหมด</th>
                    <th>฿<?php echo number_format($totalFinalPrice, 2); ?></th>
                    <th colspan="4">มีออเดอร์ทั้งหมด <?php echo $totalOrders; ?> ออเดอร์</th>
                    <th colspan="4">ค่าคอมมิชชั่นรวมทั้งหมด: ฿<?php echo number_format($totalCommission, 2); ?></th>

                </tr>
            </tbody>
        </table>
    <?php else: ?>
        <p class="text-center">ไม่มีข้อมูลการขาย</p>
    <?php endif; ?>

    <div class="goback">
        <a href="admin_dashboard.php" class="btn btn-danger gogoback">กลับ</a>
    </div>
</body>

</html>
