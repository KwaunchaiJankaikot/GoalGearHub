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

// ตรวจสอบว่ามีการส่งคำร้องเรียนหรือไม่
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['order_id']) && isset($_POST['complaint_message'])) {
        $order_id = $_POST['order_id'];
        $product_id = $_POST['product_id']; // รับ product_id จากฟอร์ม
        $complaint_message = trim($_POST['complaint_message']);
        $complaint_image = '';

        // ตรวจสอบว่ามีการอัปโหลดรูปหรือไม่
        if (isset($_FILES['complaint_image']) && $_FILES['complaint_image']['error'] === 0) {
            $upload_dir = 'uploads/';
            $complaint_image = basename($_FILES['complaint_image']['name']);
            $upload_file = $upload_dir . $complaint_image;

            // ย้ายไฟล์รูปไปยังโฟลเดอร์ uploads
            if (move_uploaded_file($_FILES['complaint_image']['tmp_name'], $upload_file)) {
                // echo "ไฟล์รูปภาพอัปโหลดเรียบร้อยแล้ว";
            } else {
                echo "เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ";
            }
        }

        // ตรวจสอบว่า order_id, product_id และข้อความร้องเรียนไม่ว่างเปล่า
        if (!empty($order_id) && !empty($complaint_message) && !empty($product_id)) {
            // บันทึกข้อมูลการร้องเรียนลงฐานข้อมูล
            $query = "INSERT INTO complaints (order_id, product_id, user_id, complaint_message, complaint_image) 
                      VALUES (:order_id, :product_id, :user_id, :complaint_message, :complaint_image)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':order_id', $order_id);
            $stmt->bindParam(':product_id', $product_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':complaint_message', $complaint_message);
            $stmt->bindParam(':complaint_image', $complaint_image);

            if ($stmt->execute()) {
                echo "<script>alert('การร้องเรียนถูกบันทึกแล้ว'); window.location.href='orderhistory.php';</script>";
            } else {
                echo "<script>alert('เกิดข้อผิดพลาดในการบันทึกการร้องเรียน');</script>";
            }
        } else {
            echo "<script>alert('กรุณากรอกข้อมูลให้ครบถ้วน');</script>";
        }
    }
}

// แสดงฟอร์มการร้องเรียนและดึงข้อมูลสินค้าที่เกี่ยวข้อง
if (isset($_GET['order_id'])) {
    $order_id = $_GET['order_id'];

    // ดึงข้อมูลสินค้าและคำสั่งซื้อที่เกี่ยวข้อง รวมถึงข้อมูลส่วนลดและโค้ดคูปอง
    $query = "SELECT o.order_id, p.product_id, p.product_name, p.product_picture, p.product_price, o.order_quantity, o.discount_amount, o.coupon_code, promo.code AS promotion_code, promo.discount AS promotion_discount
              FROM orders o
              JOIN products p ON o.product_id = p.product_id
              LEFT JOIN promotions promo ON o.promotion_id = promo.id
              WHERE o.order_id = :order_id AND o.user_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':order_id', $order_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($order) {
        $originalPrice = $order['product_price'];
        $discountAmount = $order['discount_amount'] ? $order['discount_amount'] : 0;
        $finalPrice = $originalPrice - $discountAmount;
?>

        <!DOCTYPE html>
        <html lang="en">

        <head>
            <meta charset="UTF-8">
            <title>ร้องเรียนสินค้า</title>
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
                }

                .product-details {
                    display: flex;
                    align-items: center;
                    margin-bottom: 20px;
                    padding: 10px;
                    background-color: #444;
                    border-radius: 10px;
                }

                .product-details img {
                    max-width: 150px;
                    height: auto;
                    margin-right: 20px;
                    border-radius: 10px;
                }

                .product-info p {
                    margin: 5px 0;
                }

                label {
                    display: block;
                    margin-bottom: 10px;
                }

                textarea {
                    width: 100%;
                    padding: 10px;
                    border-radius: 5px;
                    border: none;
                    margin-bottom: 20px;
                }

                button {
                    padding: 10px 15px;
                    background-color: #ffa500;
                    color: white;
                    border: none;
                    border-radius: 5px;
                    cursor: pointer;
                    display: block;
                    width: 100%;
                }

                button:hover {
                    background-color: #e69500;
                }

                input[type="file"] {
                    margin-bottom: 15px;
                }
            </style>
        </head>

        <body>
            <div class="container">
                <h2>ร้องเรียนสินค้า</h2>

                <!-- แสดงรายละเอียดสินค้า -->
                <div class="product-details">
                    <?php
                    if (isset($order['product_picture'])) {
                        // แยกรูปภาพออกมา (ในกรณีที่มีหลายรูปภาพคั่นด้วยคอมม่า)
                        $images = explode(',', $order['product_picture']);
                        $firstImage = trim($images[0]); // รูปแรก
                        $imagePath = 'uploads/' . $firstImage;

                        // ตรวจสอบว่ารูปภาพนั้นมีอยู่จริงในโฟลเดอร์ uploads หรือไม่
                        if (file_exists($imagePath)) {
                            echo '<img src="' . $imagePath . '" alt="Product Image" style="max-width: 150px; height: auto; border-radius: 10px;">';
                        } else {
                            echo '<p>ไม่พบรูปภาพสินค้า</p>';
                        }
                    } else {
                        echo '<p>ไม่มีรูปภาพสินค้า</p>';
                    }
                    ?>

                    <div class="product-info">
                        <p><strong>ชื่อสินค้า:</strong> <?php echo htmlspecialchars($order['product_name']); ?></p>
                        <p><strong>จำนวน:</strong> <?php echo htmlspecialchars($order['order_quantity']); ?></p>
                        <p><strong>ราคา:</strong> ฿<?php echo number_format($originalPrice, 2); ?></p>
                        <?php if (!empty($order['promotion_code'])): ?>
                            <p><strong>โปรโมชั่นที่ใช้:</strong> <?php echo htmlspecialchars($order['promotion_code']); ?> (ส่วนลด <?php echo number_format($order['promotion_discount']); ?>%)</p>
                        <?php endif; ?>
                        <?php if ($discountAmount > 0): ?>
                            <p><strong>ส่วนลด:</strong> ฿<?php echo number_format($discountAmount, 2); ?></p>
                        <?php endif; ?>
                        <p><strong>ราคารวม:</strong> ฿<?php echo number_format($finalPrice * $order['order_quantity'], 2); ?></p>
                    </div>
                </div>

                <!-- ฟอร์มการร้องเรียน -->
                <form action="complaint.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                    <input type="hidden" name="product_id" value="<?php echo $order['product_id']; ?>"> <!-- ซ่อน product_id ในฟอร์ม -->
                    <label for="complaint_message">กรุณากรอกรายละเอียดการร้องเรียน:</label>
                    <textarea name="complaint_message" id="complaint_message" rows="5" required></textarea>

                    <label for="complaint_image">อัปโหลดรูปภาพประกอบการร้องเรียน (ถ้ามี):</label>
                    <input type="file" name="complaint_image" accept="image/*">

                    <button type="submit">ส่งคำร้องเรียน</button>
                </form>
            </div>
        </body>

        </html>

<?php
    } else {
        echo "<script>alert('ไม่พบคำสั่งซื้อ'); window.location.href='orderhistory.php';</script>";
    }
} else {
    echo "<script>alert('ไม่พบรหัสคำสั่งซื้อ'); window.location.href='orderhistory.php';</script>";
}
?>
