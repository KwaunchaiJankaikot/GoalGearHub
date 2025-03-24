<?php
session_start();
include_once('../config/Database.php');


if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$id = $_GET['id'];
$db = new Database();
$conn = $db->getConnection();

$query = "DELETE FROM users WHERE id = :id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $id);

if ($stmt->execute()) {
    header("Location: admin_dashboard.php");
    exit();
} else {
    echo "Failed to delete member.";
}
?>
