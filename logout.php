<?php 
include_once("config/Database.php");
include_once("class/UserLogin.php");

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// สร้างวัตถุฐานข้อมูลและวัตถุผู้ใช้
$connectDB = new Database();
$db = $connectDB->getConnection();

$user = new UserLogin($db);

// ฟังก์ชันการออกจากระบบในคลาส UserLogin
$user->logOut();
?>
