<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/functions.php';

// التحقق من صحة الجلسة
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    redirect(BASE_URL . 'login.php');
}

// تعيين المبلغ الافتراضي
$amount = isset($_POST['required_amount']) ? (float)$_POST['required_amount'] : 25000;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // التحقق من CSRF token
        if (!verify_csrf_token($_POST['csrf_token'])) {
            throw new Exception('طلب غير مصرح به');
        }

        // التحقق من الحقول المطلوبة
        $required_fields = ['card_number', 'expiry', 'cvv'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("يرجى تعبئة جميع الحقول");
            }
        }

        // ━━━━━━━━━━ محاكاة الدفع ━━━━━━━━━━
        $payment_data = [
            'card_number' => sanitize_input($_POST['card_number']),
            'expiry'      => sanitize_input($_POST['expiry']),
            'cvv'         => sanitize_input($_POST['cvv']),
            'amount'      => $amount
        ];

        if (!mock_payment_gateway($payment_data)) {
            throw new Exception("فشلت عملية الدفع");
        }

        // ━━━━━━━━━━ بدء المعاملة ━━━━━━━━━━
        $conn->begin_transaction();

        try {
            // 1. تحديث جدول المحافظ
            $stmt_wallet = $conn->prepare("
                INSERT INTO wallets (user_id, balance)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE balance = balance + ?
            ");
            if (!$stmt_wallet) {
                throw new Exception("خطأ في إعداد استعلام المحفظة: " . $conn->error);
            }
            $stmt_wallet->bind_param("idd", $_SESSION['user_id'], $amount, $amount);
            $stmt_wallet->execute();

            // 2. تسجيل العملية في المدفوعات
            $transaction_id = 'TRX_' . bin2hex(random_bytes(8)); // معرف فريد
            $stmt_payment = $conn->prepare("
                INSERT INTO payments (
                    user_id,
                    amount,
                    status,
                    payment_date,
                    transaction_id,
                    payment_type
                ) VALUES (?, ?, 'completed', NOW(), ?,'topup')
            ");
            $_SESSION['sucsess'] = "تم الدفع " ;

           
            if (!$stmt_payment) {
                $_SESSION['error'] = "خطأ في إعداد استعلام الدفع " ;
                header("Location:user/dashboard.php"); // أو الصفحة الحالية
                exit();
            }
            $stmt_payment->bind_param("ids", $_SESSION['user_id'], $amount, $transaction_id);
            $stmt_payment->execute();

            // تأكيد العملية
            $conn->commit();

            // إرسال الإشعار
            send_notification(
                $_SESSION['user_id'],
                "تم شحن " . number_format($amount, 2) . " ليرة بنجاح",
                BASE_URL . 'user/dashboard.php'
            );

            set_success("تمت عملية الدفع بنجاح!");
            redirect(BASE_URL . 'user/dashboard.php');

        } catch (Exception $e) {
            $conn->rollback();
            throw new Exception("فشل في العملية: " . $e->getMessage());
        }

    } catch (Exception $e) {
        set_error($e->getMessage());
        redirect(BASE_URL . 'payment.php');
    }
}

// ━━━━━━━━━━ عرض واجهة الدفع ━━━━━━━━━━
require __DIR__ . '/includes/header.php';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إكمال عملية الدفع</title>
    <link href="<?= BASE_URL ?>assets/css/bootstrap.min.css" rel="stylesheet>
    <style>
        .payment-card {
            max-width: 500px;
            margin: 50px auto;
            border-radius: 15px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card payment-card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0 text-center">
                    <i class="fas fa-wallet"></i>
                    إكمال عملية الدفع
                </h4>
            </div>
            
            <div class="card-body">
                <div class="alert alert-info text-center">
                    <h5>المبلغ المطلوب: <?= number_format($amount, 2) ?> ل.س</h5>
                </div>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                    <input type="hidden" name="required_amount" value="<?= $amount ?>">

                    <div class="mb-3">
                        <label>رقم البطاقة</label>
                        <input type="text" 
                               class="form-control" 
                               name="card_number" 
                               placeholder="1234 5678 9012 3456" 
                               required
                               ">
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>تاريخ الانتهاء (MM/YY)</label>
                            <input type="text" 
                                   class="form-control" 
                                   name="expiry" 
                                   placeholder="01/25" 
                                   required
                                   pattern="(0[1-9]|1[0-2])\/\d{2}">
                        </div>
                        
                        <div class="col-md-6">
                            <label>رمز CVV</label>
                            <input type="text" 
                                   class="form-control" 
                                   name="cvv" 
                                   placeholder="123" 
                                   required
                                   pattern="\d{3}">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success w-100">
                        <i class="fas fa-check-circle"></i> تأكيد الدفع
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
document.querySelector('form').addEventListener('submit', function(e) {
    const expiryInput = document.querySelector('input[name="expiry"]');
    const expiryValue = expiryInput.value.trim();
    
    if (expiryValue) {
        const [month, year] = expiryValue.split('/');
        const fullYear = 2000 + parseInt(year, 10);
        const lastDay = new Date(fullYear, month, 0).getDate(); // آخر يوم في الشهر
        
        const expiryDate = new Date(fullYear, month - 1, lastDay);
        const currentDate = new Date();
        
        if (expiryDate < currentDate) {
            e.preventDefault();
            alert('بطاقة منتهية الصلاحية. يرجى استخدام بطاقة صالحة');
            expiryInput.focus();
        }
    }
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>