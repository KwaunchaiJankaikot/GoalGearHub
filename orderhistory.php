<?php
session_start();
ob_start();
include_once('header.php');
include_once('config/Database.php');
include_once('nav.php');

// ตรวจสอบว่า user ได้ล็อกอินหรือยัง
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$user_id = $_SESSION['user_id']; // ดึง user_id ของผู้ใช้งานจาก session

// ดึงข้อมูลจากตาราง orders, shipping_requests และ complaints เพื่อแสดงประวัติการซื้อ ครอบคลุมทุกสถานะ
$query = "SELECT o.Order_ID, p.product_name, p.product_price, p.product_picture, o.Order_Date,o.Order_Quantity,o.payment_method, o.Order_Status, o.final_price, o.discount_amount,o.size, 
                so.tracking_number,so.shipping_company,so.created_at,so.shipping_image, rr.refund_image, rr.status as refund_status,
                 c.status as complaint_status -- ดึงสถานะการร้องเรียนจากตาราง complaints
          FROM orders o
          JOIN products p ON o.product_id = p.product_id
        --   LEFT JOIN shipping_requests sr ON o.Order_ID =sr.order_id 
          LEFT JOIN shipping_orders so ON o.Order_ID =so.order_id 
          LEFT JOIN refund_requests rr ON o.Order_ID = rr.order_id
          LEFT JOIN complaints c ON o.Order_ID = c.order_id -- เชื่อมกับตาราง complaints
          WHERE o.user_id = :user_id
          ORDER BY o.Order_Date DESC";

$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History</title>
    <style>
        .container {
            max-width: 1200px;
            margin: 50px auto;
            padding: 20px;
            background-color: #2b2b2b;
            border-radius: 10px;
            color: white;
        }

        .order-history-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 20px;
            background-color: #444;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .product-image {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 10px;
            margin-right: 20px;
        }

        .shipping-image,
        .refund-image {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 10px;
        }

        .image-container {
            text-align: center;
            margin-bottom: 10px;
        }

        .order-details {
            flex-grow: 1;
            margin-left: 20px;
        }

        .right-section {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .order-status {
            padding: 5px 10px;
            border-radius: 3px;
            font-weight: bold;
        }

        .status-pending {
            background-color: #ffc107;
            color: #fff;
        }

        .status-shipped {
            background-color: #17a2b8;
            color: #fff;
        }

        .status-cancelled {
            background-color: #dc3545;
            color: #fff;
        }

        .status-refunded {
            background-color: #28a745;
            color: #fff;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: red;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            text-align: center;
        }

        .btnrefund {
            color: #fff;
        }

        .image-container-refunded {
            margin-top: -10px;
        }

        .image-container-refunded p {
            text-align: center;
        }
        h2{
            text-align: center;
            font-size: 2.5rem;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>ประวัติการสั่งซื้อ</h2>
        <?php if (!empty($orders)) : ?>
            <?php foreach ($orders as $order) :
                $images = explode(',', $order['product_picture']);
                $firstImage = trim($images[0]);
            ?>
                <div class="order-history-item">
                    <!-- แสดงรูปสินค้า -->
                    <div class="image-container">
                        <img src="uploads/<?php echo $firstImage; ?>" alt="Product Image" class="product-image">
                    </div>

                    <div class="order-details">
                        <p><strong>ชื่อสินค้า:</strong> <?php echo $order['product_name']; ?></p>
                        <p><strong>วันที่สั่งซื้อ:</strong> <?php echo $order['Order_Date']; ?></p>
                        <!-- ข้อมูลการจัดส่ง ถ้ามี -->
                        <?php if ($order['Order_Status'] == 'shipped') : ?>
                            <p><strong>Tracking Number:</strong> <?php echo !empty($order['tracking_number']) ? $order['tracking_number'] : 'N/A'; ?></p>
                            <p><strong>Shipping Company:</strong> <?php echo !empty($order['shipping_company']) ? $order['shipping_company'] : 'N/A'; ?></p>
                            <p><strong>วันที่จัดส่ง:</strong> <?php echo !empty($order['created_at']) ? $order['created_at'] : 'N/A'; ?></p>
                        <?php endif; ?>
                        <p><strong>จำนวนสินค้าที่ซื้อ:</strong> <?php echo $order['Order_Quantity']; ?> ชิ้น</p>
                        <p><strong>ไซส์:</strong> <?php echo !empty($order['size']) ? $order['size'] : 'ไม่ระบุ'; ?></p>   
                        <p><strong>ราคาสินค้า:</strong> ฿<?php echo number_format($order['product_price'], 2); ?></p>
                        <p><strong>ส่วนลด:</strong> ฿<?php echo number_format($order['discount_amount'], 2); ?></p>
                        <p><strong>ยอดเงินสุทธิ:</strong> ฿<?php echo number_format($order['final_price'], 2); ?></p>
                        <p><strong>สถานะการจัดส่ง:</strong>
                            <span class="order-status 
                                <?php echo $order['Order_Status'] == 'pending' ? 'status-pending' : ($order['Order_Status'] == 'shipped' ? 'status-shipped' : 'status-cancelled'); ?>">
                                <?php
                                if ($order['Order_Status'] == 'pending') {
                                    echo "รอส่งสินค้า";
                                } elseif ($order['Order_Status'] == 'shipped') {
                                    echo "จัดส่งสินค้าแล้ว";
                                } elseif ($order['Order_Status'] == 'cancelled') {
                                    echo "ยกเลิกการสั่งซื้อ";
                                }
                                ?>
                            </span>
                        </p>
                        
                        <!-- ปุ่มร้องเรียน/รายงานปัญหา และสถานะการร้องเรียน -->
                        <?php if ($order['Order_Status'] == 'shipped') : ?>
                            <?php if (isset($order['complaint_status'])) : ?>
                                <!-- แสดงสถานะการร้องเรียน -->
                                <p><strong>สถานะการร้องเรียน:</strong>
                                    <span class="order-status 
                                        <?php echo $order['complaint_status'] == 0 ? 'status-cancelled' : 'status-refunded'; ?>">
                                        <?php
                                        if ($order['complaint_status'] == 0) {
                                            echo "ยังไม่ได้ดำเนินการ";
                                        } elseif ($order['complaint_status'] == 1) {
                                            echo "ดำเนินการแล้ว";
                                        }
                                        ?>
                                    </span>
                                </p>
                            <?php endif; ?>

                            <!-- ซ่อนปุ่มร้องเรียนถ้าดำเนินการแล้ว -->
                            <?php if ($order['complaint_status'] == 0 || !isset($order['complaint_status'])) : ?>
                                <a href="complaint.php?order_id=<?php echo $order['Order_ID']; ?>" class="btn">ร้องเรียน/รายงานปัญหา</a>
                            <?php endif; ?>
                        <?php endif; ?>

                        <!-- ปุ่มขอคืนเงินในกรณีคำสั่งซื้อถูกยกเลิก -->
                        <?php if ($order['Order_Status'] == 'cancelled') : ?>
                            <?php if ($order['refund_status'] == 'pending') : ?>
                                <!-- กรณีสถานะยังเป็น pending ให้ขึ้นว่า "ยังไม่ได้รับการคืนเงิน" และยังไม่แสดงรูป -->
                                <p><strong>สถานะการคืนเงิน:</strong>
                                    <span class="order-status status-pending">ยังไม่ได้รับการคืนเงิน</span>
                                </p>
                            <?php elseif ($order['refund_status'] == 'refunded') : ?>
                                <!-- กรณีสถานะเป็น refunded ให้ขึ้นว่า "ได้รับการคืนเงินแล้ว" และแสดงรูป -->
                                <p><strong>สถานะการคืนเงิน:</strong>
                                    <span class="order-status status-refunded">ได้รับการคืนเงินแล้ว</span>
                                </p>
                            <?php elseif ($order['refund_status'] == 'cancelled') : ?>
                                <!-- กรณีสถานะเป็น cancelled ให้ขึ้นว่า "ไม่อนุมัติการคืนเงิน" -->
                                <p><strong>สถานะการคืนเงิน:</strong>
                                    <span class="order-status status-cancelled">ไม่อนุมัติการคืนเงิน</span>
                                </p>
                            <?php else : ?>
                                <!-- กรณีที่ยังไม่ได้ส่งคำขอคืนเงิน -->
                                <a href="refund_request.php?order_id=<?php echo $order['Order_ID']; ?>" class="btn btnrefund">ขอคืนเงิน</a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <div class="right-section">
                        <!-- แสดงรูปการจัดส่งถ้ามี -->
                        <?php if (!empty($order['shipping_image']) && $order['Order_Status'] == 'shipped') : ?>
                            <div class="image-container">
                                <p>รูปการจัดส่ง</p>
                                <img src="uploads/<?php echo $order['shipping_image']; ?>" alt="Shipping Image" class="shipping-image">
                            </div>
                        <?php endif; ?>

                        <!-- แสดงรูปสลิปคืนเงิน ถ้ามี -->
                        <?php if (!empty($order['refund_image']) && $order['Order_Status'] == 'cancelled') : ?>
                            <div class="image-container-refunded">
                                <p>สลิปขอเงินคืน</p>
                                <img src="uploads/<?php echo $order['refund_image']; ?>" alt="Refund Slip" class="refund-image">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <p>No order history available.</p>
        <?php endif; ?>

        <a href="welcome.php" class="btn">กลับหน้าแรก</a>
    </div>
</body>

</html>
