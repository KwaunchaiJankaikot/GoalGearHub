<?php
include_once('header.php');
include_once('config/Database.php');
// include_once('product.php');
include_once('nav.php');
include_once('filter.php');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// // ตรวจสอบว่าเซสชันมีการเก็บข้อมูลผู้ใช้หรือไม่
// if (!isset($_SESSION['user_id'])) {
//     // หากไม่มีเซสชัน ให้ตรวจสอบคุกกี้
//     if (isset($_COOKIE['email']) && isset($_COOKIE['password'])) {
//         // ถอดรหัสคุกกี้
//         $email = base64_decode($_COOKIE['email']);
//         $password = base64_decode($_COOKIE['password']);

//         // ตรวจสอบรหัสผ่านในฐานข้อมูลอีกครั้ง
//         $connectDB = new Database();
//         $db = $connectDB->getConnection();

//         $user = new UserLogin($db);
//         $user->setEmail($email);
//         $user->setPassword($password);

//         if ($user->verifyPassword()) {
//             $userId = $user->getUserId($email);

//             // ตั้งค่าเซสชันใหม่
//             $_SESSION['user_id'] = $userId;
//             $_SESSION['email'] = $email;
//             $_SESSION['name'] = $name; // เก็บอีเมลในเซสชัน
//         } else {
//             // หากรหัสผ่านไม่ถูกต้อง ให้เปลี่ยนเส้นทางไปยังหน้าเข้าสู่ระบบ
//             header("Location: signin.php");
//             exit;
//         }
//     } else {
//         // หากไม่มีทั้งเซสชันและคุกกี้ ให้เปลี่ยนเส้นทางไปยังหน้าเข้าสู่ระบบ
//         header("Location: signin.php");
//         exit;
//     }
// }

// ดึงข้อมูลสินค้าจากฐานข้อมูล
$connectDB = new Database();
$db = $connectDB->getConnection();
$query = "SELECT * FROM products";
$stmt = $db->prepare($query);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .centered {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 20vh;
        text-align: center;
    }
    .product-list {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-around;
        margin: 20px;
    }
    .product-item {
        border: 1px solid #fff333;
        padding: 20px;
        margin: 10px;
        width: 200px;
        text-align: center;
        
    }
    .product-item img {
        max-width: 100%;
        height: auto;
    }
</style>
<!-- 
<div class="centered">
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h1>
</div> -->

<div class="product-list">
    <?php foreach ($products as $product): ?>
        <div class="product-item">
            <h2><?php echo htmlspecialchars($product['product_name']); ?></h2>
            <p>Brand: <?php echo htmlspecialchars($product['product_brand']); ?></p>
            <p>Price: $<?php echo htmlspecialchars($product['product_price']); ?></p>
            <p>Status: <?php echo htmlspecialchars($product['product_status']); ?></p>
            <p>Quantity: <?php echo htmlspecialchars($product['product_quantity']); ?></p>
            <?php 
                $pictures = explode(',', $product['product_picture']);
                foreach ($pictures as $picture):
                    // ตรวจสอบให้แน่ใจว่ารูปภาพมีอยู่จริง
                    if (file_exists('img/' . $picture)): // เปลี่ยนจาก "../img/" เป็น "img/"
            ?>
                <img src="img/<?php echo htmlspecialchars($picture); ?>" alt="Product Image">
            <?php 
                    else:
                        echo "<p>Image not found: " . htmlspecialchars($picture) . "</p>";
                    endif;
                endforeach; 
            ?>
        </div>
    <?php endforeach; ?>
</div>

<?php include_once('footer.php'); ?>
