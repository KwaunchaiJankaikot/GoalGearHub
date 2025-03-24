<?php
ob_start();
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include_once('config/Database.php');
include_once('header.php');
include_once('welcome.php');

$db = new Database();
$conn = $db->getConnection();

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('กรุณาเข้าสู่ระบบก่อนดูตะกร้าสินค้า'); window.location.href='signin.php';</script>";
    exit();
}

// ฟังก์ชันสำหรับคำนวณราคารวมในตะกร้า
function calculateTotalPrice($cart)
{
    $total = 0;
    foreach ($cart as $item) {
        $total += $item['product_price'] * $item['quantity'];
    }
    return $total;
}

// ฟังก์ชันสำหรับลบสินค้าจากตะกร้า
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item'])) {
    $product_id_to_remove = $_POST['product_id'];

    $deleteQuery = "DELETE FROM cart WHERE user_id = :user_id AND product_id = :product_id";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->bindParam(':product_id', $product_id_to_remove);
    $stmt->execute();

    header("Location: cart.php");
    exit();
}

// ดึงข้อมูลสินค้าจากตะกร้าของผู้ใช้
$user_id = $_SESSION['user_id'];
$query = "SELECT p.product_id, p.product_name, p.product_price, p.product_picture, c.quantity, c.size 
         FROM cart c 
         JOIN products p ON c.product_id = p.product_id 
         WHERE c.user_id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// นับจำนวนสินค้าที่มีในตะกร้า
$cartItemCount = count($cartItems);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Shopping Cart</title>
</head>
<style>
    .cart-items-list {
        list-style-type: none;
        padding: 0;
        margin: 0;

    }

    .cart-items-list li {
        padding: 15px 0;
        /* border-bottom: 1px solid #ccc; */
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        width: 100%;
        margin-left: -00px;
        background-color: #f9f9f9;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border-radius: 5px;
        overflow: hidden;
        /* ป้องกันการหลุดออกนอก container */

    }

    .modal-body {
        padding: 30px;
        max-height: 400px;
        /* กำหนดความสูงสูงสุดของ modal */
        overflow-y: auto;
        /* กำหนดให้สามารถเลื่อนลงได้ */
        max-width: 1500px;
        /* border: 1px solid red; */
    }

    .cart-image {
        width: 100px;
        /* เพิ่มขนาดรูปภาพให้ชัดเจนขึ้น */
        height: 100px;
        margin-right: 20px;
        object-fit: fill;
        border-radius: 8px;
        flex-shrink: 0;
        /* ไม่ให้รูปภาพย่อขนาด */
    }

    .cart-item-info {
        display: flex;
        align-items: center;
        flex-grow: 1;
        /* ให้ container เต็มพื้นที่ที่เหลือ */
        padding-left: 10px;

    }

    .item-details {
        display: flex;
        flex-direction: column;
        flex-grow: 1;
        max-width: 100%;
        /* ป้องกันข้อมูลยาวเกินออกนอก container */
    }

    .item-name {
        font-size: 16px;
        color: #333;
        margin-bottom: 5px;
    }

    .item-size,
    .item-quantity,
    .item-price {
        font-size: 14px;
        color: #555;
    }

    .remove-item-btn {
        background: none;
        border: none;
        color: red;
        cursor: pointer;
        font-size: 20px;
    }

    .modal-footer {
        border-top: 1px solid #ccc;
        padding-top: 10px;
        display: flex;
        justify-content: space-between;
    }

    .btn-success {
        background-color: #28a745;
        color: white;
        border: none;
        padding: 10px 20px;
    }

    .btn-secondary {
        background-color: #6c757d;
        color: white;
        border: none;
        padding: 10px 20px;
    }

    .cart-total {
        font-weight: bold;
        font-size: 20px;
        color: #333;
    }

    /* ป้องกันการหลุดออกนอก container */
    .cart-item-details {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        flex-wrap: wrap;
        /* ทำให้ข้อมูลที่ยาวเกินไปเลื่อนไปบรรทัดใหม่ */
        /* border:1px solid red; */
        height: 100%;

    }

    .item-price {
        margin-left: auto;
        /* ดันราคาไปที่ปลายสุด */
        white-space: nowrap;
        /* ป้องกันการตัดคำ */
    }

    .cart-item-info {
        display: flex;
        align-items: center;
        flex-grow: 1;
        max-width: 60%;
        /* จำกัดความกว้างสูงสุดของ container ข้อมูล */
    }

    .item-details {
        flex-wrap: wrap;
        /* ทำให้ข้อมูลสามารถไปบรรทัดใหม่ได้ */

    }

    .exit {
        margin-left: 90px;
    }

    .modal-header {
        width: 88%;
    }

    .size {
        flex-shrink: 0;
        font-size: 14px;
        color: #555;
        text-align: left;
    }

    .item-price {
        width: 150px;
        /* border: 1px solid red; */
        margin-left: 0px;
    }

    .item-totalprice {
        /* border: 1px solid green; */
        margin-left: 45px;
    }

    .modal-content {
        /* border: 1px solid red; */
        max-width: 1500px;
        width: 100%;
        /* (<?php echo $cartItemCount; ?> รายการ) */
    }
</style>

<body>
    <div class="modal fade show" id="cartModal" tabindex="-1" role="dialog" aria-labelledby="cartModalLabel" aria-hidden="true" style="display: block;">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cartModalLabel">ตะกร้าสินค้า </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="window.location.href='welcome.php';">
                        <span class="exit" aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php if (!empty($cartItems)) : ?>
                        <ul class="cart-items-list">
                            <?php foreach ($cartItems as $item) : ?>
                                <li class="cart-item-details">
                                    <div class="cart-item-info">
                                        <?php
                                        $images = explode(',', str_replace(['[', ']', '"'], '', $item['product_picture']));
                                        if ($images && isset($images[0])) {
                                            $imagePath = 'uploads/' . htmlspecialchars(trim($images[0]));
                                            echo '<img src="' . $imagePath . '" alt="Product Image" class="cart-image">';
                                        }
                                        ?>
                                        <div class="item-details">
                                            <span class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></span>
                                            <span class="item-size">ไซส์: <?php echo htmlspecialchars($item['size']); ?></span>
                                            <span class="item-quantity">จำนวน: <?php echo htmlspecialchars($item['quantity']); ?></span>
                                            <span class="item-price">ราคาต่อชิ้น: <?php echo number_format($item['product_price'], 2); ?></span>
                                        </div>
                                    </div>
                                    <?php
                                    // คำนวณราคา = ราคา x จำนวนสินค้า
                                    $totalPriceForItem = $item['product_price'] * $item['quantity'];
                                    ?>
                                    <span class="item-totalprice">฿<?php echo number_format($totalPriceForItem, 2); ?></span>

                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($item['product_id']); ?>">
                                        <button type="submit" name="remove_item" class="remove-item-btn">&times;</button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="cart-total">ราคารวม: ฿<?php echo number_format(calculateTotalPrice($cartItems), 2); ?></p>
                    <?php else : ?>
                        <p>ไม่มีสินค้าในตะกร้า</p>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <form action="checkout.php" method="POST">
                        <input type="hidden" name="checkout_all" value="1">
                        <button type="submit" class="btn btn-success">จ่ายเงิน</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="window.location.href='welcome.php';">กลับ</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>

</html>


<?php ob_end_flush(); ?>