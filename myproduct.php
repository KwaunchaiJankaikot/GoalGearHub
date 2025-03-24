<?php
include_once('class/UserLogin.php');
include_once('header.php'); // รวมไฟล์ header.php เพื่อแสดงส่วนหัวของหน้าเว็บ
include_once('config/Database.php'); // รวมไฟล์สำหรับเชื่อมต่อฐานข้อมูล
include_once('nav.php'); // รวมไฟล์ nav.php เพื่อแสดงเมนูนำทาง
include_once('filter.php');
if (session_status() == PHP_SESSION_NONE) {
    session_start(); // เริ่มเซสชันถ้ายังไม่มีการเริ่มต้น
}

$db = new Database(); // สร้างอินสแตนซ์ของ Database เพื่อเชื่อมต่อฐานข้อมูล
$conn = $db->getConnection(); // ดึงการเชื่อมต่อฐานข้อมูล

$userLogin = new UserLogin($conn); // สร้างอินสแตนซ์ของ UserLogin

// ตรวจสอบว่าใน URL มี `user_id` หรือไม่
if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id']; // ใช้ user_id ที่ส่งมาจาก URL
    $seller_name = $userLogin->getSellerNameByUserId($user_id); // ดึงชื่อผู้ขายจาก user_id
} else {
    $user_id = $_SESSION['user_id']; // ใช้ user_id ของผู้ใช้ที่ล็อกอินอยู่
    $seller_name = $userLogin->getSellerNameByUserId($user_id); // ดึงชื่อผู้ขายจาก user_id ของผู้ใช้ที่ล็อกอิน
}

// ตรวจสอบว่ามีการคลิกปุ่มค้นหาหรือไม่เพื่อใช้ตัวกรอง
$filterApplied = (isset($_GET['category']) && $_GET['category'] != '') ||
    (isset($_GET['size']) && $_GET['size'] != '') ||
    (isset($_GET['brand']) && $_GET['brand'] != '');

// รับค่าฟิลเตอร์จากฟอร์ม
$category = isset($_GET['category']) ? $_GET['category'] : '';
$size = isset($_GET['size']) ? $_GET['size'] : '';
$product_brand = isset($_GET['brand']) ? $_GET['brand'] : '';

// สร้างคำสั่ง SQL สำหรับค้นหาสินค้าตามฟิลเตอร์หรือดึงสินค้าทั้งหมดของผู้ใช้ที่ระบุ
$query = "SELECT * FROM products WHERE user_id = :user_id"; // แสดงเฉพาะสินค้าของผู้ใช้ที่ระบุ
if ($filterApplied) {
    if ($category != '') {
        $query .= " AND category = :category";
    }
    if ($size != '') {
        $query .= " AND FIND_IN_SET(:size, product_sizes)";
    }
    if ($product_brand != '') {
        $query .= " AND product_brand = :product_brand";
    }
}

$stmt = $conn->prepare($query); // เตรียมคำสั่ง SQL สำหรับการดำเนินการ
$stmt->bindParam(':user_id', $user_id); // ผูกค่า user_id กับคำสั่ง SQL

// ผูกค่าฟิลเตอร์กับคำสั่ง SQL หากมีการกรองข้อมูล
if ($filterApplied) {
    if ($category != '') {
        $stmt->bindParam(':category', $category);
    }
    if ($size != '') {
        $stmt->bindParam(':size', $size);
    }
    if ($product_brand != '') {
        $stmt->bindParam(':product_brand', $product_brand);
    }
}

$stmt->execute(); // ดำเนินการคำสั่ง SQL
$products = $stmt->fetchAll(PDO::FETCH_ASSOC); // ดึงข้อมูลสินค้าจากฐานข้อมูล

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Filter Products</title>
    <link rel="stylesheet" href="styles.css"> <!-- ปรับตามสไตล์ของคุณ -->
    <style>
        /* สไตล์เพิ่มเติม */
        body {
            font-family: Arial, sans-serif;
        }

        .content {
            margin-left: 270px;
            padding: 20px;
            flex-grow: 1;
            width: calc(100% - 270px);
        }

        .product-list {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            
        }

        .product-item {
            background-color: #343a40;
            border-radius: 5px;
            color: #fff;
            padding: 10px;
            width: 200px;
            /* text-align: center; */
            position: relative;
            border: 1px solid transparent;
            transition: border-color 0.3s ease-in-out;
            /* border: 1px solid red; */

        }

        .product-item:hover {
            border-color: #ffa500;
            box-shadow: 0 0 10px rgba(255, 165, 0, 0.5);
        }

        .product-item img {
            width: 100%;
            height: 180px;
            object-fit: fill;
            border-radius: 5px;
            margin-bottom: 10px;
        }

        /* .product-item h5 {
            font-size: 16px;
            margin: 0;
            color: #ffc107;
        } */
        .detail {
            text-align: left;
        }

        .name {
            font-size: 16px;
            margin: 0;
            color: #ffc107;
        }

        .brand {
            font-size: 16px;
            margin: 0;
            color: white;
        }

        .price {
            margin: 0px;
            font-size: 20px;
            color: #ffc107;
        }
        /* .price {
            margin-top: 10px;
            font-size: 20px;
            color: #ffa500;
        
        } */

        .product-item a {
            text-decoration: none;
            color: inherit;
        }

        .headerfilter {
            width: 40%;
            display: flex;
            margin-left: 30%;
            
        }
    </style>
</head>

<body>
    <div class="content">
        <h1 class="headerfilter">สินค้าของ <?php echo htmlspecialchars($seller_name); ?></h1> <!-- แสดงชื่อผู้ขาย -->
        <div class="product-list">
            <?php if (!empty($products)) : ?>
                <?php foreach ($products as $product) : ?>
                    <div class="product-item">
                        <a href="product_detail.php?product_id=<?php echo htmlspecialchars($product['product_id']); ?>">
                            <?php
                            $images = explode(',', str_replace(['[', ']', '"'], '', $product['product_picture']));
                            if ($images && isset($images[0])) {
                                echo '<img src="uploads/' . htmlspecialchars(trim($images[0])) . '" alt="Product Image">';
                            }
                            ?>
                            <div class="detail"> 
                            <p class="brand"><?php echo htmlspecialchars($product['product_brand']); ?></p>
                            <p class="name"><?php echo htmlspecialchars($product['product_name']); ?></p>
                            <p class="price">฿<?php echo htmlspecialchars($product['product_price']); ?></p>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <p>No products available.</p> <!-- แสดงข้อความถ้าไม่มีสินค้า -->
            <?php endif; ?>
        </div>
    </div>
</body>

</html>
