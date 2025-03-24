<?php
include_once('header.php');
include_once('config/Database.php');
include_once('nav.php');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$db = new Database();
$conn = $db->getConnection();

// รับค่าฟิลเตอร์จากฟอร์มและตรวจสอบว่ามีการเลือกหรือไม่
$categories = isset($_GET['category']) ? $_GET['category'] : [];
$sizes = isset($_GET['size']) ? $_GET['size'] : [];
$product_brands = isset($_GET['brand']) ? $_GET['brand'] : [];
$teams = isset($_GET['team']) ? $_GET['team'] : [];

// ตรวจสอบว่ามีการใช้ตัวกรองหรือไม่
$filterApplied = !empty($categories) || !empty($sizes) || !empty($product_brands);

// สร้างคำสั่ง SQL สำหรับค้นหาสินค้าตามฟิลเตอร์ และต้องเป็นสินค้าที่ approved = 1
$query = "SELECT * FROM products WHERE approved = 1";
$params = [];

// ตรวจสอบและเพิ่มเงื่อนไขตามฟิลเตอร์หมวดหมู่
if (!empty($categories)) {
    $query .= " AND category IN (" . implode(',', array_fill(0, count($categories), '?')) . ")";
    $params = array_merge($params, $categories);
}

// ตรวจสอบและเพิ่มเงื่อนไขตามฟิลเตอร์ขนาด
if (!empty($sizes)) {
    $query .= " AND FIND_IN_SET(?, product_sizes)";
    $params[] = implode(',', $sizes); // ใช้ FIND_IN_SET สำหรับการค้นหา size
}

// ตรวจสอบและเพิ่มเงื่อนไขตามฟิลเตอร์ยี่ห้อ
if (!empty($product_brands)) {
    $query .= " AND product_brand IN (" . implode(',', array_fill(0, count($product_brands), '?')) . ")";
    $params = array_merge($params, $product_brands);
}
if (!empty($teams)) {
    $query .= " AND team_name IN (" . implode(',', array_fill(0, count($teams), '?')) . ")";
    $params = array_merge($params, $teams); // เพิ่มการกรองทีมฟุตบอล
}

// เตรียมและรันคำสั่ง SQL
$stmt = $conn->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Filter Products</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .d-flex {
            min-height: 100vh;
            display: flex;
        }

        .sidebar {
            width: 250px;
            padding: 20px;
            margin-top: 0px;
            left: 0px;
            background-color: #343a40;
            color: white;
            height: auto;
            position: absolute;
        }

        .sidebar h3,
        .sidebar h5 {
            color: white;
        }

        .form-check-label {
            color: white;
        }

        .form-check {
            margin-bottom: 10px;
        }

        .filter-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .filter-header i {
            font-size: 24px;
            margin-right: 10px;
        }

        .content {
            margin-left: 270px;
            padding: 20px;
            flex-grow: 1;
        }

        .product-list {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .product-item {
            background-color: #343a40;
            border-radius: 5px;
            color: #fff;
            padding: 10px;
            width: 200px;
            text-align: center;
            border: 1px solid transparent;
            transition: border-color 0.3s ease-in-out;
        }

        .product-item img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 10px;
        }

        .product-item h5 {
            font-size: 16px;
            margin: 0;
            color: #ffc107;
        }

        .price {
            margin-top: 10px;
            font-size: 20px;
            color: #ffa500;
        }

        .btn-show {
            color: rgba(255, 255, 255, .5);
            text-decoration: none;
            /* border: 1px solid red; */
            margin-top: -10px;
            /* border: 1px solid red; */
        }

        .btn-show:hover {
            color: #fff;
            text-decoration: none;
        }
        .btnsearch{
            color: #fff;
        }
    </style>
</head>

<body>
    <div class="container">
        <?php include_once('nav.php'); ?>

        <div class="sidebar">
            <form id="filterForm" method="GET" action="">
                <div class="filter-header">
                    <i class="fas fa-filter"></i>
                    <h5>ตัวกรองสินค้า</h5>
                </div>

                <!-- Sections for filtering -->
                <div class="filter-section">
                    <h6>หมวดหมู่</h6>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="category[]" id="shoes" value="1">
                        <label class="form-check-label" for="shoes">รองเท้า</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="category[]" id="shirt" value="2">
                        <label class="form-check-label" for="shirt">เสื้อ</label>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="category[]" id="sourvenir" value="3">
                        <label class="form-check-label" for="sourvenir">ของที่ระลึก</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="category[]" id="pant" value="4">
                        <label class="form-check-label" for="pant">กางเกง</label>
                    </div>
                </div>

                <div class="filter-section">
                    <h6>ไซส์รองเท้าฟุตบอล</h6>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="size[]" id="size8" value="8US">
                        <label class="form-check-label" for="size8">8 US</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="size[]" id="size9" value="9US">
                        <label class="form-check-label" for="size9">9 US</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="size[]" id="size10" value="10US">
                        <label class="form-check-label" for="size10">10 US</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="size[]" id="size11" value="11US">
                        <label class="form-check-label" for="size11">11 US</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="size[]" id="size12" value="12US">
                        <label class="form-check-label" for="size12">12 US</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="size[]" id="size13" value="13US">
                        <label class="form-check-label" for="size13">13 US</label>
                    </div>
                    <!-- ซ่อนตั้งแต่ที่นี่ -->
                     
                    <div id="moreSize" style="display: none;">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="size[]" id="S" value="S">
                            <label class="form-check-label" for="S">S</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="size[]" id="M" value="M">
                            <label class="form-check-label" for="M">M</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="size[]" id="L" value="L">
                            <label class="form-check-label" for="L">L</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="size[]" id="XL" value="XL">
                            <label class="form-check-label" for="XL">XL</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="size[]" id="2XL" value="2XL">
                            <label class="form-check-label" for="2XL">2XL</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="size[]" id="3XL" value="3XL">
                            <label class="form-check-label" for="3XL">3XL</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="size[]" id="4XL" value="4XL">
                            <label class="form-check-label" for="4XL">4XL</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="size[]" id="5XL" value="5XL">
                            <label class="form-check-label" for="5XL">5XL</label>
                        </div>
                    </div>
                    <a class="btn-show" href="javascript:void(0);" id="showMoreSizeBtn" onclick="showMoreSize()" style="color: #ffa500; cursor: pointer;">Show More</a>
                </div>

                <div class="filter-section">
                    <h6><br>ยี่ห้อรองเท้าฟุตบอล</h6>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="brand[]" id="nike" value="Nike">
                        <label class="form-check-label" for="nike">Nike</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="brand[]" id="adidas" value="Adidas">
                        <label class="form-check-label" for="adidas">Adidas</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="brand[]" id="puma" value="Puma">
                        <label class="form-check-label" for="puma">Puma</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="brand[]" id="underArmour" value="Under Armour">
                        <label class="form-check-label" for="underArmour">Under Armour</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="brand[]" id="newBalance" value="New Balance">
                        <label class="form-check-label" for="newBalance">New Balance</label>
                    </div>

                </div>
                <div class="filter-section">
                    <h6>ทีมฟุตบอล</h6>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="team[]" id="manchesterUnited" value="Manchester United">
                        <label class="form-check-label" for="manchesterUnited">Manchester United</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="team[]" id="chelsea" value="Chelsea">
                        <label class="form-check-label" for="chelsea">Chelsea</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="team[]" id="liverpool" value="Liverpool">
                        <label class="form-check-label" for="liverpool">Liverpool</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="team[]" id="arsenal" value="Arsenal">
                        <label class="form-check-label" for="arsenal">Arsenal</label>
                    </div>
                    <!-- ซ่อนตั้งแต่ที่นี่ -->
                    <div id="moreTeams" style="display: none;">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="team[]" id="manchesterCity" value="Manchester City">
                            <label class="form-check-label" for="manchesterCity">Manchester City</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="team[]" id="tottenhamHotspur" value="Tottenham Hotspur">
                            <label class="form-check-label" for="tottenhamHotspur">Tottenham Hotspur</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="team[]" id="realMadrid" value="Real Madrid">
                            <label class="form-check-label" for="realMadrid">Real Madrid</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="team[]" id="barcelona" value="Barcelona">
                            <label class="form-check-label" for="barcelona">Barcelona</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="team[]" id="borussiaDortmund" value="Borussia Dortmund">
                            <label class="form-check-label" for="borussiaDortmund">Borussia Dortmund</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="team[]" id="ajax" value="Ajax">
                            <label class="form-check-label" for="ajax">Ajax</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="team[]" id="atleticoMadrid" value="Atletico Madrid">
                            <label class="form-check-label" for="atleticoMadrid">Atletico Madrid</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="team[]" id="rbLeipzig" value="RB Leipzig">
                            <label class="form-check-label" for="rbLeipzig">RB Leipzig</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="team[]" id="bayerLeverkusen" value="Bayer Leverkusen">
                            <label class="form-check-label" for="bayerLeverkusen">Bayer Leverkusen</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="team[]" id="parisSaintGermain" value="Paris Saint-Germain">
                            <label class="form-check-label" for="parisSaintGermain">Paris Saint-Germain</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="team[]" id="juventus" value="Juventus">
                            <label class="form-check-label" for="juventus">Juventus</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="team[]" id="acMilan" value="AC Milan">
                            <label class="form-check-label" for="acMilan">AC Milan</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="team[]" id="interMilan" value="Inter Milan">
                            <label class="form-check-label" for="interMilan">Inter Milan</label>
                        </div>
                    </div>
                    <!-- ปุ่ม Show More -->
                    <a class="btn-show" href="javascript:void(0);" id="showMoreTeamBtn" onclick="showMoreTeam()" style="color: #ffa500; cursor: pointer;">Show More</a>
                </div>

                <button type="submit" class="btn btn-warning btnsearch mt-3">ค้นหา</button>
                <a href="welcome.php" class="btn btn-secondary mt-3">รีเซ็ต</a>
            </form>
        </div>

        <!-- <div class="content">
            <h1><?php echo $filterApplied ? 'สินค้าที่ค้นหา' : 'รายการสินค้าทั้งหมด'; ?></h1>
            <div class="product-list">
                <?php if (!empty($products)) : ?>
                    <?php foreach ($products as $product) : ?>
                        <div class="product-item">
                            <img src="uploads/<?php echo htmlspecialchars($product['product_picture']); ?>" alt="Product Image">
                            <h5><?php echo htmlspecialchars($product['product_name']); ?></h5>
                            <p class="price">฿<?php echo htmlspecialchars(number_format($product['product_price'], 2)); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p>No products available.</p>
                <?php endif; ?>
            </div>
        </div> -->
    </div>
</body>
<script>
    function showMoreSize() {
        var moreSize = document.getElementById("moreSize");
        var showMoreBtn = document.getElementById("showMoreSizeBtn");

        if (moreSize.style.display === "none") {
            moreSize.style.display = "block";
            showMoreBtn.innerText = "Show Less";
        } else {
            moreSize.style.display = "none";
            showMoreBtn.innerText = "Show More";
        }
    }

    // ฟังก์ชันสำหรับแสดง/ซ่อนทีมฟุตบอล
    function showMoreTeam() {
        var moreTeams = document.getElementById("moreTeams");
        var showMoreBtn = document.getElementById("showMoreTeamBtn");

        if (moreTeams.style.display === "none") {
            moreTeams.style.display = "block";
            showMoreBtn.innerText = "Show Less";
        } else {
            moreTeams.style.display = "none";
            showMoreBtn.innerText = "Show More";
        }
    }
</script>

</html>