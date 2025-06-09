<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}
require __DIR__ . '/../includes/config.php';
// ------ معالجة تحديث الإعدادات ------ //
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['settings'])) {
        die("لم يتم استقبال أي بيانات من النموذج");
    }  
    $errors = [];   
    foreach ($_POST['settings'] as $name => $value) {
        $value = filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        if ($value === false) {
            $errors[] = "قيمة غير صالحة لـ {$name}";
            continue;
        }
        
        try {
            $stmt = $conn->prepare("UPDATE settings SET value = ? WHERE name = ?");
            if (!$stmt) {
                throw new Exception("خطأ في الاستعلام: " . $conn->error);
            }
            $stmt->bind_param("ss", $value, $name);
            if (!$stmt->execute()) {
                throw new Exception("فشل التحديث: " . $stmt->error);
            }
            $stmt->close();
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
            error_log($e->getMessage());
        }
    }
    if (empty($errors)) {
        $_SESSION['success'] = "تم التحديث بنجاح!";
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
    }
    
    header('Location:'. BASE_URL .'admin/dashboard.php?section=settings');
    exit();
}

?>

<!-- عرض الرسائل -->
<?php if (isset($_SESSION['error'])): ?>
<script>
Swal.fire({
    icon: 'error',
    title: 'خطأ!',
    text: '<?= addslashes($_SESSION['error']) ?>'
});
</script>
<?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['success'])): ?>
<script>
Swal.fire({
    icon: 'success',
    title: 'نجاح!',
    text: '<?= addslashes($_SESSION['success']) ?>'
});
</script>
<?php unset($_SESSION['success']); ?>
<?php endif; ?>
