<?php
include_once('header.php');
include_once('config/Database.php');
include_once('nav.php');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('กรุณาล็อกอินเพื่อดูยอดขาย'); window.location.href='signin.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];
$db = new Database();
$conn = $db->getConnection();

// Query สำหรับดูยอดขายที่มีสถานะ shipped
$query = "SELECT p.product_name,product_price, p.product_picture, SUM(o.Order_Total) AS total_sales, COUNT(o.Order_ID) AS total_orders
          FROM orders o
          JOIN products p ON o.product_id = p.product_id
          WHERE p.user_id = :user_id AND o.Order_Status = 'shipped'
          GROUP BY p.product_name, p.product_picture";

$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// คำนวณยอดขายรวมทั้งหมด และจำนวนออเดอร์รวมทั้งหมด
$totalOverallSales = 0;
$totalOverallOrders = 0; // จำนวนออเดอร์รวม
foreach ($sales as $sale) {
    $totalOverallSales += $sale['total_sales'];
    $totalOverallOrders += $sale['total_orders']; // เพิ่มจำนวนออเดอร์ของแต่ละสินค้าลงในจำนวนรวม
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Total Sales</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #4a4c4c;
            color: #fff;
        }

        .container {
            max-width: 900px;
            margin: 20px auto;
            padding: 20px;
            background-color: #333;
            border-radius: 10px;
        }

        h1 {
            color: #ffa500;
            text-align: center;
        }

        .product-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background-color: #2b2b2b;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 10px;
        }

        .product-info {
            flex: 1;
            margin-left: 20px;
        }

        .product-img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 10px;
        }

        .product-name {
            font-size: 18px;
            font-weight: bold;
        }

        .total-sales {
            font-size: 16px;
            color: #ffa500;
        }

        .total-orders {
            font-size: 16px;
            color: #ffa500;
        }

        .overall-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            font-size: 22px;
            font-weight: bold;
            color: #ffa500;
            border-top: 2px solid #ffa500;
            border-bottom: 2px solid #ffa500;
            background-color: #2b2b2b;
            margin-top: 20px;
        }

        .order-left {
            text-align: left;
            flex: 1;
            /* ยืดให้ช่องทางซ้ายกินพื้นที่ */
        }

        .sales-right {
            text-align: right;
            flex: 1;
            /* ยืดให้ช่องทางขวากินพื้นที่ */
        }
        .btn-danger{
            border:1px solid red;
            margin-top: 10px;
            width: 100%;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>ยอดขายสินค้าทั้งหมดของคุณ</h1>

        <?php if (!empty($sales)) : ?>
            <?php foreach ($sales as $sale) : ?>
                <div class="product-card">
                    <?php
                    // ดึงรูปแรกจากสตริงรูปภาพที่คั่นด้วยเครื่องหมายจุลภาค
                    $pictures = explode(',', $sale['product_picture']);
                    $firstPicture = isset($pictures[0]) ? trim($pictures[0]) : 'default.jpg'; // ใช้รูปแรกหรือ default.jpg ถ้าไม่มี
                    ?>
                    <img src="uploads/<?php echo htmlspecialchars($firstPicture); ?>" alt="Product Image" class="product-img">
                    <div class="product-info">
                        <p class="product-name"><?php echo htmlspecialchars($sale['product_name']); ?></p>
                        <p class="total-sales">ยอดขายรวม: ฿<?php echo number_format($sale['total_sales'], 2); ?></p>
                        <p class="total-orders">จำนวนออเดอร์: <?php echo htmlspecialchars($sale['total_orders']); ?> ออเดอร์</p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <p>ยังไม่มีสินค้าที่จัดส่งแล้ว</p>
        <?php endif; ?>

        <!-- แสดงยอดขายรวมทั้งหมด -->
        <div class="overall-total">
            <div class="order-left">
                ขายได้ทั้งหมด: <?php echo htmlspecialchars($totalOverallOrders); ?> ออเดอร์
            </div>
            <div class="sales-right">
                ยอดขายรวมทั้งหมด: ฿<?php echo number_format($totalOverallSales, 2); ?>
            </div>
        </div>
            <a href="userdashboard.php" class="btn btn-danger" >กลับหน้าแรก</a>
    </div>
</body>

</html>