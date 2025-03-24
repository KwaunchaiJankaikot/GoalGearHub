<?php
$password = 'adminpassword'; // รหัสผ่านที่คุณต้องการแฮช
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

echo "Password: $password\n";
echo "Hashed Password: $hashed_password\n";
?>
