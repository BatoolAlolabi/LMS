<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require __DIR__ . '/../includes/config.php';

// التحقق من تسجيل الدخول

// ✅ التصحيح (تحقق من user_id أولاً)
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['user_type'] != 'user') {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

// جلب التصنيفات مع عدد الكتب
$categories = $conn->query("
    SELECT 
        c.category_id,
        c.category_name,
        COUNT(b.id) AS books_count 
    FROM categories c
    LEFT JOIN books b ON c.category_id = b.category_id
    GROUP BY c.category_id
");

// جلب تصنيفات المستخدم المختارة
$user_categories = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT category_id FROM user_categories WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $user_categories[] = $row['category_id'];
    }
}
?>
<div class="container mt-4">
    <!-- بطاقة التصنيفات المفضلة -->
    <div class="card border-0 shadow-lg mb-4">
        <div class="card-header bg-gradient-primary text-white py-3">
            <h3 class="mb-0 fw-bold">
                <i class="fas fa-tags me-2"></i> التصنيفات المفضلة
            </h3>
            <p class="mb-0 small">اختر ما يهمك من التخصصات لتحسين تجربة التصفح</p>
        </div>

        <form method="POST" action="save_categories.php" class="card-body">
            <div class="row g-4">
                <?php while ($cat = $categories->fetch_assoc()): ?>
                <div class="col-md-4">
                    <div class="category-card">
                        <input 
                            type="checkbox" 
                            name="categories[]" 
                            value="<?= $cat['category_id'] ?>" 
                            id="cat-<?= $cat['category_id'] ?>"
                            <?= in_array($cat['category_id'], $user_categories) ? 'checked' : '' ?>
                            class="form-check-input visually-hidden"
                        >
                        <label 
                            for="cat-<?= $cat['category_id'] ?>" 
                            class="d-block p-3 rounded-3 border bg-hover-light"
                        >
                            <div class="d-flex align-items-center">
                               
                                <div>
                                    <h6 class="mb-0 fw-bold"><?= htmlspecialchars($cat['category_name']) ?></h6>
                                    <small class="text-muted"><?= ($cat['books_count'] ?? 0) ?> كتاب متاح</small>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>

            <div class="text-center mt-5">
                <button type="submit" class="btn btn-lg btn-primary rounded-pill px-5 py-2">
                    <i class="fas fa-save me-2"></i> حفظ التفضيلات
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.category-card {
    position: relative;
    transition: transform 0.2s;
}

.category-card:hover {
    transform: translateY(-3px);
}

.form-check-input:checked + label {
    background: #e3f2fd !important;
    border-color: #2196F3 !important;
    box-shadow: 0 4px 15px rgba(33, 150, 243, 0.2);
}

.bg-hover-light {
    transition: all 0.3s ease;
    cursor: pointer;
    border: 2px solid #dee2e6;
}

.bg-hover-light:hover {
    background: #f8f9fa;
}

.visually-hidden {
    position: absolute;
    clip: rect(0 0 0 0);
    width: 1px;
    height: 1px;
    margin: -1px;
}
</style>