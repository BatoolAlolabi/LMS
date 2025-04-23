<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/header.php';

$token = $_GET['token'] ?? '';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <?php include __DIR__ . '/includes/alerts.php'; ?>
            <div class="card">
                <div class="card-header">تعيين كلمة مرور جديدة</div>
                <div class="card-body">
                    <form action="<?= BASE_URL ?>process.php" method="POST">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                        <div class="mb-3">
                            <input type="password" class="form-control" placeholder="كلمة المرور الجديدة"
                                name="new_password" required>
                        </div>

                        <div class="mb-3">
                            <input type="password" class="form-control" placeholder="تأكيد كلمة المرور"
                                name="confirm_password" required>
                        </div>

                        <button type="submit" name="reset_password" class="btn btn-primary">تعيين</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>