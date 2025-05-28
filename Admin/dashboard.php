<?php
// بدء الجلسة وإدارة المخرجات

// تخزين الإخراج في المخزن المؤقت
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require __DIR__ . '/../includes/config.php';


// ------ التحقق من صلاحيات المستخدم ------ //
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location:" .BASE_URL."/login.php");
    exit();
}

// جلب الإعدادات الحالية
$settings = [];
$result = $conn->query("SELECT name, value FROM settings");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $settings[$row['name']] = $row['value'];
    }
    $result->close();
} else {
    die("خطأ في جلب الإعدادات: " . $conn->error);
}

// ------ تحديد القسم النشط ------ //
$active_section = isset($_GET['section']) ? htmlspecialchars($_GET['section']) : 'personal';
require __DIR__ . '/../includes/header.php';
?>

<!-- عرض الرسائل التحذيرية -->
<?php if (isset($_SESSION['error'])): ?>
<script>
Swal.fire({
    icon: 'warning',
    title: 'انتبه.. !',
    text: '<?= $_SESSION['error']?>'
});
</script>
<?php unset($_SESSION['error']);?>
<?php endif;?>

<?php if (isset($_SESSION['succaaess'])): ?>
<script>
Swal.fire({
    icon: 'error',
    title: 'خطأ!',
    text: '<?= addslashes($_SESSION['succaaess']) ?>'
});
</script>
<?php unset($_SESSION['succaaess']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['success'])):?>
<script>
Swal.fire({
    icon: 'success',
    title: 'احسنت.. !',
    text: '<?= $_SESSION['success']?>'
});
</script>
<?php unset($_SESSION['success']);?>
<?php endif;?>
<div class="container-fluid">
    <div class="row">

        <!-- زر الشريط الجانبي للجوال -->
        <button class="btn btn-primary sidebar-toggler d-lg-none" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>

        <!-- الشريط الجانبي -->
        <div class="col-md-3 sidebar p-4">
            <div class="d-grid gap-2">
                <button onclick="showSection('personal')"
                    class="btn text-start text-white d-flex align-items-center gap-3 py-3 hover-effect  <?= ($active_section == 'personal') ? 'active' : '' ?>"
                    style="background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);border-radius: 15px;">
                    <i class="fas fa-user"></i> الرئيسية
                </button>

                <button onclick="showSection('operations')"
                    class="btn btn-outline-info text-start bg-dark text-white d-flex align-items-center gap-3 py-3 hover-effect  <?= ($active_section == 'operations') ? 'active' : '' ?>"
                    style="background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);border-radius: 15px;">
                    <i class="fas fa-sync-alt"></i> الملف الشخصي
                </button>

                <button onclick="showSection('books')"
                    class="btn btn-outline-success text-start bg-dark text-white d-flex align-items-center gap-3 py-3 hover-effect  <?= ($active_section == 'books') ? 'active' : '' ?>"
                    style="background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);border-radius: 15px;">
                    <i class="fas fa-book"></i> إدارة الكتب
                </button>

                <button onclick="showSection('ops')"
                    class="btn btn-outline-danger text-start bg-dark text-white d-flex align-items-center gap-3 py-3 hover-effect  <?= ($active_section == 'ops') ? 'active' : '' ?>"
                    style="background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);border-radius: 15px;">
                    <i class="fas fa-book"></i> إدارة الطلبات
                </button>

                <button onclick="showSection('sales')"
                    class="btn btn-outline-warning text-start bg-dark text-white d-flex align-items-center gap-3 py-3 hover-effect  <?= ($active_section == 'sales') ? 'active' : '' ?>"
                    style="background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);border-radius: 15px;">
                    <i class="fas fa-coins"></i> إدارة المبيعات
                </button>

                <button onclick="showSection('users')"
                    class="btn btn-outline-info text-start bg-dark text-white d-flex align-items-center gap-3 py-3 hover-effect  <?= ($active_section == 'users') ? 'active' : '' ?>"
                    style="background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);border-radius: 15px;">
                    <i class="fas fa-users"></i> إدارة المستخدمين
                </button>

                <button onclick="showSection('bk')"
                    class="btn btn-outline-info text-start bg-dark text-white d-flex align-items-center gap-3 py-3 hover-effect  <?= ($active_section == 'bk') ? 'active' : '' ?>"
                    style="background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);border-radius: 15px;">
                    <i class="fas fa-hdd"></i> النسخ الاحتياطي
                </button>

                <button onclick="showSection('logs')"
                    class="btn btn-outline-danger text-start bg-dark text-white d-flex align-items-center gap-3 py-3 hover-effect  <?= ($active_section == 'logs') ? 'active' : '' ?>"
                    style="background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);border-radius: 15px;">
                    <i class="fas fa-hdd"></i> سجلات النشاطات
                </button>

                <button onclick="showSection('payment')"
                    class="btn btn-outline-danger text-start bg-dark text-white d-flex align-items-center gap-3 py-3 hover-effect  <?= ($active_section == 'payment') ? 'active' : '' ?>"
                    style="background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);border-radius: 15px;">
                    <i class="fas fa-hdd"></i> سجلات الدفع
                </button>

                <button onclick="showSection('complaints')"
                    class="btn  btn-outline-danger text-start bg-dark text-white d-flex align-items-center gap-3 py-3 hover-effect  <?= ($active_section == 'complaints') ? 'active' : '' ?>"
                    style="background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);border-radius: 15px;">
                    <i class="fas fa-exclamation-circle"></i> إدارة الشكاوى
                </button>

                <button onclick="showSection('slider')"
                    class="btn text-start bg-dark text-white d-flex align-items-center gap-3 py-3 hover-effect  <?= ($active_section == 'slider') ? 'active' : '' ?>"
                    style="background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);border-radius: 15px;">
                    <i class="fas fa-images"></i> إدارة السلايدر
                </button>

                <button onclick="showSection('news_ticker')"
                    class="btn text-start bg-dark text-white d-flex align-items-center gap-3 py-3 hover-effect  <?= ($active_section == 'news_ticker') ? 'active' : '' ?>"
                    style="background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);border-radius: 15px;">
                    <i class="fas fa-newspaper"></i> الشريط الأخباري
                </button>

                <button onclick="showSection('categories')"
                    class="btn text-start bg-dark text-white d-flex align-items-center gap-3 py-3 hover-effect  <?= ($active_section == 'categories') ? 'active' : '' ?>"
                    style="background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);border-radius: 15px;">
                    <i class="fas fa-tags"></i> إدارة التصنيفات
                </button>

                <button onclick="showSection('settings')"
                    class="btn text-start bg-dark text-white d-flex align-items-center gap-3 py-3 hover-effect  <?= ($active_section == 'settings') ? 'active' : '' ?>"
                    style="background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);border-radius: 15px;">
                    <i class="fas fa-cog"></i> الإعدادات
                </button>
                <button onclick="showSection('reports')"
                    class="btn btn-outline-success text-start bg-dark text-white d-flex align-items-center gap-3 py-3 hover-effect  <?= ($active_section == 'reports') ? 'active' : '' ?>"
                    style="background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);border-radius: 15px;">
                    <i class="fas fa-chart-bar"></i> التقارير
                </button>
            </div>
        </div>

        <!-- المحتوى الرئيسي -->
        <div class="col-md-9 p-4">

            <!-- قسم الرئيسية -->
            <div id="personal" class="content-section <?= ($active_section == 'personal') ? 'active' : '' ?>">
                <div class="card">
                    <div class="card-body">
                        <?php require __DIR__ . '/personal.php'; ?>
                    </div>
                </div>
            </div>

            <!-- قسم الملف الشخصي -->
            <div id="operations" class="content-section <?= ($active_section == 'operations') ? 'active' : '' ?>">
                <div class="card">
                    <div class="card-body">
                        <?php require __DIR__ . '/profile.php'; ?>
                    </div>
                </div>
            </div>

            <!-- قسم إدارة الطلبات -->
            <div id="ops" class="content-section <?= ($active_section == 'ops') ? 'active' : '' ?>">
                <div class="card">
                    <div class="card-body">
                        <?php require __DIR__ . '/manage_loan.php'; ?>
                    </div>
                </div>
            </div>

            <!--قسم التصنيفات -->
            <div id="categories" class="content-section <?= ($active_section == 'categories') ? 'active' : '' ?>">
                <div class="card">
                    <div class="card-body">
                        <?php require __DIR__ . '/manage_categories.php'; ?>
                    </div>
                </div>
            </div>

            <!-- قسم إدارة الكتب -->
            <div id="books" class="content-section <?= ($active_section == 'books') ? 'active' : '' ?>">
                <div class="card">
                    <div class="card-body">
                        <?php require __DIR__ . '/manage_books.php'; ?>
                    </div>
                </div>
            </div>

            <!-- قسم إدارة المبيعات -->
            <div id="sales" class="content-section <?= ($active_section == 'sales') ? 'active' : '' ?>">
                <?php require __DIR__ . '/manage_ops.php'; ?>
            </div>

            <!-- قسم إدارة المستخدمين -->
            <div id="users" class="content-section <?= ($active_section == 'users') ? 'active' : '' ?>">
                <div class="card">
                    <div class="card-body">
                        <?php require __DIR__ . '/manage_users.php'; ?>
                    </div>
                </div>
            </div>

            <!-- قسم النسخ الاحتياطي -->
            <div id="bk" class="content-section <?= ($active_section == 'bk') ? 'active' : '' ?>">
                <div class="card">
                    <div class="card-body">
                        <?php require __DIR__ . '/backup_restore.php'; ?>
                    </div>
                </div>
            </div>

            <!-- قسم سجلات النشاطات -->
            <div id="logs" class="content-section <?= ($active_section == 'logs') ? 'active' : '' ?>">
                <div class="card">
                    <div class="card-body">
                        <?php require __DIR__ . '/manage_logs.php'; ?>
                    </div>
                </div>
            </div>

            <!-- قسم سجلات الدفع -->
            <div id="payment" class="content-section <?= ($active_section == 'payment') ? 'active' : '' ?>">
                <div class="card">
                    <div class="card-body">
                        <?php require __DIR__ . '/payment_logs.php'; ?>
                    </div>
                </div>
            </div>

            <!-- قسم إدارة الشكاوى -->
            <div id="complaints" class="content-section <?= ($active_section == 'complaints') ? 'active' : '' ?>">
                <div class="card">
                    <div class="card-body">
                        <?php require __DIR__ . '/manage_complaints.php'; ?>
                    </div>
                </div>
            </div>

            <!--قسم السلايدر -->
            <div id="slider" class="content-section <?= ($active_section == 'slider') ? 'active' : '' ?>">
                <div class="card">
                    <div class="card-body">
                        <h4>إدارة صور السلايدر</h4>

                        <!-- نموذج رفع صورة -->
                        <form action="process_slider.php" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <input type="file" name="slider_image" class="form-control" required>
                            </div>
                            <button type="submit" name="upload_image" class="btn btn-primary">رفع الصورة</button>
                        </form>

                        <!-- قائمة الصور -->
                        <div class="mt-4">
                            <?php
                                    $images = $conn->query("SELECT * FROM slider_images");
                                    while ($image = $images->fetch_assoc()):
                                    ?>
                            <div class="card mb-3">
                                <img src="<?= BASE_URL . $image['image_path'] ?>" class="card-img-top"
                                    style="height: 200px;">
                                <div class="card-body">
                                    <form action="process_slider.php" method="POST">
                                        <input type="hidden" name="image_id" value="<?= $image['id'] ?>">
                                        <button type="submit" name="delete_image" class="btn btn-danger">حذف</button>
                                    </form>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!--قسم الأخبار -->
            <div id="news_ticker" class="content-section <?= ($active_section == 'news_ticker') ? 'active' : '' ?>">
                <div class="card">
                    <div class="card-body">
                        <h4>إدارة الأخبار</h4>

                        <!-- إضافة خبر جديد -->
                        <form action="process_news.php" method="POST">
                            <div class="mb-3">
                                <textarea name="content" class="form-control" placeholder="محتوى الخبر"
                                    required></textarea>
                            </div>
                            <button type="submit" name="add_news" class="btn btn-primary">إضافة</button>
                        </form>

                        <!-- قائمة الأخبار -->
                        <div class="mt-4">
                                        <?php
                            $news = $conn->query("SELECT * FROM news_ticker");
                            while ($item = $news->fetch_assoc()):
                            ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <form action="process_news.php" method="POST">
                                        <input type="hidden" name="news_id" value="<?= $item['id'] ?>">
                                        <textarea name="content"
                                            class="form-control mb-2"><?= $item['content'] ?></textarea>
                                        <button type="submit" name="update_news" class="btn btn-warning">تحديث</button>
                                        <button type="submit" name="delete_news" class="btn btn-danger">حذف</button>
                                    </form>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- قسم  الاعدادات -->
            <div id="settings" class="content-section <?= ($active_section == 'settings') ? 'active' : '' ?>">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-gradient-primary text-white py-3">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-cog me-2"></i> إدارة الإعدادات العامة
                        </h5>
                    </div>

                    <div class="card-body p-4">
                        
                        <form method="POST" action="settings.php">
                            <div class="mb-4">
                                <label class="form-label text-muted mb-2">سعر الشراء (ل.س)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-tag text-primary"></i>
                                    </span>
                                    <input type="number" step="0.01" name="settings[purchase_price]"
                                        class="form-control border-start-0 ps-3"
                                        value="<?= $settings['purchase_price'] ?>" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label text-muted mb-2">سعر الإعارة (ل.س)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-hand-holding-usd text-primary"></i>
                                    </span>
                                    <input type="number" step="0.01" name="settings[rental_price]"
                                        class="form-control border-start-0 ps-3"
                                        value="<?= $settings['rental_price'] ?>" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label text-muted mb-2">غرامة التأخير اليومية (ل.س)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-exclamation-triangle text-primary"></i>
                                    </span>
                                    <input type="number" step="0.01" name="settings[late_fee]"
                                        class="form-control border-start-0 ps-3" value="<?= $settings['late_fee'] ?>"
                                        required>
                                </div>
                                <small class="text-muted">القيمة المطلوبة يجب أن تكون أكبر من الصفر</small>
                            </div>

                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn btn-primary btn-lg rounded-pill">
                                    <i class="fas fa-save me-2"></i> حفظ التغييرات
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- قسم التقارير -->
            <!-- في قسم التقارير -->
<div id="reports" class="content-section <?= ($active_section == 'reports') ? 'active' : '' ?>">
    <div class="card">
        <div class="card-body">
            <!-- نموذج التقارير -->
            <form method="post" action="manage_reports.php">
                <div class="mb-3">
                    <label for="report_type" class="form-label">اختر نوع التقرير:</label>
                    <select class="form-select" id="report_type" name="report_type">
                        <?php
                        $reportTypes = [
                            'users' => 'تقارير المستخدمين',
                            'books' => 'تقارير الكتب',
                            'borrow_requests' => 'تقارير طلبات الإعارة',
                            'payments' => 'تقارير المدفوعات',
                            'notifications' => 'تقارير الإشعارات'
                        ];
                        foreach ($reportTypes as $key => $value) {
                            $selected = ($_POST['report_type'] ?? 'users') == $key ? 'selected' : '';
                            echo "<option value='$key' $selected>$value</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="start_date" class="form-label">تاريخ البداية:</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" 
                        value="<?= $_POST['start_date'] ?? date('Y-m-d', strtotime('-1 month')) ?>">
                </div>
                <div class="mb-3">
                    <label for="end_date" class="form-label">تاريخ النهاية:</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" 
                        value="<?= $_POST['end_date'] ?? date('Y-m-d') ?>">
                </div>
                <button type="submit" class="btn btn-primary">عرض التقرير</button>
            </form>

            <!-- عرض النتائج من الجلسة -->
            <?php if (isset($_SESSION['report_results'])): ?>
                <div class="mt-4">
                    <?= $_SESSION['report_results'] ?>
                </div>
                <?php unset($_SESSION['report_results']); ?>
            <?php endif; ?>
        </div>
    </div>
</div>


        </div>
    </div>
</div>
<script>
// دالة إظهار القسم وتحديث الرابط
function showSection(sectionId) {
    // تحديث الرابط بإضافة المعلمة section
    const url = new URL(window.location.href);
    url.searchParams.set('section', sectionId);
    window.history.replaceState({}, '', url);

    // إزالة النشاط من جميع الأزرار
    document.querySelectorAll('.sidebar .btn').forEach(btn => {
        btn.classList.remove('active');
    });

    // إخفاء جميع الأقسام
    document.querySelectorAll('.content-section').forEach(section => {
        section.classList.remove('active');
    });

    // إظهار القسم المحدد وإضافة النشاط للزر
    document.getElementById(sectionId).classList.add('active');
    event.target.classList.add('active');
}

// دالة التحكم بالشريط الجانبي
function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('active');
}
</script>

<?php 

require __DIR__ . '/../includes/footer.php'; ?>