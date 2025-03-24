<?php
session_start();
include_once('header.php');
include_once('config/Database.php');
include_once('nav.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = $_POST['payment_method'];

    if ($payment_method === 'qr_code') {
        echo "กรุณาสแกน QR Code เพื่อชำระเงิน";
        // แสดง QR Code สำหรับการชำระเงิน

    } elseif ($payment_method === 'cod') {
        echo "คุณได้เลือกวิธีเก็บเงินปลายทาง";
        // จัดการกับข้อมูลการสั่งซื้อสำหรับการเก็บเงินปลายทาง
    } else {
        echo "วิธีการชำระเงินไม่ถูกต้อง";
    }
}
?>
