<?php
ob_start(); // เริ่มบัฟเฟอร์การส่งข้อมูล
include_once('header.php');

// ตรวจสอบว่ามีการเริ่มต้นเซสชันหรือไม่ ถ้าไม่มีก็ให้เริ่มเซสชัน
if (session_status() == PHP_SESSION_NONE) {
    session_start(); // เพิ่ม session_start() ที่นี่เพื่อเริ่มเซสชัน
}

// ตรวจสอบว่ามีข้อความแจ้งเตือนในเซสชันหรือไม่
if (isset($_SESSION['success_register'])) {
    echo "<div class='alert alert-success'>" . htmlspecialchars($_SESSION['success_register']) . "</div>";
    unset($_SESSION['success_register']);
}

if (isset($_SESSION['errors'])) {
    foreach ($_SESSION['errors'] as $error) {
        echo "<div class='alert alert-danger'>" . htmlspecialchars($error) . "</div>";
    }
    unset($_SESSION['errors']);
}
?>

<div class="login">
    <h3 class="my-3">ล็อคอิน</h3>

    <?php
    // รวมไฟล์ที่จำเป็น
    include_once("config/Database.php");
    include_once("class/UserLogin.php");
    include_once("class/Utils.php");

    // สร้างการเชื่อมต่อฐานข้อมูล
    $connectDB = new Database();
    $db = $connectDB->getConnection();

    // สร้างออบเจ็กต์ UserLogin
    $user = new UserLogin($db);

    // ตรวจสอบว่าฟอร์มถูกส่งด้วยการกดปุ่ม 'signin' หรือไม่
    if (isset($_POST['signin'])) {
        // ตั้งค่าอีเมลและรหัสผ่านจากข้อมูลที่ส่งมาจากฟอร์ม
        $user->setEmail($_POST['email']);
        $user->setPassword($_POST['password']);

        // ตรวจสอบว่าอีเมลมีอยู่ในระบบหรือไม่
        if ($user->emailNotExists()) {
            $_SESSION['errors'][] = "Email does not exist";
            header("Location: " . $_SERVER['PHP_SELF']); // เปลี่ยนเส้นทางเพื่อรีเฟรชหน้า
            exit();
        } else {
            // ตรวจสอบว่ารหัสผ่านถูกต้องหรือไม่
            if ($user->verifyPassword()) {
                // ถ้าล็อกอินสำเร็จ ให้ทำการตั้งค่าเซสชันและคุกกี้
                $userId = $user->getUserId();
                $email = $user->getEmail();
                $userName = $user->getName(); // ดึงชื่อผู้ใช้จากฐานข้อมูล

                // ตั้งค่า session
                $_SESSION['user_id'] = $userId;
                $_SESSION['email'] = $email;
                $_SESSION['name'] = $userName;

                if (isset($_POST['rememberme'])) {
                    // ตั้งค่าคุกกี้เพื่อจำข้อมูลการเข้าสู่ระบบ
                    setcookie('email', base64_encode($email), time() + (86400 * 30), "/"); // 30 วัน
                    setcookie('password', base64_encode($user->getPassword()), time() + (86400 * 30), "/"); // 30 วัน
                }

                // เปลี่ยนเส้นทางไปยังหน้า welcome.php
                header("Location: welcome.php");
                exit();
            } else {
                $_SESSION['errors'][] = "Password does not match";
                header("Location: " . $_SERVER['PHP_SELF']); // เปลี่ยนเส้นทางเพื่อรีเฟรชหน้า
                exit();
            }
        }
    }
    ?>
    <style>
        .login {
            background: #4a4c4c;
            width: 800px;
            height: 350px;
            padding: 2rem;
            margin: 50px auto;
            border-radius: 10px;
            box-shadow: 0 20px 35px #000001e6;
            margin-top: 8rem;
        }

        .login h3 {
            color: #ffffff;
            text-align: center;
            font-size: 24px;
        }

        .emaillogin {
            text-align: center;
            font-size: 20px;
            margin-top: 20px;
        }

        .label-login {
            width: 400px;
            height: 40px;
            border-radius: 10px;
        }

        .label-login::placeholder {
            text-align: center;
        }

        .passwordlogin {
            text-align: center;
            font-size: 20px;
            margin-left: -80px;
            margin-top: -20px;
            position: relative;
        }

        .formpasswordlogin {
            width: 400px;
            height: 40px;
            border-radius: 10px;
            margin-left: -88px;
            position: absolute;
        }

        .labelpasswordlogin {
            margin-left: -192px;
            position: absolute;
            margin-top: 7px;
        }

        .formpasswordlogin::placeholder {
            text-align: center;
        }

        .btnlogin {
            margin-left: 300px;
            margin-top: 40px;
        }

        .btngoback {
            margin-top: 40px;
        }

        .texttosignin {
            margin-left: 220px;
        }

        .texttosignin a {
            color: #4664fc;
        }

        .labelrememberme {
            width: 5rem;
            margin-left: 270px;
            margin-top: 50px;
            display: flex;
            position: absolute;
        }

        .radiorememberme {
            display: flex;
            position: absolute;
            margin-top: 58px;
            margin-left: 254px;
        }
    </style>
    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="POST">
        <div class="emaillogin">
            <label for="email address" class="emaillogin1">Email Address :</label>
            <input type="email" name="email" class="label-login" aria-describedby="email" placeholder="ใส่อีเมลล์" required>
        </div>
        <br>
        <div class="passwordlogin">
            <label for="password" class="labelpasswordlogin">Password :</label>
            <input type="password" name="password" class="formpasswordlogin" aria-describedby="password" placeholder="ใส่รหัสผ่าน" required>
        </div>
        <div class="rememberme">
            <label for="rememberme" class="labelrememberme">จดจำฉัน</label>
            <input type="checkbox" name="rememberme" class="radiorememberme">
        </div>
        <br><br>
        <button type="submit" name="signin" class="btn btn-primary btnlogin">เข้าระบบ</button>
        <a href="index.php" class="btn btn-secondary btngoback">กลับหน้าแรก</a>
    </form>

    <p class="mt-3 texttosignin">ยังไม่ได้สมัครใช่หรือไม่? ไปที่หน้า <a href="signup.php">สมัครสมาชิก</a> </p>
</div>

<?php
include_once('footer.php');
ob_end_flush(); // ส่งข้อมูลออกไปที่เบราว์เซอร์
?>
