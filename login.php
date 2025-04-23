<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <?php include __DIR__ . '/includes/alerts.php'; ?>
            <!-- رسائل تحذيرية -->
            <div class="card">
                <div class="card-header">تسجيل الدخول</div>
                <div class="card-body">
                    <form action="<?= BASE_URL ?>process.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                        <div class="mb-3">
                            <input type="text" class="form-control" placeholder="اسم المستخدم" name="name" required>
                        </div>

                        <div class="mb-3">
                            <div class="input-group">
                                <input type="password" class="form-control pe-5" id="password" placeholder="كلمة المرور"
                                    name="password" style="padding-right: 45px;" required>
                                <button type="button"
                                    class="btn btn-link position-absolute top-50 end-0 translate-middle-y"
                                    onclick="togglePassword()"
                                    style="position: absolute; right: 0; z-index: 10; background: none; border: none;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                        class="bi bi-eye" viewBox="0 0 16 16" id="eyeIcon">
                                        <path
                                            d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z" />
                                        <path
                                            d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <div class="d-flex flex-row-reverse justify-content-start align-items-center gap-2">
                                <label class="form-check-label m-0" for="remember">تذكر الحساب</label>
                                <input type="checkbox" class="form-check-input position-static" id="remember"
                                    name="remember_me">
                            </div>
                        </div>

                        <button type="submit" name="login" class="btn btn-primary">دخول</button>
                    </form>

                    <p class="auth-link mt-3">
                        ليس لديك حساب؟
                        <a href="<?= BASE_URL ?>register.php">انشاء حساب</a>
                        <br>
                        <a href="<?= BASE_URL ?>forget_password.php">هل نسيت كلمة المرور؟</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function togglePassword() {
        const passwordField = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');

        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            eyeIcon.classList.replace('bi-eye', 'bi-eye-slash');
        } else {
            passwordField.type = 'password';
            eyeIcon.classList.replace('bi-eye-slash', 'bi-eye');
        }
    }
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>