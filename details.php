<?php
// details.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();}

    error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);  

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/header.php';

if (!isset($_GET['id'])) {
    header("Location: home.php");
    exit();
}

$book_id = (int)$_GET['id'];

// جلب بيانات الكتاب
$stmt = $conn->prepare("SELECT 
    b.id,
    b.title,
    b.author,
    b.description,
    b.price,
    b.cover_image,
    b.evaluation,
    c.category_name,
    COALESCE(AVG(r.rating), 0) AS avg_rating,
    COUNT(r.id) AS total_reviews
FROM books b
LEFT JOIN categories c 
    ON b.category_id = c.category_id
LEFT JOIN book_ratings r 
    ON b.id = r.book_id
WHERE b.id = ?
GROUP BY b.id");

if (!$stmt) {
    die("خطأ في إعداد الاستعلام: " . $conn->error);
}

$stmt->bind_param("i", $book_id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
if (!$book || !isset($book['category_name'])) {
    $_SESSION['error'] = "الكتاب غير موجود أو التصنيف غير متوفر";
    header("Location: home.php");
    exit();
}

// جلب المراجعات (بعد التعديل)
$reviews = $conn->prepare("SELECT 
    r.rating,
    r.comment,
    r.created_at,
    u.name
FROM book_ratings r
LEFT JOIN users u
    ON r.user_id = u.id
WHERE r.book_id = ?");
$reviews->bind_param("i", $book_id);
$reviews->execute();
$reviews_result = $reviews->get_result();
?>

<style>
.book-details-container {
    background: #fff;
    padding: 2rem;
    border-radius: 10px;
    box-shadow: 0 0 15px rgba(0,0,0,0.1);
}
.review-card {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
}
.review-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}
</style>

<div class="container mt-5">
    <!-- العناصر على اليمين في مجموعة واحدة -->
    <div class="d-flex align-items-center gap-2">
                <!-- زر تمت القراءة -->
                <a href="home.php" class="btn btn-secondary btn-sm">العودة </a>


            </div>
    <div class="book-details-container">
        <div class="row">
            <div class="col-md-4">
                <img src="<?= BASE_URL . htmlspecialchars($book['cover_image']) ?>" 
                     class="img-fluid rounded" 
                     alt="غلاف الكتاب">
            </div>
            <div class="col-md-8">
                <h1 class="mb-3"><?= htmlspecialchars($book['title']) ?></h1>
                <p class="lead">المؤلف: <?= htmlspecialchars($book['author']) ?></p>
                <div class="mb-4">
                    <p class="badge bg-warning"><?= htmlspecialchars($book['category_name']) ?></p>
                    <p class="ms-2">التقييم: 
                        <?= str_repeat('★', $book['evaluation']) . str_repeat('☆', 5 - $book['evaluation']) ?>
</p>
                    <p class="ms-2">السعر: <?= $book['price'] ?> ل.س</p>
                </div>
                <p class="text-muted">موجز عن الكتاب: </p>
                <p><?= htmlspecialchars($book['description']) ?></p>
                
                <!-- أزرار الإجراءات -->
                <div class="mt-4">
                    <?php if(isset($_SESSION['user_id'])): ?>
                    <form method="POST" action="process.php" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                        <button type="submit" name="action" value="borrow" class="btn btn-primary">
                            <i class="fas fa-book"></i> استعارة
                        </button>
                    </form>
                    <form method="POST" action="process.php" class="d-inline ms-2">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                        <button type="submit" name="action" value="purchase" class="btn btn-success">
                            <i class="fas fa-shopping-cart"></i> شراء
                        </button>
                    </form>
                    <?php else: ?>
                    <a href="login.php" class="btn btn-secondary">
                        <i class="fas fa-sign-in-alt"></i> سجل الدخول                     </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- قسم المراجعات -->
        <div class="mt-5">
            <h3>مراجعات القراء (<?= $reviews_result->num_rows ?>)</h3>
            
            <?php if($reviews_result->num_rows > 0): ?>
                <?php while($review = $reviews_result->fetch_assoc()): ?>
                <div class="review-card">
                    <div class="review-header">
                        <div>
                            <strong><?= htmlspecialchars($review['name'] ?? 'مستخدم مجهول') ?></strong>
                            <span class="text-warning">
                                <?= str_repeat('★', $review['rating']) ?>
                            </span>
                        </div>
                        <small class="text-muted">
                            <?= date('Y-m-d', strtotime($review['created_at'])) ?>
                        </small>
                    </div>
                    <p class="mb-0"><?= htmlspecialchars($review['comment'] ?? 'بدون تعليق') ?></p>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-info">لا توجد مراجعات حتى الآن</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>