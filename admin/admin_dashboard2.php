<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
include_once('header.php');
$admin_name = $_SESSION['admin_name'];
$admin_id = $_SESSION['admin_id'];
$admin_email = $_SESSION['admin_email'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        .bodydashboard {
            background-color: #363636;
        }

        .bodydashboard h1,
        h2,
        li,
        a {
            color: white;
        }

        /* admindashboard */
        .bodydashboard {
            font-family: Arial, sans-serif;
            background-color: #2c3e50;
            color: #ecf0f1;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .dashboard-container {
            background-color: #34495e;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
            width: 350px;
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 24px;
        }

        h2 {
            text-align: center;
            font-size: 20px;
            margin-bottom: 20px;
        }

        ul {
            list-style-type: none;
            padding: 0;
        }

        ul li {
            margin-bottom: 10px;
        }

        ul li a {
            display: block;
            padding: 10px 15px;
            margin: 5px 0;
            background-color: #e74c3c;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            text-align: center;
            transition: background-color 0.3s;
        }

        ul li a:hover {
            background-color: #c0392b;
        }

        ul li a:active {
            background-color: #a93226;
        }
    </style>
</head>

<body class="bodydashboard">
    <div class="dashboard-container">
        <h1>Welcome, <?php echo htmlspecialchars($admin_email); ?></h1>
        <h2>Admin Dashboard</h2>
        <ul>
            <!-- <li><a href="add_product.php">เพิ่มสินค้า</a></li> -->
            <li><a href="money.php">จ่ายเงินให้กับร้านค้า</a></li>
            <li><a href="productmanage.php">จัดการสินค้า</a></li>
            <!-- <li><a href="delete_product.php">Delete Product</a></li> -->
            <li><a href="admin_approve.php">อนุมัติสินค้า</a></li>
            <!-- <li><a href="add_member.php">เพิ่มสมาชิก</a></li> -->
            <li><a href="edit_member.php">จัดการสมาชิก</a></li>
            <!-- <li><a href="delete_member.php">Delete Member</a></li> -->
            <li><a href="admin_complaint.php">ดูคำร้องเรียนทั้งหมด</a></li>
            <li><a href="add_promotion.php">เพิ่มโค้ดโปรโมชั่น</a></li>
            <li><a href="manage.php">จัดการตัวเลือกต่างๆ</a></li>
            <li><a href="logout.php">ออกจากระบบ</a></li>
        </ul>
    </div>
</body>

</html>