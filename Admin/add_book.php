<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: " . BASE_URL . "login.php");
    exit();
}

// جلب التصنيفات لعرضها في القائمة المنسدلة
$categories = $conn->query("SELECT * FROM categories");
?>

<div class="container mt-5">
    <h3 class="mb-4">إضافة كتاب جديد</h3>

    <!-- نموذج الإضافة -->
    <form action="<?= BASE_URL ?>process.php" method="POST" enctype="multipart/form-data">
        <div class="row g-3">
            <!-- العنوان والمؤلف -->
            <div class="col-md-6">
                <label class="form-label">عنوان الكتاب <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">المؤلف <span class="text-danger">*</span></label>
                <input type="text" name="author" class="form-control" required>
            </div>

            <!-- نوع المادة والتصنيف -->
            <div class="col-md-4">
                <label class="form-label">نوع المادة <span class="text-danger">*</span></label>
                <select name="material_type" class="form-select" required>
                    <option value="كتاب">كتاب</option>
                    <option value="مجلة">مجلة</option>
                    <option value="جريدة">جريدة</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">التصنيف <span class="text-danger">*</span></label>
                <select name="category_id" class="form-select" required>
                    <option value="">اختر التصنيف</option>
                    <?php while ($cat = $categories->fetch_assoc()): ?>
                    <option value="<?= $cat['category_id'] ?>">
                        <?= htmlspecialchars($cat['category_name']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- الكمية والسعر -->
            <div class="col-md-4">
                <label class="form-label">الكمية <span class="text-danger">*</span></label>
                <input type="number" name="quantity" class="form-control" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">السعر (ل.س) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" name="price" class="form-control" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">عدد الصفحات</label>
                <input type="number" name="page_count" class="form-control">
            </div>

            <div class="col-md-4">
                <label class="form-label">تاريخ النشر</label>
                <input type="date" name="publication_date" class="form-control">
            </div>

            <div class="col-md-4">
                <label class="form-label">الرقم الدولي (ISBN)</label>
                <input type="text" name="isbn" class="form-control">
            </div>

            <div class="col-md-4">
                <label class="form-label">التقييم (1-5)</label>
                <input type="number" name="evaluation" class="form-control" min="1" max="5">
            </div>

            <!-- الخصم -->
            <div class="col-md-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="has_discount" id="hasDiscount">
                    <label class="form-check-label" for="hasDiscount">هل يوجد خصم؟</label>
                </div>
                <input type="number" name="discount_percentage" class="form-control mt-2" 
                       placeholder="نسبة الخصم %" min="0" max="100" disabled>
            </div>

            <!-- الملفات -->
            <div class="col-md-4">
                <label class="form-label">صورة الغلاف <span class="text-danger">*</span></label>
                <input type="file" name="cover_image" class="form-control" required accept="image/*">
            </div>

            <div class="col-md-4">
                <label class="form-label">ملف الكتاب (PDF)</label>
                <input type="file" name="file_path" class="form-control" accept=".pdf">
            </div>

            <!-- الوصف -->
            <div class="col-12">
                <label class="form-label">الوصف <span class="text-danger">*</span></label>
                <textarea name="description" class="form-control" rows="4" required></textarea>
            </div>

            <!-- الأزرار -->
            <div class="col-12 text-end">
                <button type="submit" name="add_book" class="btn btn-primary px-5">
                    <i class="fas fa-save me-2"></i>حفظ الكتاب
                </button>
                <a href="manage_books.php" class="btn btn-outline-secondary px-5">إلغاء</a>
            </div>
        </div>
    </form>
</div>

<script>
// تفعيل/تعطيل حقل الخصم
document.getElementById('hasDiscount').addEventListener('change', function() {
    const discountField = document.querySelector('input[name="discount_percentage"]');
    discountField.disabled = !this.checked;
    if (!this.checked) discountField.value = '';
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>