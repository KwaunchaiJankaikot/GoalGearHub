<?php
session_start();
include_once('../config/Database.php');


if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['product_id'])) {
    die('Product ID is required');
}

$product_id = $_GET['product_id'];
$db = new Database();
$conn = $db->getConnection();

// ดึงข้อมูลหมวดหมู่สินค้าจากฐานข้อมูล
$query = "SELECT id, name FROM categories";
$stmt = $conn->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_name = $_POST['product_name'];
    $product_brand = $_POST['product_brand'];
    $product_price = $_POST['product_price'];
    $product_status = $_POST['product_status'];
    $product_quantity = $_POST['product_quantity'];
    $category_id = $_POST['category_id'];

    // การอัปโหลดไฟล์
    if(isset($_FILES["product_picture"]) && $_FILES["product_picture"]["error"] == 0) {
        $target_dir = "../img/";
        $target_file = $target_dir . basename($_FILES["product_picture"]["name"]);
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // ตรวจสอบว่าคือไฟล์รูปภาพ
        $check = getimagesize($_FILES["product_picture"]["tmp_name"]);
        if($check !== false) {
            $uploadOk = 1;
        } else {
            echo "File is not an image.";
            $uploadOk = 0;
        }

        // ตรวจสอบว่าไฟล์มีอยู่แล้วหรือไม่
        if (file_exists($target_file)) {
            echo "Sorry, file already exists.";
            $uploadOk = 0;
        }

        // ตรวจสอบขนาดไฟล์
        if ($_FILES["product_picture"]["size"] > 500000) {
            echo "Sorry, your file is too large.";
            $uploadOk = 0;
        }

        // อนุญาตเฉพาะรูปภาพบางประเภท
        if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
            echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
            $uploadOk = 0;
        }

        // ตรวจสอบว่ามีข้อผิดพลาดหรือไม่
        if ($uploadOk == 0) {
            echo "Sorry, your file was not uploaded.";
        } else {
            if (move_uploaded_file($_FILES["product_picture"]["tmp_name"], $target_file)) {
                $product_picture = basename($_FILES["product_picture"]["name"]);
            } else {
                echo "Sorry, there was an error uploading your file.";
            }
        }
    }

    $query = "UPDATE products SET product_name = :product_name, product_brand = :product_brand, product_price = :product_price, product_status = :product_status, product_quantity = :product_quantity, category_id = :category_id" . (isset($product_picture) ? ", product_picture = :product_picture" : "") . " WHERE product_id = :product_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':product_name', $product_name);
    $stmt->bindParam(':product_brand', $product_brand);
    $stmt->bindParam(':product_price', $product_price);
    $stmt->bindParam(':product_status', $product_status);
    $stmt->bindParam(':product_quantity', $product_quantity);
    $stmt->bindParam(':category_id', $category_id);
    if (isset($product_picture)) {
        $stmt->bindParam(':product_picture', $product_picture);
    }
    $stmt->bindParam(':product_id', $product_id);

    if ($stmt->execute()) {
        $success = "Product updated successfully.";
    } else {
        $error = "Failed to update product.";
    }
} else {
    $query = "SELECT * FROM products WHERE product_id = :product_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':product_id', $product_id);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product</title>
</head>
<body>
    <h1>Edit Product</h1>
    <form method="POST" action="" enctype="multipart/form-data">
        <input type="text" name="product_name" value="<?php echo htmlspecialchars($product['product_name']); ?>" required><br>
        <input type="text" name="product_brand" value="<?php echo htmlspecialchars($product['product_brand']); ?>" required><br>
        <input type="number" step="0.01" name="product_price" value="<?php echo htmlspecialchars($product['product_price']); ?>" required><br>
        <input type="file" name="product_picture"><br>
        <select name="product_status" required>
            <option value="available" <?php if($product['product_status'] == 'available') echo 'selected'; ?>>Available</option>
            <option value="unavailable" <?php if($product['product_status'] == 'unavailable') echo 'selected'; ?>>Unavailable</option>
        </select><br>
        <input type="number" name="product_quantity" value="<?php echo htmlspecialchars($product['product_quantity']); ?>" required><br>
        
        <!-- Dropdown list สำหรับ category_id -->
        <select name="category_id" required>
            <?php foreach ($categories as $category): ?>
                <option value="<?php echo $category['id']; ?>" <?php if($product['category_id'] == $category['id']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($category['name']); ?>
                </option>
            <?php endforeach; ?>
        </select><br>
        
        <button type="submit">Update Product</button>
    </form>
    <?php if (isset($success)) { echo "<p>$success</p>"; } ?>
    <?php if (isset($error)) { echo "<p>$error</p>"; } ?>
</body>
</html>
