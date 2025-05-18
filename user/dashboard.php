<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// إعداد الترقيم للكتب المستعارة
$borrowed_per_page = 6;
$borrowed_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$borrowed_page = max(1, $borrowed_page);
$borrowed_offset = ($borrowed_page - 1) * $borrowed_per_page;

// جلب الكتب المستعارة مع الترقيم
$stmt = $conn->prepare("
    SELECT SQL_CALC_FOUND_ROWS
        b.title, 
        b.author, 
        br.request_date, 
        br.due_date, 
        br.reading_completed,
        DATEDIFF(br.due_date, CURDATE()) AS remaining_days,
        MAX(n.link) AS book_link -- اختيار أحد الروابط في حالة التكرار
    FROM borrow_requests br
    JOIN books b ON br.book_id = b.id
    LEFT JOIN notifications n ON 
        br.id = n.request_id 
        AND n.link LIKE '%read_book.php%' 
    WHERE 
        br.user_id = ? 
        AND br.status = 'approved'
    GROUP BY br.id -- تجميع النتائج حسب الطلب
    LIMIT ? OFFSET ?
");
$stmt->bind_param("iii", $user_id, $borrowed_per_page, $borrowed_offset);
$stmt->execute();
$borrowed_books = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// حساب العدد الكلي للصفحات للكتب المستعارة
$total_borrowed = $conn->query("SELECT FOUND_ROWS()")->fetch_row()[0];
$total_borrowed_pages = ceil($total_borrowed / $borrowed_per_page);

// إعداد الترقيم للطلبات المعلقة
$pending_per_page = 8;
$pending_page = isset($_GET['p_page']) ? (int)$_GET['p_page'] : 1;
$pending_offset = ($pending_page - 1) * $pending_per_page;

// جلب الطلبات المعلقة مع الترقيم
$stmt = $conn->prepare("
    SELECT SQL_CALC_FOUND_ROWS br.id, b.title, b.author, br.request_date 
    FROM borrow_requests br
    JOIN books b ON br.book_id = b.id
    WHERE br.user_id = ? AND br.status = 'pending'
    LIMIT ? OFFSET ?
");
$stmt->bind_param("iii", $user_id, $pending_per_page, $pending_offset);
$stmt->execute();
$pending_requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// حساب العدد الكلي للصفحات للطلبات المعلقة
$total_pending = $conn->query("SELECT FOUND_ROWS()")->fetch_row()[0];
$total_pending_pages = ceil($total_pending / $pending_per_page);

// إعداد الترقيم لعمليات الشحن
$transactions_per_page = 5;
$transactions_page = isset($_GET['t_page']) ? (int)$_GET['t_page'] : 1;
$transactions_offset = ($transactions_page - 1) * $transactions_per_page;

// جلب عمليات الشحن مع الترقيم
$stmt = $conn->prepare("
    SELECT SQL_CALC_FOUND_ROWS
        payment_type AS type,
        amount AS value,
        payment_date AS date,
        transaction_id,
        CASE 
            WHEN payment_type = 'topup' THEN 'شحن رصيد'
            WHEN payment_type = 'borrow' THEN 'خصم استعارة'
            WHEN payment_type = 'renew' THEN 'خصم تجديد'
            WHEN payment_type = 'penalty' THEN 'خصم غرامة'
            ELSE 'شراء كتاب'
        END AS type_ar
    FROM payments 
    WHERE user_id = ? 
    ORDER BY payment_date DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param("iii", $user_id, $transactions_per_page, $transactions_offset);
$stmt->execute();
$transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// حساب العدد الكلي للصفحات لعمليات الشحن
$total_transactions = $conn->query("SELECT FOUND_ROWS()")->fetch_row()[0];
$total_transactions_pages = ceil($total_transactions / $transactions_per_page);
$_SESSION['info']="في حال لم تجد طلبك هنا انتقل ل قسم الطلبات الجارية و تابع حالته";


$active_section = isset($_GET['section']) ? htmlspecialchars($_GET['section']) : 'main';
require __DIR__ . '/../includes/header.php'; 
?>

<?php if (isset($_SESSION['success'])): ?>
<script>
Swal.fire({
    icon: 'success',
    title: 'شكرا لك.. !',
    text: '<?= $_SESSION['success'] ?>'
});
</script>
<?php unset($_SESSION['success']); ?>
<?php endif; ?>

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

<style>
.table-danger td {
    background-color: #f8d7da !important;
}

.table-success td {
    background-color: #d4edda !important;
}

.fa-book {
    color: #856404;
}

.fa-coins {
    color: #155724;
}

.card {
    border-radius: 12px;
    overflow: hidden;
    border: none;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.table-responsive {
    max-height: 500px;
    scrollbar-width: thin;
    scrollbar-color: #667eea #f8f9fa;
}

.table-responsive::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.table-responsive::-webkit-scrollbar-thumb {
    background-color: #667eea;
    border-radius: 10px;
}

.table-responsive::-webkit-scrollbar-track {
    background-color: #f8f9fa;
}

.badge {
    font-weight: 500;
    padding: 0.35em 0.65em;
}

.pagination .page-item.active .page-link {
    background-color: #667eea;
    border-color: #667eea;
}
</style>
<div class="container-fluid">
    <div class="row">
        <!-- زر الشريط الجانبي للجوال -->
        <button class="btn btn-primary sidebar-toggler d-lg-none" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <!-- الشريط الجانبي -->
        <div class="col-md-3 sidebar p-4">
            <div class="d-grid gap-2">
                <button onclick="showSection('main')"
                    class="btn text-start text-white d-flex align-items-center gap-3 py-3 hover-effect  <?= ($active_section == 'main') ? 'active' : '' ?>"
                    style="background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);border-radius: 15px;">
                    <i class="fas fa-user"></i> الرئيسية
                </button>

                <button onclick="showSection('profile')"
                    class="btn text-start text-white d-flex align-items-center gap-3 py-3 hover-effect  <?= ($active_section == 'profile') ? 'active' : '' ?>"
                    style="background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);border-radius: 15px;">
                    <i class="fas fa-user"></i> الملف الشخصي
                </button>

                <button onclick="showSection('favorit')"
                    class="btn text-start text-white d-flex align-items-center gap-3 py-3 hover-effect  <?= ($active_section == 'favorit') ? 'active' : '' ?>"
                    style="background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);border-radius: 15px;">
                    <i class="fas fa-user"></i> المفضلة
                </button>

                <button onclick="showSection('ops')"
                    class="btn text-start text-white d-flex align-items-center gap-3 py-3 hover-effect  <?= ($active_section == 'ops') ? 'active' : '' ?>"
                    style="background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);border-radius: 15px;">
                    <i class="fas fa-user"></i> العمليات الجارية
                </button>

                <button onclick="showSection('funds')"
                    class="btn text-start text-white d-flex align-items-center gap-3 py-3 hover-effect  <?= ($active_section == 'funds') ? 'active' : '' ?>"
                    style="background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);border-radius: 15px;">
                    <i class="fas fa-user"></i> العمليات المالية
                </button>

                <button onclick="showSection('waiting')"
                    class="btn text-start text-white d-flex align-items-center gap-3 py-3 hover-effect  <?= ($active_section == 'waiting') ? 'active' : '' ?>"
                    style="background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);border-radius: 15px;">
                    <i class="fas fa-user"></i> العمليات المعلقة
                </button>


            </div>
        </div>
        <!-- المحتوى الرئيسي -->
        <div class="col-md-9 p-4">
            <!-- قسم الرئيسية -->
            <div id="main" class="content-section <?= ($active_section == 'main') ? 'active' : '' ?>">
                <div class="card">
                    <div class="card-body">
                        <?php require __DIR__ . '/main.php'; ?>
                    </div>
                </div>
            </div>

            <div id="profile" class="content-section <?= ($active_section == 'profile') ? 'active' : '' ?>">
                <div class="card">
                    <div class="card-body">
                        <?php require __DIR__ . '/profile.php'; ?>
                    </div>
                </div>
            </div>
            <!-- قسم الكتب المستعارة -->
            <div id="ops" class="content-section <?= ($active_section == 'ops') ? 'active' : '' ?>">
                <div class="card">
                    <div class="card-body">
                        <div class="container-fluid py-4">
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-header bg-gradient-primary text-white py-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0 fw-bold">
                                            <i class="fas fa-hand-holding me-2"></i> إدارة طلبات الاستعارة
                                        </h5>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <?php if(count($borrowed_books) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead class="sticky-top bg-light">
                                                <tr>
                                                    <th>العنوان</th>
                                                    <th>المؤلف</th>
                                                    <th>تاريخ الاستعارة</th>
                                                    <th>تاريخ الاستحقاق</th>
                                                    <th>الحالة</th>
                                                    <th>الرابط</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($borrowed_books as $index => $book): 
                                                    $remaining = $book['remaining_days'];
                                                    $status_class = '';
                                                    $status_text = '';
                                                    
                                                    if ($book['reading_completed'] == TRUE) {
                                                        $status_class = 'returned';
                                                        $status_text = '<span class="text-primary">تم الإرجاع</span>';
                                                    } elseif ($remaining < 0) {
                                                        $status_class = 'overdue';
                                                        $status_text = '<span class="text-danger">متأخر ' . abs($remaining) . ' يوم</span>';
                                                    } elseif ($remaining <= 3) {
                                                        $status_class = 'due-soon';
                                                        $status_text = '<span class="text-warning">' . $remaining . ' أيام</span>';                             
                                                    } elseif ($remaining > 900) {
                                                        $status_class = 'due-soon';
                                                        $status_text = '<span class="text-success"> تم شراءه</span>'; 
                                                                            
                                                    } else {
                                                        $status_text = $remaining . ' يوم';
                                                    }
                                                ?>
                                                <tr>
                                                    <td><?= ($borrowed_page - 1) * $borrowed_per_page + $index + 1 ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($book['title']) ?></td>
                                                    <td><?= htmlspecialchars($book['author']) ?></td>
                                                    <td><?= date('Y/m/d', strtotime($book['request_date'])) ?></td>
                                                    <td><?= date('Y/m/d', strtotime($book['due_date'])) ?></td>
                                                    <td><?= $status_text ?></td>
                                                    <td>
                                                        <?php if (!empty($book['book_link'])): ?>
                                                        <?php if (!$book['reading_completed']): ?>
                                                        <a href="<?= htmlspecialchars($book['book_link']) ?>"
                                                            class="btn btn-primary btn-sm" target="_blank">
                                                            <i class="fas fa-book-open"></i> قراءة
                                                        </a>
                                                        <?php else: ?>
                                                        <span class="text-muted">(تمت القراءة)</span>
                                                        <?php endif; ?>
                                                        <?php else: ?>
                                                        <span class="text-muted">غير متوفر</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <!-- الترقيم -->
                                    <?php if ($total_borrowed_pages > 1): ?>
                                    <div class="card-footer bg-light">
                                        <nav aria-label="Page navigation">
                                            <ul class="pagination justify-content-center mb-0">
                                                <?php if ($borrowed_page > 1): ?>
                                                <li class="page-item"><a class="page-link" href="?page=<?= $borrowed_page - 1 ?>
                                                        &section=ops" aria-label="السابق">
                                                        <span aria-hidden="true">&laquo;</span>
                                                    </a>
                                                </li>
                                                <?php endif; ?>

                                                <?php for ($i = 1; $i <= $total_borrowed_pages; $i++): ?>
                                                <li class="page-item <?= ($i == $borrowed_page) ? 'active' : '' ?>">
                                                    <a class="page-link"href="?page=<?= $i ?>&section=ops"><?= $i ?></a>
                                                </li>
                                                <?php endfor; ?>

                                                <?php if ($borrowed_page < $total_borrowed_pages): ?>
                                                <li class="page-item">
                                                    <a class="page-link"
                                                        href="?page=<?= $borrowed_page + 1 ?>&section=ops"
                                                        aria-label="التالي">
                                                        <span aria-hidden="true">&raquo;</span>
                                                    </a>
                                                </li>
                                                <?php endif; ?>
                                            </ul>
                                        </nav>
                                    </div>
                                    <?php endif; ?>

                                    <?php else: ?>
                                    <div class="alert alert-info m-3">لا يوجد كتب مستعارة حالياً</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="waiting" class="content-section <?= ($active_section == 'waiting') ? 'active' : '' ?>">
                <div class="card">
                    <div class="card-body">
                        <?php if (isset($_SESSION['info'])): ?>
                        <div class="alert alert-success"><?= $_SESSION['info'] ?></div>
                        <?php unset($_SESSION['info']); ?>
                        <?php endif; ?>
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-gradient-primary text-white py-3">
                                <h5 class="mb-0 fw-bold">
                                    <i class="fas fa-clock me-2"></i> الطلبات المعلقة
                                </h5>
                            </div>
                            <div class="card-body p-0">
                                <?php if(count($pending_requests) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="sticky-top bg-light">
                                            <tr>
                                                <th>العنوان</th>
                                                <th>المؤلف</th>
                                                <th>تاريخ الطلب</th>
                                                <th>الحالة</th>
                                                <th>إجراءات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($pending_requests as $request): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($request['title']) ?></td>
                                                <td><?= htmlspecialchars($request['author']) ?></td>
                                                <td><?= date('Y/m/d', strtotime($request['request_date'])) ?></td>
                                                <td><span class="badge bg-warning">قيد المراجعة</span></td>
                                                <td>
                                                    <form method="POST" action="../process.php"
                                                        onsubmit="return confirm('هل تريد حذف هذا الطلب؟');">
                                                        <input type="hidden" name="request_id"
                                                            value="<?= $request['id'] ?>">
                                                        <input type="hidden" name="csrf_token"
                                                            value="<?= $_SESSION['csrf_token'] ?>">
                                                        <button type="submit" name="delete_request"
                                                            class="btn btn-danger btn-sm">
                                                            <i class="fas fa-trash"></i> حذف
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- الترقيم للطلبات المعلقة -->
                                <?php if ($total_pending_pages > 1): ?>
                                <div class="card-footer bg-light">
                                    <nav aria-label="Page navigation">
                                        <ul class="pagination justify-content-center mb-0">
                                            <?php if ($pending_page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?p_page=<?= $pending_page - 1 ?>#pending"
                                                    aria-label="السابق">
                                                    <span aria-hidden="true">&laquo;</span>
                                                </a>
                                            </li>
                                            <?php endif; ?>

                                            <?php for ($i = 1; $i <= $total_pending_pages; $i++): ?>
                                            <li class="page-item <?= ($i == $pending_page) ? 'active' : '' ?>">
                                                <a class="page-link" href="?p_page=<?= $i ?>#pending"><?= $i ?></a>
                                            </li>
                                            <?php endfor; ?>

                                            <?php if ($pending_page < $total_pending_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?p_page=<?= $pending_page + 1 ?>#pending"
                                                    aria-label="التالي">
                                                    <span aria-hidden="true">&raquo;</span>
                                                </a>
                                            </li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                </div>
                                <?php endif; ?>

                                <?php else: ?>
                                <div class="alert alert-info m-3">لا توجد طلبات معلقة</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="funds" class="content-section <?= ($active_section == 'funds') ? 'active' : '' ?>">
                <div class="card">
                    <div class="card-body">
                        <div class="card-header bg-gradient-primary text-white py-3">
                            <h5 class="mb-0 fw-bold">
                                <i class="fas fa-coins me-2"></i> عمليات الشحن
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if(count($transactions) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="sticky-top bg-light">
                                        <tr>
                                            <th>نوع العملية</th>
                                            <th>القيمة</th>
                                            <th>التاريخ</th>
                                            <th>الرقم المرجعي</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($transactions as $transaction): ?>
                                        <tr
                                            class="<?= $transaction['type'] == 'topup' ? 'table-success' : 'table-danger' ?>">
                                            <td>
                                                <?= $transaction['type_ar'] ?>
                                                <?php if($transaction['type'] == 'borrow'): ?>
                                                <i class="fas fa-book ms-2"></i>
                                                <?php else: ?>
                                                <i class="fas fa-coins ms-2"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?= number_format($transaction['value'], 2) ?> ل.س
                                                <?= $transaction['type'] == 'topup' ? '+' : '-' ?>
                                            </td>
                                            <td><?= date('Y/m/d H:i', strtotime($transaction['date'])) ?></td>
                                            <td class="text-muted"><?= $transaction['transaction_id'] ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- الترقيم لعمليات الشحن -->
                            <?php if ($total_transactions_pages > 1): ?>
                            <div class="card-footer bg-light">
                                <nav aria-label="Page navigation">
                                    <ul class="pagination justify-content-center mb-0">
                                        <?php if ($transactions_page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link"
                                                href="?t_page=<?= $transactions_page - 1 ?>#transactions"
                                                aria-label="السابق">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                        <?php endif; ?>

                                        <?php for ($i = 1; $i <= $total_transactions_pages; $i++): ?>
                                        <li class="page-item <?= ($i == $transactions_page) ? 'active' : '' ?>">
                                            <a class="page-link" href="?t_page=<?= $i ?>#transactions"><?= $i ?></a>
                                        </li>
                                        <?php endfor; ?>

                                        <?php if ($transactions_page < $total_transactions_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link"
                                                href="?t_page=<?= $transactions_page + 1 ?>#transactions"
                                                aria-label="التالي">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            </div>
                            <?php endif; ?>

                            <?php else: ?>
                            <div class="alert alert-info m-3">لا توجد عمليات شحن سابقة</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div id="favorit" class="content-section <?= ($active_section == 'favorit') ? 'active' : '' ?>">
                <div class="card">
                    <div class="card-body">
                        <?php require __DIR__ . '/favorit.php'; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
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

<?php require __DIR__ . '/../includes/footer.php'; ?>