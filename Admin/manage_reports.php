<?php
// manage_reports.php



 include __DIR__ . '/../includes/config.php';
// معالجة النموذج إذا تم إرساله
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $selectedReport = $_POST['report_type'];
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];
} else {
    $selectedReport = 'users'; // التقرير الافتراضي
}
include __DIR__ . '/../includes/header.php';
?>

<style>
.request-badge {
    display: inline-block;
    padding: 6px 12px;
    background-color: #007bff;
    color: white !important;
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.3s;
    font-weight: 500;
    text-decoration: none !important;
}

.request-badge:hover {
    background-color: #0056b3;
    transform: translateY(-2px);
    box-shadow: 0 3px 6px rgba(0,0,0,0.1);
}

.purchase-badge {
    display: inline-block;
    padding: 6px 12px;
    background-color: #28a745; /* لون أخضر */
    color: white !important;
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.3s;
    font-weight: 500;
}

.purchase-badge:hover {
    background-color: #218838;
    transform: translateY(-2px);
    box-shadow: 0 3px 6px rgba(0,0,0,0.1);
}

</style>
<div class="container-fluid py-4">
    <div class="card border-0 shadow-lg mb-4">
        <div class="card-header bg-gradient-primary text-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-chart-bar me-2"></i> تقارير النظام
                </h5>
                <div class="d-flex gap-2 align-items-center">
                    <!-- مربع البحث الجديد -->
                    <input type="text" class="form-control form-control-sm" id="liveSearch"
                        placeholder="ابحث في النتائج..." style="width: 200px;">

                    <!-- فلترة التواريخ -->
                    <form method="post" class="d-flex gap-2 align-items-center">
                        <div class="input-group input-group-sm">
                            <input type="date" name="start_date" class="form-control"
                                value="<?= isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-01') ?>">
                            <span class="input-group-text">إلى</span>
                            <input type="date" name="end_date" class="form-control"
                                value="<?= isset($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-d') ?>">
                        </div>
                        <select name="report_type" class="form-select form-select-sm" style="width: 180px;">
                            <option value="users" <?= $selectedReport == 'users' ? 'selected' : '' ?>>تقارير المستخدمين
                            </option>
                            <option value="books" <?= $selectedReport == 'books' ? 'selected' : '' ?>>تقارير الكتب
                            </option>
                            <option value="borrow_requests"
                                <?= $selectedReport == 'borrow_requests' ? 'selected' : '' ?>>طلبات الإعارة</option>
                            <option value="payments" <?= $selectedReport == 'payments' ? 'selected' : '' ?>>المدفوعات
                            </option>
                            <option value="notifications" <?= $selectedReport == 'notifications' ? 'selected' : '' ?>>
                                الإشعارات</option>
                            <option value="most_borrowed_books"
                                <?= $selectedReport == 'most_borrowed_books' ? 'selected' : '' ?>>الكتب الأكثر استعارة
                            </option>
                            <option value="most_purchased_books"
                                <?= $selectedReport == 'most_purchased_books' ? 'selected' : '' ?>>الكتب الأكثر شراء
                            </option>
                            <option value="most_active_users"
                                <?= $selectedReport == 'most_active_users' ? 'selected' : '' ?>>المستخدمين النشطين
                            </option>
                        </select>
                        <button type="submit" class="btn btn-light btn-sm">
                            تطبيق
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <?php include __DIR__ . '/../includes/alerts.php'; ?>

            <div class="table-responsive">
                <?php
                // دالة مساعدة لتنفيذ الاستعلامات وعرض النتائج في جدول
                function displayTable($conn, $query, $title, $headers) {
                    echo "<h6 class='p-3 mb-0 bg-light'>$title</h6>";
                    $result = $conn->query($query);
                    if ($result && $result->num_rows > 0) {
                        echo "<table class='table table-hover align-middle mb-0'>";
                        echo "<thead class='sticky-top bg-light'><tr>";
                        foreach ($headers as $header) {
                            echo "<th>" . $header . "</th>";
                        }
                        echo "</tr></thead><tbody>";
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            foreach ($row as $key => $value) {
                                // السماح بعرض HTML في الحقول التي تحتوي على روابط
                                if ($key === 'requests_link') {
                                    echo "<td>" . $value . "</td>";
                                }elseif($key === 'purchases_link') {
                                    echo "<td>" . $value . "</td>";                                    
                                }else {
                                    echo "<td>" . htmlspecialchars($value) . "</td>";
                                }
                            }
                            echo "</tr>";
                        }
                        echo "</tbody></table>";
                    } else {
                        echo "<div class='p-3 text-muted'>لا توجد بيانات.</div>";
                    }  
                }

                // عرض التقرير المحدد
                switch ($selectedReport) {
                    case 'users':
                        displayTable(
                            $conn,
                            "SELECT 
                                id,
                                name,
                                email,
                                CASE 
                                    WHEN user_type = 'admin' THEN 'مدير'
                                    ELSE 'مستخدم عادي'
                                END AS user_type,
                                created_at,
                                CASE
                                    WHEN status = 1 THEN 'حساب فعال'
                                    ELSE 'حساب غير فعال'
                                END AS status
                             FROM users
                             WHERE created_at BETWEEN '$startDate' AND '$endDate'",
                            "تقارير المستخدمين",
                            ["ID", "الاسم", "البريد", "نوع المستخدم", "تاريخ التسجيل", "الحالة"]
                        );
                        break;

                    case 'books':
                        displayTable(
                            $conn,
                            "SELECT b.id, b.title, b.author, b.quantity, b.price, c.category_name 
                                FROM books b
                                LEFT JOIN categories c ON b.category_id = c.category_id",
                            "تقارير الكتب",
                            ["ID", "العنوان", "المؤلف","الكمية", "السعر", "التصنيف"]
                        );
                        break;

                    case 'borrow_requests':
                        displayTable(
                            $conn,
                            "SELECT 
                                br.id,
                                u.name as user_name,
                                b.title as book_title,
                                br.request_date,
                                CASE 
                                    WHEN br.status = 'pending' THEN 'قيد المراجعة'
                                    WHEN br.status = 'approved' THEN 'تمت الموافقة'
                                    ELSE 'مرفوض'
                                END AS status,
                                CASE
                                    WHEN br.type = 'borrow' THEN 'استعارة'
                                    WHEN br.type = 'purchase' THEN 'شراء'
                                    ELSE 'تجديد' 
                                END AS request_type,
                                br.due_date
                                FROM borrow_requests br
                                JOIN users u ON br.user_id = u.id
                                JOIN books b ON br.book_id = b.id
                                WHERE br.request_date BETWEEN '$startDate' AND '$endDate'",
                            "طلبات الإعارة",
                            ["ID", "المستخدم", "الكتاب", "تاريخ الطلب", "الحالة", "النوع", "تاريخ الاستحقاق"]
                        );
                        break;

                    case 'payments':
                        displayTable(
                            $conn,
                            "SELECT 
                                p.payment_id,
                                u.name as user_name,
                                b.title as book_title,
                                p.amount,
                                p.payment_date,
                                CASE
                                    WHEN p.payment_type = 'borrow' THEN 'استعارة'
                                    WHEN p.payment_type = 'purchase' THEN 'شراء'
                                    WHEN p.payment_type = 'topup' THEN 'شحن رصيد'
                                    WHEN p.payment_type = 'penalty' THEN 'غرامة تأخير'
                                    ELSE p.payment_type
                                END AS payment_type
                                FROM payments p
                                LEFT JOIN users u ON p.user_id = u.id
                                LEFT JOIN books b ON p.book_id = b.id
                                WHERE p.payment_date BETWEEN '$startDate' AND '$endDate'",
                            "المدفوعات",
                            ["ID", "المستخدم", "الكتاب", "المبلغ", "تاريخ الدفع", "نوع الدفع"]
                        );
                        break;

                    case 'notifications':
                        displayTable(
                            $conn,
                            "SELECT n.notification_id, u.name as user_name, n.message, n.created_at, n.is_read
                             FROM notifications n
                             JOIN users u ON n.user_id = u.id
                             WHERE n.created_at BETWEEN '$startDate' AND '$endDate'",
                            "الإشعارات",
                            ["ID", "المستخدم", "الرسالة", "تاريخ الإرسال", "مقروء"]
                        );
                        break;

                    case 'most_borrowed_books':
                        displayTable(
                            $conn,
                            "SELECT 
                                b.id,
                                b.title,
                                COUNT(DISTINCT br.id) AS total_requests,
                                CONCAT(
                                    '<span class=\"request-badge\" ',
                                    'data-book=\"', b.id, '\">
                                    تفاصيل
                                    </span>'
                                ) AS requests_link
                                FROM borrow_requests br
                                JOIN books b ON br.book_id = b.id
                                WHERE br.request_date BETWEEN '$startDate' AND '$endDate'
                                GROUP BY b.id
                                ORDER BY total_requests DESC
                                LIMIT 50",
                            "الكتب الأكثر استعارة",
                            ["ID", "العنوان", "عدد الطلبات"]
                        );
                        break;
                   

                        case 'most_purchased_books':
                            displayTable(
                                $conn,
                                "SELECT 
                                    b.id,
                                    b.title,
                                    COUNT(DISTINCT p.payment_id) AS total_purchases, 
                                    CONCAT(
                                        '<span class=\"purchase-badge\" ',
                                        'data-book=\"', b.id, '\">',
                                        COUNT(DISTINCT p.payment_id),
                                        '</span>'
                                    ) AS purchases_link
                                 FROM payments p
                                 JOIN borrow_requests br ON p.request_id = br.id
                                 JOIN books b ON br.book_id = b.id
                                 WHERE br.type = 'purchase'
                                   AND p.status = 'completed'
                                   AND br.request_date BETWEEN '$startDate' AND '$endDate'
                                 GROUP BY b.id
                                 ORDER BY total_purchases DESC
                                 LIMIT 100",
                                "الكتب الأكثر شراء",
                                ["ID", "العنوان", "عدد المبيعات"]
                            );
                            break;
                    case 'most_active_users':
                        displayTable(
                            $conn,
                            "SELECT u.id, u.name, COUNT(br.id) AS total_requests 
                            FROM borrow_requests br
                            JOIN users u ON br.user_id = u.id
                            WHERE br.request_date BETWEEN '$startDate' AND '$endDate'
                            GROUP BY u.id
                            ORDER BY total_requests DESC
                            LIMIT 10",
                            "المستخدمين الأكثر طلباً",
                            ["ID", "الاسم", "عدد الطلبات"]
                        );
                        break;
                }
                
                ?>
            </div>
        </div>
    </div>
</div>

<script>
// البحث الفوري في الجدول
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('liveSearch');

    searchInput.addEventListener('input', function(e) {
        const term = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('tbody tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(term) ? '' : 'none';
        });
    });
});

// حدث النقر لعرض التفاصيل

document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('click', function(e) {
        const target = e.target;
        if (target.classList.contains('request-badge')) {
            const bookId = target.dataset.book;
            
            fetch(`get_borrowers.php?book_id=${bookId}`)
                .then(response => response.json())
                .then(data => {
                    const detailsHtml = `
                        <div class="request-details">
                            <h6 class="mb-3">تفاصيل الطلبات</h6>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>المستخدم</th>
                                            <th>التاريخ</th>
                                            <th>الحالة</th>
                                            <th>النوع</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${data.map(req => `
                                            <tr>
                                                <td>${req.user_name}</td>
                                                <td>${req.request_date}</td>
                                                <td>${req.status}</td>
                                                <td>${req.type}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    `;
                    
                    Swal.fire({
                        title: 'تفاصيل طلبات الكتاب',
                        html: detailsHtml,
                        width: '800px',
                        confirmButtonText: 'إغلاق'
                    });
                })
                .catch(error => {
                    Swal.fire('خطأ', 'تعذر تحميل البيانات', 'error');
                });
        }
        if (target.classList.contains('purchase-badge')) {
            const bookId = target.dataset.book;
            
            fetch(`get_purchase_details.php?book_id=${bookId}`)
                .then(response => response.json())
                .then(data => {
                    const detailsHtml = `
                        <div class="purchase-details">
                            <h6 class="mb-3">تفاصيل المشتريات</h6>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>المشتري</th>
                                            <th>تاريخ الشراء</th>
                                            <th>المبلغ</th>
                                            <th>الحالة</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${data.map(purchase => `
                                            <tr>
                                                <td>${purchase.user_name}</td>
                                                <td>${purchase.payment_date}</td>
                                                <td>${purchase.amount} ل.س</td>
                                                <td>${purchase.status}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    `;
                    
                    Swal.fire({
                        title: 'تفاصيل المشتريات',
                        html: detailsHtml,
                        width: '800px',
                        confirmButtonText: 'إغلاق'
                    });
                })
                .catch(error => {
                    Swal.fire('خطأ', 'تعذر تحميل البيانات', 'error');
                });
        }
    });
});


</script>

<?php include __DIR__ . '/../includes/footer.php';?>