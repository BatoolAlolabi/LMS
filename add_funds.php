<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$rabi=50;
// ━━━━━━━━━━ جلب الرصيد الحالي ━━━━━━━━━━
$balance = 0.00;
try {
    $stmt = $conn->prepare("SELECT balance FROM wallets WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $wallet = $result->fetch_assoc();
    $balance = $wallet ? (float)$wallet['balance'] : 0.00;
} catch (Exception $e) {
    error_log("Error fetching balance: " . $e->getMessage());
}

// ━━━━━━━━━━ تحديد المبلغ المطلوب والرسالة ━━━━━━━━━━
$required_amount = $_SESSION['required_amount'] ?? 25000;
$action = $_SESSION['action'] ?? 'borrow';
$message = ($action === 'borrow') ? 'لإكمال الاستعارة' : 'لإكمال الشراء';
$funds=$required_amount - $balance;
$_SESSION['funds']=$funds;
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header text-white bg-warning">
                    <h4 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>رصيد غير كافي</h4>
                </div>
                <div class="card-body">
                    <!-- حالة عدم وجود رصيد مطلقًا -->
                    <?php if ($balance == 0): ?>
                    <div class="alert alert-dark">
                        <h5><i class="fas fa-wallet-slash me-2"></i>لا يوجد رصيد</h5>
                        <p class="mb-0">المحفظة فارغة. يرجى إضافة رصيد لاستخدام الخدمات.</p>
                    </div>
                    <?php else: ?>
                    <!-- حالة الرصيد غير كافي -->
                    <div class="alert alert-danger">
                        <h5>رصيدك الحالي: <?= number_format($balance, 2) ?> ل.س</h5>
                        <p class="mb-0">تحتاج أن يكون رصيدك <?= number_format($required_amount) ?> ليرة على الأقل
                            <?= $message ?>.</p>
                    </div>
                    <?php endif; ?>

                    <!-- زر الدفع -->
                    <form action="payment.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="required_amount" value="<?= $required_amount ?>">
                        <input type="hidden" name="funds" value="<?=  number_format($funds) ?>">
                        <input type="hidden" name="rabi" value="<?=  number_format($rabi) ?>">
                        <button type="submit" class="btn btn-success w-100" ">
                            <i class="fas fa-coins me-2" ></i>شحن <?= number_format($funds) ?> ليرة
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>