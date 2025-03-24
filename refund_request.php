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
$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : null;

// ตรวจสอบว่า order_id มีค่าหรือไม่
if (!$order_id) {
    echo "ไม่พบข้อมูลคำสั่งซื้อ";
    exit();
}

// ดึงข้อมูลคำสั่งซื้อพร้อมรูปภาพจากตาราง products
$query = "SELECT o.Order_ID, p.product_name,p.product_price, p.product_picture, o.Order_Date, o.final_price, o.discount_amount, o.Order_Status
          FROM orders o
          JOIN products p ON o.product_id = p.product_id
          WHERE o.Order_ID = :order_id AND o.user_id = :user_id";

$stmt = $conn->prepare($query);
$stmt->bindParam(':order_id', $order_id);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();

$order = $stmt->fetch(PDO::FETCH_ASSOC);

// ตรวจสอบว่ามีคำสั่งซื้อที่ตรงกับ order_id หรือไม่
if (!$order) {
    echo "ไม่พบข้อมูลคำสั่งซื้อ";
    exit();
}

// เมื่อผู้ใช้กดส่งคำขอคืนเงิน
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $refund_reason = isset($_POST['refund_reason']) ? trim($_POST['refund_reason']) : '';

    if (!empty($refund_reason)) {
        // บันทึกคำขอคืนเงินลงในฐานข้อมูล
        $query = "INSERT INTO refund_requests (order_id, user_id, refund_reason, request_date, status)
                  VALUES (:order_id, :user_id, :refund_reason, NOW(), 'pending')";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':order_id', $order_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':refund_reason', $refund_reason);

        if ($stmt->execute()) {
            echo "<script>
                    alert('ส่งคำขอคืนเงินเรียบร้อยแล้ว');
                    window.location.href='orderhistory.php';
                  </script>";
        } else {
            echo "<script>alert('เกิดข้อผิดพลาดในการส่งคำขอคืนเงิน');</script>";
        }
    } else {
        echo "<script>alert('กรุณาเลือกเหตุผลในการขอคืนเงิน');</script>";
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ขอคืนเงิน</title>
    <style>
        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: #2b2b2b;
            border-radius: 10px;
            color: white;
        }

        .order-details {
            margin-bottom: 20px;
        }

        .order-details img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 10px;
        }

        label {
            display: block;
            margin-bottom: 10px;
            font-size: 18px;
        }

        select {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            border: none;
            background-color: #444;
            color: white;
            font-size: 16px;
        }

        button {
            display: block;
            width: 100%;
            padding: 10px;
            background-color: red;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 18px;
            cursor: pointer;
        }

        button:hover {
            background-color: #c9302c;
            color: inherit;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>ขอคืนเงิน</h2>

        <!-- แสดงรายละเอียดสินค้าที่ผู้ใช้ต้องการขอคืนเงิน -->
        <div class="order-details">
            <p><strong>ชื่อสินค้า:</strong> <?php echo $order['product_name']; ?></p>
            <p><strong>วันที่สั่งซื้อ:</strong> <?php echo $order['Order_Date']; ?></p>
            <p><strong>ราคาสินค้า:</strong> ฿<?php echo number_format($order['product_price'], 2); ?></p>
            <p><strong>ส่วนลด:</strong> ฿<?php echo number_format($order['discount_amount'], 2); ?></p>
            <p><strong>ยอดเงินสุทธิ:</strong> ฿<?php echo number_format($order['final_price'], 2); ?></p>
            <!-- ตรวจสอบว่าไฟล์ภาพมีอยู่จริงหรือไม่ -->
            <?php
            $img_path = "uploads/" . $order['product_picture'];
            // echo "<p>เส้นทางรูปภาพ: " . $img_path . "</p>"; // ดีบั๊กเพื่อแสดงเส้นทางไฟล์รูปภาพ

            // แยกไฟล์รูปภาพออกจากกันโดยใช้เครื่องหมายจุลภาค
            $product_pictures = explode(',', $order['product_picture']);
            $first_picture = trim($product_pictures[0]); // ใช้รูปแรก

            $img_path = "uploads/" . $first_picture; // สร้างเส้นทางใหม่สำหรับรูปแรก

            if (!empty($first_picture) && file_exists($img_path)): ?>
                <img src="<?php echo $img_path; ?>" alt="Product Image">
            <?php else: ?>
                <p>No Image Available</p>
            <?php endif; ?>

        </div>

        <!-- ฟอร์มสำหรับเลือกเหตุผลในการขอคืนเงิน -->
        <form action="refund_request.php?order_id=<?php echo $order_id; ?>" method="POST">
            <label for="refund_reason">เลือกเหตุผลในการขอคืนเงิน:</label>
            <select name="refund_reason" id="refund_reason" required>
                <option value="">-- กรุณาเลือกเหตุผล --</option>
                <option value="สินค้าที่สั่งถูกยกเลิก">สินค้าที่สั่งถูกยกเลิก</option>
                <option value="ไม่ได้รับสินค้า">ไม่ได้รับสินค้า</option>
            </select>

            <button type="submit">ส่งคำขอคืนเงิน</button>
        </form>
    </div>
</body>

</html>