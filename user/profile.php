<?php
require __DIR__ . '/../includes/config.php';

// التحقق من تسجيل الدخول والصالحيات
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'user') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = $success = '';

// ... (الكود PHP الخاص بمعالجة النموذج يبقى كما هو بدون تغيير) ...

// جلب البيانات الحالية
$sql = "SELECT * FROM users WHERE id = $user_id";
$result = $conn->query($sql);
$user = $result->fetch_assoc();

?>

<div class="container mt-4">
    <div class="card border-0 shadow-sm">
        <!-- هيدر البطاقة مع التدرج اللوني -->
        <div class="card-header bg-gradient-info text-white py-3">
            <h5 class="mb-0 fw-bold">
                <i class="fas fa-user-edit me-2"></i> الملف الشخصي
            </h5>
        </div>
        
        <div class="card-body p-4">
            <!-- رسائل التنبيه المنسقة -->
            <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center mb-4">
                <i class="fas fa-exclamation-circle me-3 fa-lg"></i>
                <div>
                    <h5 class="alert-heading mb-1">خطأ!</h5>
                    <p class="mb-0"><?php echo $error; ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success d-flex align-items-center mb-4">
                <i class="fas fa-check-circle me-3 fa-lg"></i>
                <div>
                    <h5 class="alert-heading mb-1">تم التحديث!</h5>
                    <p class="mb-0"><?php echo $success; ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- نموذج التعديل المحسّن -->
            <form method="POST" action="">
                <!-- حقل الاسم -->
                <div class="mb-4">
                    <label class="form-label text-muted mb-2">الاسم الكامل</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light">
                            <i class="fas fa-user-tag text-info"></i>
                        </span>
                        <input 
                            type="text" 
                            name="name" 
                            class="form-control border-start-0 ps-3" 
                            value="<?php echo htmlspecialchars($user['name']); ?>" 
                            required
                        >
                    </div>
                </div>
                
                <!-- حقل البريد الإلكتروني -->
                <div class="mb-4">
                    <label class="form-label text-muted mb-2">البريد الإلكتروني</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light">
                            <i class="fas fa-at text-info"></i>
                        </span>
                        <input 
                            type="email" 
                            name="email" 
                            class="form-control border-start-0 ps-3" 
                            value="<?php echo htmlspecialchars($user['email']); ?>" 
                            required
                        >
                    </div>
                </div>
                
                <!-- حقل كلمة المرور مع خاصية الإظافة -->
                <div class="mb-4">
                    <label class="form-label text-muted mb-2">كلمة المرور الجديدة</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light">
                            <i class="fas fa-lock text-info"></i>
                        </span>
                        <input 
                            type="password" 
                            name="password" 
                            id="password" 
                            class="form-control border-start-0 ps-3" 
                            placeholder="اتركه فارغاً للحفاظ على كلمة المرور الحالية"
                        >
                        <button 
                            type="button" 
                            class="btn btn-outline-secondary" 
                            onclick="togglePasswordVisibility()"
                        >
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                    <small class="text-muted">8 أحرف على الأقل مع رموز وأرقام</small>
                </div>
                
                <!-- زر الحفظ مع الأيقونة -->
                <div class="d-grid gap-2 mt-4">
                    <button 
                        type="submit" 
                        name="update_profile" 
                        class="btn btn-info btn-lg rounded-pill text-white"
                    >
                        <i class="fas fa-save me-2"></i> حفظ التعديلات
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- الأنماط المخصصة -->
<style>
.card {
    border-radius: 12px;
    overflow: hidden;
    transition: transform 0.3s ease;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}

.bg-gradient-info {
    background: linear-gradient(135deg, #17ead9 0%, #6078ea 100%);
}

.input-group-text {
    border-right: none;
    background-color: #f8f9fa !important;
}

.form-control {
    border-left: none;
    padding-left: 0.75rem;
}

.form-control:focus {
    box-shadow: none;
    border-color: #ced4da;
}

.btn-info {
    background-color: #17a2b8;
    border-color: #17a2b8;
}

.btn-info:hover {
    background-color: #138496;
    border-color: #117a8b;
}

.alert {
    border-radius: 8px;
    border: none;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}
</style>

<!-- سكربت إظافة/إخفاء كلمة المرور -->
<script>
function togglePasswordVisibility() {
    const passwordInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        eyeIcon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>