<?php
ob_start(); // เริ่มต้นการ output buffering
session_start();
include_once('config/Database.php');

$db = new Database();
$conn = $db->getConnection();

// ตรวจสอบว่าเป็นคำร้อง POST และมีการส่ง add_to_cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = isset($_POST['product_id']) ? $_POST['product_id'] : null;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1; // ใช้ quantity จากฟอร์มหรือค่าเริ่มต้นคือ 1
    $size = isset($_POST['size']) ? $_POST['size'] : null; // เพิ่มการตรวจสอบขนาดสินค้า
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

    // ตรวจสอบว่าผู้ใช้ล็อกอินหรือไม่
    if (!$user_id) {
        echo "กรุณาล็อกอินก่อนที่จะเพิ่มสินค้าในตะกร้า";
    } elseif ($product_id && $quantity > 0 && $size) {
        // ตรวจสอบว่าสินค้ามีอยู่ในตะกร้าแล้วหรือไม่
        $query = "SELECT * FROM cart WHERE user_id = :user_id AND product_id = :product_id AND size = :size";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->bindParam(':size', $size);
        $stmt->execute();
        $existingItem = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingItem) {
            // ถ้ามีสินค้าอยู่แล้ว ให้เพิ่มจำนวนสินค้าตามที่ผู้ใช้เลือก
            $newQuantity = $existingItem['quantity'] + $quantity;
            $updateQuery = "UPDATE cart SET quantity = :quantity WHERE user_id = :user_id AND product_id = :product_id AND size = :size";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bindParam(':quantity', $newQuantity);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':product_id', $product_id);
            $stmt->bindParam(':size', $size);
            $stmt->execute();
        } else {
            // ถ้าไม่มีสินค้าในตะกร้า เพิ่มสินค้าใหม่ลงในตะกร้า
            $insertQuery = "INSERT INTO cart (user_id, product_id, quantity, size) VALUES (:user_id, :product_id, :quantity, :size)";
            $stmt = $conn->prepare($insertQuery);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':product_id', $product_id);
            $stmt->bindParam(':quantity', $quantity);
            $stmt->bindParam(':size', $size);
            $stmt->execute();
        }

        // แจ้งเตือนว่าเพิ่มสินค้าสำเร็จ
        echo "เพิ่มสินค้าลงในตะกร้าเรียบร้อยแล้ว";
    } else {
        // แจ้งเตือนว่าการเพิ่มสินค้าไม่สำเร็จ เนื่องจากข้อมูลไม่ครบถ้วนหรือไม่ถูกต้อง
        echo "การเพิ่มสินค้าล้มเหลว กรุณาตรวจสอบข้อมูล (ขนาดหรือจำนวนสินค้าไม่ถูกต้อง)";
    }
} else {
    // ถ้าไม่ใช่คำร้องแบบ POST หรือไม่มี `add_to_cart`
    echo "คำขอไม่ถูกต้อง";
}

ob_end_flush(); // ปิดการ output buffering และส่งผลลัพธ์
?>
