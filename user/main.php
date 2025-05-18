<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
require __DIR__ . '/../includes/config.php';

// ━━━━━━━━━━ التحقق من الصلاحيات ━━━━━━━━━━
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: " . BASE_URL . "login.php");
    exit();
}

// ━━━━━━━━━━ جلب بيانات المحفظة ━━━━━━━━━━
$balance = 0.00;
try {
    $stmt = $conn->prepare("SELECT balance FROM wallets WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $wallet = $result->fetch_assoc();
    $balance = $wallet ? (float)$wallet['balance'] : 0.00;
} catch (Exception $e) {
    error_log("Wallet Error: " . $e->getMessage());
}
// ━━━━━━━━━━ جلب الإشعارات (غير المقروءة فقط) ━━━━━━━━━━
$notifications=[];
$stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

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


<div class="container mt-4">
    <!-- بطاقة الرصيد المحدثة -->
    <div class="row mb-4">
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-gradient-primary text-white py-3">
                    <h5 class="card-title mb-0 fw-bold">
                        <i class="fas fa-wallet me-2"></i> الرصيد
                    </h5>
                </div>

                <div class="card-body text-center py-4">
                    <?php if ($balance == 0.00): ?>
                    <div class="alert alert-warning py-2 mb-0 small d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                        <div>
                            <h5 class="alert-heading mb-1">لا يوجد رصيد متاح</h5>
                            <p class="mb-0">قم بإضافة رصيد لبدء استخدام الخدمات</p>
                        </div>
                    </div>
                    <?php else: ?>
                    <h2 class="display-5 mb-3 fw-bold <?= $balance < 5000 ? 'text-danger' : 'text-success' ?>">
                        <?= number_format($balance, 2) ?> ل.س
                    </h2>
                    <?php if ($balance < 5000): ?>
                    <div class="alert alert-danger py-2 mb-0 small">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        الرصيد الحالي لا يكفي للاستعارة (الحد الأدنى 5,000 ليرة)
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="card-footer bg-light text-center py-2">
                    <a href="<?= BASE_URL ?>payment.php" class="btn btn-sm btn-outline-primary rounded-pill">
                        <i class="fas fa-coins me-1"></i> شحن الرصيد
                    </a>
                </div>
            </div>
        </div>
    </div>


    <!-- قسم الإشعارات المحدث -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-dark text-white py-3">
            <h5 class="mb-0 fw-bold">
                <i class="fas fa-bell me-2"></i> الإشعارات الحديثة
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($notifications)): ?>
            <div class="alert alert-info m-4 d-flex align-items-center">
                <i class="fas fa-info-circle me-3 fa-2x"></i>
                <div>
                    <h5 class="alert-heading mb-1">لا توجد إشعارات جديدة</h5>
                    <p class="mb-0">سيظهر هنا أي إشعارات جديدة تتلقاها</p>
                </div>
            </div>
            <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($notifications as $notif): ?>
                <div class="list-group-item list-group-item-action" data-notif="<?= $notif['notification_id'] ?>">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="me-3">
                            <i class="fas fa-bell text-warning me-2"></i>
                            <span><?= htmlspecialchars($notif['message']) ?></span>
                        </div>
                        <small class="text-muted"><?= date('H:i', strtotime($notif['created_at'])) ?></small>
                    </div>
                    <?php if ($notif['link']): ?>
                    <div class="mt-2 text-end">
                        <a href="<?= $notif['link'] ?>" class="btn btn-sm btn-outline-primary"
                            onclick="markAsRead(<?= $notif['notification_id'] ?>, event)">
                            <i class="fas fa-external-link-alt me-1"></i> عرض التفاصيل
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>



<!-- الأنماط المخصصة -->
<!-- الأنماط الإضافية -->
<style>
.card {
    
    border-radius: 10px;
    overflow: hidden;
    border: none;
}

.card:hover {
   
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.bg-gradient-info {
    background: linear-gradient(135deg, #17ead9 0%, #6078ea 100%);
}

.bg-gradient-success {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.list-group-item {
    border-left: none;
    border-right: none;
    padding: 1.25rem 1.5rem;
}

.list-group-item:first-child {
    border-top: none;
}
</style>

<script>
function markAsRead(notifId, event) {
    event.preventDefault();
    console.log('Notification ID:', notifId); // <-- طباعة ID
    console.log('Request URL:', '/mark_read.php?id=' + notifId); // <-- طباعة المسار

    fetch('/autolibrary/mark_read.php?id=' + notifId)
        .then(response => {
            if (!response.ok) {
                throw new Error('فشل الاتصال بالخادم');
            }
            console.log('Response Status:', response.status); // <-- طباعة حالة الاستجابة
            return response.json();
        })
        .then(data => {
            console.log('Response Data:', data); // <-- طباعة البيانات
            if (data.success) {
                const notificationElement = document.querySelector(`[data-notif="${notifId}"]`);
                if (notificationElement) notificationElement.remove();
            }else {
                console.error('Error:', data.error);
            }
        })
        .catch(error => console.error('Error:', error));
       
}
</script>