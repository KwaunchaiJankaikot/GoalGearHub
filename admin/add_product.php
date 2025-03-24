<?php
session_start();
include_once('../config/Database.php');
include_once('header.php');

// ตรวจสอบว่า admin เข้าสู่ระบบหรือไม่
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();

$admin_id = $_SESSION['admin_id']; // ใช้ admin_id สำหรับแอดมิน

// ฟังก์ชันเพื่ออัปโหลดรูปภาพ
function getUniqueFileName($target_dir, $file_name)
{
    $path_parts = pathinfo($file_name);
    $base_name = $path_parts['filename'];
    $extension = isset($path_parts['extension']) ? '.' . $path_parts['extension'] : '';
    $unique_name = $base_name . $extension;
    $counter = 1;

    while (file_exists($target_dir . $unique_name)) {
        $unique_name = $base_name . '_' . $counter . $extension;
        $counter++;
    }

    return $unique_name;
}

function uploadImages($files, $target_dir = "uploads/")
{
    $uploaded_files = [];
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    foreach ($files["name"] as $key => $name) {
        $unique_name = getUniqueFileName($target_dir, basename($name));
        $target_file = $target_dir . $unique_name;
        if (move_uploaded_file($files["tmp_name"][$key], $target_file)) {
            $clean_path = str_replace("\\", "/", basename($unique_name));
            $uploaded_files[] = $clean_path;
        }
    }
    return $uploaded_files;
}

// ถ้ามีการส่งฟอร์มเพื่อเพิ่มสินค้า
if (isset($_POST['add_product'])) {
    $product_name = $_POST['product_name'];
    $product_brand = $_POST['product_brand'];
    $product_price = $_POST['product_price'];
    $product_quantity = $_POST['product_quantity'];
    $product_status = $_POST['product_status'];
    $product_category = $_POST['product_category'];
    $product_description = $_POST['product_description'];
    $team_name = isset($_POST['football_team']) ? $_POST['football_team'] : ''; // ใช้ team_name

    // เก็บข้อมูลไซส์ที่ถูกเลือก (เช่น S, M, L สำหรับเสื้อผ้า หรือ 8US, 9US สำหรับรองเท้า)
    $product_size = isset($_POST['product_size']) ? implode(',', $_POST['product_size']) : '';

    $uploaded_files = uploadImages($_FILES['product_images']);
    $images_string = implode(',', $uploaded_files);

    // เพิ่มข้อมูลสินค้า ใช้ team_name แทน team_id และ admin_id แทน user_id
    $query = "INSERT INTO products (user_id, product_name, product_brand, product_price, product_quantity, product_status, category, product_description, product_sizes, team_name, product_picture, approved) 
              VALUES (:admin_id, :product_name, :product_brand, :product_price, :product_quantity, :product_status, :product_category, :product_description, :product_sizes, :team_name, :images, 1)"; // approved = 1 for admin
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':admin_id', $admin_id); // ใช้ admin_id แทน
    $stmt->bindParam(':product_name', $product_name);
    $stmt->bindParam(':product_brand', $product_brand);
    $stmt->bindParam(':product_price', $product_price);
    $stmt->bindParam(':product_quantity', $product_quantity);
    $stmt->bindParam(':product_status', $product_status);
    $stmt->bindParam(':product_category', $product_category);
    $stmt->bindParam(':product_description', $product_description);
    $stmt->bindParam(':product_sizes', $product_size);
    $stmt->bindParam(':team_name', $team_name); // ใช้ team_name
    $stmt->bindParam(':images', $images_string);
    $stmt->execute();

    $_SESSION['success_product'] = "Product added successfully.";
    header("Location: admin_add_product.php");
    exit();
}

// ฟังก์ชันสำหรับลบสินค้า
if (isset($_GET['delete'])) {
    $product_id = $_GET['delete'];
    $query = "DELETE FROM products WHERE product_id = :product_id AND user_id = :admin_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':product_id', $product_id);
    $stmt->bindParam(':admin_id', $admin_id); // ใช้ admin_id แทน user_id
    $stmt->execute();
    $_SESSION['success_product'] = "Product deleted successfully.";
    header("Location: admin_add_product.php");
    exit();
}

// ดึงข้อมูลสินค้าทั้งหมดที่ผู้ใช้เพิ่ม โดยเชื่อมกับตาราง categories เพื่อดึงชื่อหมวดหมู่
$query = "SELECT products.*, categories.name as category_name FROM products 
          JOIN categories ON products.category = categories.id 
          WHERE products.admin_id = :admin_id"; // เปลี่ยนจาก user_id เป็น admin_id
$stmt = $conn->prepare($query);
$stmt->bindParam(':admin_id', $_SESSION['admin_id']); // ใช้ admin_id ของแอดมิน
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ดึงข้อมูลหมวดหมู่สินค้าทั้งหมด
$query = "SELECT * FROM categories";
$stmt = $conn->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลแบรนด์สินค้าทั้งหมด
$query = "SELECT * FROM brands";
$stmt = $conn->prepare($query);
$stmt->execute();
$brands = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
            max-width: 1100px;
            overflow-x: auto;
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 28px;
        }

        form {
            max-width: 500px;
            margin: 0 auto;
            background-color: #34495e;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        }

        input,
        select,
        button,
        textarea,
        .btngoback {
            display: block;
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            border: none;
            font-size: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: #34495e;
            color: white;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }

        th {
            background-color: #2980b9;
            color: white;
            text-align: center;
        }

        td.description {
            word-wrap: break-word;
            overflow-wrap: break-word;
            white-space: normal;
            max-width: 230px;
        }

        button {
            padding: 10px;
            border-radius: 5px;
            border: none;
            background-color: #27ae60;
            color: white;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btngoback {
            background-color: red;
        }

        .edit-btn {
            background-color: #27ae60;
        }

        .delete-btn {
            background-color: #e74c3c;
            color: white;
            text-decoration: none;
            padding: 10px;
            border-radius: 3px;
            transition: background-color 0.3s ease;
            border: none;
            display: inline-block;
            text-align: center;
            cursor: pointer;
        }

        .delete-btn:hover {
            background-color: #c0392b;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>เพิ่มสินค้า (แอดมิน)</h1>
        <!-- การแจ้งเตือน -->
        <?php if (isset($_SESSION['success_product'])): ?>
            <div class="alert alert-success" role="alert">
                <?php echo htmlspecialchars($_SESSION['success_product']); ?>
            </div>
            <?php unset($_SESSION['success_product']); ?>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <input type="text" name="product_name" placeholder="ชื่อสินค้า" required>

            <select name="product_brand" id="product_brand" required>
                <option value="">เลือกแบรนด์</option>
                <?php foreach ($brands as $brand): ?>
                    <option value="<?php echo htmlspecialchars($brand['name']); ?>"><?php echo htmlspecialchars($brand['name']); ?></option>
                <?php endforeach; ?>
            </select>

            <input type="number" step="0.01" name="product_price" placeholder="ราคา" required>
            <input type="file" name="product_images[]" multiple required>

            <select name="product_status" required>
                <option value="available">พร้อมขาย</option>
                <option value="unavailable">ไม่พร้อมขาย</option>
            </select>

            <input type="number" name="product_quantity" placeholder="จำนวนสินค้าที่มี" required>

            <select name="product_category" id="product_category" required>
                <option value="">เลือกหมวดหมู่</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
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

            <label for="football_team">เลือกทีมฟุตบอล</label>
            <select name="football_team" id="football_team">
                <option value="">เลือกทีมฟุตบอล</option>
                <?php foreach ($teams as $team): ?>
                    <option value="<?php echo htmlspecialchars($team['team_name']); ?>"><?php echo htmlspecialchars($team['team_name']); ?></option>
                <?php endforeach; ?>
            </select>

            <textarea name="product_description" placeholder="รายละเอียดสินค้า" required></textarea>
            <button type="submit" name="add_product">เพิ่มสินค้า</button>
            <a href="admin_dashboard.php" class="btngoback btn btn-danger">กลับหน้าแรก</a>
        </form>

        <h2>สินค้าที่เพิ่มโดยแอดมิน</h2>
        <table>
            <thead>
                <tr>
                    <th>ชื่อ</th>
                    <th>แบรนด์</th>
                    <th>ราคา</th>
                    <th>จำนวน</th>
                    <th>สถานะ</th>
                    <th>หมวดหมู่</th>
                    <th>ไซส์</th>
                    <th>ทีมฟุตบอล</th>
                    <th>รูปภาพ</th>
                    <th>อนุมัติ</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($result as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['product_brand']); ?></td>
                        <td><?php echo htmlspecialchars($row['product_price']); ?></td>
                        <td><?php echo htmlspecialchars($row['product_quantity']); ?></td>
                        <td><?php echo htmlspecialchars($row['product_status']); ?></td>
                        <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['product_sizes']); ?></td>
                        <td>
                            <?php
                            echo htmlspecialchars($row['team_name']); // แสดงชื่อทีม
                            ?>
                        </td>
                        <td>
                            <?php
                            $images = explode(',', $row['product_picture']);
                            foreach ($images as $image) {
                                echo '<img src="uploads/' . htmlspecialchars(trim($image)) . '" alt="Product Image" style="width:50px;height:50px;">';
                            }
                            ?>
                        </td>
                        <td><?php echo $row['approved'] ? 'อนุมัติ' : 'ยังไม่อนุมัติ'; ?></td>
                        <td>
                            <button class="edit-btn" onclick="showEditForm(<?php echo $row['product_id']; ?>)">แก้ไข</button>
                            <button class="delete-btn" onclick="confirmDelete(<?php echo $row['product_id']; ?>)">ลบ</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    </div>
</body>

</html>
