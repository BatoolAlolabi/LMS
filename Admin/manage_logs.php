<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/db_logger.php';

// التحقق من صلاحيات المدير
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: " . BASE_URL . "login.php");
    exit();
}

// ─── إعداد الترقيم ─────────────────────────────────────────────────
$records_per_page = 10; // عدد السجلات لكل صفحة
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max(1, $current_page);
$offset = ($current_page - 1) * $records_per_page;

try {
    $total_logs = DatabaseLogger::getTotalLogs();
    $total_pages = ceil($total_logs / $records_per_page);
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    $total_logs = 0;
    $total_pages = 1;
}

$logs = DatabaseLogger::readLogs($records_per_page, $offset);
?>


    <style>
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
        .bg-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/alerts.php'; ?>

    <div class="container-fluid py-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-gradient-primary text-white py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-clipboard-list me-2"></i> سجلات النشاطات
                </h5>
            </div>

            <div class="card-body p-0">
                <?php include __DIR__ . '/../includes/alerts.php'; ?>

                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="sticky-top bg-light">
                            <tr>
                                <th>التاريخ</th>
                                <th>نوع الحدث</th>
                                <th>المستخدم</th>
                                <th>التفاصيل</th>
                                <th>عنوان IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?= htmlspecialchars($log['created_at']) ?></td>
                                <td><?= htmlspecialchars($log['event_type']) ?></td>
                                <td><?= htmlspecialchars($log['user']) ?></td>
                                <td><?= htmlspecialchars($log['details']) ?></td>
                                <td><?= htmlspecialchars($log['ip_address']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- الترقيم -->
                <?php if ($total_pages > 1): ?>
                <div class="card-footer bg-light">
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center mb-0">
                            <?php if ($current_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $current_page - 1 ?>" aria-label="السابق">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= ($i == $current_page) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                            </li>
                            <?php endfor; ?>

                            <?php if ($current_page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $current_page + 1 ?>" aria-label="التالي">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

