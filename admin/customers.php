<?php
include_once('../config/Database.php');

// เริ่มต้น session (ถ้ายังไม่เริ่ม)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ตรวจสอบการล็อกอิน (สำหรับผู้ใช้ที่เป็น admin)
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// สร้างการเชื่อมต่อฐานข้อมูล
$db = new Database();
$conn = $db->getConnection();

// ดึงข้อมูลลูกค้าทั้งหมดจากตาราง users
$query = "SELECT id, name, email, phone FROM users ORDER BY id DESC";  // เอา 'created_at' ออก
$stmt = $conn->prepare($query);
$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <h1>Customers</h1>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Customer ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $index => $customer): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($customer['id']) ?></td>
                            <td><?= htmlspecialchars($customer['name']) ?></td>
                            <td><?= htmlspecialchars($customer['email']) ?></td>
                            <td><?= htmlspecialchars($customer['phone']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>
