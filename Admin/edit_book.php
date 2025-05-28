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

// جلب بيانات الكتاب
$book = [];
if (isset($_GET['id'])) {
    $book_id = (int)$_GET['id']; 
    $stmt = $conn->prepare("SELECT * FROM books WHERE id = ?"); 
    $stmt->bind_param("i", $book_id); 
    $stmt->execute(); 
    $result = $stmt->get_result(); 
    $book = $result->fetch_assoc();
    
    if (!$book) {
        $_SESSION['error'] = "الكتاب غير موجود";
        header("Location: manage_books.php");
        exit();
    }
} else {
    header("Location: manage_books.php");
    exit();
}
?>

<div class="container mt-5">
    <!-- عرض رسائل الخطأ أو النجاح -->
    <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
    <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <h3 class="mb-4">تعديل معلومات الكتاب</h3>

    <!-- نموذج التعديل -->
    <form action="<?= BASE_URL ?>process.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
        
        <div class="row g-3">
            <!-- العنوان والمؤلف -->
            <div class="col-md-6">
                <label class="form-label">عنوان الكتاب <span class="text-danger">*</span></label>
                <input type="text" name="title" value="<?= htmlspecialchars($book['title']) ?>" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">المؤلف <span class="text-danger">*</span></label>
                <input type="text" name="author" value="<?= htmlspecialchars($book['author']) ?>" class="form-control" required>
            </div>

            <!-- نوع المادة والتصنيف -->
            <div class="col-md-6">
                <label class="form-label">نوع المادة <span class="text-danger">*</span></label>
                <select name="material_type" class="form-select" required>
                    <option value="كتاب" <?= $book['material_type'] == 'كتاب' ? 'selected' : '' ?>>كتاب</option>
                    <option value="مجلة" <?= $book['material_type'] == 'مجلة' ? 'selected' : '' ?>>مجلة</option>
                    <option value="جريدة" <?= $book['material_type'] == 'جريدة' ? 'selected' : '' ?>>جريدة</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">التصنيف <span class="text-danger">*</span></label>
                <select name="category_id" class="form-select" required>
                    <option value="">اختر التصنيف</option>
                    <?php
                    $categories = $conn->query("SELECT * FROM categories");
                    while ($cat = $categories->fetch_assoc()):
                    ?>
                    <option value="<?= $cat['category_id'] ?>" <?= $book['category_id'] == $cat['category_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['category_name']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- الحقول الإضافية -->
            <div class="col-md-4">
                <label class="form-label">عدد الصفحات</label>
                <input type="number" name="page_count" value="<?= $book['page_count'] ?>" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">تاريخ النشر</label>
                <input type="date" name="publication_date" value="<?= $book['publication_date'] ?>" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">الرقم الدولي (ISBN)</label>
                <input type="text" name="isbn" value="<?= $book['isbn'] ?>" class="form-control">
            </div>

            <!-- الكمية والسعر -->
            <div class="col-md-4">
                <label class="form-label">الكمية المتاحة <span class="text-danger">*</span></label>
                <input type="number" name="quantity" value="<?= $book['quantity'] ?>" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">السعر (ل.س) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" name="price" value="<?= $book['price'] ?>" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">التقييم (1-5)</label>
                <input type="number" name="evaluation" value="<?= $book['evaluation'] ?>" class="form-control" min="1" max="5">
            </div>

            <!-- الخصم -->
            <div class="col-md-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="has_discount" id="hasDiscount" <?= !empty($book['discount_percentage']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="hasDiscount">هل يوجد خصم؟</label>
                </div>
                <input type="number" name="discount_percentage" class="form-control mt-2" 
                    placeholder="نسبة الخصم %" min="0" max="100" 
                    value="<?= $book['discount_percentage'] ?? '' ?>"
                    <?= empty($book['discount_percentage']) ? 'disabled' : '' ?>>
            </div>
            <div class="col-md-4">
                <label class="form-label">كتاب الشهر</label>
                <div class="form-check form-switch">
                    <input 
                        type="checkbox" 
                        name="book_of_the_month" 
                        class="form-check-input" 
                        value="1"
                        <?= isset($book['book_of_the_month']) && $book['book_of_the_month'] ? 'checked' : '' ?>
                    >
                </div>
            </div>

            <!-- الملفات -->
            <div class="col-md-4">
                <label class="form-label">صورة الغلاف <span class="text-danger">*</span></label>
                <?php if (!empty($book['cover_image'])): ?>
                <img src="<?= BASE_URL . $book['cover_image'] ?>" class="img-fluid rounded mb-2" style="max-width: 200px;">
                <?php else: ?>
                <div class="text-muted">لا توجد صورة مرفقة</div>
                <?php endif; ?>
                <input type="file" name="cover_image" class="form-control mt-2">
                <small class="text-muted">اختياري - اتركه فارغًا للحفاظ على الصورة الحالية</small>
            </div>
            <div class="col-md-4">
                <label class="form-label">ملف الكتاب (PDF)</label>
                <?php if (!empty($book['file_path'])): ?>
                <a href="<?= BASE_URL . $book['file_path'] ?>" class="btn btn-outline-primary btn-sm" target="_blank">عرض الملف</a>
                <?php else: ?>
                <div class="text-muted">لا يوجد ملف مرفق</div>
                <?php endif; ?>
                <input type="file" name="file_path" class="form-control mt-2">
                <small class="text-muted">اختياري - اتركه فارغًا للحفاظ على الملف الحالي</small>
            </div>

            <!-- الوصف -->
            <div class="col-12">
                <label class="form-label">الوصف <span class="text-danger">*</span></label>
                <textarea name="description" class="form-control" rows="4" required><?= htmlspecialchars($book['description']) ?></textarea>
            </div>

            <!-- الأزرار -->
            <div class="col-12 text-end">
                <button type="submit" name="update_book" class="btn btn-primary px-5">
                    <i class="fas fa-save me-2"></i>حفظ التعديلات
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
