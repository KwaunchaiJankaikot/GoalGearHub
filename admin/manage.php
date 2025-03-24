<?php
session_start();
ob_start();
include_once('header.php');
include_once('../config/Database.php');

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// เพิ่มข้อมูลหมวดหมู่ ธนาคาร วิธีชำระเงิน หรือทีมฟุตบอล
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        $category_name = trim($_POST['category_name']);
        $query = "INSERT INTO categories (name) VALUES (:name)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':name', $category_name);
        $stmt->execute();
        $_SESSION['success'] = "เพิ่มหมวดหมู่สินค้าเรียบร้อยแล้ว";
        header("Location: manage.php");
        exit();
    } elseif (isset($_POST['add_bank'])) {
        $bank_name = trim($_POST['bank_name']);
        $query = "INSERT INTO banks (name) VALUES (:name)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':name', $bank_name);
        $stmt->execute();
        $_SESSION['success'] = "เพิ่มธนาคารเรียบร้อยแล้ว";
        header("Location: manage.php");
        exit();
    } elseif (isset($_POST['add_payment_method'])) {
        $payment_method = trim($_POST['payment_method']);
        $query = "INSERT INTO payment_methods (method_name) VALUES (:method_name)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':method_name', $payment_method);
        $stmt->execute();
        $_SESSION['success'] = "เพิ่มวิธีการชำระเงินเรียบร้อยแล้ว";
        header("Location: manage.php");
        exit();
    } elseif (isset($_POST['add_brand'])) {
        $brand_name = trim($_POST['brand_name']);
        $query = "INSERT INTO brands (name) VALUES (:name)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':name', $brand_name);
        $stmt->execute();
        $_SESSION['success'] = "เพิ่มแบรนด์สินค้าเรียบร้อยแล้ว";
        header("Location: manage.php");
        exit();
    } elseif (isset($_POST['add_team'])) {
        $team_name = trim($_POST['team_name']);
        $query = "INSERT INTO teams (team_name) VALUES (:team_name)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':team_name', $team_name);
        $stmt->execute();
        $_SESSION['success'] = "เพิ่มทีมฟุตบอลเรียบร้อยแล้ว";
        header("Location: manage.php");
        exit();
    } elseif (isset($_POST['add_shipping'])) {
        $shipping_name = trim($_POST['shipping_name']);
        $query = "INSERT INTO shipping_options (shipping_name) VALUES (:shipping_name)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':shipping_name', $shipping_name);
        $stmt->execute();
        $_SESSION['success'] = "เพิ่มบริษัทขนส่งเรียบร้อยแล้ว";
        header("Location: manage.php");
        exit();
    } elseif (isset($_POST['add_account'])) {
        $account_name = trim($_POST['account_name']);
        $account_number = trim($_POST['account_number']);
        $bank_name = trim($_POST['bank_name']);
        $query = "INSERT INTO account_numbers (account_name, account_number, bank_name) VALUES (:account_name, :account_number, :bank_name)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':account_name', $account_name);
        $stmt->bindParam(':account_number', $account_number);
        $stmt->bindParam(':bank_name', $bank_name);
        $stmt->execute();
        $_SESSION['success'] = "เพิ่มเลขบัญชีเรียบร้อยแล้ว";
        header("Location: manage.php");
        exit();
    }
}

// ลบข้อมูลตามประเภทที่ถูกส่งมา
if (isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];
    $type = $_POST['delete_type'];

    if ($type === 'category') {
        $query = "DELETE FROM categories WHERE id = :id";
    } elseif ($type === 'bank') {
        $query = "DELETE FROM banks WHERE id = :id";
    } elseif ($type === 'payment_method') {
        $query = "DELETE FROM payment_methods WHERE id = :id";
    } elseif ($type === 'brand') {
        $query = "DELETE FROM brands WHERE id = :id";
    } elseif ($type === 'team') {
        $query = "DELETE FROM teams WHERE id = :id";
    } elseif ($type === 'shipping') {
        $query = "DELETE FROM shipping_options WHERE id = :id";
    } elseif ($type === 'account') {
        $query = "DELETE FROM account_numbers WHERE id = :id";
    }

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    $_SESSION['success'] = "ลบข้อมูลเรียบร้อยแล้ว";
    header("Location: manage.php");
    exit();
}

// ดึงข้อมูลตัวเลือกทั้งหมด
$categoriesQuery = "SELECT * FROM categories";
$categoriesStmt = $conn->prepare($categoriesQuery);
$categoriesStmt->execute();
$categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

$banksQuery = "SELECT * FROM banks";
$banksStmt = $conn->prepare($banksQuery);
$banksStmt->execute();
$banks = $banksStmt->fetchAll(PDO::FETCH_ASSOC);

$paymentMethodsQuery = "SELECT * FROM payment_methods";
$paymentMethodsStmt = $conn->prepare($paymentMethodsQuery);
$paymentMethodsStmt->execute();
$paymentMethods = $paymentMethodsStmt->fetchAll(PDO::FETCH_ASSOC);

$brandsQuery = "SELECT * FROM brands";
$brandsStmt = $conn->prepare($brandsQuery);
$brandsStmt->execute();
$brands = $brandsStmt->fetchAll(PDO::FETCH_ASSOC);

$teamsQuery = "SELECT * FROM teams";
$teamsStmt = $conn->prepare($teamsQuery);
$teamsStmt->execute();
$teams = $teamsStmt->fetchAll(PDO::FETCH_ASSOC);

$shippingQuery = "SELECT * FROM shipping_options";
$shippingStmt = $conn->prepare($shippingQuery);
$shippingStmt->execute();
$shipping = $shippingStmt->fetchAll(PDO::FETCH_ASSOC);

$accountsQuery = "SELECT * FROM account_numbers";
$accountsStmt = $conn->prepare($accountsQuery);
$accountsStmt->execute();
$accounts = $accountsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Options</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #2c3e50;
            color: #ecf0f1;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            background-color: #34495e;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.4);
            width: 80%;
            max-width: 900px;
            overflow-x: auto;
        }

        h2,
        h3 {
            text-align: center;
            color: #ecf0f1;
        }

        h3 {
            margin-top: 10px;
            /* เพิ่ม margin-top 10px */
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            width: 100%;
            text-align: center;
        }

        .alert-success {
            background-color: #27ae60;
            color: white;
        }

        .alert-danger {
            background-color: #e74c3c;
            color: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            padding: 12px;
            text-align: center;
            vertical-align: middle;
            border-bottom: 1px solid #ecf0f1;
            font-size: 14px;
        }

        th {
            background-color: #2980b9;
            color: white;
        }

        input[type="text"],
        button {
            padding: 10px;
            border-radius: 5px;
            border: none;
            margin-bottom: 10px;
            width: 100%;
        }

        button {
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        /* button:hover {
            background-color: #2ecc71;
        } */

        .btn-primary {
            background-color: #27ae60;
            /* ปรับสีเป็นสีเขียว */
            color: white;
            width: 100%;
        }

        .btn-danger {
            background-color: #e74c3c;
            color: white;
            /* border: 10px solid yellow; */
            width: 40%;
        }

        .actions {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .actions a,
        .actions button {
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            padding: 15px 30px;
            border-radius: 8px;
            border: none;
            color: white;
            text-decoration: none;
        }

        .back {
            background-color: #e74c3c;
        }

        .back:hover {
            background-color: #c0392b;
        }

        input[type="text"],
        select {
            padding: 10px;
            width: 100%;
            border-radius: 5px;
            border: none;
            margin-bottom: 10px;
            font-size: 14px;
            background-color: white;
            /* Same as the input field */
            color: black ;
            /* Text color */
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>จัดการตัวเลือก</h2>

        <!-- แสดงข้อความแจ้งเตือน -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success'];
                unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['danger'])): ?>
            <div class="alert alert-danger">
                <?php echo $_SESSION['danger'];
                unset($_SESSION['danger']); ?>
            </div>
        <?php endif; ?>

        <!-- Form for adding account number -->
        <h3>เพิ่มเลขบัญชีสำหรับรับเงิน</h3>
        <form action="manage.php" method="POST">
            <div class="mb-3">
                <input type="text" name="account_name" placeholder="ชื่อเจ้าของบัญชี" required>
                <input type="text" name="account_number" placeholder="เลขบัญชี" required>

                <!-- Dropdown select for bank names -->
                <select class="mb-3" name="bank_name" required>
                    <option value="" disabled selected>เลือกธนาคาร</option>
                    <?php foreach ($banks as $bank): ?>
                        <option value="<?php echo htmlspecialchars($bank['name']); ?>">
                            <?php echo htmlspecialchars($bank['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" name="add_account" class="btn-primary">เพิ่มเลขบัญชี</button>
        </form>

        <!-- List of account numbers -->
        <h3>รายการเลขบัญชีสำหรับรับเงิน(จะแสดงตรงหน้าชำระเงิน)</h3>
        <table>
            <thead>
                <tr>
                    <!-- <th>ID</th> -->
                    <th>ชื่อเจ้าของบัญชี</th>
                    <th>เลขบัญชี</th>
                    <th>ชื่อธนาคาร</th>
                    <th>การจัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($accounts as $account): ?>
                    <tr>
                        <!-- <td><?php echo htmlspecialchars($account['id']); ?></td> -->
                        <td><?php echo htmlspecialchars($account['account_name']); ?></td>
                        <td><?php echo htmlspecialchars($account['account_number']); ?></td>
                        <td><?php echo htmlspecialchars($account['bank_name']); ?></td>
                        <td>
                            <form action="manage.php" method="POST" style="display:inline;">
                                <input type="hidden" name="delete_id" value="<?php echo $account['id']; ?>">
                                <input type="hidden" name="delete_type" value="account">
                                <button type="submit" class="btn-danger">ลบ</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <!-- ฟอร์มเพิ่มหมวดหมู่สินค้า -->
        <h3>เพิ่มหมวดหมู่สินค้า</h3>
        <form action="manage.php" method="POST">
            <div class="mb-3">
                <input type="text" name="category_name" placeholder="ชื่อหมวดหมู่" required>
            </div>
            <button type="submit" name="add_category" class="btn-primary">เพิ่มหมวดหมู่</button>
        </form>

        <!-- แสดงรายการหมวดหมู่สินค้า -->
        <h3>รายการหมวดหมู่สินค้า</h3>
        <table>
            <thead>
                <tr>
                    <!-- <th>ID</th> -->
                    <th>ชื่อหมวดหมู่</th>
                    <th>การจัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <!-- <td><?php echo htmlspecialchars($category['id']); ?></td> -->
                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                        <td>
                            <form action="manage.php" method="POST" style="display:inline;">
                                <input type="hidden" name="delete_id" value="<?php echo $category['id']; ?>">
                                <input type="hidden" name="delete_type" value="category">
                                <button type="submit" class="btn-danger">ลบ</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- ฟอร์มเพิ่มธนาคาร -->
        <h3>เพิ่มธนาคาร</h3>
        <form action="manage.php" method="POST">
            <div class="mb-3">
                <input type="text" name="bank_name" placeholder="ชื่อธนาคาร" required>
            </div>
            <button type="submit" name="add_bank" class="btn-primary">เพิ่มธนาคาร</button>
        </form>

        <!-- แสดงรายการธนาคาร -->
        <h3>รายการธนาคาร</h3>
        <table>
            <thead>
                <tr>
                    <!-- <th>ID</th> -->
                    <th>ชื่อธนาคาร</th>
                    <th>การจัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($banks as $bank): ?>
                    <tr>
                        <!-- <td><?php echo htmlspecialchars($bank['id']); ?></td> -->
                        <td><?php echo htmlspecialchars($bank['name']); ?></td>
                        <td>
                            <form action="manage.php" method="POST" style="display:inline;">
                                <input type="hidden" name="delete_id" value="<?php echo $bank['id']; ?>">
                                <input type="hidden" name="delete_type" value="bank">
                                <button type="submit" class="btn-danger">ลบ</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <!-- ฟอร์มเพิ่มแบรนด์ -->
        <h3>เพิ่มแบรนด์</h3>
        <form action="manage.php" method="POST">
            <div class="mb-3">
                <label for="brand_name">ชื่อแบรนด์:</label>
                <input type="text" name="brand_name" id="brand_name" class="form-control" required>
            </div>
            <button type="submit" name="add_brand" class="btn-primary">เพิ่มแบรนด์</button>
        </form>
        <!-- แสดงรายการแบรนด์ -->
        <h3>รายการแบรนด์</h3>
        <table>
            <thead>
                <tr>
                    <!-- <th>ID</th> -->
                    <th>ชื่อแบรนด์</th>
                    <th>การจัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($brands as $brand): ?>
                    <tr>
                        <!-- <td><?php echo htmlspecialchars($brand['id']); ?></td> -->
                        <td><?php echo htmlspecialchars($brand['name']); ?></td>
                        <td>
                            <form action="manage.php" method="POST" style="display:inline;">
                                <input type="hidden" name="delete_id" value="<?php echo $brand['id']; ?>">
                                <input type="hidden" name="delete_type" value="brand">
                                <button type="submit" class="btn btn-danger">ลบ</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <!-- ฟอร์มเพิ่มวิธีการชำระเงิน
        <h3>เพิ่มวิธีการชำระเงิน</h3>
        <form action="manage.php" method="POST">
            <div class="mb-3">
                <input type="text" name="payment_method" placeholder="ชื่อวิธีการชำระเงิน" required>
            </div>
            <button type="submit" name="add_payment_method" class="btn-primary">เพิ่มวิธีการชำระเงิน</button>
        </form>

        แสดงรายการวิธีการชำระเงิน
        <h3>รายการวิธีการชำระเงิน</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ชื่อวิธีการชำระเงิน</th>
                    <th>การจัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($paymentMethods as $method): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($method['id']); ?></td>
                        <td><?php echo htmlspecialchars($method['method_name']); ?></td>
                        <td>
                            <form action="manage.php" method="POST" style="display:inline;">
                                <input type="hidden" name="delete_id" value="<?php echo $method['id']; ?>">
                                <input type="hidden" name="delete_type" value="payment_method">
                                <button type="submit" class="btn-danger">ลบ</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table> -->
        <!-- ฟอร์มเพิ่มทีมฟุตบอล -->
        <h3>เพิ่มทีมฟุตบอล</h3>
        <form action="manage.php" method="POST">
            <div class="mb-3">
                <input type="text" name="team_name" placeholder="ชื่อทีมฟุตบอล" required>
            </div>
            <button type="submit" name="add_team" class="btn-primary">เพิ่มทีมฟุตบอล</button>
        </form>

        <!-- แสดงรายการทีมฟุตบอล -->
        <h3>รายการทีมฟุตบอล</h3>
        <table>
            <thead>
                <tr>
                    <!-- <th>ID</th> -->
                    <th>ชื่อทีมฟุตบอล</th>
                    <th>การจัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($teams as $team): ?>
                    <tr>
                        <!-- <td><?php echo htmlspecialchars($team['id']); ?></td> -->
                        <td><?php echo htmlspecialchars($team['team_name']); ?></td>
                        <td>
                            <form action="manage.php" method="POST" style="display:inline;">
                                <input type="hidden" name="delete_id" value="<?php echo $team['id']; ?>">
                                <input type="hidden" name="delete_type" value="team">
                                <button type="submit" class="btn-danger">ลบ</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <!-- ฟอร์มเพิ่มบริษัทขนส่ง -->
        <h3>เพิ่มบริษัทขนส่ง</h3>
        <form action="manage.php" method="POST">
            <div class="mb-3">
                <input type="text" name="shipping_name" placeholder="ชื่อบริษัทขนส่ง" required>
            </div>
            <button type="submit" name="add_shipping" class="btn-primary">เพิ่มบริษัทขนส่ง</button>
        </form>

        <!-- แสดงรายการบริษัทขนส่ง -->
        <h3>รายการบริษัทขนส่ง</h3>
        <table>
            <thead>
                <tr>
                    <!-- <th>ID</th> -->
                    <th>ชื่อบริษัทขนส่ง</th>
                    <th>การจัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($shipping as $option): ?>
                    <tr>
                        <!-- <td><?php echo htmlspecialchars($option['id']); ?></td> -->
                        <td><?php echo htmlspecialchars($option['shipping_name']); ?></td>
                        <td>
                            <form action="manage.php" method="POST" style="display:inline;">
                                <input type="hidden" name="delete_id" value="<?php echo $option['id']; ?>">
                                <input type="hidden" name="delete_type" value="shipping">
                                <button type="submit" class="btn-danger ">ลบ</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <!-- ปุ่มกลับไปหน้า Dashboard -->
        <div class="actions">
            <a href="admin_dashboard.php" class="back">กลับไปหน้า Dashboard</a>
        </div>
    </div>
</body>

</html>