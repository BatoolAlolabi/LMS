<?php
ob_start();
session_start();

// إلغاء جميع متغيرات الجلسة
$_SESSION = array();

// حذف كوكي الجلسة
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 86400, '/');
}

// تدمير الجلسة
session_destroy();
// التوجيه مع إضافة header لمنع التخزين المؤقت
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header("Location: login.php");
ob_end_flush();
exit();
