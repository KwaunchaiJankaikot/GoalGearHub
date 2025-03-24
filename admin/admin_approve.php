<?php
// เริ่มต้นเซสชัน
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ตรวจสอบว่าแอดมินล็อกอินหรือยัง
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// รวมการเชื่อมต่อฐานข้อมูล
include_once('../config/Database.php'); // ใช้เส้นทางสัมพัทธ์
include_once('header.php');
$connectDB = new Database();
$db = $connectDB->getConnection();

// รับสินค้าที่ยังไม่ได้รับการอนุมัติ
$query = "SELECT * FROM products WHERE approved = 0";
$stmt = $db->prepare($query);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// การอนุมัติหรือปฏิเสธสินค้า
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $productId = $_POST['product_id'];
    if (isset($_POST['approve'])) {
        $query = "UPDATE products SET approved = 1 WHERE product_id = :product_id";
    } elseif (isset($_POST['reject'])) {
        $query = "DELETE FROM products WHERE product_id = :product_id";
    }
    $stmt = $db->prepare($query);
    $stmt->bindParam(':product_id', $productId);
    $stmt->execute();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Approve Products</title>
    <style>
        /* Container styles */
        .container {
            background-color: #34495e;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.4);
            width: 100%;
            max-width: 1100px;
            overflow-x: auto;
            margin-top: 15px;
        }

        /* Header styles */
        h1 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 28px;
        }

        /* Table styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: #34495e;
            color: white;
        }

        /* Table header and cell styles */
        th, td {
            padding: 12px;
            text-align: center;
            vertical-align: middle;
            border-bottom: 1px solid #ecf0f1;
            word-wrap: break-word; /* ตัดบรรทัดถ้ายาวเกินไป */
            white-space: normal;
            overflow-wrap: break-word; /* ตัดบรรทัดที่คำยาวเกินไป */
            max-width: 200px; /* ความกว้างสูงสุดของคอลัมน์ */
        }

        th {
            background-color: #2980b9;
            color: white;
        }

        /* ตัดบรรทัดให้คำอธิบาย */
        .description {
            max-width: 300px;
            white-space: normal;
            overflow-wrap: break-word;
            word-wrap: break-word;
        }

        /* Button styles */
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
        .btnall{
            /* border:1px solid red; */
            text-align: center;
        }

        /* Go back button styles */
        .goback {
            margin-top: 10px;
        }

        /* Reject button styles */
        .reject {
            /* background-color: #e74c3c; */
        }

        .reject:hover {
            background-color: #c0392b;
        }

        .product-images img {
            margin-right: 5px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>อนุมัติสินค้า</h1>

        <table border="0">
            <tr>
                <th>ชื่อ</th>
                <th>แบรนด์</th>
                <th>ราคา</th>
                <th>คำอธิบาย</th>
                <th>รูป</th>
                <th></th>
            </tr>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                    <td><?php echo htmlspecialchars($product['product_brand']); ?></td>
                    <td><?php echo htmlspecialchars($product['product_price']); ?></td>
                    <td class="description"><?php echo htmlspecialchars($product['product_description']); ?></td>
                    <td>
                        <div class="product-images">
                            <?php
                            $images = explode(',', $product['product_picture']);
                            if ($images) {
                                foreach ($images as $image) {
                                    $image_url = '../uploads/' . htmlspecialchars(trim($image));
                                    if (file_exists($image_url)) {
                                        echo '<img src="' . $image_url . '" alt="Product Image" style="width:50px;height:50px;">';
                                    } else {
                                        echo 'Image not found: ' . $image_url;
                                    }
                                }
                            } else {
                                echo "No images available.";
                            }
                            ?>
                        </div>
                    </td>
                    <td>
                        <form method="POST" action="admin_approve.php">
                            <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                            <button type="submit" name="approve" class="edit btn btn-success">อนุมัติ</button>
                            <button type="submit" name="reject" class="reject btn btn-danger">ปฎิเสธ</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <div class="btnall">
        <a href="admin_dashboard.php"><button class="btn btn-danger goback">กลับหน้าแรก</button></a>
        </div>
    </div>
</body>

</html>
