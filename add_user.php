<?php

include "../config.php";

$name = $_GET["name"];
$password = $_GET["password"];
$email = $_GET["email"];

$sql = "INSERT INTO  users (name,password,email)
              VALUES ('$name','$password','$email')";

$result2 = $conn->query($sql);
// التحقق من النجاح
if ($result2 === true) {
    echo "تم إدخال البيانات بنجاح!";
} else {
    echo "فشل الإدخال: " . $conn->error;
}
header('location:http://localhost/test/battoul/sketch.php');
