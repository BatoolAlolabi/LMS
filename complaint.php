<?php
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/header.php';
?>

<?php if (isset($_SESSION['error'])): ?>
<script>
Swal.fire({
    icon: 'warning',
    title: 'انتبه.. !',
    text: '<?= $_SESSION['error'] ?>'
});
</script>
<?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">تقديم شكوى</div>
                <div class="card-body">
                    <form action="<?= BASE_URL ?>process.php" method="POST">
                        <div class="mb-3">
                            <input type="email" class="form-control" name="email" placeholder="البريد الإلكتروني" required>
                        </div>
                        <div class="mb-3">
                            <textarea class="form-control" name="complaint" rows="5" placeholder="نص الشكوى" required></textarea>
                        </div>
                        <button type="submit" name="submit_complaint" class="btn btn-primary">إرسال</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>