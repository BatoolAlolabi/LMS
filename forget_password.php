<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <?php include __DIR__ . '/includes/alerts.php'; ?>
            <div class="card">
                <div class="card-header">استعادة كلمة المرور</div>
                <div class="card-body">
                    <form action="<?= BASE_URL ?>process.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                        <div class="mb-3">
                            <input type="email" class="form-control" placeholder="البريد الإلكتروني" name="email"
                                required>
                        </div>

                        <button type="submit" name="forget_password" class="btn btn-primary">إرسال الرابط</button>
                    </form>
                    div
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>