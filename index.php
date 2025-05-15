<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/header.php';
?>
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    text-decoration:none;
}

/* الشريط الأخباري */
.news-ticker {
    background: #f8f9fa;
    padding: 10px 0;
    border-bottom: 2px solid #dee2e6;
    margin-bottom: 30px;
}

marquee {
    font-size: 1.2em;
    color: #333;
    padding: 0 15px;
}

/* الحاوية الرئيسية */
.main-container {
    max-width: 1200px;
    margin: 0 auto 30px;
    padding: 0 15px;
    display: flex;
    gap: 20px;
}

/* قسم السلايدر */
.slideshow-container {
    flex: 0 0 70%;
    height: 400px;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.mySlides {
    display: none;
    width: 100%;
    height: 100%;
    opacity: 0;
    transition: opacity 1s ease-in-out;
}

.mySlides.active {
    display: block;
    opacity: 1;
}

.mySlides img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* البطاقات الجانبية */
.side-cards {
    flex: 0 0 28%;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.side-card {
    height: 190px;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s;
}

.side-card:hover {
    transform: translateY(-5px);
}

.side-card img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* بطاقات المحتوى */
.cards-container {
    text-decoration:none;
    max-width: 1200px;
    margin: 0 auto 50px;
    padding: 0 15px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 25px;
}

.card {
    
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s;
   
   
}

.card:hover {
    transform: translateY(-5px);
}

.card img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-bottom: 2px solid #f8f9fa;
}

.card-content {
    padding: 15px;
    decoration:none;
   
}

.card-content h3 {
    color: #389;
    margin-bottom: 10px;
    decoration:none;
}
.card-link {
    text-decoration: none !important;
}

.card-link:hover {
    text-decoration: none !important;
}

/* تجاوبية */
@media (max-width: 992px) {
    .main-container {
        flex-direction: column;
    }

    .slideshow-container,
    .side-cards {
        flex: 0 0 100%;
    }

    .side-card {
        height: 250px;
    }
}

@media (max-width: 576px) {
    .cards-container {
        grid-template-columns: 1fr;
    }
}
</style>

<!-- الشريط الأخباري -->
<div class="news-ticker">
    <marquee behavior="scroll" direction="right">
        <?php
        $news = $conn->query("SELECT * FROM news_ticker WHERE is_active = 1");
        while ($item = $news->fetch_assoc()):
            echo htmlspecialchars($item['content']) . " | ";
        endwhile;
        ?>
    </marquee>
</div>

<!-- السلايدر + البطاقات الجانبية -->
<div class="main-container">
    <div class="slideshow-container">
        <?php
        $slides = $conn->query("SELECT * FROM slider_images WHERE is_active = 1");
        $first = true;
        while ($slide = $slides->fetch_assoc()):
        ?>
        <div class="mySlides <?= $first ? 'active' : '' ?>">
            <img src="<?= BASE_URL . $slide['image_path'] ?>" alt="Slider Image">
        </div>
        <?php $first = false; endwhile; ?>
    </div>

    <div class="side-cards">
        <a href="home.php?year=2024" class="side-card">
            <img src="<?= BASE_URL ?>assets/lib/2024.png" alt="Side Card 1">
        </a>
        <a href="home.php?year=2025" class="side-card">
            <img src="<?= BASE_URL ?>assets/lib/2025.png" alt="Side Card 2">
        </a>
    </div>
</div>

<!-- البطاقات الثلاث -->
<div class="cards-container">
    <!-- بطاقة الكتب -->
    <a href="home.php?material_type=كتاب" class="card-link">
        <div class="card">
            <img src="<?= BASE_URL ?>assets/lib/library.png" alt="كتب">
            <div class="card-content">
                <h3>المكتبة الرقمية</h3>
                <p>تصفح آلاف الكتب الإلكترونية</p>
            </div>
        </div>
    </a>

    <!-- بطاقة المجلات -->
    <a href="home.php?material_type=مجلة" class="card-link">
        <div class="card">
            <img src="<?= BASE_URL ?>assets/lib/magaziens.png" alt="مجلات">
            <div class="card-content">
                <h3>المجلات الدورية</h3>
                <p>آخر الإصدارات من المجلات</p>
            </div>
        </div>
    </a>

    <!-- بطاقة الصحف -->
    <a href="home.php?material_type=جريدة" class="card-link">
        <div class="card">
            <img src="<?= BASE_URL ?>assets/lib/wallpapers.png" alt="جريدة">
            <div class="card-content">
                <h3>الصحف اليومية</h3>
                <p>أحدث نسخ الصحف اليومية</p>
            </div>
        </div>
    </a>
</div>

<script>
// سكريبت السلايدر المعدل
let slideIndex = 0;
const slides = document.getElementsByClassName("mySlides");

function showSlides() {
    // إخفاء جميع الشرائح
    Array.from(slides).forEach(slide => {
        slide.classList.remove('active');
    });

    slideIndex++;

    if (slideIndex > slides.length) {
        slideIndex = 1;
    }

    // إظهار الشريحة الحالية
    slides[slideIndex - 1].classList.add('active');

    setTimeout(showSlides, 5000);
}

// بدء التشغيل التلقائي
showSlides();
</script>

<?php require __DIR__ . '/includes/footer.php';?>