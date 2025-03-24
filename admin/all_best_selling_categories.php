<?php
session_start();
ob_start();
include_once('header.php');
include_once('../config/Database.php');

// ตรวจสอบว่าแอดมินล็อกอินหรือยัง
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// ดึงข้อมูลหมวดหมู่ที่ขายดีที่สุด
$query = "
    SELECT c.name AS category_name, COUNT(o.product_id) AS sales
    FROM orders o
    JOIN products p ON o.product_id = p.product_id
    JOIN categories c ON p.category = c.id
    WHERE o.order_status = 'shipped'
    GROUP BY c.name
    ORDER BY sales DESC";

$stmt = $conn->prepare($query);
$stmt->execute();
$bestSellingCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Best Selling Categories</title>
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
        }

        .container {
            background-color: #34495e;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.4);
            width: 80%; /* ปรับขนาดให้เหมือนกับ all_best_selling_products.php */
            max-width: 900px; /* ขนาดสูงสุด */
            overflow-x: auto;
            text-align: center;
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 28px;
        }

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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            text-align: center;
            vertical-align: middle;
            border-bottom: 1px solid #ecf0f1;
            font-size: 14px;
        }

        th {
            background-color: #2980b9;
            color: white;
        }
    </style>
</head>

<body>
<div class="container">
    <h1>หมวดหมู่ขายดี</h1>
    <table>
        <thead>
            <tr>
                <th>ชื่อหมวดหมู่</th>
                <th>จำนวนสินค้าที่ขายได้</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bestSellingCategories as $category): ?>
                <tr>
                    <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                    <td><?php echo $category['sales']; ?> ครั้ง</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
      <!-- ปุ่มกลับ -->
      <div class="back-button">
            <a href="admin_dashboard.php"><button>กลับไปยัง Dashboard</button></a>
        </div>
</div>
</body>

</html>
