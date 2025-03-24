<?php 
session_start(); // เริ่มต้น session เพื่อให้สามารถเข้าถึงตัวแปร $_SESSION ได้
?>

<!-- HTML -->
<nav class="col-md-3 col-lg-2 d-md-block sidebar">
    <div class="welcome-text">
        Welcome, <?php echo isset($_SESSION['admin_email']) ? htmlspecialchars($_SESSION['admin_email']) : 'Guest'; ?>
    </div>
    <ul class="nav flex-column textsidebar">
        <li class="nav-item">
            <a class="nav-link" href="#">Dashboard</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="sales_details.php">ดูคำสั่งซื้อสินค้า</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="money.php">จ่ายเงินให้กับร้านค้า</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="sales_chart.php">รายงานการขาย</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="admin_refund.php">คำขอคืนเงิน</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="productmanage.php">จัดการสินค้า</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="admin_approve.php">อนุมัติสินค้า</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="edit_member.php">จัดการสมาชิก</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="admin_complaint.php">ดูคำร้องเรียนทั้งหมด</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="add_promotion.php">เพิ่มโค้ดโปรโมชั่น</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="manage.php">จัดการตัวเลือกต่างๆ</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="logout.php">ออกจากระบบ</a>
        </li>
    </ul>
</nav>

<!-- CSS -->
<style>
    /* กำหนดสีพื้นหลังของ Sidebar */
    .sidebar {
        background-color: #343a40; /* สีพื้นหลังเข้ม */
        height: 100vh; /* ทำให้ sidebar ยาวเต็มหน้าจอ */
        padding: 20px;
        color: #ffffff; /* สีข้อความสีขาว */
        /* width: 220px; */
    }

    /* ตกแต่งข้อความ welcome */
    .welcome-text {
        font-size: 18px;
        font-weight: bold;
        margin-bottom: 30px;
        text-align: center;
    }

    /* ตกแต่งลิงก์ใน sidebar */
    .nav-link {
        color: #ecf0f1; /* สีข้อความในลิงก์ */
        padding: 10px 20px;
        font-size: 16px;
        text-decoration: none;
        display: block;
        transition: background-color 0.3s ease; /* เพิ่ม transition ในการเปลี่ยนสีพื้นหลัง */
    }

    /* เปลี่ยนสีพื้นหลังเมื่อเมาส์ hover */
    .nav-link:hover {
        background-color: #34495E; /* สีพื้นหลังเมื่อ hover */
        color: #ffffff;
        border-radius: 5px; /* เพิ่มมุมโค้งเล็กน้อย */
    }

    /* ลิงก์ที่ active */
    .nav-link.active {
        background-color: #1ABC9C; /* สีพื้นหลังของลิงก์ที่ active */
        color: #ffffff;
        border-radius: 5px; /* มุมโค้ง */
    }

    /* จัดให้ ul มีระยะห่างระหว่างรายการมากขึ้น */
    .nav.flex-column {
        list-style: none;
        padding-left: 0;
    }

    /* จัดการระยะห่างระหว่างรายการใน ul */
    .nav-item {
        margin-bottom: 10px;
    }

    /* กำหนดให้ลิงก์ที่อยู่ใน nav-item เต็มความกว้างของ sidebar */
    .nav-item a {
        display: block;
        width: 100%;
    }
</style>
