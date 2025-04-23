<?php
session_start();
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/header.php';
?>

<div class="container mt-5">
    <div class="alert alert-info text-center">
        <h4>⚠️ هذه بيئة افتراضية</h4>
        <p>لن يتم إرسال بريد حقيقي. انسخ الرابط التالي:</p>
        <div class="alert alert-light">
            <code><?= $_SESSION['reset_link'] ?? 'الرابط غير متوفر' ?></code>
        </div>
        <a href="login.php" class="btn btn-primary">العودة لتسجيل الدخول</a>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>