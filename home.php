<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/header.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
// معالجة معلمة السنة
$selected_year = null;
if (isset($_GET['year'])) {
    $selected_year = (int)$_GET['year'];
}
// 1. معالجة معلمة الفلترة
$selected_material = null;
if (isset($_GET['material_type'])) {
    $allowed_types = ['كتاب', 'مجلة', 'جريدة'];
    $material_type = htmlspecialchars($_GET['material_type']);
    if (in_array($material_type, $allowed_types)) {
        $selected_material = $material_type;
    }
}

// 2. استعلامات الكتب مع الفلترة
function getFilteredBooks($conn, $selected_material, $selected_year) {
    $query = "SELECT b.*, c.category_name 
          FROM books b 
          LEFT JOIN categories c ON b.category_id = c.category_id 
          WHERE 1=1";
    $params = [];
    $types = "";
    
    if ($selected_material) {
        $query .= " AND material_type = ?";
        $params[] = $selected_material;
        $types .= "s";
    }
    
    if ($selected_year) {
        $query .= " AND YEAR(publication_date) = ?";
        $params[] = $selected_year;
        $types .= "i";
    }
    
    if (!empty($params)) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result();
    }
    
    return $conn->query($query);
}

// 3. جلب البيانات مع التصفية
$physical_books = getFilteredBooks($conn, $selected_material, $selected_year);

// 4. الكتب المقترحة مع الفلترة
$recommended_books = [];
if (isset($_SESSION['user_id'])) {
    // جلب التصنيفات المفضلة للمستخدم
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT category_id FROM user_categories WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if (!empty($user_categories)) {
        // بناء قائمة الـ placeholders للاستعلام
        $category_ids = array_column($user_categories, 'category_id');
        $placeholders = implode(',', array_fill(0, count($category_ids), '?'));
        
        // بناء الاستعلام الأساسي
        $query = "SELECT b.* FROM books b 
                 WHERE b.category_id IN ($placeholders)";
        
        // إضافة فلترة نوع المادة إذا كانت محددة
        if ($selected_material) {
            $query .= " AND b.material_type = ?";
        }
        
        // تحضير أنواع البيانات والمعلمات
        $types = str_repeat('i', count($category_ids));
        $params = $category_ids;
        
        if ($selected_material) {
            $types .= 's';
            $params[] = $selected_material;
        }
        
        // تنفيذ الاستعلام
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $recommended_books = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
// 5. الكتب الأعلى تقييمًا مع الفلترة
$rated_books_query = "SELECT * FROM books WHERE evaluation > 4";

if ($selected_material) {
    $rated_books_query .= " AND material_type = ?";
    $stmt = $conn->prepare($rated_books_query);
    $stmt->bind_param("s", $selected_material);
    $stmt->execute();
    $rated_books = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}elseif($selected_year) {
    $rated_books_query .= " AND YEAR(publication_date) = ?";
    $stmt = $conn->prepare($rated_books_query);
    $stmt->bind_param("i", $selected_year);
    $stmt->execute();
    $rated_books = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

}else {
    $rated_books = $conn->query($rated_books_query)->fetch_all(MYSQLI_ASSOC);
}
?>

<!-- 6. إضافة مؤشر الفلترة في الواجهة -->
<?php if ($selected_material): ?>
<div class="alert alert-info mb-4">
    <i class="fas fa-filter"></i>
    يتم عرض: <strong><?= $selected_material ?></strong>
    <a href="home.php" class="btn btn-sm btn-outline-secondary float-left">
        <i class="fas fa-times"></i> إزالة الفلترة
    </a>
</div>
<?php endif; ?>
<?php if ($selected_year): ?>
<div class="alert alert-info mb-4">
    <i class="fas fa-calendar-alt"></i>
    السنة: <strong><?= $selected_year ?></strong>
    <a href="home.php" class="btn btn-sm btn-outline-secondary float-left">
        <i class="fas fa-times"></i> إزالة الفلترة
    </a>
</div>
<?php endif; ?>

<style>
.flip-inner {
    position: relative;
    width: 60%;
    height: 100%;
    transition: transform 0.6s;
    transform-style: preserve-3d;


    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
}

.flip-card:hover .flip-inner {

    transform: rotateY(180deg);
}

.flip-front,
.flip-back {
    position: absolute;
    width: 100%;
    height: 100%;
    backface-visibility: hidden;
    border-radius: 1px;
    overflow: hidden;
    -webkit-backface-visibility: hidden;
    /* Safari */
    backface-visibility: hidden;
}

.flip-back {
    background: #000;
    color: #fff;
    padding: 15px;
    transform: rotateY(180deg);
    display: flex;
    flex-direction: column;

}

.card-actions {
    margin-top: auto;
    display: flex;
    gap: 10px;
    justify-content: center;

}

/* تنسيقات النافذة المنبثقة */
#bookDetailsModal .modal-content {
    background: #1a1a1a;
    color: #fff;
}

#bookDetailsModal img {
    max-height: 300px;
    object-fit: cover;
}

/* أنماط الـ accordion */
.accordion-header {
    cursor: pointer;
    padding: 12px 15px;
    background: rgb(171, 181, 191);
    color: white;
    border-radius: 5px;
    margin-bottom: 5px;
    font-weight: bold;
    transition: all 0.3s ease;
    width: 100%;

}

.accordion-header:not(.collapsed) {
    background-color: black;
    border-bottom: none;
    border-radius: 5px 5px 0 0;
}

.accordion-content {
    padding: 0 10px 10px 25px;
    border: 1px solid #dee2e6;
    border-top: none;
    border-radius: 0 0 5px 5px;
    display: block;
}

/* تنسيقات الفلاتر */
.search-filter-container {
    padding: 15px;
    background: #f8f9fa;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
}

.filter-group {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
}

.dropdown-toggle {
    min-width: 120px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 15px;
    border-radius: 20px !important;
}

.dropdown-menu {
    max-height: 300px;
    overflow-y: auto;
    min-width: 200px;
}

.filter-option {
    padding: 8px 15px;
    display: flex;
    align-items: center;
    transition: all 0.2s;
}

.filter-option.active {
    background-color: rgb(255, 255, 255);
    color: white !important;
}

.filter-option i {
    margin-left: 8px;
}

#searchInput {
    border-radius: 20px 0 0 20px !important;
    padding: 10px 15px;
}

.input-group button {
    border-radius: 0 20px 20px 0 !important;
    padding: 0 15px;
}

#resetFilters {
    padding: 8px 15px;
    border-radius: 20px;
}

.no-results {
    text-align: center;
    padding: 20px;
    color: #6c757d;
    font-size: 1.1rem;
}
</style>
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

<div>
    <!-- شريط البحث والفلاتر -->
    <div class="search-filter-container">
        <div class="filter-group">
            <!-- مربع البحث -->
            <div class="flex-grow-1" style="min-width: 250px;">
                <div class="input-group">
                    <input type="text" id="searchInput" class="form-control" placeholder="ابحث عن كتاب أو مؤلف..."
                        autocomplete="off">

                </div>
            </div>

            <!-- فلترة التصنيفات -->
            <div class="dropdown">
                <button class="btn btn-outline-primary dropdown-toggle" type="button" id="categoryDropdown"
                    data-bs-toggle="dropdown">
                    <i class="fas fa-tags"></i> <span class="filter-label">التصنيف</span>
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item filter-option active" href="#" data-filter-type="category"
                            data-value="all">
                            <i class="fas fa-check"></i> الكل
                        </a></li>
                    <?php
                    $categories = $conn->query("SELECT * FROM categories");
                    while($cat = $categories->fetch_assoc()):
                    ?>
                    <li><a class="dropdown-item filter-option" href="#" data-filter-type="category"
                            data-value="<?= $cat['category_id'] ?>">
                            <i class="fas fa-tag"></i> <?= $cat['category_name'] ?>
                        </a></li>
                    <?php endwhile; ?>
                </ul>
            </div>

            <!-- فلتر نوع المادة -->
            <div class="dropdown">
                <button class="btn btn-outline-primary dropdown-toggle" type="button" id="materialTypeDropdown"
                    data-bs-toggle="dropdown">
                    <i class="fas fa-book"></i>
                    <?= $selected_material ? $selected_material : 'نوع المادة' ?>
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item <?= !$selected_material ? 'active' : '' ?>" href="home.php">الكل</a>
                    </li>
                    <li><a class="dropdown-item <?= $selected_material == 'كتاب' ? 'active' : '' ?>"
                            href="home.php?material_type=كتاب">كتب</a></li>
                    <li><a class="dropdown-item <?= $selected_material == 'مجلة' ? 'active' : '' ?>"
                            href="home.php?material_type=مجلة">مجلات</a></li>
                    <li><a class="dropdown-item <?= $selected_material == 'جريدة' ? 'active' : '' ?>"
                            href="home.php?material_type=جريدة">صحف</a></li>
                </ul>
            </div>

            <!-- فلترة المؤلفين -->
            <div class="dropdown">
                <button class="btn btn-outline-primary dropdown-toggle" type="button" id="authorDropdown"
                    data-bs-toggle="dropdown">
                    <i class="fas fa-user-edit"></i> <span class="filter-label">المؤلف</span>
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item filter-option active" href="#" data-filter-type="author"
                            data-value="all">
                            <i class="fas fa-check"></i> الكل
                        </a></li>
                    <?php
                    $authors = $conn->query("SELECT DISTINCT author FROM books ORDER BY author ASC");
                    while($auth = $authors->fetch_assoc()):
                    ?>
                    <li><a class="dropdown-item filter-option" href="#" data-filter-type="author"
                            data-value="<?= htmlspecialchars($auth['author']) ?>">
                            <i class="fas fa-user"></i> <?= htmlspecialchars($auth['author']) ?>
                        </a></li>
                    <?php endwhile; ?>
                </ul>
            </div>

            <!-- فلترة السعر -->
            <div class="dropdown">
                <button class="btn btn-outline-primary dropdown-toggle" type="button" id="priceDropdown"
                    data-bs-toggle="dropdown">
                    <i class="fas fa-money-bill-wave"></i> <span class="filter-label">السعر</span>
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item filter-option active" href="#" data-filter-type="price"
                            data-value="all">
                            <i class="fas fa-check"></i> الكل
                        </a></li>
                    <li><a class="dropdown-item filter-option" href="#" data-filter-type="price" data-value="0-50">
                            <i class="fas fa-coins"></i> أقل من 50
                        </a></li>
                    <li><a class="dropdown-item filter-option" href="#" data-filter-type="price" data-value="50-100">
                            <i class="fas fa-coins"></i> 50 - 100
                        </a></li>
                    <li><a class="dropdown-item filter-option" href="#" data-filter-type="price" data-value="100-500">
                            <i class="fas fa-coins"></i> 100 - 500
                        </a></li>
                    <li><a class="dropdown-item filter-option" href="#" data-filter-type="price" data-value="500+">
                            <i class="fas fa-coins"></i> أكثر من 500
                        </a></li>
                </ul>
            </div>

            <!-- فلترة التقييم -->
            <div class="dropdown">
                <button class="btn btn-outline-primary dropdown-toggle" type="button" id="ratingDropdown"
                    data-bs-toggle="dropdown">
                    <i class="fas fa-star"></i> <span class="filter-label">التقييم</span>
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item filter-option active" href="#" data-filter-type="rating"
                            data-value="all">
                            <i class="fas fa-check"></i> الكل
                        </a></li>
                    <li><a class="dropdown-item filter-option" href="#" data-filter-type="rating" data-value="5">
                            <i class="fas fa-star"></i> 5 نجوم
                        </a></li>
                    <li><a class="dropdown-item filter-option" href="#" data-filter-type="rating" data-value="4">
                            <i class="fas fa-star"></i> 4 نجوم فأعلى
                        </a></li>
                    <li><a class="dropdown-item filter-option" href="#" data-filter-type="rating" data-value="3">
                            <i class="fas fa-star"></i> 3 نجوم فأعلى
                        </a></li>
                </ul>
            </div>

            <!-- زر إعادة التعيين -->
            <button id="resetFilters" class="btn btn-outline-danger">
                <i class="fas fa-undo"></i>
            </button>
        </div>
    </div>
    <!-- رسالة عدم وجود نتائج -->
    <div id="noResultsMessage" class="no-results" style="display: none;">
        لا توجد نتائج مطابقة لبحثك
    </div>


    <div class="accordion">
        <?php if (!empty($rated_books)): ?>
        <button class="accordion-header">الأعلى تقييماً</button>
        <div class="accordion-content">
            <div class="row g-4">
                <?php foreach ($rated_books as $book): ?>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="flip-card h-100" data-category="<?= $book['category_id'] ?>"
                        data-author="<?= htmlspecialchars($book['author']) ?>" data-price="<?= $book['price'] ?>"
                        data-rating="<?= $book['evaluation'] ?>">
                        <div class="flip-inner">
                            <!-- الوجه الأمامي -->
                            <div class="flip-front">
                                <img src="<?= BASE_URL ?><?= htmlspecialchars($book['cover_image']) ?>"
                                    alt="غلاف الكتاب">

                            </div>

                            <!-- الوجه الخلفي -->
                            <div class="flip-back">
                                <h6 class="fw-bold"><?= htmlspecialchars($book['title']) ?></h6>
                                <p class="small"><?= htmlspecialchars($book['author']) ?></p>

                                <div class="rating-stars mb-3">
                                    <?= str_repeat('★', $book['evaluation']) . str_repeat('☆', 5 - $book['evaluation']) ?>
                                </div>


                                <div class="card-actions">

                                
                                    <!-- أيقونة التفاصيل -->
                                    <button class="btn btn-info btn-sm"
                                        onclick="window.location.href='details.php?id=<?= $book['id'] ?>'">
                                        <i class="fas fa-info"></i>
                                    </button>

                                    <!--<button class="btn btn-info btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#bookDetails" onclick="loadBookDetails(
                                            '<?= addslashes($book['title']) ?>',
                                            '<?= addslashes($book['author']) ?>',
                                            '<?= addslashes($book['category_name']) ?>',
                                            <?= $book['evaluation'] ?>,
                                            <?= $book['price'] ?>,
                                            '<?= addslashes($book['description']) ?>',
                                            '<?= htmlspecialchars($book['cover_image']) ?>'
                                        )">
                                        <i class="fas fa-info"></i>
                                    </button> -->

                                    <!-- الأزرار كأيقونات -->
                                    <?php if(isset($_SESSION['user_id'])): ?>
                                    <div class="card-actions">
                                        <form method="POST" action="process.php">
                                            <input type="hidden" name="csrf_token"
                                                value="<?= $_SESSION['csrf_token'] ?>">
                                            <button type="submit" name="action" value="borrow"
                                                class="btn btn-primary btn-sm rounded-circle" title="استعارة الكتاب">
                                                <i class="fas fa-book"></i>
                                            </button>
                                            <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                                        </form>

                                        <form method="POST" action="process.php">
                                            <input type="hidden" name="csrf_token"
                                                value="<?= $_SESSION['csrf_token'] ?>">
                                            <button type="submit" name="action" value="purchase"
                                                class="btn btn-success btn-sm rounded-circle" title="شراء الكتاب">
                                                <i class="fas fa-shopping-cart"></i>
                                            </button>
                                            <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                                        </form>
                                    </div>
                                    <?php else: ?>
                                    <button class="btn btn-secondary btn-sm rounded-circle" data-bs-toggle="modal"
                                        data-bs-target="#loginModal" title="تسجيل الدخول">
                                        <i class="fas fa-sign-in-alt"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="accordion">
        <?php if (!empty($recommended_books)): ?>
        <button class="accordion-header"> المفضلة</button>
        <div class="accordion-content">
            <div class="row g-4">
                <?php foreach ($recommended_books as $book): ?>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="flip-card h-100" data-category="<?= $book['category_id'] ?>"
                        data-author="<?= htmlspecialchars($book['author']) ?>" data-price="<?= $book['price'] ?>"
                        data-rating="<?= $book['evaluation'] ?>">
                        <div class="flip-inner">
                            <!-- الوجه الأمامي -->
                            <div class="flip-front">
                                <img src="<?= BASE_URL ?><?= htmlspecialchars($book['cover_image']) ?>"
                                    alt="غلاف الكتاب">
                            </div>

                            <!-- الوجه الخلفي -->
                            <div class="flip-back">
                                <h6 class="fw-bold"><?= htmlspecialchars($book['title']) ?></h6>
                                <p class="small"><?= htmlspecialchars($book['author']) ?></p>

                                <div class="rating-stars mb-3">
                                    <?= str_repeat('★', $book['evaluation']) . str_repeat('☆', 5 - $book['evaluation']) ?>
                                </div>

                                <div class="card-actions">
                                <button class="btn btn-info btn-sm"
                                        onclick="window.location.href='details.php?id=<?= $book['id'] ?>'">
                                        <i class="fas fa-info"></i>
                                    </button>
                                    <!-- أيقونة التفاصيل 
                                    <button class="btn btn-info btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#bookDetails" onclick="loadBookDetails(
                                            '<?= addslashes($book['title']) ?>',
                                            '<?= addslashes($book['author']) ?>',
                                            '<?= addslashes($book['category_name']) ?>',
                                            <?= $book['evaluation'] ?>,
                                            <?= $book['price'] ?>,
                                            '<?= addslashes($book['description']) ?>',
                                            '<?= htmlspecialchars($book['cover_image']) ?>'
                                        )">
                                        <i class="fas fa-info"></i>
                                    </button>-->
                                    <!-- الأزرار كأيقونات -->
                                    <?php if(isset($_SESSION['user_id'])): ?>
                                    <div class="card-actions">
                                        <form method="POST" action="process.php">
                                            <input type="hidden" name="csrf_token"
                                                value="<?= $_SESSION['csrf_token'] ?>">
                                            <button type="submit" name="action" value="borrow"
                                                class="btn btn-primary btn-sm rounded-circle" title="استعارة الكتاب">
                                                <i class="fas fa-book"></i>
                                            </button>
                                            <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                                        </form>

                                        <form method="POST" action="process.php">
                                            <input type="hidden" name="csrf_token"
                                                value="<?= $_SESSION['csrf_token'] ?>">
                                            <button type="submit" name="action" value="purchase"
                                                class="btn btn-success btn-sm rounded-circle" title="شراء الكتاب">
                                                <i class="fas fa-shopping-cart"></i>
                                            </button>
                                            <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                                        </form>
                                    </div>
                                    <?php else: ?>
                                    <button class="btn btn-secondary btn-sm rounded-circle" data-bs-toggle="modal"
                                        data-bs-target="#loginModal" title="تسجيل الدخول">
                                        <i class="fas fa-sign-in-alt"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

    </div>

    <div class="accordion">
        <button class="accordion-header"> المكتبة الشاملة</button>
        <div class="accordion-content">
            <div class="row g-4">
                <?php while($book = $physical_books->fetch_assoc()): ?>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="flip-card h-100" data-category="<?= $book['category_id'] ?>"
                        data-author="<?= htmlspecialchars($book['author']) ?>" data-price="<?= $book['price'] ?>"
                        data-rating="<?= $book['evaluation'] ?>">
                        <div class="flip-inner">
                            <!-- الوجه الأمامي -->
                            <div class="flip-front">
                                <img src="<?= BASE_URL ?><?= htmlspecialchars($book['cover_image']) ?>"
                                    alt="غلاف الكتاب">
                            </div>

                            <!-- الوجه الخلفي -->
                            <div class="flip-back">
                                <h6 class="fw-bold"><?= htmlspecialchars($book['title']) ?></h6>
                                <p class="small"><?= htmlspecialchars($book['author']) ?></p>

                                <div class="rating-stars mb-3">
                                    <?= str_repeat('★', $book['evaluation']) . str_repeat('☆', 5 - $book['evaluation']) ?>
                                </div>

                                <div class="card-actions">
                                    <!-- أيقونة التفاصيل -->
                                    <button class="btn btn-info btn-sm"
                                        onclick="window.location.href='details.php?id=<?= $book['id'] ?>'">
                                        <i class="fas fa-info"></i>
                                    </button>
                                    
                                    <!-- الأزرار كأيقونات -->
                                    <?php if(isset($_SESSION['user_id'])): ?>
                                    <div class="card-actions">
                                        <form method="POST" action="process.php">
                                            <input type="hidden" name="csrf_token"
                                                value="<?= $_SESSION['csrf_token'] ?>">
                                            <button type="submit" name="action" value="borrow"
                                                class="btn btn-primary btn-sm rounded-circle" title="استعارة الكتاب">
                                                <i class="fas fa-book"></i>
                                            </button>
                                            <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                                        </form>

                                        <form method="POST" action="process.php">
                                            <input type="hidden" name="csrf_token"
                                                value="<?= $_SESSION['csrf_token'] ?>">
                                            <button type="submit" name="action" value="purchase"
                                                class="btn btn-success btn-sm rounded-circle" title="شراء الكتاب">
                                                <i class="fas fa-shopping-cart"></i>
                                            </button>
                                            <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                                        </form>
                                    </div>
                                    <?php else: ?>
                                    <button class="btn btn-secondary btn-sm rounded-circle" data-bs-toggle="modal"
                                        data-bs-target="#loginModal" title="تسجيل الدخول">
                                        <i class="fas fa-sign-in-alt"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>


    <script>
    // دالة تحميل المراجعات
    function loadBookReviews(bookId) {
        if (!bookId) return;

        // إظهار حالة التحميل
        $('#reviewsContent').html(`
        <div class="text-center py-4">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p>جاري تحميل المراجعات...</p>
        </div>
    `);

        // جلب البيانات
        $.ajax({
            url: 'get_reviews.php',
            type: 'GET',
            data: {
                book_id: bookId
            },
            success: function(response) {
                $('#reviewsContent').html(response);
                $('#reviewsModal').modal('show');
            },
            error: function() {
                $('#reviewsContent').html(`
                <div class="alert alert-danger">
                    فشل في تحميل المراجعات. يرجى المحاولة لاحقاً.
                </div>
            `);
            }
        });
    }
    </script>
</div>
</div>
</div>
</div>


<!-- النوافذ المنبثقة -->
<script>
// كائن لحفظ حالة الفلاتر
const activeFilters = {
    category: 'all',
    author: 'all',
    price: 'all',
    rating: 'all'
};

function applyFilters() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    let hasVisibleBooks = false;

    document.getElementById('noResultsMessage').style.display = 'none';

    // تطبيق الفلاتر على جميع أقسام الكتب بغض النظر عن وجود محتوى
    document.querySelectorAll('.accordion').forEach(accordion => {
        let sectionHasVisibleBooks = false;
        const content = accordion.querySelector('.accordion-content');
        const header = accordion.querySelector('.accordion-header');

        // إذا لم يكن هناك محتوى في القسم، نعتبره مطابقاً للفلاتر
        if (!content || content.children.length === 0) {
            return; // نستمر إلى القسم التالي
        }

        accordion.querySelectorAll('.flip-card').forEach(card => {
            const title = card.querySelector('h6')?.textContent?.toLowerCase() || '';
            const author = (card.dataset.author || '').toLowerCase();
            const category = card.dataset.category || 'all';
            const price = parseFloat(card.dataset.price) || 0;
            const rating = parseFloat(card.dataset.rating) || 0;

            const matchesSearch = title.includes(searchTerm) || author.includes(searchTerm) ||
                searchTerm === '';
            const matchesCategory = activeFilters.category === 'all' || category === activeFilters
                .category;
            const matchesAuthor = activeFilters.author === 'all' || card.dataset.author ===
                activeFilters.author;
            const matchesPrice = checkPriceFilter(price);
            const matchesRating = checkRatingFilter(rating);

            if (matchesSearch && matchesCategory && matchesAuthor && matchesPrice && matchesRating) {
                card.style.display = 'block';
                sectionHasVisibleBooks = true;
                hasVisibleBooks = true;
            } else {
                card.style.display = 'none';
            }
        });

        // إظهار/إخفاء قسم الـ Accordion
        if (sectionHasVisibleBooks) {
            content.style.display = 'block';
            header.classList.remove('collapsed');
        } else {
            content.style.display = 'none';
            header.classList.add('collapsed');
        }
    });

    if (!hasVisibleBooks) {
        document.getElementById('noResultsMessage').style.display = 'block';
    }
}
// دوال مساعدة للفلاتر
function checkPriceFilter(price) {
    if (activeFilters.price === 'all') return true;

    const [min, max] = activeFilters.price.split('-');
    if (max === '+') return price >= parseInt(min);
    if (!max) return price <= parseInt(min);

    return price >= parseInt(min) && price <= parseInt(max);
}

function checkRatingFilter(rating) {
    if (activeFilters.rating === 'all') return true;
    return rating >= parseInt(activeFilters.rating);
}

// تحديث واجهة الفلاتر
function updateActiveFiltersUI() {
    document.querySelectorAll('.filter-option').forEach(option => {
        option.classList.remove('active');
        const filterType = option.dataset.filterType;
        const filterValue = option.dataset.value;

        if (activeFilters[filterType] === filterValue) {
            option.classList.add('active');

            // تحديث نص الزر الرئيسي مع الاحتفاظ بكلمة "الكل"
            const dropdownButton = document.getElementById(`${filterType}Dropdown`);
            if (dropdownButton) {
                const icon = dropdownButton.querySelector('i').outerHTML;
                let label = option.textContent.trim();

                // إذا كانت القيمة "all" نستخدم الكلمة الأصلية
                if (filterValue === 'all') {
                    label = filterType === 'category' ? 'التصنيف' :
                        filterType === 'author' ? 'المؤلف' :
                        filterType === 'price' ? 'السعر' : 'التقييم';
                }

                dropdownButton.innerHTML = `${icon} <span class="filter-label">${label}</span>`;
            }
        }
    });
}

// أحداث الفلاتر
document.querySelectorAll('.filter-option').forEach(option => {
    option.addEventListener('click', function(e) {
        e.preventDefault();
        const filterType = this.dataset.filterType;
        const filterValue = this.dataset.value;

        activeFilters[filterType] = filterValue;
        applyFilters();
    });
});

// حدث البحث
document.getElementById('searchInput').addEventListener('input', applyFilters);

// إعادة تعيين الفلاتر
document.getElementById('resetFilters').addEventListener('click', function() {
    document.getElementById('searchInput').value = '';
    activeFilters.category = 'all';
    activeFilters.author = 'all';
    activeFilters.price = 'all';
    activeFilters.rating = 'all';

    // إعادة تعيين نص الأزرار
    document.querySelectorAll('.dropdown-toggle').forEach(btn => {
        const icon = btn.querySelector('i').outerHTML;
        const label = btn.id === 'categoryDropdown' ? 'التصنيف' :
            btn.id === 'authorDropdown' ? 'المؤلف' :
            btn.id === 'priceDropdown' ? 'السعر' : 'التقييم';
        btn.innerHTML = `${icon} <span class="filter-label">${label}</span>`;
    });

    applyFilters();
});

// تهيئة الفلاتر عند التحميل
document.addEventListener('DOMContentLoaded', function() {
    // إعادة تعيين جميع الفلاتر
    resetAllFilters();

    // تطبيق الفلاتر الأولي
    setTimeout(applyFilters, 100); // تأخير بسيط لضمان تحميل جميع العناصر
});

function resetAllFilters() {
    document.getElementById('searchInput').value = '';
    activeFilters.category = 'all';
    activeFilters.author = 'all';
    activeFilters.price = 'all';
    activeFilters.rating = 'all';
    updateActiveFiltersUI();
}

// دالة عرض تفاصيل الكتاب
function loadBookDetails(title, author, category, rating, price, desc, cover) {
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalAuthor').textContent = author;
    document.getElementById('modalCategory').textContent = category;
    document.getElementById('modalPrice').textContent = price;
    document.getElementById('modalDesc').textContent = desc;
    document.getElementById('modalCover').src = "<?= BASE_URL ?>" + cover;

    const stars = '★'.repeat(rating) + '☆'.repeat(5 - rating);
    document.getElementById('modalRating').innerHTML = stars;
}
</script>


<?php require __DIR__ . '/includes/footer.php'; ?>