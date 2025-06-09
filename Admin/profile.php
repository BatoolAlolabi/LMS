<?php
ob_start(); // إضافة تخزين مؤقت للإخراج
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    
    $check_sql = "SELECT id FROM users WHERE email = '$email' AND id != $user_id";
    $check_result = $conn->query($check_sql);
    
    if ($check_result->num_rows > 0) {
        $error = "البريد الإلكتروني مسجل مسبقاً";
    } else {
        $password_update = '';
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $password_update = ", password = '$hashed_password'";
        }
        
        $sql = "UPDATE users 
                SET name = '$name', email = '$email' $password_update 
                WHERE id = $user_id";
        
        if ($conn->query($sql) ){
            $success = "تم تحديث البيانات بنجاح";
            $_SESSION['user_name'] = $name;
        } else {
            $error = "خطأ في التحديث: " . $conn->error;
        }
        header("Location: dashboard.php?section=operations");
ob_end_flush(); // إرسال المحتوى وتنظيف المخزن المؤقت
exit();
       
    }
    
}

$sql = "SELECT * FROM users WHERE id = $user_id";
$result = $conn->query($sql);
$user = $result->fetch_assoc();
?>

<div class="container mt-4">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-gradient-primary text-white py-3">
            <h5 class="mb-0 fw-bold">
                <i class="fas fa-user-cog me-2"></i> تحديث الملف الشخصي
            </h5>
        </div>
        
        <div class="card-body p-4">
            <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center">
                <i class="fas fa-exclamation-circle me-3 fa-lg"></i>
                <div>
                    <h5 class="alert-heading mb-1">خطأ!</h5>
                    <p class="mb-0"><?php echo $error; ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success d-flex align-items-center">
                <i class="fas fa-check-circle me-3 fa-lg"></i>
                <div>
                    <h5 class="alert-heading mb-1">نجاح!</h5>
                    <p class="mb-0"><?php echo $success; ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-4">
                    <label class="form-label text-muted mb-2">اسم المستخدم</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light">
                            <i class="fas fa-user text-primary"></i>
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
                
                <div class="mb-4">
                    <label class="form-label text-muted mb-2">البريد الإلكتروني</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light">
                            <i class="fas fa-envelope text-primary"></i>
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
                
                <div class="mb-4">
                    <label class="form-label text-muted mb-2">كلمة المرور الجديدة</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light">
                            <i class="fas fa-lock text-primary"></i>
                        </span>
                        <input 
                            type="password" 
                            name="password" 
                            id="password" 
                            class="form-control border-start-0 ps-3"
                            placeholder="اتركه فارغاً إذا لم ترغب في التغيير"
                            autocomplete="new-password"
                        >
                        <button 
                            type="button" 
                            class="btn btn-outline-secondary" 
                            onclick="togglePasswordVisibility()"
                        >
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                    <small class="text-muted">يجب أن تحتوي على 8 أحرف على الأقل</small>
                </div>
                
                <div class="d-grid gap-2 mt-4">
                    <button 
                        type="submit" 
                        name="update_profile" 
                        class="btn btn-primary btn-lg rounded-pill"
                    >
                        <i class="fas fa-save me-2"></i> حفظ التغييرات
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- الأنماط الإضافية -->
<style>
.card {
    border-radius: 12px;
    overflow: hidden;
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.input-group-text {
    border-right: none;
}

.form-control {
    border-left: none;
    padding-left: 0;
}

.form-control:focus {
    box-shadow: none;
    border-color: #ced4da;
}

.btn-outline-secondary {
    border-color: #ced4da;
}

.btn-outline-secondary:hover {
    background-color: #f8f9fa;
}

.alert {
    border-radius: 8px;
    border: none;
}
</style>

<script>
function togglePasswordVisibility() {
    const passwordInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.classList.remove('fa-eye');
        eyeIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        eyeIcon.classList.remove('fa-eye-slash');
        eyeIcon.classList.add('fa-eye');
    }
}
</script>