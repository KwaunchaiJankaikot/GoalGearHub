<?php
ob_start(); // เริ่มต้นการเก็บข้อมูล output ใน buffer

include('nav.php');
include_once("config/Database.php");
include_once("class/UserLogin.php");
include_once("class/Utils.php");
include('header.php');

// ตรวจสอบว่ามีการเริ่มต้นเซสชันหรือไม่
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบหรือไม่ ถ้าไม่เข้าสู่ระบบจะเปลี่ยนเส้นทางไปยังหน้า signin.php
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

// สร้างการเชื่อมต่อฐานข้อมูล
$connectDB = new Database();
$db = $connectDB->getConnection();

// สร้างออบเจ็กต์ UserLogin เพื่อใช้งานฟังก์ชันต่าง ๆ ที่เกี่ยวกับผู้ใช้
$user = new UserLogin($db);
$user->setUserId($_SESSION['user_id']);
$userInfo = $user->getUserInfo();

// ตั้งค่าข้อมูลเบอร์โทรศัพท์ เลขบัญชี และชื่อธนาคารจากข้อมูลที่ดึงมา
$phone = $userInfo['phone'] ?? '';
$bankAccount = $userInfo['banknumber'] ?? '';
$bankName = $userInfo['bankname'] ?? '';

// ดึงข้อมูลธนาคารจากฐานข้อมูล
$banksQuery = "SELECT * FROM banks";
$banksStmt = $db->prepare($banksQuery);
$banksStmt->execute();
$banks = $banksStmt->fetchAll(PDO::FETCH_ASSOC);

// ตรวจสอบว่าฟอร์มถูกส่งหรือไม่
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $newAddress = $_POST['address'];
    $newPassword = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $newPhone = $_POST['phone'];
    $bankAccount = !empty($_POST['bank_account']) ? $_POST['bank_account'] : $userInfo['banknumber'];
    $bankName = !empty($_POST['bank_name']) ? $_POST['bank_name'] : $userInfo['bankname'];

    $errors = []; // อาร์เรย์สำหรับเก็บข้อผิดพลาด

    // ตรวจสอบเบอร์โทรศัพท์
    if (!empty($newPhone) && !preg_match('/^[0-9]{10}$/', $newPhone)) {
        $errors[] = "เบอร์ต้องมี10หลัก";
    }

    // ตรวจสอบเลขบัญชี
    if (!empty($bankAccount) && !preg_match('/^[0-9]+$/', $bankAccount)) {
        $errors[] = "เลขบัญชีต้องมีแค่ตัวเลข";
    }

    // อัปเดตที่อยู่หากมีการเปลี่ยนแปลง
    if (!empty($newAddress)) {
        $user->updateAddress($newAddress);
        $userInfo['address'] = $newAddress; // อัปเดตข้อมูลใน $userInfo
    }

    // ตรวจสอบรหัสผ่านและอัปเดต
    if (!empty($newPassword)) {
        if ($newPassword === $confirmPassword) {
            $user->updatePassword(password_hash($newPassword, PASSWORD_DEFAULT));
        } else {
            $errors[] = "รหัสและคอนเฟิร์มรหัสไม่ตรงกัน";
        }
    }

    // อัปเดตเบอร์โทรศัพท์และข้อมูลธนาคาร
    if (empty($errors)) {
        if (!empty($newPhone)) $user->updatePhone($newPhone);
        if ($bankAccount !== $userInfo['banknumber'] || $bankName !== $userInfo['bankname']) {
            $user->updateBankInfo($bankAccount, $bankName);
        }
        $_SESSION['success'] = "อัพเดทโปรไฟล์แล้ว";
        header("Location: profile.php");
        exit();
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
        header("Location: profile.php");
        exit();
    }
}
?>

<style>
    .navbar {
        display: flex;
        background-color: #4a4c4c;
        text-align: center;
        border-bottom: 1px solid #363636;
        margin: 0;
        padding: 0;
        width: 100%;
        height: 90px;
        position: relative;
        font-size: 16px;
    }

    .navbar-brand {
        color: #ffff;
        font-size: 26px;
        text-align: center;
        margin-left: 40px;
        position: absolute;
    }

    ul li {
        margin: 0 1rem;
        margin-left: 650px;
        width: 200px;
        height: 40px;
        margin-top: 0px;
    }

    .iconnav1 {
        font-size: 40px;
        margin-top: 0px;
        margin-left: 480px;
        position: absolute;
    }

    .welcomenav {
        margin-left: -125px;
        width: 80px;
        height: 22px;

    }

    .navusername {
        margin-left: -120px;
        font-size: 14px;
        margin-top: -50px;
        display: flex;
        width: 250px;
        height: 30px;
    }

    .navbar-text {
        margin-top: -15px;
    }

    .search {
        display: flex;
        align-items: left;
        justify-content: center;
        padding: 5px;
        border-radius: 28px;
        background: #4a4c4c;
        width: 43%;
        margin-top: -10px;
    }

    .search-input {
        font-size: 16px;
        font-family: "Open Sans", sans-serif;
        color: #333333;
        margin-left: var(--padding);
        outline: none;
        border: none;
        width: 600px;
        height: 40px;
        background: #ffff;
        border-radius: 28px;
        text-align: center;
        position: relative;
        margin-right: -800px;
        /* border: 1px solid red; */
        margin-top: 18px;
    }

    .search-input::placeholder {
        color: #3b3b3b;
    }

    .search-icon {
        color: #333333;
        position: absolute;
        margin-left: 200px;
        margin-top: 25px;
        z-index: 1;
        /* border: 1px solid blue; */
    }

    .dropdown-content {
        display: none;
        position: absolute;
        background-color: #f9f9f9;
        min-width: 160px;
        box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.2);
        z-index: 1;
        margin-left: -120px;
    }

    .dropdown-content a {
        color: black;
        padding: 12px 16px;
        text-decoration: none;
        display: block;
    }

    .dropdown-content a:hover {
        background-color: #f1f1f1;
    }

    .scroll-button {
        cursor: pointer;
        font-size: 24px;
        margin-left: -50px;
        margin-top: -35px;
        display: flex;
        position: relative;
        width: 25px;
    }

    .cartnav {
        font-size: 30px;
        margin-top: -35px;
        margin-left: 70px;
        position: absolute;
        color: #fff;
    }

    .favoritenav {
        font-size: 30px;
        margin-top: -35px;
        margin-left: 30px;
        position: absolute;
        color: #fff;
    }

    .nav-link {
        margin-top: 2px;
        margin-left: -135px;
        position: absolute;
    }

    .profile-container {
        display: flex;
        justify-content: space-between;
        max-width: 1200px;
        margin: 20px auto;
        padding: 20px;
        background-color: #2b2b2b;
        color: white;
        border-radius: 10px;
    }

    .profile-left,
    .profile-right {
        width: 48%;
    }

    .profile-left {
        background-color: #444;
        padding: 20px;
        border-radius: 10px;
    }

    .profile-right {
        background-color: #333;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    .profile-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
        border-radius: 10px;
        overflow: hidden;
    }

    .profile-table th,
    .profile-table td {
        border: 1px solid #444;
        padding: 12px 10px;
        text-align: left;
        background-color: #555;
    }

    .profile-table th {
        background-color: #666;
        color: #ffffff;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .profile-table tr:hover {
        background-color: #777;
    }

    label {
        display: block;
        margin-bottom: 5px;
        color: #ddd;
    }

    input,
    textarea,
    select,
    button {
        width: 100%;
        padding: 10px;
        margin-bottom: 10px;
        border-radius: 5px;
        border: 1px solid #555;
        background-color: #666;
        color: white;
    }

    button {
        background-color: #ffa500;
        cursor: pointer;
        transition: background-color 0.3s ease;
        border: none;
    }

    button:hover {
        background-color: #e69500;
    }
</style>

<script>
    // ตรวจสอบว่า session มีข้อความ success หรือ error หรือไม่
    <?php if (isset($_SESSION['success'])): ?>
        alert("<?php echo $_SESSION['success']; ?>");
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        alert("<?php echo $_SESSION['error']; ?>");
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
</script>

<div class="profile-container">
    <div class="profile-left">
        <h2>เปลี่ยนข้อมูลของคุณ</h2>
        <form action="profile.php" method="POST">
            <div class="form-group">
                <label for="address">ที่อยู่:</label>
                <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($userInfo['address']); ?>">
            </div>
            <div class="form-group">
                <label for="password">รหัสผ่าน:</label>
                <input type="password" id="password" name="password">
            </div>
            <div class="form-group">
                <label for="confirm_password">ยืนยันรหัสผ่าน:</label>
                <input type="password" id="confirm_password" name="confirm_password">
            </div>
            <div class="form-group">
                <label for="phone">เบอร์โทรศัพท์:</label>
                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
            </div>
            <div class="form-group">
                <label for="bank_account">เลขบัญชี:</label>
                <input type="text" id="bank_account" name="bank_account" value="<?php echo htmlspecialchars($bankAccount); ?>">
            </div>
            <div class="form-group">
                <label for="bank_name">ชื่อธนาคาร:</label>
                <select id="bank_name" name="bank_name">
                    <option value="">เลือกธนาคารของคุณ</option>
                    <?php foreach ($banks as $bank): ?>
                        <option value="<?php echo htmlspecialchars($bank['name']); ?>" <?php echo ($bank['name'] == $bankName) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($bank['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit">บันทึก</button>
        </form>
    </div>

    <div class="profile-right">
        <h2>ข้อมูลของคุณ</h2>
        <table class="profile-table">
            <tr>
                <th>ชื่อ</th>
                <td><?php echo htmlspecialchars($userInfo['name']); ?></td>
            </tr>
            <tr>
                <th>อีเมลล์</th>
                <td><?php echo htmlspecialchars($userInfo['email']); ?></td>
            </tr>
            <tr>
                <th>ที่อยู่</th>
                <td><?php echo htmlspecialchars($userInfo['address']); ?></td>
            </tr>
            <tr>
                <th>เบอร์โทรศัพท์</th>
                <td><?php echo htmlspecialchars($phone); ?></td>
            </tr>
            <tr>
                <th>เลขบัญชี</th>
                <td><?php echo htmlspecialchars($bankAccount); ?></td>
            </tr>
            <tr>
                <th>ชื่อธนาคาร</th>
                <td><?php echo htmlspecialchars($bankName); ?></td>
            </tr>
        </table>
    </div>
</div>
</div>

<?php ob_end_flush(); ?>
<?php include('footer.php'); ?>