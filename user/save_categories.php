<?php
session_start();
require __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// حذف التصنيفات القديمة
$conn->query("DELETE FROM user_categories WHERE user_id = {$_SESSION['user_id']}");

// إضافة التصنيفات الجديدة
if (!empty($_POST['categories'])) {
    $stmt = $conn->prepare("INSERT INTO user_categories (user_id, category_id) VALUES (?, ?)");
    foreach ($_POST['categories'] as $cat_id) {
        $stmt->bind_param("ii", $_SESSION['user_id'], $cat_id);
        $stmt->execute();
    }
}

header('Location: ' . BASE_URL . 'user/dashboard.php');
exit();