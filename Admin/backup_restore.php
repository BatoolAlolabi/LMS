<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require __DIR__ . '/../includes/config.php';
?>

<div class="container mt-4">
    <div class="card border-0 shadow-sm">
        <!-- رأس البطاقة -->
        <div class="card-header bg-gradient-primary text-white py-3">
            <h5 class="mb-0 fw-bold">
                <i class="fas fa-database me-2"></i> النسخ الاحتياطي والاسترجاع
            </h5>
        </div>

        <div class="card-body p-4">
            <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success d-flex align-items-center mb-4">
                <i class="fas fa-check-circle me-3 fa-lg"></i>
                <div>
                    <h5 class="alert-heading mb-1">نجاح!</h5>
                    <p class="mb-0"><?= $_SESSION['success'] ?></p>
                </div>
            </div>
            <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger d-flex align-items-center mb-4">
                <i class="fas fa-exclamation-circle me-3 fa-lg"></i>
                <div>
                    <h5 class="alert-heading mb-1">خطأ!</h5>
                    <p class="mb-0"><?= $_SESSION['error'] ?></p>
                </div>
            </div>
            <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- نموذج النسخ الاحتياطي -->
            <form method="post" class="mb-5">
                <h4 class="text-muted mb-4">
                    <i class="fas fa-save me-2"></i> إنشاء نسخة احتياطية
                </h4>
                <div class="mb-4">
                    <label class="form-label text-muted mb-2">اختر نوع النسخة:</label>
                    <div class="input-group">
                        <select name="backup_type" class="form-select">
                            <option value="database">قاعدة البيانات</option>
                            <option value="files">الملفات</option>
                        </select>
                        <button type="submit" name="backup" class="btn btn-primary rounded-pill">
                            <i class="fas fa-download me-2"></i> إنشاء
                        </button>
                    </div>
                </div>
            </form>

            <!-- نموذج الاسترجاع -->
            <form method="post" enctype="multipart/form-data">
                <h4 class="text-muted mb-4">
                    <i class="fas fa-upload me-2"></i> استرجاع نسخة
                </h4>
                <div class="mb-4">
                    <label class="form-label text-muted mb-2">اختر ملف النسخة:</label>
                    <div class="input-group">
                        <input type="file" name="restore_file" class="form-control" required>
                        <button type="submit" name="restore" class="btn btn-warning rounded-pill">
                            <i class="fas fa-upload me-2"></i> استرجاع
                        </button>
                    </div>
                    <small class="text-muted">الملفات المسموحة: .sql, .zip</small>
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
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.form-select,
.form-control {
    border-radius: 8px !important;
}

.btn-rounded {
    border-radius: 50px;
}

.alert {
    border-radius: 8px;
    border: none;
}
</style>