<?php
// เริ่ม session
session_start();

// รวมไฟล์ Database.php เพื่อเชื่อมต่อกับฐานข้อมูล
include('../config/Database.php');
include('header.php');

// ตรวจสอบว่า admin ได้ล็อกอินหรือไม่ ถ้าไม่ได้ล็อกอินให้เปลี่ยนเส้นทางไปยังหน้า login.php
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// สร้างวัตถุ Database และเชื่อมต่อกับฐานข้อมูล
$database = new Database();
$conn = $database->getConnection();

// ฟังก์ชันสำหรับอัปโหลดรูปภาพ
function uploadImages($files, $target_dir = "../uploads/")
{
    $uploaded_files = [];
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    foreach ($files["name"] as $key => $name) {
        $unique_name = uniqid() . "_" . basename($name);
        $target_file = $target_dir . $unique_name;
        if (move_uploaded_file($files["tmp_name"][$key], $target_file)) {
            $uploaded_files[] = $unique_name;
        }
    }
    return $uploaded_files;
}

// ตรวจสอบการส่งฟอร์มเพื่ออัปเดตข้อมูลสินค้า
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_product'])) {
    // รับค่าจากฟอร์มและกรองข้อมูล
    $product_id = intval($_POST['product_id']);
    $product_name = htmlspecialchars(trim($_POST['product_name']), ENT_QUOTES, 'UTF-8');
    $product_brand = htmlspecialchars(trim($_POST['product_brand']), ENT_QUOTES, 'UTF-8');
    $product_price = floatval($_POST['product_price']);
    $product_quantity = intval($_POST['product_quantity']);
    $product_status = htmlspecialchars(trim($_POST['product_status']), ENT_QUOTES, 'UTF-8');
    $product_category = intval($_POST['product_category']);
    $product_description = htmlspecialchars(trim($_POST['product_description']), ENT_QUOTES, 'UTF-8');
    $product_sizes = isset($_POST['product_size']) ? implode(',', $_POST['product_size']) : '';
    $team_name = isset($_POST['team_name']) ? htmlspecialchars(trim($_POST['team_name']), ENT_QUOTES, 'UTF-8') : '';

    // ตรวจสอบว่ามีการอัปโหลดรูปภาพใหม่หรือไม่
    $uploaded_files = [];
    if (!empty($_FILES['product_images']['name'][0])) {
        $uploaded_files = uploadImages($_FILES['product_images']);
        $images_string = implode(',', $uploaded_files);

        // อัปเดตรูปภาพในฐานข้อมูล
        $query = "UPDATE products SET product_picture = :product_picture WHERE product_id = :product_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':product_picture', $images_string);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
    }

    // อัปเดตข้อมูลสินค้า
    $query = "UPDATE products 
              SET product_name = :product_name, 
                  product_brand = :product_brand, 
                  product_price = :product_price, 
                  product_quantity = :product_quantity, 
                  product_status = :product_status, 
                  category = :product_category, 
                  product_description = :product_description, 
                  product_sizes = :product_sizes, 
                  team_name = :team_name
              WHERE product_id = :product_id";

    $stmt = $conn->prepare($query);

    // ผูกค่ากับพารามิเตอร์
    $stmt->bindParam(':product_name', $product_name);
    $stmt->bindParam(':product_brand', $product_brand);
    $stmt->bindParam(':product_price', $product_price);
    $stmt->bindParam(':product_quantity', $product_quantity);
    $stmt->bindParam(':product_status', $product_status);
    $stmt->bindParam(':product_category', $product_category);
    $stmt->bindParam(':product_description', $product_description);
    $stmt->bindParam(':product_sizes', $product_sizes);
    $stmt->bindParam(':team_name', $team_name);
    $stmt->bindParam(':product_id', $product_id);

    // ตรวจสอบการอัปเดตสำเร็จหรือไม่
    if ($stmt->execute()) {
        $_SESSION['success'] = "อัปเดตสินค้าสำเร็จ!";
    } else {
        $_SESSION['error'] = "ไม่สามารถอัปเดตสินค้าได้";
    }

    // รีเฟรชหน้าหลังจากอัปเดตข้อมูลเสร็จสิ้น
    header("Location: productmanage.php");
    exit();
}

if (isset($_GET['delete_id'])) {
    $product_id = intval($_GET['delete_id']);
    
    try {
        // เริ่มต้น transaction
        $conn->beginTransaction();
        
        // ลบรายการในตาราง cart ที่อ้างอิงกับ product_id
        $deleteCartQuery = "DELETE FROM cart WHERE product_id = :product_id";
        $stmt = $conn->prepare($deleteCartQuery);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        
        // ลบสินค้าจากตาราง products
        $deleteProductQuery = "DELETE FROM products WHERE product_id = :product_id";
        $stmt = $conn->prepare($deleteProductQuery);
        $stmt->bindParam(':product_id', $product_id);
        
        if ($stmt->execute()) {
            // ยืนยัน transaction ถ้าลบสำเร็จ
            $conn->commit();
            $_SESSION['success'] = "ลบสินค้าสำเร็จ!";
        } else {
            // ยกเลิก transaction ถ้าเกิดข้อผิดพลาด
            $conn->rollBack();
            $_SESSION['error'] = "ไม่สามารถลบสินค้าได้";
        }
        
    } catch (Exception $e) {
        // ยกเลิก transaction ถ้าเกิดข้อผิดพลาด
        $conn->rollBack();
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการลบสินค้า: " . $e->getMessage();
    }
    
    header("Location: productmanage.php");
    exit();
}


// ดึงข้อมูลสินค้าทั้งหมดและหมวดหมู่ที่เกี่ยวข้องจากตาราง products และ categories
$query = "SELECT p.*, c.name AS category_name, p.category AS category_id 
          FROM products p 
          LEFT JOIN categories c ON p.category = c.id";
$stmt = $conn->prepare($query);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลหมวดหมู่ทั้งหมดจากตาราง categories
$query = "SELECT * FROM categories";
$stmt = $conn->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลทีมฟุตบอลทั้งหมดจากตาราง teams
$query = "SELECT * FROM teams";
$stmt = $conn->prepare($query);
$stmt->execute();
$teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products</title>
    <style>
        .container {
            background-color: #34495e;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.4);
            width: 100%;
            max-width: 1300px;
            overflow-x: auto;
        }

        h1,
        h2 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 28px;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }

        .alert-success {
            background-color: #27ae60;
            color: white;
        }

        .alert-danger {
            background-color: #e74c3c;
            color: white;
        }

        .actions {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        input,
        select,
        button,
        textarea {
            padding: 10px;
            border-radius: 5px;
            border: none;
            margin-bottom: 10px;
            font-size: 14px;
            width: 100%;
            box-sizing: border-box;
        }

        button {
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #ecf0f1;
            font-size: 14px;
        }

        th {
            background-color: #2980b9;
            color: white;
        }

        td {
            word-wrap: break-word;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .product-images img {
            width: 50px;
            height: 50px;
            margin: 5px;
        }

        .edit-form {
            display: none;
        }

        .edit-form td form {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            width: 100%;
            max-width: 600px;
            border: 1px solid transparent;
            border-radius: 8px;
            padding: 20px;
            background-color: #2c3e50;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
            margin-left: 300px;
        }

        .edit-form td form input,
        .edit-form td form select,
        .edit-form td form textarea,
        .edit-form td form button {
            margin-bottom: 10px;
        }

        .edit-form td form button {
            width: 100%;
            background-color: #27ae60;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
        }

        .edit-form td form button:hover {
            background-color: #2ecc71;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            table,
            th,
            td {
                font-size: 12px;
            }

            .edit-form td form input,
            .edit-form td form select,
            .edit-form td form textarea,
            .edit-form td form button {
                font-size: 12px;
            }
        }
        .btnall{
           margin-top: 10px;
           text-align: center;
        }

    </style>
</head>

<body>
    <div class="container">
        <h1>จัดการสินค้าทั้งหมด</h1>

        <!-- ข้อความแจ้งเตือน -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success'];
                unset($_SESSION['success']); ?>
            </div>
        <?php elseif (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php echo $_SESSION['error'];
                unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- ตารางแสดงสินค้าทั้งหมด -->
        <table>
            <thead>
                <tr>
                    <th>ชื่อสินค้า</th>
                    <th>แบรนด์</th>
                    <th>ราคา</th>
                    <th>จำนวน</th>
                    <th>สถานะ</th>
                    <th>หมวดหมู่</th>
                    <th>ทีมฟุตบอล</th>
                    <th>ไซส์</th>
                    <th>คำอธิบาย</th>
                    <th>รูปภาพ</th>
                    <th>การดำเนินการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($product['product_brand']); ?></td>
                        <td><?php echo htmlspecialchars($product['product_price']); ?></td>
                        <td><?php echo htmlspecialchars($product['product_quantity']); ?></td>
                        <td><?php echo htmlspecialchars($product['product_status']); ?></td>
                        <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                        <td><?php echo htmlspecialchars($product['team_name']); ?></td>
                        <td><?php echo htmlspecialchars($product['product_sizes']); ?></td>
                        <td><?php echo htmlspecialchars($product['product_description']); ?></td>
                        <td>
                            <?php
                            $images = explode(',', $product['product_picture']);
                            foreach ($images as $image) {
                                echo '<img src="../uploads/' . htmlspecialchars($image) . '" width="50" height="50">';
                            }
                            ?>
                        </td>
                        <td>
                            <button class="btn btn-success edit-btn" onclick="showEditForm(<?php echo $product['product_id']; ?>)">แก้ไข</button>
                            <button class="btn btn-danger delete-btn " onclick="confirmDelete(<?php echo $product['product_id']; ?>)">ลบ</button>
                        </td>
                    </tr>
                    <!-- ฟอร์มแก้ไขสินค้า -->
                    <tr id="edit-form-<?php echo $product['product_id']; ?>" class="edit-form">
                        <td colspan="11">
                            <form method="post" enctype="multipart/form-data">
                                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                <input type="text" name="product_name" value="<?php echo htmlspecialchars($product['product_name']); ?>" required>
                                <input type="text" name="product_brand" value="<?php echo htmlspecialchars($product['product_brand']); ?>" required>
                                <input type="number" step="0.01" name="product_price" value="<?php echo htmlspecialchars($product['product_price']); ?>" required>
                                <input type="number" name="product_quantity" value="<?php echo htmlspecialchars($product['product_quantity']); ?>" required>
                                <select name="product_status" required>
                                    <option value="available" <?php if ($product['product_status'] == 'available') echo 'selected'; ?>>พร้อมขาย</option>
                                    <option value="unavailable" <?php if ($product['product_status'] == 'unavailable') echo 'selected'; ?>>ไม่พร้อมขาย</option>
                                </select>
                                <select name="product_category" required>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" <?php if ($category['id'] == $product['category_id']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <label for="product_size">เลือกไซส์ (สามารถเลือกได้หลายอัน):</label>
                                <select name="product_size[]" id="product_size" multiple>
                                    <option value="S">S</option>
                                    <option value="M">M</option>
                                    <option value="L">L</option>
                                    <option value="XL">XL</option>
                                    <option value="2XL">2XL</option>
                                    <option value="3XL">3XL</option>
                                    <option value="4XL">4XL</option>
                                    <option value="5XL">5XL</option>
                                    <option value="8US">8US</option>
                                    <option value="9US">9US</option>
                                    <option value="10US">10US</option>
                                    <option value="11US">11US</option>
                                    <option value="12US">12US</option>
                                    <option value="13US">13US</option>
                                </select>
                                <label for="team_name">ทีมฟุตบอล:</label>
                                <select name="team_name">
                                    <?php foreach ($teams as $team): ?>
                                        <option value="<?php echo htmlspecialchars($team['team_name']); ?>" <?php if ($team['team_name'] == $product['team_name']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($team['team_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <label for="product_images">อัปโหลดรูปภาพใหม่:</label>
                                <input type="file" name="product_images[]" multiple>
                                <textarea name="product_description" required><?php echo htmlspecialchars($product['product_description']); ?></textarea>
                                <button type="submit" name="edit_product">บันทึกการเปลี่ยนแปลง</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="btnall">
        <a href="admin_dashboard.php" class="btngoback btn btn-danger">กลับหน้าแรก</a>
        </div>
    </div>


    <script>
        function showEditForm(productId) {
            var form = document.getElementById('edit-form-' + productId);
            form.style.display = form.style.display === 'none' ? 'table-row' : 'none';
        }

        function confirmDelete(productId) {
            if (confirm('คุณแน่ใจหรือว่าต้องการลบสินค้านี้?')) {
                window.location.href = 'productmanage.php?delete_id=' + productId;
            }
        }
    </script>
</body>

</html>