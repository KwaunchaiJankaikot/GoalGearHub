<?php
session_start();
include_once('../config/Database.php');
include_once('../header.php');

// ตรวจสอบว่าแอดมินล็อกอินหรือยัง
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// ดึงคำขอคืนเงินที่ยังไม่ได้รับการดำเนินการ
$pendingRefundQuery = "
    SELECT rr.id, rr.order_id, rr.user_id, rr.refund_reason, rr.request_date, rr.status, rr.refund_image, o.final_price, o.Order_Quantity,o.size, p.product_name, p.product_picture, p.product_price, u.name AS customer_name, u.bankname, u.banknumber
    FROM refund_requests rr
    JOIN orders o ON rr.order_id = o.Order_ID
    JOIN products p ON o.product_id = p.product_id
    JOIN users u ON rr.user_id = u.id
    WHERE rr.status = 'pending'
    ORDER BY rr.request_date DESC";
$pendingStmt = $conn->prepare($pendingRefundQuery);
$pendingStmt->execute();
$pendingRefundRequests = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลประวัติการคืนเงินที่ได้รับการคืนเงินแล้ว
$completedRefundQuery = "
    SELECT rr.id, rr.order_id, rr.user_id, rr.refund_reason, rr.request_date, rr.status, rr.refund_image, o.final_price, p.product_name, p.product_picture, p.product_price, u.name AS customer_name, u.bankname, u.banknumber
    FROM refund_requests rr
    JOIN orders o ON rr.order_id = o.Order_ID
    JOIN products p ON o.product_id = p.product_id
    JOIN users u ON rr.user_id = u.id
    WHERE rr.status = 'refunded'
    ORDER BY rr.request_date DESC";
$completedStmt = $conn->prepare($completedRefundQuery);
$completedStmt->execute();
$completedRefundRequests = $completedStmt->fetchAll(PDO::FETCH_ASSOC);

// เมื่อแอดมินอัปโหลดสลิปโอนเงิน
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_slip'])) {
    $refund_id = $_POST['refund_id'];
    $refund_image_name = '';

    if (isset($_FILES['refund_image']) && $_FILES['refund_image']['error'] === 0) {
        $upload_dir = '../uploads/';
        $refund_image_name = basename($_FILES['refund_image']['name']);
        $upload_file = $upload_dir . $refund_image_name;

        if (move_uploaded_file($_FILES['refund_image']['tmp_name'], $upload_file)) {
            $updateQuery = "UPDATE refund_requests SET refund_image = :refund_image, status = 'refunded' WHERE id = :refund_id";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bindParam(':refund_image', $refund_image_name);
            $stmt->bindParam(':refund_id', $refund_id);

            if ($stmt->execute()) {
                echo "<script>alert('อัปโหลดสลิปโอนเงินสำเร็จ'); window.location.href='admin_refund.php';</script>";
            } else {
                echo "<script>alert('เกิดข้อผิดพลาดในการบันทึกข้อมูล');</script>";
            }
        }
    }
}
// เมื่อแอดมินกดปุ่มยกเลิกคำขอคืนเงิน
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_refund'])) {
    $refund_id = $_POST['refund_id'];

    // อัปเดตสถานะคำขอคืนเงินเป็น 'cancelled'
    $cancelQuery = "UPDATE refund_requests SET status = 'cancelled' WHERE id = :refund_id";
    $stmt = $conn->prepare($cancelQuery);
    $stmt->bindParam(':refund_id', $refund_id);

    if ($stmt->execute()) {
        echo "<script>alert('ยกเลิกคำขอคืนเงินสำเร็จ'); window.location.href='admin_refund.php';</script>";
    } else {
        echo "<script>alert('เกิดข้อผิดพลาดในการยกเลิกคำขอคืนเงิน');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Refund Requests</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <style>
        body {
            background-color: #34495e;
        }

        .container {
            background-color: #34495e;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.4);
            width: 100%;
            max-width: 800px;
            margin: 30px auto;
        }

        .product-container {
            background-color: #2c3e50;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
        }

        .product-info p {
            margin: 5px 0;
            line-height: 1.6;
            font-size: 16px;
            color: #ecf0f1;
        }

        .payment-form {
            margin-top: 20px;
            width: 100%;
        }

        .payment-form input[type="file"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            color: #ecf0f1;
        }

        .payment-form button {
            background-color: #27ae60;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }

        .payment-form button:hover {
            background-color: #219150;
        }

        h2 {
            font-size: 24px;
            color: #ecf0f1;
            text-align: center;
            margin-bottom: 20px;
        }

        .product-image img {
            width: 150px;
            height: auto;
            border-radius: 8px;
        }

        .container p {
            color: #fff;
        }

        .uploadtextrefund {
            color: #fff;
        }
        .btnall{
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>คำขอคืนเงินที่ยังไม่ได้ดำเนินการ</h2>
        <?php if (!empty($pendingRefundRequests)) : ?>
            <?php foreach ($pendingRefundRequests as $request) : ?>
                <div class="product-container">
                    <div class="product-info">
                        <p><strong>Order ID:</strong> <?php echo $request['order_id']; ?></p>
                        <p><strong>ชื่อผู้สั่ง:</strong> <?php echo $request['customer_name']; ?></p>
                        <p><strong>บัญชีธนาคาร:</strong> <?php echo $request['bankname']; ?></p>
                        <p><strong>เลขบัญชี:</strong> <?php echo $request['banknumber']; ?></p>
                        <p><strong>เหตุผลในการคืนเงิน:</strong> <?php echo $request['refund_reason']; ?></p>
                        <p><strong>ไซส์:</strong> <?php echo $request['size']; ?> </p>
                        <p><strong>วันที่ขอคืนเงิน:</strong> <?php echo $request['request_date']; ?></p>
                        <p><strong>ราคาที่ต้องโอน:</strong> ฿<?php echo number_format($request['final_price'], 2); ?></p>
                        <!-- รูปสินค้า -->
                        <div class="product-image">
                            <?php
                            $product_pictures = explode(',', $request['product_picture']);
                            $first_picture = trim($product_pictures[0]);
                            $img_path = "../uploads/" . $first_picture;
                            if (!empty($first_picture) && file_exists($img_path)): ?>
                                <img src="<?php echo $img_path; ?>" alt="Product Image">
                            <?php else: ?>
                                <p>No Image Available</p>
                            <?php endif; ?>
                        </div>
                        <!-- ฟอร์มอัปโหลดสลิปโอนเงิน -->
                        <div class="payment-form">
                            <form action="admin_refund.php" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="refund_id" value="<?php echo $request['id']; ?>">
                                <label for="refund_image" class="uploadtextrefund">อัปโหลดสลิปโอนเงิน:</label>
                                <input type="file" name="refund_image" required>
                                <button type="submit" name="submit_slip">อัปโหลดสลิป</button>
                            </form>
                            <div class="payment-form">
                                <form action="admin_refund.php" method="POST">
                                    <input type="hidden" name="refund_id" value="<?php echo $request['id']; ?>">
                                    <button type="submit" name="cancel_refund" style="background-color: #e74c3c;">ยกเลิกคำขอคืนเงิน</button>
                                </form>
                            </div>
                        </div>
                        <!-- ฟอร์มสำหรับยกเลิกคำขอคืนเงิน -->

                    </div>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <p>ไม่มีคำขอคืนเงินในขณะนี้</p>
        <?php endif; ?>
    </div>

    <!-- Container สำหรับประวัติการคืนเงิน -->
    <div class="container">
        <h2>ประวัติการคืนเงิน</h2>
        <?php if (!empty($completedRefundRequests)) : ?>
            <?php foreach ($completedRefundRequests as $request) : ?>
                <div class="product-container">
                    <div class="product-info">
                        <p><strong>Order ID:</strong> <?php echo $request['order_id']; ?></p>
                        <p><strong>ชื่อผู้สั่ง:</strong> <?php echo $request['customer_name']; ?></p>
                        <p><strong>บัญชีธนาคาร:</strong> <?php echo $request['bankname']; ?></p>
                        <p><strong>เลขบัญชี:</strong> <?php echo $request['banknumber']; ?></p>
                        <p><strong>เหตุผลในการคืนเงิน:</strong> <?php echo $request['refund_reason']; ?></p>
                        <p><strong>วันที่คืนเงิน:</strong> <?php echo $request['request_date']; ?></p>
                        <p><strong>ราคาที่โอนคืน:</strong> ฿<?php echo number_format($request['final_price'], 2); ?></p>
                        <!-- รูปสลิปโอนเงิน -->
                        <div class="product-image">
                            <?php
                            if (!empty($request['refund_image'])):
                                $img_path = "../uploads/" . $request['refund_image']; ?>
                                <img src="<?php echo $img_path; ?>" alt="Refund Slip">
                            <?php else: ?>
                                <p>No Slip Available</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <p>ไม่มีประวัติการคืนเงิน</p>
        <?php endif; ?>
        <div class="btnall">
            <a href="admin_dashboard.php" class="btn btn-danger">กลับหน้าแรก</a>
        </div>
    </div>
</body>

</html>