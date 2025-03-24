<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ตรวจสอบหน้าปัจจุบัน
$currentPage = basename($_SERVER['PHP_SELF']);

// ดึงจำนวนสินค้าจากตะกร้า ถ้าผู้ใช้ล็อกอิน
$cartItemCount = 0;
if (isset($_SESSION['user_id'])) {
    include_once('config/Database.php');
    $db = new Database();
    $conn = $db->getConnection();

    $query ="SELECT SUM(quantity) AS total_items FROM cart WHERE user_id = :user_id";
    // $query = "SELECT COUNT(*) AS total_items FROM cart WHERE user_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $cartItemCount = $row['total_items'] ? $row['total_items'] : 0; // ถ้าไม่มีสินค้าให้ตั้งค่าเป็น 0

        // ดึงข้อมูลทั้งหมดจากตาราง users
        $query = "SELECT * FROM users WHERE id = :user_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
        // ตรวจสอบว่ามีข้อมูล 'fullname' หรือไม่
        if ($userData && array_key_exists('fullname', $userData)) {
            $fullname = $userData['fullname'];
        } else {
            $fullname = 'ไม่พบชื่อ';
        }
    
        // ดึงข้อมูลอื่น ๆ ที่ต้องการ เช่น email, address, phone
        $email = isset($userData['email']) ? $userData['email'] : 'ไม่พบอีเมล';
        $address = isset($userData['address']) ? $userData['address'] : 'ไม่พบที่อยู่';
        $phone = isset($userData['phone']) ? $userData['phone'] : 'ไม่พบเบอร์โทรศัพท์';
        $bankname = isset($userData['bankname']) ? $userData['bankname'] : 'ไม่พบธนาคาร';
        $banknumber = isset($userData['banknumber']) ? $userData['banknumber'] : 'ไม่พบเลขบัญชีธนาคาร';
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
        margin-left: -122px;
        text-align: left;
        width: 80px;
        height: 22px;
        /* border: 1px solid red; */
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
    }

    .search-input::placeholder {
        color: #3b3b3b;
    }

    .search-icon {
        color: #333333;
        position: absolute;
        margin-left: 200px;
        margin-top: 9.5px;
        z-index: 1;
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

    .numbercart {
        background-color: red;
        color: white;
        border-radius: 50%;
        padding: 5px 10px;
        position: absolute;
        top: 14px;
        right: 15px;
        font-size: 10px;
    }
</style>

<nav class="navbar navbar-expand-lg navbar-dark bg-grey">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">GoalGear.Hub</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="search">
            <form action="search.php" method="GET" id="searchForm">
                <input class="search-input" type="search" name="query" placeholder="Search.." onkeydown="submitForm(event)" />
                <span class="search-icon material-symbols-outlined">search</span>
            </form>
            <script>
                function submitForm(event) {
                    if (event.key === 'Enter') {
                        document.getElementById('searchForm').submit();
                    }
                }

                function toggleDropdown() {
                    var dropdownContent = document.getElementById('dropdownContent');
                    if (dropdownContent.style.display === 'block') {
                        dropdownContent.style.display = 'none';
                    } else {
                        dropdownContent.style.display = 'block';
                    }
                }

                window.onclick = function(event) {
                    if (!event.target.matches('.scroll-button')) {
                        var dropdowns = document.getElementsByClassName("dropdown-content");
                        for (var i = 0; i < dropdowns.length; i++) {
                            var openDropdown = dropdowns[i];
                            if (openDropdown.style.display === 'block') {
                                openDropdown.style.display = 'none';
                            }
                        }
                    }
                }
            </script>
        </div>
        <div class="collapse navbar-collapse " id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <span class="material-symbols-outlined iconnav1">person</span>
                <?php if (isset($_SESSION['user_id'])) { ?>
                    <li class="nav-item">
                        <p class="welcomenav">Welcome</p>
                        <div class="dropdown">
                            <span class="material-symbols-outlined scroll-button" onclick="toggleDropdown()">keyboard_double_arrow_down</span>
                            <div class="dropdown-content" id="dropdownContent">
                                <a href="profile.php">ข้อมูลของฉัน</a>
                                <a href="orderhistory.php">ประวัติการสั่งซื้อ</a>
                                <a href="userdashboard.php">ขายสินค้าของคุณ</a>
                                <a href="myproduct.php">สินค้าของคุณ</a>
                                <a href="logout.php">Logout</a>
                            </div>
                        </div>
                        <span class="navbar-text navusername">User : <?php echo htmlspecialchars($email); ?></span>
                        <a href="cart.php">
                            <span class="material-symbols-outlined cartnav">shopping_cart</span>
                            <?php if ($cartItemCount > 0): ?>
                                <span class="numbercart"><?php echo $cartItemCount; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php } else { ?>
                    <li class="nav-item">
                        <a class="nav-link active" href="signin.php">Login | Sign Up</a>
                    </li>
                <?php } ?>
            </ul>
        </div>
</nav>
