<?php
require_once("config/database.php");
include_once('header.php');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['signup'])) {
    // ดึงข้อมูลจากฟอร์ม
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = $_POST['phone'];

    // ข้อผิดพลาด
    $errors = [];

    // ตรวจสอบความถูกต้องของข้อมูล
    if (empty($name)) {
        $errors[] = "Name is required";
    }

    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    if (empty($password)) {
        $errors[] = "Password is required";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    if (empty($phone)) {
        $errors[] = "Phone number is required";
    } elseif (!preg_match("/^[0-9]{10}$/", $phone)) {
        $errors[] = "Invalid phone number format";
    }

    // หากไม่มีข้อผิดพลาด ให้ดำเนินการลงทะเบียนผู้ใช้
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $query = "INSERT INTO users (name, email, password, phone) VALUES (:name, :email, :password, :phone)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':phone', $phone);
            $stmt->execute();

            // เปลี่ยนเส้นทางไปยังหน้าเข้าสู่ระบบหลังจากลงทะเบียนสำเร็จ
            header("Location: signin.php");
            exit();
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    } else {
        // แสดงข้อผิดพลาด
        foreach ($errors as $error) {
            echo "<div class='alert alert-danger'>$error</div>";
        }
    }
}
?>
<div class="signup">
    <style>
        .signup {
            background: #4a4c4c;

            width: 700px;
            height: 450px;
            padding: 1.5rem;
            margin: 50px auto;
            border-radius: 10px;
            box-shadow: 0 20px 35px #000001e6;
        }

        .signup h3 {
            color: #ffffff;
            text-align: center;
            font-size: 24px;
        }

        .labelnamesignup {
            text-align: right;
            font-size: 16px;
            /* border: 1px solid #ffffff; */
            width: 25%;
            /* align-items: right;
    justify-content: right; */
            /* display: flex; */
        }

        .labelnamesignup1 {
            width: 400px;
            /* border: 1px solid #ff0000; */
            margin-left: 180px;
            margin-top: -35px;

        }

        .labelemailsignup {
            text-align: right;
            font-size: 16px;
            /* border: 1px solid #ffffff; */
            width: 25%;
            /* align-items: right;
    justify-content: right; */
            /* display: flex; */
        }

        .labelemailsignup1 {
            width: 400px;
            /* border: 1px solid #ff0000; */
            margin-left: 180px;
            margin-top: -35px;


        }

        .labelpasswordsignup {
            text-align: right;
            font-size: 16px;
            /* border: 1px solid #ffffff; */
            width: 25%;
            /* align-items: right;
    justify-content: right; */
            /* display: flex; */
        }

        .labelpasswordsignup1 {
            width: 400px;
            /* border: 1px solid #ff0000; */
            margin-left: 180px;
            margin-top: -35px;


        }

        .labelconfirmpasswordsignup {
            text-align: right;
            font-size: 16px;
            /* border: 1px solid #ffffff; */
            width: 25%;
            /* align-items: right;
    justify-content: right; */
            /* display: flex; */
        }

        .labelconfirmpasswordsignup1 {
            width: 400px;
            /* border: 1px solid #ff0000; */
            margin-left: 180px;
            margin-top: -35px;

        }

        .labeltelsignup {
            text-align: right;
            font-size: 16px;
            /* border: 1px solid #ffffff; */
            width: 25%;
            /* align-items: right;
    justify-content: right; */
            /* display: flex; */

        }

        .labeltelsignup1 {
            width: 400px;
            /* border: 1px solid #ff0000; */
            margin-left: 180px;
            margin-top: -35px;

        }

        .buttonsignupsignup {
            margin-left: 230px;
            /* border: 1px solid #ff0000; */
        }

        .signup p {
            text-align: center;
        }

        .remembermelabel {
            /* border: 1px solid #ff0000; */
            display: flex;
            width: 80px;
            margin-left: 16.5rem;
            position: absolute;
            margin-top: 10px;

        }

        .remembermecheckbox {
            margin-left: 15.5rem;
            margin-top: 18px;
            position: absolute;
            font-size: 50px;
        }
    </style>
    <h3 class='my-3'>สมัครสมาชิก</h3><br>

    <form action="register.php" method="POST">
        <div class="mb-3 namesignup">
            <label for="name" class="labelnamesignup">Name</label>
            <input type="text" name="name" class="form-control labelnamesignup1" aria-describedby="name" placeholder="ใส่ชื่อ" required>
        </div>
        <div class="mb-3 emailsignup">
            <label for="email" class="labelemailsignup">Email</label>
            <input type="email" name="email" class="form-control labelemailsignup1" aria-describedby="email" placeholder="ใส่อีเมล" required>
        </div>
        <div class="mb-3 passwordsignup">
            <label for="password" class="labelpasswordsignup">Password</label>
            <input type="password" name="password" class="form-control labelpasswordsignup1" aria-describedby="password" placeholder="ใส่พาสเวิร์ด" required>
        </div>
        <div class="mb-3 confirmpasswordsignup">
            <label for="confirm password" class="labelconfirmpasswordsignup">Confirm Password</label>
            <input type="password" name="confirm_password" class="form-control labelconfirmpasswordsignup1" aria-describedby="confirm password" placeholder="ใส่พาสเวิร์ดอีกครั้ง" required>
        </div>
        <div class="mb-3 telsignup">
            <label for="phone" class="labeltelsignup">Phone number</label><br>
            <input type="tel" id="phone" name="phone" class="form-control labeltelsignup1" placeholder="123-456-7890" pattern="[0-9]{10}" required>
        </div>

        <button type="submit" name="signup" class="btn btn-primary buttonsignupsignup">สมัครสมาชิก</button>
        <a href="index.php" class="btn btn-secondary buttonngobacksignup">กลับหน้าแรก</a>
    </form>
    <p class="mt-3">สมัครสมาชิกแล้วใช่หรือไม่? ไปที่หน้า <a href="signin.php">เข้าสู่ระบบ</a> </p>
</div>
<?php include_once('footer.php'); ?>