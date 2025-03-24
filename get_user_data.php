<?php
session_start();
include_once('config/Database.php');

$db = new Database();
$conn = $db->getConnection();

$user_id = $_SESSION['user_id'];

$query = "SELECT name, address, phone FROM users WHERE id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();

$userInfo = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($userInfo);
?>
