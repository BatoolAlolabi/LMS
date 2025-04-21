<?php
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/header.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// جلب التصنيفات باستخدام prepared statement
$stmt = $conn->prepare("SELECT category_id, category_name FROM categories");
$stmt->execute();
$categories = $stmt->get_result();
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
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">إنشاء حساب جديد</div>
                <div class="card-body">
                    <form action="process.php" method="POST" id="regForm">
                        <!-- الخطوة 1: البيانات الأساسية -->
                        <div id="step1">
                            <div class="mb-3">
                                <input type="text" class="form-control" 
                                       name="name" placeholder="الاسم الكامل" required>
                            </div>
                            <div class="mb-3">
                                <input type="email" class="form-control" 
                                       name="email" placeholder="البريد الإلكتروني" required>
                            </div>
                            <div class="mb-3">
                                <input type="password" class="form-control" 
                                       name="password" id="password" 
                                       placeholder="كلمة المرور" required>
                            </div>
                            <div class="mb-3">
                                <input type="password" class="form-control" 
                                       id="confirm_password" 
                                       placeholder="تأكيد كلمة المرور" required>
                            </div>
                            <button type="button" class="btn btn-primary" onclick="nextStep()">التالي</button>
                        </div>

                        <!-- الخطوة 2: اختيار التصنيفات -->
                        <div id="step2" style="display:none;">
                            <h5>اختر التصنيفات المفضلة:</h5>
                            <div class="row">
                                <?php while($cat = $categories->fetch_assoc()): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input 
                                            type="checkbox" 
                                            name="categories[]" 
                                            value="<?= $cat['category_id'] ?>"
                                            id="cat_<?= $cat['category_id'] ?>"
                                            class="form-check-input"
                                        >
                                        <label class="form-check-label" for="cat_<?= $cat['category_id'] ?>">
                                            <?= htmlspecialchars($cat['category_name']) ?>
                                        </label>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                            <div class="mt-3">
                                <button type="button" class="btn btn-secondary" onclick="prevStep()">السابق</button>
                                <button type="submit" class="btn btn-success" name="register">تسجيل</button>
                            </div>
                        </div>
                    </form>
                    <div class="mt-3">
                        لديك حساب بالفعل؟ <a href="login.php">سجل دخول</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
function nextStep() {
    const pass = document.getElementById('password').value;
    const confirmPass = document.getElementById('confirm_password').value;
    
    if (pass !== confirmPass) {
        alert('كلمة المرور غير متطابقة!');
        return;
    }
    
    document.getElementById('step1').style.display = 'none';
    document.getElementById('step2').style.display = 'block';
}

function prevStep() {
    document.getElementById('step2').style.display = 'none';
    document.getElementById('step1').style.display = 'block';
}

document.getElementById('regForm').addEventListener('submit', function(e) {
    const checkboxes = document.querySelectorAll('input[name="categories[]"]:checked');
    if (checkboxes.length === 0) {
        e.preventDefault();
        alert('الرجاء اختيار تصنيف واحد على الأقل!');
    }
});
</script>

<?php include 'includes/footer.php'; ?>