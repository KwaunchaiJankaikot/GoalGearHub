<?php
include_once('header.php');
include_once('config/Database.php');
include_once('nav.php');
include_once('filter.php');  // เรียกใช้ filter.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// เชื่อมต่อกับฐานข้อมูล
$db = new Database();
$conn = $db->getConnection();

// รับค่าตัวกรองจากฟอร์ม
$categories = isset($_GET['category']) ? $_GET['category'] : [];
$sizes = isset($_GET['size']) ? $_GET['size'] : [];
$product_brands = isset($_GET['brand']) ? $_GET['brand'] : [];
$teams = isset($_GET['team']) ? $_GET['team'] : [];

// รับคำค้นหาจากฟอร์ม
$searchQuery = isset($_GET['query']) ? htmlspecialchars($_GET['query']) : '';

// เริ่มสร้าง SQL โดยรวมทั้งคำค้นหาและตัวกรอง
$sql = "SELECT * FROM products WHERE approved = 1";  // ตรวจสอบว่า products ผ่านการอนุมัติแล้ว
$params = [];

// ถ้ามีคำค้นหา
if (!empty($searchQuery)) {
    $sql .= " AND (product_name LIKE :searchQuery OR product_brand LIKE :searchQuery OR team_name LIKE :searchQuery)";
    $params[':searchQuery'] = '%' . $searchQuery . '%';
}

// ตรวจสอบเงื่อนไขตัวกรองหมวดหมู่
if (!empty($categories)) {
    $sql .= " AND category IN (" . implode(',', array_fill(0, count($categories), '?')) . ")";
    $params = array_merge($params, $categories);
}

// ตรวจสอบเงื่อนไขตัวกรองขนาด
if (!empty($sizes)) {
    $sql .= " AND FIND_IN_SET(?, product_sizes)";
    $params[] = implode(',', $sizes);  // FIND_IN_SET ใช้สำหรับการค้นหา size
}

// ตรวจสอบเงื่อนไขตัวกรองยี่ห้อ
if (!empty($product_brands)) {
    $sql .= " AND product_brand IN (" . implode(',', array_fill(0, count($product_brands), '?')) . ")";
    $params = array_merge($params, $product_brands);
}

// ตรวจสอบเงื่อนไขตัวกรองทีม
if (!empty($teams)) {
    $sql .= " AND team_name IN (" . implode(',', array_fill(0, count($teams), '?')) . ")";
    $params = array_merge($params, $teams);
}

// เตรียมและรันคำสั่ง SQL
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $user_id = $_SESSION['user_id'];
    $quantity = 1; // ปริมาณเริ่มต้นเป็น 1

    // ตรวจสอบว่าสินค้ามีอยู่ในตะกร้าแล้วหรือไม่
    $query = "SELECT * FROM cart WHERE user_id = :user_id AND product_id = :product_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':product_id', $product_id);
    $stmt->execute();
    $existingItem = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingItem) {
        // ตรวจสอบว่าจำนวนสินค้าในตะกร้าเกิน 30 หรือไม่
        if ($existingItem['quantity'] < 30) {
            // ถ้ามีสินค้าอยู่แล้ว และไม่เกิน 30 ชิ้น ให้เพิ่มจำนวน
            $newQuantity = $existingItem['quantity'] + 1;
            $updateQuery = "UPDATE cart SET quantity = :quantity WHERE user_id = :user_id AND product_id = :product_id";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bindParam(':quantity', $newQuantity);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':product_id', $product_id);
            $stmt->execute();
        } else {
            // แจ้งเตือนว่าจำนวนสินค้าเกิน 30 ชิ้น
            echo "<script>alert('คุณไม่สามารถเพิ่มสินค้ามากกว่า 30 ชิ้นในตะกร้าได้');</script>";
        }
    } else {
        // ถ้าไม่มีสินค้า เพิ่มลงตะกร้า
        $insertQuery = "INSERT INTO cart (user_id, product_id, quantity) VALUES (:user_id, :product_id, :quantity)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->execute();
    }
}
// ค้นหาข้อมูลหมวดหมู่
function getCategoryName($categoryId, $conn)
{
    $query = "SELECT name FROM categories WHERE id = :category_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':category_id', $categoryId);
    $stmt->execute();
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    return $category ? $category['name'] : 'ไม่ทราบหมวดหมู่';
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Search Results</title>
    <style>
        body {
            background-color: #4a4c4c;
            color: #fff;
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
            padding: 20px;
            /* justify-content: center; */
            margin-left: 270px;
            /* เพิ่ม margin-left เพื่อให้ไม่ทับกับ sidebar */
        }

        .product-item {
            background-color: #343a40;
            border-radius: 5px;
            color: #fff;
            padding: 10px;
            width: 200px;
            text-align: center;
            position: relative;
            border: 1px solid transparent;
            transition: border-color 0.3s ease-in-out;
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

        .product-item a {
            text-decoration: none;
            color: inherit;
        }

        .notfound {
            text-align: center;
        }

        .textname {
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

        .cart-icon {
            background-color: transparent;
            border: none;
            cursor: pointer;
            color: #28a745;
            font-size: 20px;
            margin-top: 10px;
            transition: color 0.3s;
        }

        h1 {
            text-align: center;
            margin-left: 290px;
            width:75%;
            margin-top: 20px;
            /* border: 1px solid red; */
            /* position: relative; */
        }
    </style>
</head>

<body>
<h1 class="searchresult">
    <?php 
    if (empty($categories) && empty($sizes) && empty($product_brands) && empty($teams) && empty($searchQuery)) {
        echo 'รายการสินค้าทั้งหมด';
    } else {
        $searchText = 'สินค้าที่ค้นหา "';

        if (!empty($searchQuery)) {
            $searchText .= '' . $searchQuery;
        }

        if (!empty($categories)) {
            if (!empty($searchQuery)) $searchText .= ' | ';
            $categoryNames = array_map(function ($categoryId) use ($conn) {
                return getCategoryName($categoryId, $conn);
            }, $categories);
            $searchText .= '' . implode(', ', $categoryNames);
        }

        if (!empty($sizes)) {
            if (!empty($categories) || !empty($searchQuery)) $searchText .= ' | ';
            $searchText .= '' . implode(', ', $sizes);
        }

        if (!empty($product_brands)) {
            if (!empty($categories) || !empty($sizes) || !empty($searchQuery)) $searchText .= ' | ';
            $searchText .= '' . implode(', ', $product_brands);
        }

        if (!empty($teams)) {
            if (!empty($categories) || !empty($sizes) || !empty($product_brands) || !empty($searchQuery)) $searchText .= ' | ';
            $searchText .= '' . implode(', ', $teams);
        }

        $searchText .= '"';
        echo $searchText;
    }
    ?>
</h1>

    <div class="product-list">
        <?php if (!empty($results)) : ?>
            <?php foreach ($results as $product) : ?>
                <div class="product-item">
                    <a href="product_detail.php?product_id=<?php echo htmlspecialchars($product['product_id']); ?>">
                        <?php
                        // โค้ดแสดงรูปภาพ
                        $images = explode(',', str_replace(['[', ']', '"'], '', $product['product_picture']));
                        if ($images && isset($images[0])) {
                            $imagePath = 'uploads/' . htmlspecialchars(trim($images[0]));
                            echo '<img src="' . $imagePath . '" alt="Product Image">';
                        }
                        ?>
                        <div class="textname">
                            <p class="brand"><?php echo htmlspecialchars($product['product_brand']); ?></p>
                            <p class="name"><?php echo htmlspecialchars($product['product_name']); ?></p>
                            <p class="price">฿<?php echo htmlspecialchars($product['product_price']); ?></p>
                        </div>
                    </a>
                    <!-- <form method="POST" action="">
                        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['product_id']); ?>">
                        <button type="submit" name="add_to_cart" class="cart-icon">🛒</button>
                    </form> -->
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <p class="notfound">No products found.</p>
        <?php endif; ?>
    </div>
</body>

<script>
    function showAlert(message) {
        alert(message);
    }

    <?php if (isset($_POST['add_to_cart'])) : ?>
        showAlert('เพิ่มสินค้าลงตะกร้าสำเร็จ');
    <?php endif; ?>
</script>

</html>