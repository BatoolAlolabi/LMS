<?php
// book_of_the_month.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/header.php';

// جلب كتاب الشهر الحالي
$book_of_month_query = $conn->query("
    SELECT 
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
    WHERE b.book_of_the_month = 1
    GROUP BY b.id
    LIMIT 1
");

if (!$book_of_month_query || $book_of_month_query->num_rows === 0) {
    echo '<div class="container mt-5">
        <div class="alert alert-warning">لا يوجد كتاب محدد ككتاب الشهر حالياً.</div>
    </div>';
    require __DIR__ . '/includes/footer.php';
    exit();
}

$book = $book_of_month_query->fetch_assoc();
$book_id = $book['id'];

// جلب بيانات المفضلة
$favorites = [];
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $fav_result = $conn->query("SELECT book_id FROM favorite_books WHERE user_id = $user_id");
    while ($row = $fav_result->fetch_assoc()) {
        $favorites[] = $row['book_id'];
    }
}

// جلب كتب أخرى لنفس الكاتب
$author = $book['author'];
$author_books_query = $conn->prepare("
    SELECT DISTINCT b.id, b.title, b.author, b.cover_image, b.price 
    FROM books b
    WHERE b.author = ? 
    AND b.id != ?
    GROUP BY b.title, b.author 
    LIMIT 10
");
$author_books_query->bind_param("si", $author, $book_id);
$author_books_query->execute();
$author_books_result = $author_books_query->get_result();

// جلب المراجعات
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
/* إضافة تنسيق خاص لشارة كتاب الشهر */
.book-of-month-badge {
    font-size: 1.2rem;
    vertical-align: middle;
    background: linear-gradient(45deg, #ff6b6b, #ff8e8e);
    border-radius: 20px;
    padding: 5px 15px;
}
</style>

<div class="container mt-5">
    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="home.php" class="btn btn-secondary btn-sm">العودة</a>
    </div>
    
    <div class="book-details-container">
        <div class="row">
            <div class="col-md-4">
                <img src="<?= BASE_URL . htmlspecialchars($book['cover_image']) ?>" 
                     class="img-fluid rounded shadow-lg" 
                     alt="غلاف الكتاب">
            </div>
            <div class="col-md-8">
                <h1 class="mb-3">
                    <?= htmlspecialchars($book['title']) ?>
                    <span class="book-of-month-badge">كتاب الشهر ★</span>
                </h1>
                <p class="lead">المؤلف: <?= htmlspecialchars($book['author']) ?></p>
                <div class="mb-4">
                    <p class="badge bg-warning"><?= htmlspecialchars($book['category_name']) ?></p>
                    <p class="ms-2">التقييم:
                        <?= str_repeat('★', $book['evaluation']) . str_repeat('☆', 5 - $book['evaluation']) ?>
                    </p>
                    <p class="ms-2">السعر: <?= number_format($book['price'], 2) ?> ل.س</p>
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
                    <button class="btn btn-success btn-sm add-to-cart" 
                            data-book-id="<?= $book['id'] ?>"
                            data-book-title="<?= htmlspecialchars($book['title']) ?>"
                            data-book-price="<?= $book['price'] ?>"
                            data-book-image="<?= $book['cover_image'] ?>">
                        <i class="fas fa-cart-plus"></i> شراء
                    </button>
                    <?php else: ?>
                    <a href="login.php" class="btn btn-secondary">
                        <i class="fas fa-sign-in-alt"></i> سجل الدخول 
                    </a>
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

        <!-- كتب أخرى لنفس الكاتب -->
        <?php if ($author_books_result->num_rows > 0): ?>
            <div class="mt-5">
                <h3>كتب أخرى لـ <?= htmlspecialchars($author) ?></h3>
                <div class="owl-carousel owl-theme">
                    <?php while($author_book = $author_books_result->fetch_assoc()): 
                        $is_favorite = in_array($author_book['id'], $favorites);
                    ?>
                    <div class="item">
                        <div class="card h-100 shadow">
                            <?php if(!empty($author_book['cover_image'])): ?>
                            <img src="<?= BASE_URL . $author_book['cover_image'] ?>" 
                                 class="card-img-top" 
                                 alt="غلاف الكتاب"
                                 style="height: 250px; object-fit: cover;">
                            <?php endif; ?>
                            <div class="card-body">
                                <h6 class="card-title"><?= htmlspecialchars($author_book['title']) ?></h6>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-success"><?= number_format($author_book['price'], 2) ?> ل.س</span>
                                </div>
                                <div class="d-flex justify-content-between mt-2">
                                    <button class="btn btn-info btn-sm"
                                            onclick="window.location.href='details.php?id=<?= $author_book['id'] ?>'">
                                        <i class="fas fa-info"></i>
                                    </button>
                                    <button class="btn btn-sm <?= $is_favorite ? 'btn-danger' : 'btn-outline-danger' ?> toggle-favorite"
                                            data-book-id="<?= $author_book['id'] ?>">
                                        <i class="fas fa-heart"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function(){
    var $carousel = $('.owl-carousel');
    var itemCount = $carousel.find('.item').length;
    var showNav = itemCount > 3; 

    $carousel.owlCarousel({
        rtl: true,
        loop: false,
        margin: 15,
        nav: showNav,
        responsive: {
            0: { items: 1 },
            600: { items: 2 },
            1000: { items: 3 }
        },
        navText: [
            '<i class="fas fa-chevron-right"></i>',
            '<i class="fas fa-chevron-left"></i>'
        ]
    });
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>