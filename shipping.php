<?php
session_start();
ob_start();
include_once('header.php');
include_once('config/Database.php');
include_once('nav.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$owner_id = $_SESSION['user_id'];

// ดึงคำสั่งซื้อที่รอการจัดส่ง
$query = "SELECT o.Order_ID as order_id, o.user_id, u.name as customer_name, p.product_id, p.product_name, p.product_picture, p.product_price, o.Order_Quantity, 
o.size, o.payment_method, o.qr_code_image, promo.code as coupon_code, promo.discount as coupon_discount, u.address as customer_address, u.phone as customer_phone
FROM orders o
JOIN products p ON o.product_id = p.product_id
JOIN users u ON o.user_id = u.id
LEFT JOIN promotions promo ON o.promotion_id = promo.id
WHERE p.user_id = :owner_id AND o.Order_Status = 'pending'";

$stmt = $conn->prepare($query);
$stmt->bindParam(':owner_id', $owner_id);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit_shipping'])) {
        $order_id = $_POST['order_id'] ?? '';
        $product_id = $_POST['product_id'] ?? '';
        $tracking_number = trim($_POST['tracking_number'] ?? '');
        $shipping_date = $_POST['shipping_date'] ?? '';
        $shipping_company = $_POST['shipping_company'] ?? '';

        // Debug: Print received data
        error_log("Received POST data: " . print_r($_POST, true));
        error_log("Received FILES data: " . print_r($_FILES, true));

        // ตรวจสอบว่าค่าทั้งหมดไม่เป็นค่าว่าง
        if (empty($order_id) || empty($product_id) || empty($tracking_number) || empty($shipping_date) || empty($shipping_company)) {
            echo "<script>alert('กรุณากรอกข้อมูลให้ครบถ้วน');</script>";
        } else {
            // อัปโหลดรูปภาพ
            $image_name = '';
            if (isset($_FILES['shipping_image']) && $_FILES['shipping_image']['error'] === 0) {
                $upload_dir = 'uploads/';
                $image_name = uniqid() . '_' . basename($_FILES['shipping_image']['name']);
                $upload_file = $upload_dir . $image_name;

                if (move_uploaded_file($_FILES['shipping_image']['tmp_name'], $upload_file)) {
                    error_log("File uploaded successfully: " . $upload_file);
                } else {
                    error_log("Failed to upload file: " . $upload_file);
                    echo "<script>alert('เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ');</script>";
                    $image_name = '';
                }
            }

            // บันทึกข้อมูลการจัดส่งในตาราง shipping_orders
            $query = "INSERT INTO shipping_orders (order_id, product_id, owner_id, tracking_number, shipping_date, shipping_company, shipping_image, status) 
                      VALUES (:order_id, :product_id, :owner_id, :tracking_number, :shipping_date, :shipping_company, :shipping_image, 'shipping')";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':order_id', $order_id);
            $stmt->bindParam(':product_id', $product_id);
            $stmt->bindParam(':owner_id', $owner_id);
            $stmt->bindParam(':tracking_number', $tracking_number);
            $stmt->bindParam(':shipping_date', $shipping_date);
            $stmt->bindParam(':shipping_company', $shipping_company);
            $stmt->bindParam(':shipping_image', $image_name);

            if ($stmt->execute()) {
                // อัปเดตสถานะคำสั่งซื้อเป็น 'shipped'
                $updateQuery = "UPDATE orders SET Order_Status = 'shipped' WHERE Order_ID = :order_id";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->bindParam(':order_id', $order_id);
                $updateStmt->execute();

                error_log("Shipping data inserted successfully");
                echo "<script>
                    alert('บันทึกข้อมูลการจัดส่งสำเร็จ');
                    window.location.href='shipping.php';
                </script>";
                exit();
            } else {
                $errorInfo = $stmt->errorInfo();
                error_log("Database error: " . print_r($errorInfo, true));
                echo "<script>alert('เกิดข้อผิดพลาดในการบันทึกข้อมูลการจัดส่ง: " . $errorInfo[2] . "');</script>";
            }
        }
    }

    // การยกเลิกคำสั่งซื้อ
    if (isset($_POST['cancel_order'])) {
        $order_id = $_POST['order_id'];

        if (!empty($order_id)) {
            $updateQuery = "UPDATE orders SET Order_Status = 'cancelled' WHERE Order_ID = :order_id";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bindParam(':order_id', $order_id);

            if ($updateStmt->execute()) {
                echo "<script>alert('ยกเลิกคำสั่งซื้อเรียบร้อย'); window.location.href='shipping.php';</script>";
            } else {
                echo "<script>alert('เกิดข้อผิดพลาดในการยกเลิกคำสั่งซื้อ');</script>";
            }
        } else {
            echo "<script>alert('ไม่พบข้อมูล Order ID');</script>";
        }
    }
}

// ดึงข้อมูลจากตาราง shipping_options
$shippingQuery = "SELECT shipping_name FROM shipping_options WHERE enabled = 1";
$shippingStmt = $conn->prepare($shippingQuery);
$shippingStmt->execute();
$shippingOptions = $shippingStmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>กรอกข้อมูลการจัดส่ง</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .shipping-container {
            display: flex;
            justify-content: space-between;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: #2b2b2b;
            color: white;
            border-radius: 10px;
        }

        .shipping-left, .shipping-right {
            width: 48%;
        }

        .shipping-left {
            background-color: #444;
            padding: 20px;
            border-radius: 10px;
        }

        .shipping-right {
            background-color: #444;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        input, select, button {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            background-color: #666;
            color: white;
            border: 1px solid #555;
        }

        button {
            background-color: #ffa500;
            cursor: pointer;
            border: none;
        }

        button:hover {
            background-color: #e69500;
        }

        .order-summary {
            display: flex;
            flex-direction: column;
            padding: 15px;
            border-radius: 10px;
            background-color: #333;
            min-height: 320px;
        }

        .order-details img {
            max-width: 100px;
            max-height: 100px;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <div class="shipping-container">
        <div class="shipping-left">
            <h2>กรอกข้อมูลการจัดส่ง</h2>
            <?php if (!empty($orders)): ?>
                <form action="shipping.php" method="POST" enctype="multipart/form-data" id="shippingForm">
                    <label for="order_id">เลือกคำสั่งซื้อ:</label>
                    <select name="order_id" id="order_id" required>
                        <?php foreach ($orders as $order): ?>
                            <option value="<?php echo $order['order_id']; ?>" 
                                    data-product-id="<?php echo $order['product_id']; ?>"
                                    data-product-name="<?php echo htmlspecialchars($order['product_name']); ?>"
                                    data-product-image="<?php echo htmlspecialchars($order['product_picture']); ?>"
                                    data-quantity="<?php echo $order['Order_Quantity']; ?>"
                                    data-size="<?php echo htmlspecialchars($order['size']); ?>"
                                    data-customer-name="<?php echo htmlspecialchars($order['customer_name']); ?>"
                                    data-customer-address="<?php echo htmlspecialchars($order['customer_address']); ?>"
                                    data-customer-phone="<?php echo htmlspecialchars($order['customer_phone']); ?>"
                                    data-payment-method="<?php echo htmlspecialchars($order['payment_method']); ?>"
                                    data-qr-code="<?php echo htmlspecialchars($order['qr_code_image']); ?>">
                                <?php echo $order['product_name']; ?> (จำนวน: <?php echo $order['Order_Quantity']; ?>) - โดย: <?php echo $order['customer_name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <input type="hidden" name="product_id" id="product_id" value="">

                    <label for="tracking_number">หมายเลขพัสดุ:</label>
                    <input type="text" name="tracking_number" id="tracking_number" required>

                    <label for="shipping_date">วันที่จัดส่ง:</label>
                    <input type="date" name="shipping_date" id="shipping_date" required>

                    <label for="shipping_company">บริษัทขนส่ง:</label>
                    <select name="shipping_company" id="shipping_company" required>
                        <?php foreach ($shippingOptions as $option): ?>
                            <option value="<?php echo htmlspecialchars($option['shipping_name']); ?>">
                                <?php echo htmlspecialchars($option['shipping_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="shipping_image">อัปโหลดรูปภาพการจัดส่ง:</label>
                    <input type="file" name="shipping_image" id="shipping_image" accept="image/*" onchange="previewQrImage(event)">
                    <img id="qr-code-preview" src="#" alt="Shipping Image Preview" style="display:none; max-width: 250px; border-radius: 8px; margin-bottom: 10px;" />

                    <button type="submit" name="submit_shipping">บันทึกข้อมูลการจัดส่ง</button>
                </form>
            <?php else: ?>
                <p>ไม่มีคำสั่งซื้อที่ต้องจัดส่ง</p>
            <?php endif; ?>
        </div>

        <div class="shipping-right" id="order-details">
            <h2>รายละเอียดการจัดส่ง</h2>
            <div class="order-summary">
                <div class="order-details">
                    <img id="product-image" src="" alt="Product Image">
                    <p id="product-name">ชื่อสินค้า: </p>
                    <p id="order-quantity">จำนวน: </p>
                    <p id="order-size">ไซส์: </p>
                    <p id="customer-name">ผู้ซื้อ: </p>
                    <p id="customer-address">ที่อยู่จัดส่ง: </p>
                    <p id="customer-phone">เบอร์โทรศัพท์: </p>
                    <p id="payment-method">วิธีชำระเงิน: </p>
                    <img id="qr-code-image" src="" alt="QR Code Slip" style="display:none;">
                </div>

                <form action="shipping.php" method="POST" onsubmit="return confirm('คุณแน่ใจว่าต้องการยกเลิกคำสั่งซื้อ?');">
                    <input type="hidden" name="order_id" id="cancel_order_id" value="">
                    <button type="submit" name="cancel_order" class="cancel-button">ยกเลิกคำสั่งซื้อ</button>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.getElementById('order_id').addEventListener('change', function() {
        var selectedOption = this.options[this.selectedIndex];

        // กำหนดค่าต่าง ๆ ให้กับ input และข้อมูลการจัดส่ง
        document.getElementById('product_id').value = selectedOption.getAttribute('data-product-id');
        document.getElementById('cancel_order_id').value = this.value;
        document.getElementById('product-name').textContent = 'ชื่อสินค้า: ' + selectedOption.getAttribute('data-product-name');
        document.getElementById('order-quantity').textContent = 'จำนวน: ' + selectedOption.getAttribute('data-quantity');
        document.getElementById('order-size').textContent = 'ไซส์: ' + selectedOption.getAttribute('data-size');
        document.getElementById('customer-name').textContent = 'ผู้ซื้อ: ' + selectedOption.getAttribute('data-customer-name');
        document.getElementById('customer-address').textContent = 'ที่อยู่จัดส่ง: ' + selectedOption.getAttribute('data-customer-address');
        document.getElementById('customer-phone').textContent = 'เบอร์โทรศัพท์: ' + selectedOption.getAttribute('data-customer-phone');
        document.getElementById('payment-method').textContent = 'วิธีชำระเงิน: ' + selectedOption.getAttribute('data-payment-method');

        // ดึงรูปภาพสินค้า
        var imagePath = 'uploads/' + selectedOption.getAttribute('data-product-image').split(',')[0];
        document.getElementById('product-image').src = imagePath;

        // ตรวจสอบวิธีการชำระเงินและแสดง QR Code หากมี
        var qrCodeImage = selectedOption.getAttribute('data-qr-code');
        var paymentMethod = selectedOption.getAttribute('data-payment-method');

        if (paymentMethod === 'qr_code' && qrCodeImage) {
            document.getElementById('qr-code-image').src = 'uploads/' + qrCodeImage;
            document.getElementById('qr-code-image').style.display = 'block';
        } else {
            document.getElementById('qr-code-image').style.display = 'none';
        }
    });

    // ฟังก์ชันสำหรับแสดงภาพตัวอย่างของ QR Code ก่อนอัปโหลด
    function previewQrImage(event) {
        const preview = document.getElementById('qr-code-preview');
        const file = event.target.files[0];
        const reader = new FileReader();

        preview.style.display = 'none';

        reader.onload = function() {
            preview.src = reader.result;
            preview.style.display = 'block'; 
        };

        if (file) {
            reader.readAsDataURL(file);
        }
    }

    // เมื่อหน้าเว็บโหลดเสร็จสิ้น ให้เลือกคำสั่งซื้อแรกสุดโดยอัตโนมัติ
    window.onload = function() {
        var orderSelect = document.getElementById('order_id');
        if (orderSelect) {
            orderSelect.dispatchEvent(new Event('change'));
        }
    };
</script>

</body>

</html>
