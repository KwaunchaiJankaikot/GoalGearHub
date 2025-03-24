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

$user_id = $_SESSION['user_id'];

// ดึงข้อมูลสินค้าจากตะกร้าของผู้ใช้จากฐานข้อมูล
$query = "SELECT p.product_id, p.product_name, p.product_price, p.product_picture, p.product_quantity, c.quantity, c.size 
          FROM cart c 
          JOIN products p ON c.product_id = p.product_id 
          WHERE c.user_id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ตรวจสอบว่ามีสินค้าหรือไม่
if (empty($cartItems)) {
    echo "<script>alert('ตะกร้าของคุณว่างเปล่า'); window.location.href='welcome.php';</script>";
    exit();
}

// กำหนดค่าเริ่มต้นสำหรับตัวแปรที่รับข้อมูลจากฟอร์ม
$name = isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '';
$address = isset($_POST['address']) ? htmlspecialchars($_POST['address']) : '';
$phone = isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '';
$paymentMethod = isset($_POST['payment_method']) ? htmlspecialchars($_POST['payment_method']) : '';
$couponCode = isset($_POST['coupon_code']) ? htmlspecialchars($_POST['coupon_code']) : '';

// ตรวจสอบการร้องขอ POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['apply_coupon'])) {
        $couponCode = trim($_POST['coupon_code']);
        $coupon = applyCoupon($conn, $couponCode); // ดึงคูปองจากฐานข้อมูล

        if ($coupon !== false) {
            $_SESSION['discount_percent'] = $coupon['discount']; // ส่วนลดเป็นเปอร์เซ็นต์
            $_SESSION['coupon_code'] = $coupon['code']; // เก็บชื่อคูปองลงใน session
            $_SESSION['promotion_id'] = $coupon['id']; // เก็บ promotion_id ไว้ใน session
            echo "<script>alert('คูปองถูกต้อง! ส่วนลดได้รับการใช้แล้ว');</script>";
        } else {
            echo "<script>alert('คูปองไม่ถูกต้องหรือหมดอายุแล้ว');</script>";
        }
    } elseif (isset($_POST['checkout'])) {
        $name = trim($_POST['name']);
        $address = trim($_POST['address']);
        $phone = trim($_POST['phone']);
        $paymentMethod = $_POST['payment_method'];
        $couponCode = isset($_SESSION['coupon_code']) ? $_SESSION['coupon_code'] : null; // ดึงรหัสคูปองจาก session
        $promotion_id = isset($_SESSION['promotion_id']) ? $_SESSION['promotion_id'] : null; // ดึง promotion_id จาก session

        // ตรวจสอบข้อมูลว่ากรอกครบถ้วนหรือไม่
        if (empty($name) || empty($address) || empty($phone) || empty($paymentMethod)) {
            echo "<script>alert('กรุณากรอกข้อมูลให้ครบถ้วนทุกช่องและเลือกวิธีการชำระเงิน');</script>";
        } else {
            // คำนวณราคาทั้งหมด
            $totalPrice = 0;
            foreach ($cartItems as $item) {
                $totalPrice += $item['product_price'] * $item['quantity'];
            }

            // ตรวจสอบการใช้คูปองและคำนวณส่วนลด
            $discountAmount = 0;
            if (isset($_SESSION['discount_percent'])) {
                $discountAmount = $totalPrice * ($_SESSION['discount_percent'] / 100);
                $totalPrice -= $discountAmount;
            }

            // บันทึกคำสั่งซื้อ พร้อมเก็บข้อมูลคูปองและวิธีชำระเงิน
            saveOrder($conn, $user_id, $cartItems, $couponCode, $promotion_id, $discountAmount, $paymentMethod);

            // Reset ส่วนลดเมื่อมีการเริ่มใหม่ทุกครั้ง
            unset($_SESSION['discount_percent']);
            unset($_SESSION['coupon_code']);
            unset($_SESSION['promotion_id']);

            // ล้างตะกร้า
            clearCart($conn, $user_id);

            echo "<script>alert('คำสั่งซื้อสำเร็จแล้ว!'); window.location.href='welcome.php';</script>";
        }
    }
}

// ฟังก์ชันบันทึกคำสั่งซื้อ
function saveOrder($conn, $user_id, $items, $couponCode, $promotion_id, $discountAmount, $paymentMethod)
{
    // ตรวจสอบว่าไฟล์ QR code มีการอัปโหลดหรือไม่
    $qr_code_name = NULL;
    if (isset($_FILES['qr_code_file']) && $_FILES['qr_code_file']['error'] === 0) {
        $upload_dir = 'uploads/';
        $qr_code_name = basename($_FILES['qr_code_file']['name']);
        $upload_file = $upload_dir . $qr_code_name;

        // ย้ายไฟล์ไปยังโฟลเดอร์ที่ต้องการ
        if (!move_uploaded_file($_FILES['qr_code_file']['tmp_name'], $upload_file)) {
            echo "เกิดข้อผิดพลาดในการอัปโหลดไฟล์ QR code";
        }
    }

    foreach ($items as $item) {
        $status = "pending";
        $orderTotal = $item['product_price'] * $item['quantity']; // ราคารวมต่อสินค้า (ก่อนหักส่วนลด)

        // ตรวจสอบว่าจำนวนสินค้าที่สั่งไม่เกินจำนวนที่มีอยู่ในคลัง
        if ($item['quantity'] > $item['product_quantity']) {
            echo "จำนวนสินค้าที่สั่งเกินกว่าจำนวนที่มีในคลัง";
            return; // หยุดการทำงานหากจำนวนเกินคลัง
        }

        // คำนวณส่วนลดต่อสินค้า
        $itemDiscount = 0;
        if (isset($_SESSION['discount_percent'])) {
            $itemDiscount = ($item['product_price'] * ($_SESSION['discount_percent'] / 100)) * $item['quantity']; // คำนวณส่วนลดต่อสินค้า
        }

        // คำนวณราคาสุทธิหลังหักส่วนลด
        $finalPrice = isset($_SESSION['discount_percent']) 
            ? ($item['product_price'] - $item['product_price'] * ($_SESSION['discount_percent'] / 100)) * $item['quantity'] 
            : $item['product_price'] * $item['quantity']; // ราคาสุทธิหลังหักส่วนลดต่อสินค้า

        $commission_rate = 0.10; // อัตราค่าคอมมิชชั่น 10%
        // คำนวณค่าคอมมิชชั่นและจำนวนเงินที่ผู้ขายจะได้รับ
        $commission_amount = $finalPrice * $commission_rate; // คำนวณค่าคอมมิชชั่น
        $seller_amount = $finalPrice - $commission_amount;   // คำนวณจำนวนเงินที่ผู้ขายได้รับหลังหักค่าคอมมิชชั่น

        // บันทึกคำสั่งซื้อในตาราง orders พร้อมค่าคอมมิชชั่นและจำนวนเงินที่ผู้ขายจะได้รับ
        $query = "INSERT INTO orders (user_id, product_id, Order_Total, Order_Quantity, Order_Status, promotion_id, discount_amount, final_price, payment_method, qr_code_image, size, commission_amount, seller_amount) 
                  VALUES (:user_id, :product_id, :total_price, :quantity, :status, :promotion_id, :discount_amount, :final_price, :payment_method, :qr_code_image, :size, :commission_amount, :seller_amount)";
        try {
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':product_id', $item['product_id']);
            $stmt->bindParam(':total_price', $orderTotal);
            $stmt->bindParam(':quantity', $item['quantity']);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':promotion_id', $promotion_id); // promotion_id ที่ถูกเก็บใน session
            $stmt->bindParam(':discount_amount', $itemDiscount); // ส่วนลดต่อสินค้า
            $stmt->bindParam(':final_price', $finalPrice); // ราคาสุทธิหลังหักส่วนลด
            $stmt->bindParam(':payment_method', $paymentMethod);
            $stmt->bindParam(':qr_code_image', $qr_code_name);
            $stmt->bindParam(':size', $item['size']); // เพิ่มการบันทึกไซส์ลงในคำสั่งซื้อ
            $stmt->bindParam(':commission_amount', $commission_amount); // ค่าคอมมิชชั่น
            $stmt->bindParam(':seller_amount', $seller_amount); // จำนวนเงินที่ผู้ขายได้รับ
            $stmt->execute();

            // ลดจำนวนสินค้าที่สั่งซื้อออกจากคลังสินค้า
            $updateQuery = "UPDATE products SET product_quantity = product_quantity - :quantity WHERE product_id = :product_id AND product_quantity >= :quantity";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bindParam(':quantity', $item['quantity']);
            $updateStmt->bindParam(':product_id', $item['product_id']);
            $updateStmt->execute();

            // ตรวจสอบว่ามีการอัปเดตจำนวนสินค้าหรือไม่
            if ($updateStmt->rowCount() == 0) {
                echo "ไม่สามารถอัปเดตจำนวนสินค้าคงคลังได้ โปรดตรวจสอบว่าในคลังมีสินค้าพอ";
                return;
            }
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}

// ฟังก์ชันล้างตะกร้าสินค้า
function clearCart($conn, $user_id)
{
    $query = "DELETE FROM cart WHERE user_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
}

// ฟังก์ชันการตรวจสอบและใช้คูปอง
function applyCoupon($conn, $couponCode)
{
    $query = "SELECT * FROM promotions WHERE code = :coupon_code AND start_date <= CURDATE() AND end_date >= CURDATE()";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':coupon_code', $couponCode);
    $stmt->execute();
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($coupon) {
        return $coupon; // คืนค่าข้อมูลคูปองทั้งหมด รวมถึง code, discount, และ promotion_id
    } else {
        return false;
    }
}
// ดึงข้อมูลบัญชีธนาคารจากตาราง account_numbers
$bankQuery = "SELECT account_name,bank_name, account_number FROM account_numbers";
$bankStmt = $conn->prepare($bankQuery);
$bankStmt->execute();
$bankAccounts = $bankStmt->fetchAll(PDO::FETCH_ASSOC);

$totalPrice = 0;
foreach ($cartItems as $item) {
    $totalPrice += $item['product_price'] * $item['quantity'];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout</title>
    <link rel="stylesheet" href="styles.css">
</head>
<style>
    /* เพิ่มสไตล์สำหรับหน้า checkout */
    .checkout-container {
        display: flex;
        justify-content: space-between;
        max-width: 1200px;
        margin: 20px auto;
        padding: 20px;
        background-color: #2b2b2b;
        color: white;
        border-radius: 10px;
    }

    .checkout-left {
        background-color: #444;
        padding: 20px;
        border-radius: 10px;
        width: 53%;
    }

    .checkout-right {
        background-color: #333;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        width: 55%;
    }

    .checkout-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
        border-radius: 10px;
        overflow: hidden;
    }

    .checkout-table th, .checkout-table td {
        border: 1px solid #444;
        padding: 12px 10px;
        text-align: left;
        background-color: #555;
    }

    .checkout-table th {
        background-color: #666;
        color: #ffffff;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .checkout-table tr:hover {
        background-color: #777;
    }

    .checkout-total-container {
        margin-top: 10px;
        padding: 10px;
        text-align: left;
    }

    .checkout-total {
        font-weight: bold;
        font-size: 18px;
        color: #ffa500;
    }

    label {
        display: block;
        margin-bottom: 5px;
        color: #ddd;
    }

    input, textarea, select, button {
        width: 100%;
        padding: 10px;
        margin-bottom: 10px;
        border-radius: 5px;
        border: 1px solid #555;
        background-color: #666;
        color: white;
    }

    button {
        background-color: #ffa500;
        cursor: pointer;
        transition: background-color 0.3s ease;
        border: none;
    }

    button:hover {
        background-color: #e69500;
    }

    .checkbox-container {
        margin-bottom: 10px;
        display: flex;
    }

    .checkbox-container label {
        margin-right: 50%;
        width: 800%;
        font-size: 16px;
    }

    .alert, .success-message {
        text-align: left;
        padding: 10px;
        font-size: 16px;
        flex-grow: 1;
        margin-left: 0px;
        margin-top: 10px;
        border-radius: 5px;
    }

    .alert {
        color: red;
        background-color: #ffdddd;
    }

    .success-message {
        color: green;
        background-color: #ddffdd;
    }

    .remove-item {
        color: red;
        cursor: pointer;
        text-align: center;
    }

    .remove-item button {
        background: transparent;
        border: none;
        color: red;
        font-size: 18px;
        cursor: pointer;
    }

    .remove-item button:hover {
        color: #ff5555;
    }

    .imgqr {
        width: 200px;
        height: auto;
        margin-top: 10px;
        border: 2px solid #444;
    }
</style>

<body>
    <div class="checkout-container">
        <!-- Left Section: Input Form -->
        <div class="checkout-left">
            <h2>ข้อมูลการจัดส่งและการชำระเงิน</h2>
            <form action="checkout.php" method="POST" enctype="multipart/form-data">
                <div class="checkbox-container">
                    <input type="checkbox" id="loadDataCheckbox" onclick="toggleUserData()">
                    <label for="loadDataCheckbox">ใช้ข้อมูลที่เคยบันทึกไว้</label>
                </div>

                <label for="name">ชื่อ:</label>
                <input type="text" name="name" id="name" value="<?php echo $name; ?>" required>

                <label for="address">ที่อยู่สำหรับจัดส่ง:</label>
                <textarea name="address" id="address" rows="3" required><?php echo $address; ?></textarea>

                <label for="phone">เบอร์โทรศัพท์:</label>
                <input type="text" name="phone" id="phone" value="<?php echo $phone; ?>" required>

                <label for="payment_method">เลือกวิธีการชำระเงิน:</label>
                <select name="payment_method" id="payment_method" required>
                    <option value="">เลือกวิธีการชำระเงิน</option>
                    <option value="qr_code" <?php echo ($paymentMethod === 'qr_code') ? 'selected' : ''; ?>>โอนจ่าย</option>
                    <option value="เก็บเงินปลายทาง" <?php echo ($paymentMethod === 'cod') ? 'selected' : ''; ?>>เก็บเงินปลายทาง</option>
                </select>

                <!-- แสดงข้อมูลบัญชีธนาคารเมื่อเลือกโอนจ่าย -->
                <div id="bank-info" style="display: none;">
                    <p><strong>กรุณาชำระเงินผ่านบัญชีธนาคาร:</strong></p>
                    <?php foreach ($bankAccounts as $account): ?>
                        <p>
                            ชื่อบัญชี: <?php echo htmlspecialchars($account['account_name']); ?><br>
                            เลขที่บัญชี: <?php echo htmlspecialchars($account['account_number']); ?><br>
                            ธนาคาร: <?php echo htmlspecialchars($account['bank_name']); ?>
                        </p>
                    <?php endforeach; ?>
                    <label for="qr_code_file">อัปโหลดสลิปโอนเงิน:</label>
                    <input type="file" id="qr_code_file" name="qr_code_file" onchange="previewQrImage(event)">
                    <img id="qr-code-preview" class="imgqr" style="display:none;" alt="QR Code Preview">
                </div>

                <form action="checkout.php" method="POST" enctype="multipart/form-data">
                    <!-- ฟิลด์สำหรับกรอกรหัสคูปอง -->
                    <label for="coupon_code">รหัสคูปอง:</label>
                    <input type="text" name="coupon_code" id="coupon_code" value="<?php echo $couponCode; ?>">

                    <button type="submit" name="apply_coupon">ใช้คูปอง</button>
                    <button type="submit" name="checkout">ดำเนินการชำระเงิน</button>
                </form>
            </form>
        </div>

        <!-- Right Section: Order Details -->
        <div class="checkout-right">
            <h2>รายละเอียดการสั่งซื้อ</h2>

            <table class="checkout-table">
                <tr>
                    <th>รูปภาพ</th>
                    <th>ชื่อสินค้า</th>
                    <th>ไซส์</th> <!-- เพิ่มคอลัมน์สำหรับไซส์ -->
                    <th>ราคาต่อหน่วย</th>
                    <th>จำนวน</th>
                    <th>ส่วนลด</th>
                    <th>ราคารวม</th>
                </tr>
                <?php
                $totalDiscount = 0;
                foreach ($cartItems as $item):
                    $itemTotalPrice = $item['product_price'] * $item['quantity'];
                    $discount = 0;

                    // คำนวณส่วนลดถ้ามีการใช้คูปอง
                    if (isset($_SESSION['discount_percent'])) {
                        $discount = $itemTotalPrice * ($_SESSION['discount_percent'] / 100);
                    }

                    $totalDiscount += $discount;
                    $finalPrice = $itemTotalPrice - $discount;
                ?>
                    <tr>
                        <td>
                            <?php
                            $images = explode(',', str_replace(['[', ']', '"'], '', $item['product_picture']));
                            $imagePath = 'uploads/' . htmlspecialchars(trim($images[0]));
                            ?>
                            <img src="<?php echo $imagePath; ?>" alt="Product Image" width="80">
                        </td>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td><?php echo isset($item['size']) ? htmlspecialchars($item['size']) : 'ไม่ระบุ'; ?></td> <!-- เพิ่มการตรวจสอบว่าไซส์มีหรือไม่ -->
                        <td>฿<?php echo number_format($item['product_price'], 2); ?></td>
                        <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                        <td>฿<?php echo number_format($discount, 2); ?></td>
                        <td>฿<?php echo number_format($finalPrice, 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <div class="checkout-total-container">
                <p class="checkout-total">ราคารวมทั้งหมด: ฿<?php echo number_format($totalPrice, 2); ?></p>

                <?php if (isset($_SESSION['discount_percent'])): ?>
                    <p class="checkout-total">ส่วนลดทั้งหมด: -฿<?php echo number_format($totalDiscount, 2); ?></p>
                    <p class="checkout-total">ราคาหลังจากหักส่วนลด: ฿<?php echo number_format($totalPrice - $totalDiscount, 2); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function toggleUserData() {
            const isChecked = document.getElementById('loadDataCheckbox').checked;
            if (isChecked) {
                fetch('get_user_data.php')
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('name').value = data.name;
                        document.getElementById('address').value = data.address;
                        document.getElementById('phone').value = data.phone;
                    })
                    .catch(error => console.error('Error fetching user data:', error));
            } else {
                document.getElementById('name').value = '';
                document.getElementById('address').value = '';
                document.getElementById('phone').value = '';
            }
        }

        function previewQrImage(event) {
            const input = event.target;
            const reader = new FileReader();
            reader.onload = function() {
                const previewImage = document.getElementById('qr-code-preview');
                previewImage.src = reader.result;
                previewImage.style.display = 'block';
            };
            reader.readAsDataURL(input.files[0]);
        }

        document.getElementById('payment_method').addEventListener('change', function() {
            const selectedMethod = this.value;
            const bankInfoDiv = document.getElementById('bank-info');
            if (selectedMethod === 'qr_code') {
                bankInfoDiv.style.display = 'block';
            } else {
                bankInfoDiv.style.display = 'none';
            }
        });
    </script>

</body>
</html>
