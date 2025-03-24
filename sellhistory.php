<?php
ob_start();  // เริ่มการเก็บข้อมูลบัฟเฟอร์

include_once('header.php');
include_once('config/Database.php');
include_once('nav.php');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$db = new Database();
$conn = $db->getConnection();

// ตรวจสอบว่า user ล็อกอินแล้วหรือยัง
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

$seller_id = $_SESSION['user_id'];

// ดึงข้อมูลคำสั่งซื้อของสินค้าที่ผู้ใช้เป็นผู้ขายและสถานะไม่ใช่ cancelled พร้อมดึงข้อมูลจาก paid_image
$query = "SELECT o.order_id, p.product_name, p.product_picture, o.order_quantity, o.Order_Total, o.size, 
                 o.order_date, o.order_status, o.admin_pay, o.paid_image, o.discount_amount, 
                 o.commission_amount, promo.code AS coupon_code, promo.discount AS coupon_discount
          FROM orders o 
          JOIN products p ON o.product_id = p.product_id 
          LEFT JOIN promotions promo ON o.promotion_id = promo.id
          WHERE p.user_id = :seller_id AND o.order_status != 'cancelled' 
          ORDER BY o.order_date DESC";

$stmt = $conn->prepare($query);
$stmt->bindParam(':seller_id', $seller_id);
$stmt->execute();
$sellOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>ประวัติการขาย</title>
    <link rel="stylesheet" href="styles.css">
</head>
<style>
    /* สไตล์ CSS */
    .container {
        max-width: 1200px;
        margin: 50px auto;
        padding: 20px;
        background-color: #2b2b2b;
        border-radius: 10px;
        color: white;
    }

    h1 {
        text-align: center;
        color: #fff;
        margin-bottom: 20px;
    }

    .product-container {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background-color: #444;
        padding: 20px;
        margin-bottom: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .product-container img {
        max-width: 150px;
        height: auto;
        margin-right: 20px;
        border-radius: 10px;
    }

    .product-info {
        flex-grow: 1;
    }

    .product-info p {
        margin: 5px 0;
    }

    .button-container {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 10px;
    }

    .slip-image {
        max-width: 150px;
        cursor: pointer;
        margin-top: 10px;
        border-radius: 8px;
        transition: transform 0.2s;
    }

    .slip-image:hover {
        transform: scale(1.05);
    }

    .discount-info {
        color: #fff;
    }

    .totalprice {
        color: #27ae60;
    }

    .payment-status {
        padding: 5px 10px;
        border-radius: 5px;
        font-weight: bold;
        display: inline-block;
        color: white;
        font-size: 14px;
    }

    .paid {
        background-color: #28a745;
    }

    .unpaid {
        background-color: #dc3545;
    }

    /* Modal Style */
    .modal {
        display: none;
        position: fixed;
        z-index: 1;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.9);
    }

    .modal-content {
        margin: auto;
        display: block;
        max-width: 60%;
        max-height: 80%;
        object-fit: contain;
        border-radius: 8px;
    }

    .close {
        position: absolute;
        top: 15px;
        right: 35px;
        color: #fff;
        font-size: 40px;
        font-weight: bold;
        cursor: pointer;
        text-align: right;
        width: 10%;
    }

    .close:hover,
    .close:focus {
        color: #f1f1f1;
        text-decoration: none;
        cursor: pointer;
    }
</style>

<body>
    <div class="container">
        <h1>ประวัติการขาย</h1>
        <?php foreach ($sellOrders as $order): ?>
            <div class="product-container">
                <?php
                // แสดงรูปสินค้าจาก product_picture
                $images = explode(',', str_replace(['[', ']', '"'], '', $order['product_picture']));
                if ($images && isset($images[0])) {
                    $imagePath = 'uploads/' . htmlspecialchars(trim($images[0]));
                    if (file_exists($imagePath)) {
                        echo '<img src="' . $imagePath . '" alt="Product Image">';
                    } else {
                        echo '<p>รูปภาพไม่พบ</p>';
                    }
                } else {
                    echo '<p>ไม่มีรูปภาพ</p>';
                }
                ?>
                <div class="product-info">
                    <p>ชื่อสินค้า: <?php echo htmlspecialchars($order['product_name']); ?></p>
                    <p>จำนวน: <?php echo htmlspecialchars($order['order_quantity']); ?></p>
                    <p>ไซส์: <?php echo htmlspecialchars($order['size']); ?></p>

                    <?php
                    $originalPrice = $order['Order_Total'];
                    $discountAmount = $order['discount_amount'];
                    $commissionAmount = $order['commission_amount'];
                    $couponCode = $order['coupon_code'];
                    $couponDiscount = $order['coupon_discount'];

                    // คำนวณราคาหลังหักส่วนลดและค่าคอมมิชชั่น
                    $finalPrice = !empty($discountAmount) ? $originalPrice - $discountAmount : $originalPrice;
                    $sellerAmount = $finalPrice - $commissionAmount;
                    ?>
                    <p>วันที่สั่งซื้อ: <?php echo date("d M Y H:i:s", strtotime($order['order_date'])); ?></p>
                    <p>ราคาสินค้า: ฿<?php echo htmlspecialchars(number_format($originalPrice, 2)); ?></p>
                    <?php if (!empty($couponDiscount) && !empty($couponCode)): ?>
                        <p class="discount-info">ใช้คูปอง: <?php echo htmlspecialchars($couponCode); ?></p>
                        <p class="discount-info">ส่วนลด: <?php echo htmlspecialchars($couponDiscount); ?>% (<?php echo htmlspecialchars(number_format($discountAmount, 2)); ?>฿)</p>
                        <p class="totalprice">ราคาหลังหักส่วนลด: ฿<?php echo htmlspecialchars(number_format($finalPrice, 2)); ?></p>
                    <?php endif; ?>
                    <p class="discount-info">ค่าคอมมิชชั่นที่ถูกหัก: ฿<?php echo htmlspecialchars(number_format($commissionAmount, 2)); ?></p>
                    <p class="totalprice">ราคาหลังหักค่าคอมมิชชั่น: ฿<?php echo htmlspecialchars(number_format($sellerAmount, 2)); ?></p>

                    <p>สถานะการชำระเงิน:
                        <span class="payment-status <?php echo $order['admin_pay'] ? 'paid' : 'unpaid'; ?>">
                            <?php echo $order['admin_pay'] ? 'จ่ายแล้ว' : 'ยังไม่ได้จ่าย'; ?>
                        </span>
                    </p>
                </div>

                <div class="button-container">
                    <?php
                    // แสดงรูปจาก paid_image
                    if (!empty($order['paid_image'])) {
                        $paidImagePath = 'uploads/' . htmlspecialchars($order['paid_image']);
                        if (file_exists($paidImagePath)) {
                            echo '<img class="slip-image" src="' . $paidImagePath . '" alt="Payment Slip" onclick="openModal(\'' . $paidImagePath . '\')">';
                        } else {
                            echo '<p>รูปภาพไม่พบ</p>';
                        }
                    } else {
                        echo '<p>ยังไม่ชำระเงิน</p>';
                    }
                    ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Modal สำหรับดูรูปขนาดใหญ่ -->
    <!-- <div id="imageModal" class="modal">
        <span class="close" onclick="closeModal()">&times;</span>
        <img class="modal-content" id="modalImage">
    </div>

    <script>
        function openModal(imageSrc) {
            document.getElementById('imageModal').style.display = "block";
            document.getElementById('modalImage').src = imageSrc;
        }

        function closeModal() {
            document.getElementById('imageModal').style.display = "none";
        }
    </script> -->
</body>

</html>

<?php ob_end_flush(); // สิ้นสุดบัฟเฟอร์ ?>
