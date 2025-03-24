<?php
session_start();
ob_start();
include_once('header.php');
include_once('config/Database.php');
include_once('nav.php');

// ตรวจสอบว่า user ล็อกอินแล้วหรือยัง
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$user_id = $_SESSION['user_id']; // ดึง user_id ของผู้ใช้งานจาก session

// ตรวจสอบคำสั่ง SQL ที่ดึงข้อมูลการร้องเรียนที่เกี่ยวข้องกับ user
$query = "SELECT c.*, p.product_name, p.product_picture 
          FROM complaints c 
          JOIN products p ON c.product_id = p.product_id 
          WHERE p.user_id = :user_id";  // แก้ไขตรงนี้เป็น user_id

// เตรียมคำสั่ง SQL
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการการร้องเรียนของฉัน</title>
    <style>
        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #2b2b2b;
            color: white;
            border-radius: 10px;
        }

        h2 {
            text-align: center;
            color: #ffa500;
            margin-bottom: 20px;
        }

        .complaint-item {
            background-color: #444;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
        }

        .complaint-item img.product {
            width: 150px;
            height: auto;
            border-radius: 10px;
            object-fit: cover;
        }

        .complaint-details {
            flex: 1;
            margin-left: 20px;
        }

        .uploaded-label {
            color: #fff;
            font-size: 14px;
            margin-top: 10px;
        }

        .uploaded-image {
            max-width: 100px;
            height: auto;
            border-radius: 10px;
        }

        .right-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            /* border: 1px solid red; */
            margin-top: -20px;
        }

        .status-box {
            display: inline-block;
            padding: 5px 10px;
            /* border: 2px solid #ffa500; */
            border-radius: 5px;
            color: #fff;
            font-weight: bold;
            margin-top: 5px;
            background-color: #28a745;
        }

        .complaint-info p {
            margin: 5px 0;
        }

        .complaint-item img.product {
            width: 100px;
            height: 100px;
            border-radius: 10px;
            object-fit: cover;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>รายการการร้องเรียนของฉัน</h2>

        <!-- ตรวจสอบว่ามีรายการร้องเรียนหรือไม่ -->
        <?php if ($complaints): ?>
            <!-- วนลูปรายการร้องเรียน -->
            <?php foreach ($complaints as $complaint): ?>
                <div class="complaint-item">
                    <?php
                    // แยกรูปภาพสินค้าจากฐานข้อมูล
                    $images = explode(',', $complaint['product_picture']);
                    $firstImage = trim($images[0]);
                    $imagePath = 'uploads/' . $firstImage;

                    // ตรวจสอบว่ารูปภาพมีอยู่จริง
                    if (file_exists($imagePath)) {
                        echo '<img src="' . $imagePath . '" alt="Product Image" class="product">';
                    } else {
                        echo '<img src="img/default_product.png" alt="Default Image" class="product">';
                    }
                    ?>

                    <div class="complaint-details">
                        <p><strong>ชื่อสินค้า:</strong> <?php echo htmlspecialchars($complaint['product_name']); ?></p>
                        <p><strong>รายละเอียดการร้องเรียน:</strong> <?php echo htmlspecialchars($complaint['complaint_message']); ?></p>
                        <p><strong>วันที่ร้องเรียน:</strong> <?php echo htmlspecialchars($complaint['complaint_date']); ?></p>
                        <p><strong>สถานะ:</strong>
                            <?php echo $complaint['status'] == 1 ? '<span class="status-box">แอดมินดำเนินการแล้ว</span>' : '<span class="status-box">แอดมินยังไม่ดำเนินการ</span>'; ?>
                        </p>
                    </div>

                    <!-- จัดการรูปภาพที่อัปโหลดอยู่ทางขวา -->
                    <div class="right-section">
                        <p class="uploaded-label"><strong>รูปภาพที่อัปโหลด:</strong></p>
                        <img src="uploads/<?php echo htmlspecialchars($complaint['complaint_image']); ?>" alt="Uploaded Image" class="uploaded-image">
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- ข้อความหากไม่มีรายการร้องเรียน -->
            <p>ไม่มีการร้องเรียน</p>
        <?php endif; ?>
    </div>
</body>

</html>

