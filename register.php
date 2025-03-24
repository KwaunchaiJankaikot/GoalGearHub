<?php
require_once("config/database.php");

$database = new Database();
$conn = $database->getConnection();

if (isset($_POST['signup'])) {
    $name = $_POST["name"];
    $email = $_POST["email"];
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];
    $phone = $_POST["phone"];
    
    if ($password != $confirm_password) {
        echo "<script>alert('Password not match');window.location.href='signup.php';</script>";
        exit();
    }

    $query = "INSERT INTO users (name, email, password, phone) VALUES (:name, :email, :password, :phone)";
    
    try {
        $stmt = $conn->prepare($query);

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":password", $hashedPassword);
        $stmt->bindParam(":phone", $phone);

        if ($stmt->execute()) {
            header("Location: signin.php");
        } else {
            header("Location: signup.php");
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>
