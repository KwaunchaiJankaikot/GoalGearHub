<?php
// เริ่มต้นการทำงานด้วยการดึงข้อมูลยอดขายจากฐานข้อมูล
include_once('../config/Database.php');
include_once('header.php');
// include_once('admin_sidebar.php');


$db = new Database();
$conn = $db->getConnection();

// ตรวจสอบว่ากำลังแสดงยอดขายรายวัน, รายเดือน หรือรายปี
$type = isset($_GET['type']) ? $_GET['type'] : 'month';

switch ($type) {
    case 'day':
        // ดึงยอดขายตามวัน
        $query = "SELECT DATE(Order_Date) as date, SUM(final_price) as total_sales
                  FROM orders
                  WHERE Order_Status = 'shipped'
                  GROUP BY DATE(Order_Date)
                  ORDER BY Order_Date";
        break;
    case 'year':
        // ดึงยอดขายตามปี
        $query = "SELECT YEAR(Order_Date) as year, SUM(final_price) as total_sales
                  FROM orders
                  WHERE Order_Status = 'shipped'
                  GROUP BY YEAR(Order_Date)
                  ORDER BY YEAR(Order_Date)";
        break;
    case 'month':
    default:
        // ดึงยอดขายตามเดือน
        $query = "SELECT MONTH(Order_Date) as month, SUM(final_price) as total_sales
                  FROM orders
                  WHERE Order_Status = 'shipped'
                  GROUP BY MONTH(Order_Date)";
        break;
}

$stmt = $conn->prepare($query);
$stmt->execute();
$salesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// กำหนด array สำหรับเก็บยอดขาย
if ($type == 'day') {
    $sales = [];
    $labels = [];
    foreach ($salesData as $data) {
        $sales[] = (float)$data['total_sales'];
        $labels[] = $data['date']; // เก็บวันที่สำหรับกราฟ
    }
} elseif ($type == 'year') {
    $sales = array_fill(0, 8, 0); // สร้างอาร์เรย์ที่มีค่าเริ่มต้น 0 สำหรับ 8 ปี
    $currentYear = (int)date("Y"); // ปีปัจจุบัน
    $labels = [];
    for ($i = 7; $i >= 0; $i--) {
        $labels[] = $currentYear - $i; // ใส่ label สำหรับ 8 ปีล่าสุด
    }

    foreach ($salesData as $data) {
        $year = (int)$data['year'];
        $index = array_search($year, $labels);
        if ($index !== false) {
            $sales[$index] = (float)$data['total_sales'];
        }
    }
} else {
    $sales = array_fill(1, 12, 0); // สร้างอาร์เรย์ที่มีค่าเริ่มต้น 0 สำหรับ 12 เดือน
    $labels = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    foreach ($salesData as $data) {
        $sales[(int)$data['month']] = (float)$data['total_sales'];
    }
}
// คำนวณยอดขายทั้งหมด
$totalSales = array_sum(array_column($salesData, 'total_sales'));

// ดึงข้อมูลหมวดหมู่ทั้งหมดรวมทั้งที่ขายไม่ได้
$categoryQuery = "SELECT c.name AS category_name, COALESCE(SUM(o.Order_Quantity), 0) AS total_quantity_sold
                  FROM categories c
                  LEFT JOIN products p ON c.id = p.category
                  LEFT JOIN orders o ON p.product_id = o.product_id AND o.Order_Status = 'shipped'
                  GROUP BY c.name
                  ORDER BY total_quantity_sold DESC";
$stmt = $conn->prepare($categoryQuery);
$stmt->execute();
$soldCategoryData = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categoryLabels = [];
$categoryCounts = [];
foreach ($soldCategoryData as $category) {
    $categoryLabels[] = $category['category_name'];
    $categoryCounts[] = (int)$category['total_quantity_sold'];
}

// ดึงข้อมูลแบรนด์ทั้งหมดรวมทั้งที่ขายไม่ได้
$brandQuery = "SELECT p.product_brand AS brand_name, COALESCE(SUM(o.Order_Quantity), 0) AS total_quantity_sold
               FROM products p
               LEFT JOIN orders o ON p.product_id = o.product_id AND o.Order_Status = 'shipped'
               GROUP BY p.product_brand
               ORDER BY total_quantity_sold DESC";
$stmt = $conn->prepare($brandQuery);
$stmt->execute();
$soldBrandData = $stmt->fetchAll(PDO::FETCH_ASSOC);

$brandLabels = [];
$brandCounts = [];
foreach ($soldBrandData as $brand) {
    $brandLabels[] = $brand['brand_name'];
    $brandCounts[] = (int)$brand['total_quantity_sold'];
}



// ดึงข้อมูลสินค้าจากฐานข้อมูล
$allSellingProductsQuery = "SELECT p.product_name, p.product_price, p.product_quantity, u.name AS seller_name, SUM(o.Order_Quantity) AS total_quantity_sold
                            FROM orders o
                            JOIN products p ON o.product_id = p.product_id
                            JOIN users u ON p.user_id = u.id
                            WHERE o.Order_Status = 'shipped'
                            GROUP BY p.product_id
                            ORDER BY total_quantity_sold DESC";

$allSellingProductsStmt = $conn->prepare($allSellingProductsQuery);
$allSellingProductsStmt->execute();
$allSellingProducts = $allSellingProductsStmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลหมวดหมู่ที่ขายดีที่สุด
$query = "
    SELECT c.name AS category_name, SUM(o.Order_Quantity) AS sales
    FROM orders o
    JOIN products p ON o.product_id = p.product_id
    JOIN categories c ON p.category = c.id
    WHERE o.order_status = 'shipped'
    GROUP BY c.name
    ORDER BY sales DESC";


$stmt = $conn->prepare($query);
$stmt->execute();
$bestSellingCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลแบรนด์ที่ขายดีที่สุด
$bestSellingBrandsQuery = "
    SELECT p.product_brand AS brand_name, SUM(o.Order_Quantity) AS sales
    FROM orders o
    JOIN products p ON o.product_id = p.product_id
    WHERE o.Order_Status = 'shipped'
    GROUP BY p.product_brand
    ORDER BY sales DESC";

$bestSellingBrandsStmt = $conn->prepare($bestSellingBrandsQuery);
$bestSellingBrandsStmt->execute();
$bestSellingBrands = $bestSellingBrandsStmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงยอดรวมรายรับทั้งหมดจากฐานข้อมูล
$totalSalesQuery = "
    SELECT SUM(final_price) AS total_sales
    FROM orders
    WHERE Order_Status = 'shipped'
";
$stmtTotal = $conn->prepare($totalSalesQuery);
$stmtTotal->execute();
$totalSales = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total_sales'];
// <span>รายรับทั้งหมด: ฿<?php echo number_format($totalSales, 2);

// ดึงข้อมูลสินค้าพร้อมค่าคอมมิชชั่นรวมตามสินค้า
$productsCommissionQuery = "
    SELECT p.product_name, p.product_price, SUM(o.Order_Quantity) AS total_quantity_sold, SUM(o.commission_amount) AS total_commission
    FROM orders o
    JOIN products p ON o.product_id = p.product_id
    WHERE o.Order_Status = 'shipped' AND o.commission_amount > 0
    GROUP BY p.product_name, p.product_price
    ORDER BY p.product_name
";
$stmt = $conn->prepare($productsCommissionQuery);
$stmt->execute();
$productsCommissionData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// คำนวณค่าคอมมิชชั่นรวมทั้งหมด
$totalCommission = 0;
foreach ($productsCommissionData as $product) {
    $totalCommission += $product['total_commission'];
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>กราฟยอดขาย</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background-color: #2c3e50;
            color: #ecf0f1;
            font-family: Arial, sans-serif;
        }

        .container {
            max-width: 70%;
            /* ปรับจาก 1200px เป็น 70% ของหน้าจอ */
            margin: 20px auto;
            background-color: #34495e;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            /* border: 1px solid red; */
            /* margin-top: -630px; */
            /* margin-left: 300px; */

        }


        .chart-container {
            max-width: 50%;
            /* ปรับจาก 800px เป็น 70% ของหน้าจอ */
            margin: 20px auto;

        }

        canvas {
            max-width: 100%;
        }

        h1,
        h2 {
            text-align: center;
            color: #ecf0f1;
        }

        .btn-container {
            text-align: center;
            margin-bottom: 30px;
        }

        button {
            padding: 10px 20px;
            margin: 0 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            background-color: #3498db;
            color: white;
            font-size: 16px;
        }

        button.active {
            background-color: #2980b9;
        }

        button:hover {
            background-color: #2980b9;
        }

        .styled-table {
            width: 100%;
            border-collapse: collapse;
            margin: 25px 0;
            font-size: 18px;
            text-align: center;
            border-radius: 5px 5px 0 0;
            overflow: hidden;
        }

        .styled-table thead tr {
            background-color: #3498db;
            color: #ffffff;
            text-align: center;
            font-weight: bold;
        }

        .styled-table th,
        .styled-table td {
            padding: 12px 15px;
            border: 1px solid #dddddd;
        }

        .styled-table tbody tr {
            background-color: #2c3e50;
            /* กำหนดให้ทุกบรรทัดมีสีเดียวกัน */
            color: #ffffff;
            /* กำหนดสีตัวหนังสือ */
        }

        img {
            border-radius: 5px;
        }
    </style>
</head>

<body>
    <div class="allchart">

        <div class="container">
            <h1>กราฟยอดขาย</h1>

            <div class="btn-container">
                <button class="<?php echo ($type == 'day') ? 'active' : ''; ?>" onclick="window.location.href='?type=day'">รายวัน</button>
                <button class="<?php echo ($type == 'month') ? 'active' : ''; ?>" onclick="window.location.href='?type=month'">รายเดือน</button>
                <button class="<?php echo ($type == 'year') ? 'active' : ''; ?>" onclick="window.location.href='?type=year'">รายปี</button>
            </div>

            <div class="chart-container">
                <canvas id="salesChart"></canvas>

            </div>
        </div>

        <!-- Pie Chart สำหรับหมวดหมู่ -->
        <div class="container">
            <h2>จำนวนสินค้าตามหมวดหมู่</h2>
            <div class="chart-container">
                <canvas id="categorySalesChart"></canvas>
            </div>
        </div>

        <!-- Pie Chart สำหรับแบรนด์ -->
        <div class="container">
            <h2>จำนวนสินค้าตามแบรนด์</h2>
            <div class="chart-container">
                <canvas id="brandSalesChart"></canvas>
            </div>
        </div>

        <div class="container">
            <h2>สินค้าที่ขายได้ทั้งหมด</h2>
            <table class="styled-table">
                <thead>
                    <tr>
                        <!-- ไม่ต้องแสดงรูปภาพ -->
                        <!-- <th>รูปภาพ</th> -->
                        <th>ชื่อสินค้า</th>
                        <th>ราคาต่อชิ้น</th>
                        <th>จำนวนที่ขายได้</th>
                        <th>จำนวนคงเหลือ</th>
                        <th>ชื่อคนขาย</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($allSellingProducts as $product) {
                        // ไม่ต้องสร้างเส้นทางรูปภาพแล้ว
                        echo "<tr>";
                        // ไม่ต้องแสดงรูปภาพ
                        // echo "<td><img src='" . $imagePath . "' alt='Product Image' width='100' height='100'></td>";
                        echo "<td>" . htmlspecialchars($product['product_name']) . "</td>";
                        echo "<td>฿" . number_format($product['product_price'], 2) . "</td>";
                        echo "<td>" . htmlspecialchars($product['total_quantity_sold']) . "</td>";
                        echo "<td>" . htmlspecialchars($product['product_quantity']) . "</td>";
                        echo "<td>" . htmlspecialchars($product['seller_name']) . "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>

        </div>
        <div class="container">
            <h1>หมวดหมู่ขายดี</h1>
            <table class="styled-table">
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
        </div>
        <div class="container">
            <h1>แบรนด์ที่ขายดี</h1>
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>ชื่อแบรนด์</th>
                        <th>จำนวนสินค้าที่ขายได้</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bestSellingBrands as $brand): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($brand['brand_name']); ?></td>
                            <td><?php echo $brand['sales']; ?> ครั้ง</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="container">
    <h2>คอมมิชชั่นของสินค้าทั้งหมด</h2>
    <table class="styled-table">
        <thead>
            <tr>
                <th>ชื่อสินค้า</th>
                <th>ราคาต่อชิ้น</th>
                <th>จำนวนที่ขายได้</th>
                <th>ค่าคอมมิชชั่น</th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($productsCommissionData as $product) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($product['product_name']) . "</td>";
                echo "<td>฿" . number_format($product['product_price'], 2) . "</td>";
                echo "<td>" . htmlspecialchars($product['total_quantity_sold']) . "</td>";
                echo "<td>฿" . number_format($product['total_commission'], 2) . "</td>";
                echo "</tr>";
            }
            ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" style="text-align:right">ค่าคอมมิชชั่นรวมทั้งหมด:</th>
                <th>฿<?php echo number_format($totalCommission, 2); ?></th>
            </tr>
        </tfoot>
    </table>
</div>
        <script>
            // นำข้อมูลยอดขายจาก PHP มาใช้ใน JavaScript สำหรับกราฟเส้น
            const sales = <?php echo json_encode(array_values($sales)); ?>;
            const labels = <?php echo json_encode($labels); ?>;

            // นำข้อมูลหมวดหมู่และจำนวนสินค้าที่ขายไม่ได้จาก PHP มาใช้ใน JS สำหรับกราฟ Pie
            const categoryLabels = <?php echo json_encode($categoryLabels); ?>;
            const categoryCounts = <?php echo json_encode($categoryCounts); ?>;

            // นำข้อมูลแบรนด์และจำนวนสินค้าที่ขายไม่ได้จาก PHP มาใช้ใน JS สำหรับกราฟ Pie
            const brandLabels = <?php echo json_encode($brandLabels); ?>;
            const brandCounts = <?php echo json_encode($brandCounts); ?>;

            // สร้างกราฟ Line สำหรับยอดขายรายวัน รายเดือน และรายปี
            const ctx = document.getElementById('salesChart').getContext('2d');
            const chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'ยอดขาย',
                        data: sales,
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderWidth: 1
                    }]
                },
                options: {

                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: '#ffffff' // เปลี่ยนสีตัวหนังสือบนแกน y ให้เป็นสีขาว
                            },
                            // grid: {
                            //     color: '#DDDDDD' // เปลี่ยนสีเส้นกริดแนวตั้งให้สว่างขึ้น
                            // }
                        },
                        x: {
                            ticks: {
                                color: '#ffffff' // เปลี่ยนสีตัวหนังสือบนแกน x ให้เป็นสีขาว
                            },
                            // grid: {
                            //     color: '#DDDDDD' // เปลี่ยนสีเส้นกริดแนวตั้งให้สว่างขึ้น
                            // }
                        }
                    },
                    plugins: {
                        legend: {
                            labels: {
                                color: '#FFFFFF', // เปลี่ยนสีตัวหนังสือใน legend ให้เป็นสีขาว
                                font: {
                                    size: 12 // ปรับขนาดตัวหนังสือใน legend

                                }
                            }
                        }
                    }
                }

            });
            // สร้าง Pie Chart สำหรับหมวดหมู่
            const categoryCtx = document.getElementById('categorySalesChart').getContext('2d');
            const categoryChart = new Chart(categoryCtx, {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode($categoryLabels); ?>,
                    datasets: [{
                        label: 'จำนวนสินค้าตามหมวดหมู่',
                        data: <?php echo json_encode($categoryCounts); ?>,
                        backgroundColor: ['lightcoral', 'lightblue', 'lightyellow', 'lightgreen', 'lightpink', 'lightgray'],
                        borderWidth: 1
                    }]
                },
                options: {
                    plugins: {
                        legend: {
                            labels: {
                                color: '#ffffff', // เปลี่ยนสีตัวหนังสือใน legend เป็นสีขาว
                                font: {
                                    size: 14 // ขนาดของตัวอักษรใน legend
                                }
                            }
                        }
                    }
                }
            });

            // สร้าง Pie Chart สำหรับแบรนด์
            const brandCtx = document.getElementById('brandSalesChart').getContext('2d');
            const brandChart = new Chart(brandCtx, {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode($brandLabels); ?>,
                    datasets: [{
                        label: 'จำนวนสินค้าตามแบรนด์',
                        data: <?php echo json_encode($brandCounts); ?>,
                        backgroundColor: ['lightcoral', 'lightblue', 'lightyellow', 'lightgreen', 'lightpink', 'lightgray'],
                        borderWidth: 1
                    }]
                },
                options: {
                    plugins: {
                        legend: {
                            labels: {
                                color: '#ffffff', // เปลี่ยนสีตัวหนังสือใน legend เป็นสีขาว
                                font: {
                                    size: 14 // ขนาดของตัวอักษรใน legend
                                }
                            }
                        }
                    }
                }
            });
        </script>
        <div style="text-align: center;">
            <a href="admin_dashboard.php" class="btn btn-danger">กลับหน้าแรก</a>
        </div>

</body>

</html>