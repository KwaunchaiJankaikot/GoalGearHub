<?php
include_once('../config/Database.php');

// เริ่มต้น session (ถ้ายังไม่เริ่ม)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ตรวจสอบการล็อกอิน (สำหรับผู้ใช้ที่เป็น admin)
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// สร้างการเชื่อมต่อฐานข้อมูล
$db = new Database();
$conn = $db->getConnection();

// ดึงข้อมูลคำสั่งซื้อทั้งหมดจากตาราง orders
$query = "SELECT o.order_id, p.product_name, o.order_quantity, o.Order_Total, o.order_date, o.order_status 
          FROM orders o 
          JOIN products p ON o.product_id = p.product_id
          ORDER BY o.order_date DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <h1>Orders</h1>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Order ID</th>
                        <th>Product Name</th>
                        <th>Quantity</th>
                        <th>Total</th>
                        <th>Order Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $index => $order): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($order['order_id']) ?></td>
                            <td><?= htmlspecialchars($order['product_name']) ?></td>
                            <td><?= htmlspecialchars($order['order_quantity']) ?></td>
                            <td>฿<?= number_format($order['Order_Total'], 2) ?></td>
                            <td><?= htmlspecialchars($order['order_date']) ?></td>
                            <td>
                                <?php 
                                switch ($order['order_status']) {
                                    case 'pending':
                                        echo '<span class="badge bg-warning">Pending</span>';
                                        break;
                                    case 'shipped':
                                        echo '<span class="badge bg-primary">Shipped</span>';
                                        break;
                                    case 'delivered':
                                        echo '<span class="badge bg-success">Delivered</span>';
                                        break;
                                    case 'cancelled':
                                        echo '<span class="badge bg-danger">Cancelled</span>';
                                        break;
                                    default:
                                        echo '<span class="badge bg-secondary">' . htmlspecialchars($order['order_status']) . '</span>';
                                        break;
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>
