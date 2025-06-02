<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);


// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
// جلب بيانات المستخدم
$user_id = $_SESSION['user_id'];
$sql = "SELECT name, user_type FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
if (!$user) {
    die("المستخدم غير موجود");
}
// جلب رصيد المحفظة للمدير
$balance = 0.00;
if ($user['user_type'] === 'admin') {
    $wallet_sql = "SELECT balance FROM wallets WHERE user_id = ?";
    $wallet_stmt = $conn->prepare($wallet_sql);
    $wallet_stmt->bind_param("i", $user_id);
    $wallet_stmt->execute();
    $wallet_result = $wallet_stmt->get_result();
    $wallet = $wallet_result->fetch_assoc();
    $balance = $wallet ? (float)$wallet['balance'] : 0.00;
}
// جلب الإشعارات إذا كان مديرًا
$notifications = [];
if ($user['user_type'] === 'admin') {
    $notif_sql = "
        SELECT * 
        FROM notifications 
        WHERE user_id = ? 
        AND is_read = 0 
        AND (expires_at IS NULL OR expires_at > NOW())
        ORDER BY created_at DESC
    ";
    $notif_stmt = $conn->prepare($notif_sql);
    $notif_stmt->bind_param("i", $user_id);
    $notif_stmt->execute();
    $notifications = $notif_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
// ━━━━━━━━━━ جلب إحصائيات المستخدمين ━━━━━━━━━━
$stats = [
    'total_users' => 0,
    'online_users' => 0,
    'new_users_today' => 0
];
// عدد المستخدمين الكلي
$total_sql = "SELECT COUNT(*) AS total_users FROM users";
$total_result = $conn->query($total_sql);
if ($total_result) {
    $stats['total_users'] = $total_result->fetch_assoc()['total_users'];
}
// عدد المستخدمين النشطين (آخر 5 دقائق)
$online_sql = "SELECT COUNT(*) AS online_users FROM users 
               WHERE last_activity >= NOW() - INTERVAL 5 MINUTE";
$online_result = $conn->query($online_sql);
if ($online_result) {
    $stats['online_users'] = $online_result->fetch_assoc()['online_users'];
}
// عدد المستخدمين الجدد اليوم
$new_users_sql = "SELECT COUNT(*) AS new_users_today FROM users 
                  WHERE DATE(created_at) = CURDATE()";
$new_users_result = $conn->query($new_users_sql);
if ($new_users_result) {
    $stats['new_users_today'] = $new_users_result->fetch_assoc()['new_users_today'];
}
ob_end_flush();
?>
<div class="container mt-4">
    <!-- بطاقات الإحصائيات المحسنة -->
    <div class="row mb-4">
        <!-- بطاقة الرصيد للمدير -->
        <?php if ($user['user_type'] === 'admin'): ?>
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-gradient-primary text-white py-3">
                    <h5 class="card-title mb-0 fw-bold">
                        <i class="fas fa-wallet me-2"></i> رصيد المكتبة (ل.س)
                    </h5>
                </div>
                <div class="card-body text-center py-4">
                    <h2 class="display-6 mb-3 fw-bold ">
                        <?= number_format($balance, 2) ?> 
                    </h2>
                    
                </div>
                
            </div>
        </div>
        <?php endif; ?>

        <!-- بطاقة المستخدمين الكلي -->
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-gradient-info text-white py-3">
                    <h5 class="card-title mb-0 fw-bold">
                        <i class="fas fa-users me-2"></i> المستخدمين الكلي
                    </h5>
                </div>
                <div class="card-body text-center py-4">
                    <h2 class="display-5 mb-3 fw-bold text-primary"><?= $stats['total_users'] ?></h2>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-info" 
                             style="width: <?= min(100, ($stats['total_users'] / max(1, $stats['total_users'])) * 100) ?>%">
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light text-center py-2">
                    <small class="text-muted">
                        <i class="fas fa-user-plus me-1"></i> <?= $stats['new_users_today'] ?> جديد اليوم
                    </small>
                </div>
            </div>
        </div>

        <!-- بطاقة المستخدمين النشطين -->
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-gradient-success text-white py-3">
                    <h5 class="card-title mb-0 fw-bold">
                        <i class="fas fa-signal me-2"></i> النشطين الآن
                    </h5>
                </div>
                <div class="card-body text-center py-4">
                    <h2 class="display-5 mb-3 fw-bold text-success"><?= $stats['online_users'] ?></h2>
                    <div class="d-flex justify-content-center">
                        <span class="badge bg-success me-2">
                            <i class="fas fa-circle me-1"></i> نشيط
                        </span>
                        <span class="badge bg-secondary">
                            <i class="fas fa-circle me-1"></i> غير نشيط
                        </span>
                    </div>
                </div>
                <div class="card-footer bg-light text-center py-2">
                    <small class="text-muted">
                        آخر 5 دقائق
                    </small>
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
                <div class="list-group-item list-group-item-action">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="me-3">
                            <i class="fas fa-bell text-warning me-2"></i>
                            <span><?= htmlspecialchars($notif['message']) ?></span>
                        </div>
                        <small class="text-muted"><?= date('H:i', strtotime($notif['created_at'])) ?></small>
                    </div>
                    <?php if ($notif['link']): ?>
                    <div class="mt-2 text-end">
                        <a href="<?= $notif['link'] ?>" 
                            class="btn btn-sm btn-outline-primary"
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