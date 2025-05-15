<?php
require __DIR__ . '/includes/config.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// بدء معاملة لضمان سلامة البيانات
$conn->begin_transaction();

// جلب إعدادات الغرامات من قاعدة البيانات
$settings = [];
$result = $conn->query("SELECT name,value FROM settings WHERE name IN ('late_fee', 'borrow_fee', 'purchase_price')");
while ($row = $result->fetch_assoc()) {
    $settings[$row['name'] = $row['value']];
}

// التحقق من وجود جميع الإعدادات المطلوبة
if (!isset($settings['late_fee'])) {
    die("إعدادات الغرامات غير موجودة في قاعدة البيانات!");
}


try {
    // 1. جلب جميع الطلبات المتأخرة
    $sql = "
        SELECT 
            br.id,
            br.user_id,
            br.due_date,
            br.last_penalty_date,
            DATEDIFF(CURDATE(), br.due_date) AS total_days_overdue,
            DATEDIFF(CURDATE(), COALESCE(br.last_penalty_date, br.due_date)) AS new_days_overdue,
            w.balance
        FROM borrow_requests br
        JOIN wallets w ON br.user_id = w.user_id
        WHERE 
            br.status = 'approved'
            AND br.reading_completed = 0
            AND br.due_date < CURDATE()
            AND (br.last_penalty_date IS NULL OR br.last_penalty_date < CURDATE())
    ";

    $result = $conn->query($sql);

    // 2. الحصول على حساب المدير (افترض أن user_id الخاص بالمدير هو 1)
    $admin_id = 5; // يمكن تغيير هذا الرقم حسب إعداداتك
    $stmt = $conn->prepare("SELECT balance FROM wallets WHERE user_id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $admin_wallet = $stmt->get_result()->fetch_assoc();

    if (!$admin_wallet) {
        throw new Exception("حساب المدير غير موجود!");
    }

    foreach ($result as $row) {
        $days_overdue = $row['new_days_overdue'];
        if ($days_overdue <= 0) continue;

        $penalty_amount = $days_overdue * $settings['late_fee'];
        $user_id = $row['user_id'];
        $current_balance = $row['balance'];

        // 3. التحقق من وجود رصيد كافي لدى المستخدم
        if ($current_balance < $penalty_amount) {
            error_log("رصيد غير كافي للمستخدم $user_id. الرصيد الحالي: $current_balance, المطلوب: $penalty_amount");
            continue;
        }

        // 4. خصم المبلغ من المستخدم
        $stmt = $conn->prepare("
            UPDATE wallets 
            SET balance = balance - ? 
            WHERE user_id = ?
        ");
        $stmt->bind_param("di", $penalty_amount, $user_id);
        $stmt->execute();

        // 5. إضافة المبلغ إلى حساب المدير
        $stmt = $conn->prepare("
            UPDATE wallets 
            SET balance = balance + ? 
            WHERE user_id = ?
        ");
        $stmt->bind_param("di", $penalty_amount, $admin_id);
        $stmt->execute();

        // 6. تسجيل الغرامة في جدول المدفوعات
        $transaction_id = 'TRX_' . bin2hex(random_bytes(8));
        $stmt = $conn->prepare("
            INSERT INTO payments 
                (user_id, request_id, payment_type, amount, payment_date, status, transaction_id) 
            VALUES 
                (?, ?, 'penalty', ?, NOW(), 'completed', ?)
        ");
        $stmt->bind_param("iids", $user_id, $row['id'], $penalty_amount, $transaction_id);
        $stmt->execute();

        // 7. تحديث طلب الاستعارة
        $stmt = $conn->prepare("
            UPDATE borrow_requests
            SET 
                last_penalty_date = CURDATE(),
                total_penalty = total_penalty + ?
            WHERE id = ?
        ");
        $stmt->bind_param("di", $penalty_amount, $row['id']);
        $stmt->execute();
    }

    // تأكيد المعاملة
    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    die("Error: " . $e->getMessage());
}

$conn->close();