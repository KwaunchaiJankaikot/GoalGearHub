<?php
session_start();
include_once('../config/Database.php');


if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $query = "DELETE FROM promotions WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Promotion deleted successfully.";
    } else {
        $_SESSION['error'] = "Failed to delete promotion.";
    }
}

header("Location: add_promotion.php"); // เปลี่ยนเส้นทางกลับไปยังหน้าจัดการโปรโมชั่น
exit();
?>
