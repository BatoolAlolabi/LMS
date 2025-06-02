<?php
// payment_logs.php - سجلات الدفع

require __DIR__ . '/../includes/config.php';

// جلب سجلات الدفع من قاعدة البيانات
$payments = $conn->query("
    SELECT * FROM payments 
    ORDER BY payment_date DESC
");
?>

<div class="table-responsive">
    <table class="table table-bordered table-hover">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>المستخدم</th>
                <th>المبلغ</th>
                <th>التاريخ</th>
                <th>الحالة</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($payment = $payments->fetch_assoc()): ?>
            <tr>
                <td><?= $payment['payment_id'] ?></td>
                <td><?= htmlspecialchars($payment['user_id']) ?></td>
                <td><?= $payment['amount'] ?> ر.س</td>
                <td><?= date('Y-m-d H:i', strtotime($payment['payment_date'])) ?></td>
                <td>
                    <span class="badge bg-success">تم الدفع</span>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>