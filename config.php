<?php

$host = "localhost";
$user = "rabi";
$password = "Asd@123@123";
$dbname = "bookDB";

$conn = new mysqli($host, $user, $password, $dbname);

$conn->set_charset('utf8');

?>