<?php
session_start();
ob_start();
include_once('header.php');
include_once('../config/Database.php');

// ตรวจสอบว่าแอดมินล็อกอินหรือยัง
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// ฟังก์ชันอัปเดตสถานะการร้องเรียน
function markComplaintAsResolved($conn, $complaint_id) {
    $query = "UPDATE complaints SET status = 1 WHERE id = :complaint_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':complaint_id', $complaint_id);
    return $stmt->execute();
}

// ตรวจสอบการทำเครื่องหมายว่าดำเนินการเสร็จสิ้น
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complaint_id'])) {
    $complaint_id = $_POST['complaint_id'];

    if (markComplaintAsResolved($conn, $complaint_id)) {
        // อัปเดตสำเร็จ เก็บสถานะการสำเร็จไว้ใน session
        $_SESSION['status'] = 'resolved';
        header("Location: admin_complaint.php");
        exit();
    } else {
        // หากมีข้อผิดพลาด เก็บสถานะข้อผิดพลาดไว้ใน session
        $_SESSION['status'] = 'error';
        header("Location: admin_complaint.php");
        exit();
    }
}

// ดึงข้อมูลการร้องเรียนที่ยังไม่ได้ดำเนินการจากตาราง complaints
$query = "SELECT c.id AS complaint_id, c.order_id, c.complaint_message, c.complaint_image, c.complaint_date, 
                 o.order_id, o.order_quantity,o.final_price, p.product_name, p.product_picture, p.product_price, u.name AS user_name, 
                 pr.report_count, us.name AS seller_name, c.status AS complaint_status
          FROM complaints c
          JOIN orders o ON c.order_id = o.order_id
          JOIN products p ON o.product_id = p.product_id
          JOIN users u ON o.user_id = u.id
          LEFT JOIN product_reports pr ON p.product_id = pr.product_id
          LEFT JOIN users us ON p.user_id = us.id
          WHERE c.status = 0
          ORDER BY c.complaint_date DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>จัดการการร้องเรียน</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #2c3e50;
            color: #ecf0f1;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            background-color: #34495e;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.4);
            width: 100%;
            overflow-x: auto;
        }

        h1, h2 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 28px;
        }

        .alert-success {
            background-color: #27ae60;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            text-align: center;
            margin-bottom: 20px;
        }

        .alert-error {
            background-color: #e74c3c;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            text-align: center;
            margin-bottom: 20px;
        }

        .complaint-item {
            display: flex;
            flex-direction: column;
            margin-bottom: 30px;
            padding: 15px;
            background-color: #2c3e50;
            border-radius: 10px;
        }

        .complaint-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 18px;
        }

        .complaint-body {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            align-items: center;
            margin-bottom: 20px;
        }

        .complaint-body img {
            max-width: 150px;
            height: auto;
            border-radius: 10px;
        }

        .product-info p {
            margin: 5px 0;
            font-size: 16px;
        }

        .complaint-message {
            margin-top: 20px;
            background-color: #444;
            padding: 10px;
            border-radius: 5px;
            font-size: 16px;
        }

        .complaint-image-upload {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .complaint-upload-img {
            max-width: 150px;
            height: auto;
            border-radius: 10px;
            margin-top: 10px;
        }

        .label {
            margin-bottom: 10px;
            font-size: 16px;
        }

        .button-wrapper {
            display: flex;
            justify-content: flex-end;
        }

        button {
            padding: 10px 20px;
            background-color: #ffa500;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }

        button:hover {
            background-color: #e69500;
        }

        .no-image {
            color: #ccc;
            font-style: italic;
        }
        .btnall{
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="container">
        <h2>รายการการร้องเรียน</h2>

        <!-- แสดงการแจ้งเตือนความสำเร็จหรือข้อผิดพลาด -->
        <?php if (isset($_SESSION['status'])) : ?>
            <?php if ($_SESSION['status'] == 'resolved') : ?>
                <div class="alert-success">ดำเนินการเสร็จสิ้น</div>
            <?php elseif ($_SESSION['status'] == 'error') : ?>
                <div class="alert-error">เกิดข้อผิดพลาดในการดำเนินการ</div>
            <?php endif; ?>
            <?php unset($_SESSION['status']); // ลบค่า status เพื่อไม่ให้แสดงอีกเมื่อรีเฟรชหน้า ?>
        <?php endif; ?>

        <?php if (!empty($complaints)) : ?>
            <?php foreach ($complaints as $complaint) : ?>
                <div class="complaint-item">
                    <div class="complaint-header">
                        <div><strong>ชื่อผู้ใช้:</strong> <?php echo htmlspecialchars($complaint['user_name']); ?></div>
                        <div class="complaint-date"><strong>วันที่ร้องเรียน:</strong> <?php echo htmlspecialchars($complaint['complaint_date']); ?></div>
                    </div>

                    <div class="complaint-body">
                        <div class="product-image">
                            <?php
                            if (isset($complaint['product_picture'])) {
                                $images = explode(',', $complaint['product_picture']);
                                $firstImage = trim($images[0]);
                                $imagePath = '../uploads/' . $firstImage;

                                if (file_exists($imagePath)) {
                                    echo '<img src="' . $imagePath . '" alt="Product Image">';
                                } else {
                                    echo '<p class="no-image">ไม่พบรูปภาพสินค้า</p>';
                                }
                            } else {
                                echo '<p class="no-image">ไม่มีรูปภาพสินค้า</p>';
                            }
                            ?>
                        </div>

                        <div class="complaint-image-upload">
                            <p class="label"><strong>รูปภาพที่อัพโหลด:</strong></p>
                            <?php if (!empty($complaint['complaint_image'])) : ?>
                                <?php
                                $complaintImagePath = '../uploads/' . htmlspecialchars($complaint['complaint_image']);
                                if (file_exists($complaintImagePath)) {
                                    echo '<img src="' . $complaintImagePath . '" alt="Complaint Image" class="complaint-upload-img">';
                                } else {
                                    echo '<p class="no-image">ไม่พบรูปภาพการร้องเรียน</p>';
                                }
                                ?>
                            <?php else : ?>
                                <p class="no-image">ไม่มีรูปภาพที่อัปโหลด</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="product-info">
                        <p><strong>ชื่อสินค้า:</strong> <?php echo htmlspecialchars($complaint['product_name']); ?></p>
                        <p><strong>ชื่อคนขาย:</strong> <?php echo htmlspecialchars($complaint['seller_name']); ?></p>
                    </div>

                    <div class="complaint-message">
                        <p><strong>รายละเอียดการร้องเรียน:</strong></p>
                        <p><?php echo htmlspecialchars($complaint['complaint_message']); ?></p>
                    </div>

                    <div class="button-wrapper">
                        <form action="admin_complaint.php" method="POST">
                            <input type="hidden" name="complaint_id" value="<?php echo $complaint['complaint_id']; ?>">
                            <button type="submit" name="mark_as_resolved">ทำเครื่องหมายว่าดำเนินการเสร็จสิ้น</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <p>ไม่มีการร้องเรียนที่ยังไม่ได้ดำเนินการ</p>
        <?php endif; ?>
        <div class="btnall">
            <a href="admin_dashboard.php" class="btn btn-danger">กลับหน้าแรก</a>
        </div>
    </div>
</body>
</html>
