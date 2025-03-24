<?php
session_start();
ob_start();
include_once('header.php');
// include_once('sales_chart.php');
include_once('../config/Database.php');

// ตรวจสอบว่าแอดมินล็อกอินหรือยัง
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// ดึงข้อมูลสถิติยอดขายสุทธิและรวมราคาสินค้าทั้งหมด (หักส่วนลด)
$salesQuery = "
    SELECT SUM(o.final_price) AS total_sales, COUNT(o.order_id) AS total_orders 
    FROM orders o
    WHERE o.order_status = 'shipped'
";

$salesStmt = $conn->prepare($salesQuery);
$salesStmt->execute();
$salesData = $salesStmt->fetch(PDO::FETCH_ASSOC);

$usersQuery = "SELECT COUNT(id) AS total_users FROM users";
$usersStmt = $conn->prepare($usersQuery);
$usersStmt->execute();
$usersData = $usersStmt->fetch(PDO::FETCH_ASSOC);

$productsQuery = "SELECT COUNT(product_id) AS total_products FROM products";
$productsStmt = $conn->prepare($productsQuery);
$productsStmt->execute();
$productsData = $productsStmt->fetch(PDO::FETCH_ASSOC);

// ดึงข้อมูลสินค้าที่รออนุมัติ (จากตาราง products ที่มีสถานะ 'pending')
$pendingProductsQuery = "SELECT COUNT(product_id) AS total_pending_products FROM products WHERE approved = 0";
$pendingProductsStmt = $conn->prepare($pendingProductsQuery);
$pendingProductsStmt->execute();
$pendingProductsData = $pendingProductsStmt->fetch(PDO::FETCH_ASSOC);

$reportedProductsQuery = "SELECT COUNT(report_count) AS total_reported FROM complaints WHERE status = 0";
$reportedProductsStmt = $conn->prepare($reportedProductsQuery);
$reportedProductsStmt->execute();
$reportedProductsData = $reportedProductsStmt->fetch(PDO::FETCH_ASSOC);

// ดึงข้อมูลยอดขายรายเดือน
$monthlySalesQuery = "
    SELECT MONTH(Order_Date) AS month, SUM(final_price) AS total_sales
    FROM orders
    WHERE Order_Status = 'shipped'
    GROUP BY MONTH(Order_Date)";
$monthlySalesStmt = $conn->prepare($monthlySalesQuery);
$monthlySalesStmt->execute();
$monthlySales = $monthlySalesStmt->fetchAll(PDO::FETCH_ASSOC);

// เตรียมข้อมูลสำหรับกราฟ
$monthlySalesData = array_fill(1, 12, 0); // เตรียม array ขนาด 12 สำหรับแต่ละเดือน

foreach ($monthlySales as $sale) {
    $monthlySalesData[(int)$sale['month']] = $sale['total_sales'];
}

// ดึงข้อมูลสินค้าที่ขายดีที่สุดจากฐานข้อมูล (แสดงเฉพาะ 3 อันดับแรก)
$query = "
    SELECT p.product_name, c.name AS category_name, SUM(o.Order_Quantity) AS sales
    FROM orders o
    JOIN products p ON o.product_id = p.product_id
    JOIN categories c ON p.category = c.id
    WHERE o.Order_Status = 'shipped'
    GROUP BY p.product_name, c.name
    ORDER BY sales DESC
    LIMIT 3";
// จำกัด 3 อันดับแรก
$stmt = $conn->prepare($query);
$stmt->execute();
$topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลหมวดหมู่ที่ขายดีที่สุดจากฐานข้อมูล (แสดงเฉพาะ 3 อันดับแรก)
$categoryQuery = "
    SELECT c.name AS category_name, SUM(o.Order_Quantity) AS sales
    FROM orders o
    JOIN products p ON o.product_id = p.product_id
    JOIN categories c ON p.category = c.id
    WHERE o.Order_Status = 'shipped'
    GROUP BY c.name
    ORDER BY sales DESC
    LIMIT 3";  // จำกัด 3 อันดับแรก

$categoryStmt = $conn->prepare($categoryQuery);
$categoryStmt->execute();
$topCategories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลbrandsที่ขายดีที่สุดจากฐานข้อมูล (แสดงเฉพาะ 3 อันดับแรก)
$brandQuery = "
    SELECT p.product_brand AS brand_name, SUM(o.Order_Quantity) AS sales
    FROM orders o
    JOIN products p ON o.product_id = p.product_id
    WHERE o.Order_Status = 'shipped'
    GROUP BY p.product_brand
    ORDER BY sales DESC
    LIMIT 3";
  // จำกัด 3 อันดับแรก
$brandStmt = $conn->prepare($brandQuery);
$brandStmt->execute();
$topbrand = $brandStmt->fetchAll(PDO::FETCH_ASSOC);


// ดึงข้อมูลคำขอคืนเงิน (จากตาราง refund_requests ที่มีสถานะ 'pending')
$refundRequestsQuery = "
    SELECT rr.id, rr.order_id, rr.user_id, rr.refund_reason, rr.request_date, rr.status, rr.refund_image, 
           o.final_price, u.name as customer_name
    FROM refund_requests rr
    JOIN orders o ON rr.order_id = o.Order_ID
    JOIN users u ON rr.user_id = u.id
    WHERE rr.status = 'pending'
    ORDER BY rr.request_date DESC";
$refundRequestsStmt = $conn->prepare($refundRequestsQuery);
$refundRequestsStmt->execute();
$refundRequests = $refundRequestsStmt->fetchAll(PDO::FETCH_ASSOC);

// นับจำนวนคำร้องขอคืนเงิน
$totalRefundRequests = count($refundRequests);

// สีสำหรับอันดับที่ 1, 2, 3
$rankColors = ['GOLD', 'SILVER', '#CD7F32']; // สีทอง สีเงิน สีทองแดง
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Statistics</title>
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet"> -->
    <style>
        .sidebar {
            background-color: #343a40;
            /* สีพื้นหลัง sidebar */
            height: 125vh;
            /* ทำให้ Sidebar ยาวเต็มหน้าจอ */
            padding: 15px;
            color: #ffffff;
        }

        .nav-link {
            color: #ffffff;
            padding: 10px;
            display: block;
            text-decoration: none;
        }

        .nav-link:hover {
            /* background-color: #495057; */
            color: #ffffff;
        }

        .welcome-text {
            background-color: #343a40;
            /* ปรับสีพื้นหลังให้กลมกลืนกับ sidebar */
            color: #ffffff;
            /* สีของข้อความ */
            padding: 10px;
            margin-bottom: 10px;
            text-align: center;
            font-size: 1.2em;
            border-radius: 5px;
        }

        .main-content {
            margin-left: 15px;
            /* เว้นระยะจาก sidebar */
        }

        .textsidebar {
            color: #ffffff;
        }

        .refundcard {
            background-color: gray;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="welcome-text">
                    Welcome, <?php echo htmlspecialchars($_SESSION['admin_email']); ?>
                </div>
                <ul class="nav flex-column textsidebar">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="sales_details.php">ดูคำสั่งซื้อสินค้า</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="paid.php">จ่ายเงินให้กับร้านค้า</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="sales_chart.php">รายงานการขาย</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_refund.php">คำขอคืนเงิน</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="productmanage.php">จัดการสินค้า</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_approve.php">อนุมัติสินค้า</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="edit_member.php">จัดการสมาชิก</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_complaint.php">ดูคำร้องเรียนทั้งหมด</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="add_promotion.php">เพิ่มโค้ดโปรโมชั่น</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage.php">จัดการตัวเลือกต่างๆ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">ออกจากระบบ</a>
                    </li>
                </ul>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                </div>

                <!-- สถิติต่างๆ -->
                <div class="row">
                    <!-- Total Sales -->
                    <div class="col-md-3">
                        <a href="sales_details.php" class="text-decoration-none">
                            <div class="card text-white bg-primary mb-3">
                                <div class="card-header">ยอดขาย</div>
                                <div class="card-body">
                                    <h5 class="card-title">฿<?php echo number_format($salesData['total_sales'], 2); ?></h5>
                                    <p class="card-text">Total Orders: <?php echo $salesData['total_orders']; ?></p>
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- Total Users -->
                    <div class="col-md-3">
                        <a href="edit_member.php" class="text-decoration-none">
                            <div class="card text-white bg-success mb-3">
                                <div class="card-header">สมาชิกทั้งหมด</div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $usersData['total_users']; ?></h5>
                                    <p class="card-text">Registered Members</p>
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- Total Products -->
                    <div class="col-md-3">
                        <a href="productmanage.php" class="text-decoration-none">
                            <div class="card text-white bg-info mb-3">
                                <div class="card-header">สินค้าทั้งหมด</div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $productsData['total_products']; ?></h5>
                                    <p class="card-text">Products Listed</p>
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- สินค้าที่รอการอนุมัติ -->
                    <div class="col-md-3">
                        <a href="admin_approve.php" class="text-decoration-none">
                            <div class="card text-white bg-warning mb-3">
                                <div class="card-header">สินค้าที่รอการอนุมัติ</div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $pendingProductsData['total_pending_products']; ?></h5>
                                    <p class="card-text">Products awaiting approval</p>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="col-md-3">
                        <a href="admin_refund.php" class="text-decoration-none">
                            <div class="card text-white bg-secondary mb-3"> 
                                <div class="card-header">คำขอคืนเงิน</div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $totalRefundRequests; ?></h5>
                                    <p class="card-text">Refund Requests Pending</p>
                                </div>
                            </div>
                        </a>
                    </div>



                    <div class="col-md-3">
                        <a href="admin_complaint.php" class="text-decoration-none">
                            <div class="card text-white bg-danger mb-3">
                                <div class="card-header">สินค้าที่ถูกรายงาน</div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $reportedProductsData['total_reported']; ?></h5>
                                    <p class="card-text">Times Products Reported</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- สินค้าขายดี -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">สินค้าขายดี</h1>
                </div>
                <div class="row">
                    <?php foreach ($topProducts as $index => $product): ?>
                        <div class="col-md-3">
                            <div class="card text-white mb-3" style="background-color: <?php echo $rankColors[$index % 3]; ?>;">
                                <div class="card-header">อันดับที่ <?php echo $index + 1; ?></div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($product['product_name']); ?></h5>
                                    <p class="card-text"><?php echo $product['sales']; ?> ครั้งที่ถูกซื้อ</p>
                                    <p class="card-text">หมวดหมู่: <?php echo $product['category_name']; ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <!-- ปุ่มดูสินค้าขายดีทั้งหมด -->
                    <div class="col-md-12 text-center">
                        <a href="sales_chart.php" class="btn btn-primary">ดูสินค้าขายดีทั้งหมด</a>
                    </div>
                </div>

                <!-- หมวดหมู่ขายดี -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">หมวดหมู่ขายดี</h1>
                </div>
                <div class="row">
                    <?php foreach ($topCategories as $index => $category): ?>
                        <div class="col-md-3">
                            <div class="card text-white mb-3" style="background-color: <?php echo $rankColors[$index % 3]; ?>;">
                                <div class="card-header">หมวดหมู่อันดับที่ <?php echo $index + 1; ?></div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($category['category_name']); ?></h5>
                                    <p class="card-text"><?php echo $category['sales']; ?> ครั้งที่ถูกซื้อ</p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <!-- ปุ่มดูหมวดหมู่ที่ขายดีทั้งหมด -->
                    <div class="col-md-12 text-center">
                        <a href="sales_chart.php" class="btn btn-primary">ดูหมวดหมู่ที่ขายดีทั้งหมด</a>
                    </div>

                     <!-- brandขายดี -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">แบรนด์ขายดี</h1>
                </div>
                <div class="row">
                    <?php foreach ($topbrand as $index => $bestbrand): ?>
                        <div class="col-md-3">
                            <div class="card text-white mb-3" style="background-color: <?php echo $rankColors[$index % 3]; ?>;">
                                <div class="card-header">หมวดหมู่อันดับที่ <?php echo $index + 1; ?></div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($bestbrand['brand_name']); ?></h5>
                                    <p class="card-text"><?php echo $bestbrand['sales']; ?> ครั้งที่ถูกซื้อ</p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <!-- ปุ่มดูแบรนด์ที่ขายดีทั้งหมด -->
                    <div class="col-md-12 text-center">
                        <a href="sales_chart.php" class="btn btn-primary">ดูแบรนด์ที่ขายดีทั้งหมด</a>
                    </div>
                </div>
            </main>
        </div>
    </div>

</body>

</html>