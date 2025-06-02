<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    die("الوصول مرفوض!");
}

// إضافة خبر
if (isset($_POST['add_news'])) {
    $content = $conn->real_escape_string($_POST['content']);
    $conn->query("INSERT INTO news_ticker (content) VALUES ('$content')");
    $_SESSION['success'] = "تمت إضافة الخبر";
}

// تحديث خبر
if (isset($_POST['update_news'])) {
    $id = (int)$_POST['news_id'];
    $content = $conn->real_escape_string($_POST['content']);
    $conn->query("UPDATE news_ticker SET content = '$content' WHERE id = $id");
    $_SESSION['success'] = "تم التحديث";
}

// حذف خبر
if (isset($_POST['delete_news'])) {
    $id = (int)$_POST['news_id'];
    $conn->query("DELETE FROM news_ticker WHERE id = $id");
    $_SESSION['success'] = "تم الحذف";
}

header("Location:dashboard.php?section=news_ticker");
exit();