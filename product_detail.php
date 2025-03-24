<?php
ob_start();
include_once('header.php');
include_once('config/Database.php');
include_once('nav.php');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('กรุณาล็อกอินก่อนเพื่อดูรายละเอียดสินค้า'); window.location.href='welcome.php';</script>";
    exit(); // หยุดการรันของ script หากยังไม่ได้ล็อกอิน
}

$db = new Database();
$conn = $db->getConnection();

if (isset($_GET['product_id'])) {
    $product_id = $_GET['product_id'];

    // ดึงข้อมูลสินค้า
    $query = "SELECT * FROM products WHERE product_id = :product_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':product_id', $product_id);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo "<script>alert('ไม่พบสินค้าที่คุณต้องการ'); window.location.href='index.php';</script>";
        exit();
    }

    // ดึงข้อมูลชื่อผู้ขาย
    $query = "SELECT name FROM users WHERE id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $product['user_id']);
    $stmt->execute();
    $seller = $stmt->fetch(PDO::FETCH_ASSOC);
    $seller_name = $seller['name'];

    // ตรวจสอบว่าผู้ใช้เคยซื้อสินค้านี้หรือยัง
    $userId = $_SESSION['user_id'];
    $query = "SELECT COUNT(*) FROM orders WHERE user_id = :user_id AND product_id = :product_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':product_id', $product_id);
    $stmt->execute();
    $hasPurchased = $stmt->fetchColumn() > 0;

    // แปลงรูปภาพเป็น array หรือ string แยกด้วยจุลภาค
    $images = explode(',', $product['product_picture']);

    // ดึงข้อมูลรีวิว
    $query = "SELECT r.Review_Detail, r.rating, r.Review_Date, u.name AS reviewer_name 
              FROM review r 
              JOIN users u ON r.user_id = u.id 
              WHERE r.product_id = :product_id 
              ORDER BY r.Review_Date DESC";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':product_id', $product_id);
    $stmt->execute();
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ค่าเฉลี่ยของคะแนนรีวิว
    $query = "SELECT AVG(rating) as average_rating FROM review WHERE product_id = :product_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':product_id', $product_id);
    $stmt->execute();
    $ratingResult = $stmt->fetch(PDO::FETCH_ASSOC);
    $average_rating = $ratingResult ? round($ratingResult['average_rating'], 2) : 0;
} else {
    header("Location: index.php");
    exit();
}

// การจัดการเพิ่มสินค้าลงตะกร้า
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $user_id = $_SESSION['user_id'];
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    $size = isset($_POST['size']) ? $_POST['size'] : '';

    // ตรวจสอบการมีอยู่ของสินค้าในตะกร้า
    $query = "SELECT * FROM cart WHERE user_id = :user_id AND product_id = :product_id AND size = :size";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':product_id', $product_id);
    $stmt->bindParam(':size', $size);
    $stmt->execute();
    $existingItem = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingItem) {
        $newQuantity = $existingItem['quantity'] + $quantity;
        $updateQuery = "UPDATE cart SET quantity = :quantity WHERE user_id = :user_id AND product_id = :product_id AND size = :size";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bindParam(':quantity', $newQuantity);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->bindParam(':size', $size);
        $stmt->execute();
    } else {
        // เพิ่มสินค้าลงตะกร้า
        $insertQuery = "INSERT INTO cart (user_id, product_id, quantity, size) VALUES (:user_id, :product_id, :quantity, :size)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':size', $size);
        $stmt->execute();
    }

    echo "<script>
    alert('เพิ่มสินค้าลงในตะกร้าเรียบร้อยแล้ว');
    window.location.href = 'product_detail.php?product_id={$product_id}';
</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Product Detail</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<style>
    /* สไตล์ CSS ทั้งหมด */
    .container {
        display: flex;
        flex-direction: column;
        max-width: 1200px;
        margin: 20px auto;
        padding: 20px;
        background-color: #2b2b2b;
        border-radius: 10px;
    }

    .product-container {
        display: flex;
        justify-content: space-between;
        max-width: 1200px;
        width: 90%;
        background-color: #2b2b2b;
        padding: 20px;
        border-radius: 10px;
        margin: 20px auto;
    }

    .main-image {
        width: 400px;
        height: 300px;
        overflow: hidden;
        border-radius: 10px;
        margin-bottom: 20px;
    }

    .main-image img {
        width: 100%;
        height: 100%;
        object-fit: fill;
    }

    .small-images img {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 5px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
        margin-right: 10px;
    }

    .small-images-container {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-bottom: 20px;
        width: 100%;
        margin-top: 20px;
    }

    .product-gallery,
    .product-details {
        flex: 1;
    }

    .product-gallery {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding-right: 20px;
        position: relative;
        max-width: 50%;
    }

    .product-details {
        max-width: 50%;
        text-align: left;
        padding-left: 20px;
    }

    .product-details p {
        word-wrap: break-word;
        white-space: pre-wrap;
    }

    .product-details h2,
    .product-details h3 {
        color: #ffa500;
    }

    .product-details .price {
        color: #ffa500;
        font-size: 24px;
        margin: 10px 0;
    }

    .quantity {
        display: flex;
        align-items: center;
        margin: 20px 0;
        /* border: 1px solid red; */
        margin-top: 0px;
        width: 80px;
        height: 40px;
        padding: 10px;
        font-size: 16px;
        border-radius: 5px;
        outline: none;

    }

    .quantity button,
    .quantity input {
        background-color: #444;
        color: #fff;
        border: none;
        padding: 10px;
        border-radius: 0px;
        text-align: center;
    }

    .actions,
    .actions-cart {
        display: flex;
        gap: 20px;
        margin-top: 20px;
    }

    .actions button,
    .actions-cart button {
        background-color: #ffa500;
        /* เปลี่ยนสีปุ่มเป็นสีเขียว */
        color: #fff;
        border: none;
        padding: 10px 15px;
        cursor: pointer;
        border-radius: 5px;
        font-size: 16px;
        transition: background-color 0.3s ease;
    }

    .actions-cart {
        /* border: 1px solid red; */
        margin-top: 47.2px;
        margin-left: -120px;
        /* height: 50px; */
    }

    /* .actions button:hover {
        background-color: #218838;
    } */


    .slider-icon {
        font-size: 24px;
        color: white;
        cursor: pointer;
        padding: 10px;
        background-color: rgba(0, 0, 0, 0.5);
        border-radius: 50%;
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        z-index: 10;
    }

    .slider-icon-left {
        left: -15px;
        top: 250px;
    }

    .slider-icon-right {
        right: 0px;
        top: 250px;
    }

    .review-section {
        background-color: #444;
        padding: 20px;
        border-radius: 10px;
        color: white;
        text-align: left;
        margin-top: 20px;

    }

    .review-section textarea {
        width: 100%;
        padding: 15px;
        font-size: 14px;
        border-radius: 8px;
        border: 1px #333;
        background-color: #333;
        color: white;
        resize: vertical;
        margin-bottom: 15px;
        outline: none;
        transition: border-color 0.3s ease;
    }

    .review-section label {
        display: inline-block;
        font-size: 16px;
        margin-right: 10px;
        color: #ddd;
    }

    .review-section select {
        padding: 10px;
        border-radius: 5px;
        background-color: #444;
        color: white;
        border: 1px solid #ccc;
        outline: none;
        margin-right: 10px;
    }

    .review-section button {
        background-color: #ffa500;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;

    }

    .review {
        background-color: #333;
        padding: 20px;
        margin-bottom: 20px;
        border-radius: 10px;
        color: white;
    }

    .review strong {
        color: #ffa500;
        font-size: 18px;
    }

    .review p {
        font-size: 16px;
        line-height: 1.5;
        margin-bottom: 10px;
    }

   

    .average-rating h3 {
        margin: 0;
        font-size: 24px;
        color: #ffa500;
    }

    .average-rating p {
        font-size: 30px;
        margin: 10px 0;
        color: #ffd700;
    }

    .average-rating-container {
        background-color: #444;
        color: #fff;
        padding: 10px 20px;
        border-radius: 10px;
        text-align: center;
        margin: 20px 0;
        font-size: 24px;
        max-width: 400px;
        margin-left: auto;
        margin-right: auto;
        box-shadow: #2b2b2b;
    }

    .average-rating-score {
        font-size: 36px;
        margin-top: 10px;
    }

    .sizes button {
        background-color: #ffa500;
        /* เปลี่ยนสีพื้นหลัง */
        color: #fff;
        /* สีข้อความ */
        border: none;
        /* ไม่มีขอบ */
        padding: 10px 20px;
        /* ระยะห่างภายในปุ่ม */
        margin-right: 10px;
        /* ระยะห่างระหว่างปุ่ม */
        border-radius: 5px;
        /* ขอบมน */
        font-size: 16px;
        /* ขนาดตัวอักษร */
        /* cursor: pointer; */
        /* เปลี่ยนเคอร์เซอร์เมื่อ hover */
        /* transition: background-color 0.3s ease; */
        /* เพิ่ม transition เมื่อ hover */
        /* border: 1px solid red; */
        width: auto;
        margin: 5px;

    }


    .sizes button.active {
        background-color: #ff4500;
        /* สีเมื่อเลือกปุ่ม */
        font-weight: bold;
        /* ทำให้ตัวอักษรหนาขึ้นเมื่อเลือก */
    }

    /* .average-rating-stars {
        font-size: 30px;
        margin-top: 10px;
        color: #ffd700;
    } */


    @media (max-width: 768px) {
        .container {
            flex-direction: column;
        }

        .product-container {
            flex-direction: column;
        }

        .main-image {
            width: 100%;
            max-height: 300px;
        }
    }
</style>

<body>
    <div class="container">
        <div class="product-container">
            <div class="product-gallery">
                <div class="small-images-container">
                    <i class="fas fa-chevron-left slider-icon slider-icon-left" onclick="prevImage()"></i>
                    <div class="small-images" id="smallImages">
                        <?php foreach ($images as $index => $image): ?>
                            <img src="uploads/<?php echo htmlspecialchars(trim($image)); ?>" alt="Product Image" onclick="changeImage('uploads/<?php echo htmlspecialchars(trim($image)); ?>', <?php echo $index; ?>)">
                        <?php endforeach; ?>
                    </div>
                    <i class="fas fa-chevron-right slider-icon slider-icon-right" onclick="nextImage()"></i>
                </div>
                <div class="main-image">
                    <img id="mainImage" src="uploads/<?php echo htmlspecialchars(trim($images[0])); ?>" alt="Product Image">
                </div>
            </div>

            <div class="product-details">
                <h2><?php echo htmlspecialchars($product['product_name']); ?></h2>
                <p>จำนวนสินค้าที่มี: <?php echo htmlspecialchars($product['product_quantity']); ?></p>
                <p>ขายโดย: <?php echo htmlspecialchars($seller_name); ?></p>
                <h3 class="price">฿<?php echo htmlspecialchars($product['product_price']); ?></h3>
                <p class="note">คำอธิบาย:</p>
                <p><?php echo htmlspecialchars($product['product_description']); ?></p>

                <?php if (!empty($product['product_sizes'])): ?>
                    <p>ไซส์:</p>
                    <div class="sizes">
                        <?php $sizes = explode(',', $product['product_sizes']); ?>
                        <?php foreach ($sizes as $size): ?>
                            <button type="button" class="size-button" data-size="<?php echo htmlspecialchars(trim($size)); ?>">
                                <?php echo htmlspecialchars(trim($size)); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>


                <div class="actions">
                    <!-- <form method="POST" action="checkout.php">
                        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['product_id']); ?>">
                        <input type="number" class="quantity" name="quantity" value="1" min="1" id="quantityInput">
                        <input type="hidden" name="size" id="selectedSize">
                        <button type="submit" name="buy_now" class="buy-now-button">ซื้อทันที</button>
                    </form> -->


                    <form method="POST" action="">
                        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['product_id']); ?>">
                        <input type="number" class="quantity" name="quantity" value="1" min="1" max="<?php echo htmlspecialchars($product['product_quantity']); ?>" id="quantityInput">
                        <input type="hidden" name="size" id="selectedSize">
                        <button type="submit" name="add_to_cart" class="cart-icon">เพิ่มสินค้าลงตะกร้า</button>
                    </form>

                </div>

            </div>
        </div>

        <div class="average-rating-container">
            <p>คะแนนเฉลี่ยของสินค้านี้:</p>
            <div class="average-rating-stars">
                <?php
                // แปลงคะแนนเฉลี่ยเป็นจำนวนดาว (เต็มและครึ่งดาว)
                $fullStars = floor($average_rating); // จำนวนดาวเต็ม
                $halfStar = $average_rating - $fullStars >= 0.5 ? 1 : 0; // ครึ่งดาว
                $emptyStars = 5 - ($fullStars + $halfStar); // ดาวที่ไม่มีคะแนน

                // แสดงดาวเต็ม
                for ($i = 0; $i < $fullStars; $i++) {
                    echo '<i class="fas fa-star" style="color: #ffa500;"></i>';
                }

                // แสดงครึ่งดาว (ถ้ามี)
                if ($halfStar) {
                    echo '<i class="fas fa-star-half-alt" style="color: #ffa500;"></i>';
                }

                // แสดงดาวที่ไม่มีคะแนน
                for ($i = 0; $i < $emptyStars; $i++) {
                    echo '<i class="far fa-star" style="color: #ffa500;"></i>';
                }
                ?>
            </div>
        </div>


        <div class="review-section">
            <h3>รีวิวสินค้า</h3>
            <?php if (count($reviews) > 0): ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review">
                        <p><strong><?php echo htmlspecialchars($review['reviewer_name']); ?>:</strong></p>
                        <p><?php echo htmlspecialchars($review['Review_Detail']); ?></p> 
                        <p class="review-date">รีวิวเมื่อ: <?php echo date("d M Y H:i:s ", strtotime($review['Review_Date'])); ?></p>
                        <p>คะแนน:
                            <?php
                            echo str_repeat('<i class="fas fa-star" style="color: #ffa500;"></i>', $review['rating']);
                            echo str_repeat('<i class="far fa-star" style="color: #ffa500;"></i>', 5 - $review['rating']);
                            ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>ยังไม่มีรีวิวสินค้านี้จากสมาชิก</p>
            <?php endif; ?>
        </div>

        <?php if ($hasPurchased): ?>
            <div class="review-section">
                <h3>เขียนรีวิวของคุณ</h3>
                <form id="reviewForm">
                    <textarea name="review_text" rows="4" placeholder="เขียนรีวิวของคุณที่นี่..."></textarea>
                    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product_id); ?>">
                    <label>ให้คะแนน:
                        <select name="rating">
                            <option value="5">5</option>
                            <option value="4">4</option>
                            <option value="3">3</option>
                            <option value="2">2</option>
                            <option value="1">1</option>
                        </select>
                    </label>
                    <button type="submit">ส่งรีวิว</button>
                </form>
            </div>
        <?php endif; ?>

        <script>
            let currentImageIndex = 0;
            const images = <?php echo json_encode($images); ?>;

            function changeImage(src, index) {
                document.getElementById("mainImage").src = src;
                currentImageIndex = index;
            }

            function prevImage() {
                currentImageIndex = (currentImageIndex > 0) ? currentImageIndex - 1 : images.length - 1;
                document.getElementById("mainImage").src = 'uploads/' + images[currentImageIndex].trim();
            }

            function nextImage() {
                currentImageIndex = (currentImageIndex < images.length - 1) ? currentImageIndex + 1 : 0;
                document.getElementById("mainImage").src = 'uploads/' + images[currentImageIndex].trim();
            }

            const sizeButtons = document.querySelectorAll('.size-button');
            const selectedSizeInput = document.getElementById('selectedSize');
            const cartSelectedSizeInput = document.getElementById('cartSelectedSize');

            sizeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    sizeButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    const selectedSize = this.getAttribute('data-size');
                    selectedSizeInput.value = selectedSize;
                    cartSelectedSizeInput.value = selectedSize;
                });
            });

            $('#reviewForm').on('submit', function(event) {
                event.preventDefault();

                $.ajax({
                    url: 'submit_review.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        if (response === "success") {
                            alert("รีวิวของคุณถูกส่งเรียบร้อยแล้ว");
                            $('#reviewForm')[0].reset();
                        } else {
                            alert("เกิดข้อผิดพลาดในการส่งรีวิว");
                        }
                    },
                    error: function(xhr, status, error) {
                        alert("เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์");
                    }
                });
            });
            // ตรวจสอบเมื่อมีการเปลี่ยนค่าใน input
            const quantityInput = document.getElementById('quantityInput');
            const maxQuantity = quantityInput.getAttribute('max'); // ดึงค่า max จาก attribute

            quantityInput.addEventListener('input', function() {
                const currentValue = parseInt(quantityInput.value, 10);

                // ตรวจสอบว่าค่าที่ใส่เกินจำนวนสินค้าหรือไม่
                if (currentValue > maxQuantity) {
                    alert('จำนวนสินค้าที่สั่งไม่สามารถเกินจากที่มีในคลัง');
                    quantityInput.value = maxQuantity; // กำหนดค่าใหม่ให้ไม่เกิน max
                }
            });
        </script>
    </div>
</body>

</html>
<?php
ob_end_flush(); // ส่งข้อมูลที่บัฟเฟอร์ไปยังเบราว์เซอร์และหยุดการบัฟเฟอร์
?>