<?php
session_start();
include('../config/Database.php');

// ตรวจสอบว่าแอดมินล็อกอินหรือยัง
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// ดึงข้อมูลสินค้าขายดี พร้อมรายละเอียดว่าเป็นของใคร ขายได้กี่ครั้ง และเหลือสินค้าคงเหลือเท่าไหร่
$query = "
    SELECT p.product_name, p.product_price, u.name AS seller, COUNT(o.product_id) AS sales, p.product_quantity
    FROM orders o
    JOIN products p ON o.product_id = p.product_id
    JOIN users u ON p.user_id = u.id
    WHERE o.order_status = 'shipped'
    GROUP BY p.product_name, p.product_price, u.name, p.product_quantity
    ORDER BY sales DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$bestSellingProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Best Selling Products</title>
    <style>
        /* การตกแต่งด้วย CSS */
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
            flex-direction: column;
        }

        .container {
            background-color: #34495e;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.4);
            width: 80%;
            max-width: 800px;
            text-align: center;
        }

        h1 {
            margin-bottom: 20px;
            font-size: 28px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            padding: 12px;
            text-align: center;
            /* จัดข้อความให้อยู่ตรงกลาง */
            vertical-align: middle;
            /* จัดให้ข้อความอยู่ตรงกลางแนวตั้ง */
            border-bottom: 1px solid #ecf0f1;
            font-size: 14px;
        }

        th {
            background-color: #2980b9;
            color: white;
            text-align: center;
            padding: 12px;
            border-bottom: 1px solid #ecf0f1;
            font-size: 14px;
        }

        td {
            word-wrap: break-word;
            max-width: 250px;
        }


        /* ปุ่ม */
        button {
            padding: 10px 20px;
            font-size: 14px;
            color: white;
            background-color: red;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        /* button:hover {
            background-color: #2ecc71;
        } */

        .back-button {
            margin-top: 20px;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>สินค้าขายดีทั้งหมด</h1>
        <table>
            <thead>
                <tr>
                    <th>ชื่อสินค้า</th>
                    <th>ผู้ขาย</th>
                    <th>ราคา</th>
                    <th>จำนวนครั้งที่ขายได้</th>
                    <th>สินค้าคงเหลือ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($bestSellingProducts)): ?>
                    <?php foreach ($bestSellingProducts as $product): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($product['seller']); ?></td>
                            <td>฿<?php echo number_format($product['product_price'], 2); ?></td>
                            <td><?php echo $product['sales']; ?> ครั้ง</td>
                            <td><?php echo $product['product_quantity']; ?> ชิ้น</td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">ไม่มีข้อมูลสินค้าขายดี</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- ปุ่มกลับ -->
        <div class="back-button">
            <a href="admin_dashboard.php"><button>กลับไปยัง Dashboard</button></a>
        </div>
    </div>
</body>

</html>