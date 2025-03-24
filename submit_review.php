<?php
session_start();
include_once('config/Database.php');

// ตรวจสอบว่าผู้ใช้ล็อกอินหรือไม่
if (!isset($_SESSION['user_id'])) {
    echo "error";
    exit();
}

// รับข้อมูลจาก POST
$user_id = $_SESSION['user_id'];
$product_id = $_POST['product_id'];
$review_text = $_POST['review_text'];
$rating = intval($_POST['rating']);

// ตรวจสอบว่าข้อมูลรีวิวไม่ว่าง
if (empty($review_text) || $rating <= 0) {
    echo "error";
    exit();
}

// สร้างการเชื่อมต่อกับฐานข้อมูล
$db = new Database();
$conn = $db->getConnection();

// บันทึกรีวิวลงฐานข้อมูล
$query = "INSERT INTO review (user_id, product_id, Review_Detail, rating, Review_Date) 
          VALUES (:user_id, :product_id, :review_text, :rating, NOW())";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->bindParam(':product_id', $product_id);
$stmt->bindParam(':review_text', $review_text);
$stmt->bindParam(':rating', $rating);

if ($stmt->execute()) {
    echo "success";
} else {
    echo "error";
}
?>
