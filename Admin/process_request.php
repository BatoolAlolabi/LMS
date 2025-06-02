<?php
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/admin_auth.php';
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("طلب غير مصرح به!");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = (int)$_POST['request_id'];
    $action = $_POST['action'];
    
    try {
            // ━━━━━━━━━━ الحصول على معرف المدير ━━━━━━━━━━
        $stmt_admin = $conn->prepare("SELECT id FROM users WHERE user_type = 'admin' LIMIT 1");
        $stmt_admin->execute();
        $admin_id = $stmt_admin->get_result()->fetch_assoc()['id'];
        
        if (!$admin_id) {
            throw new Exception("لم يتم العثور على حساب المدير!");
        }
        // ━━━━━━━━━━ خصم المبلغ بعد الموافقة ━━━━━━━━━━

            // الحصول على user_id من جدول borrow_requests
        $stmt_user = $conn->prepare("SELECT user_id FROM borrow_requests WHERE id = ?");
        $stmt_user->bind_param("i", $request_id);
        $stmt_user->execute();
        $result = $stmt_user->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("طلب غير موجود!");
        }
        
        $user_id = $result->fetch_assoc()['user_id']; // user_id هنا يتم استخراج
        
            // الحصول على المبلغ من الطلب بدلًا من القيمة الثابتة
        $stmt_amount = $conn->prepare("SELECT amount FROM borrow_requests WHERE id = ?");
        $stmt_amount->bind_param("i", $request_id);
        $stmt_amount->execute();
        $amount = $stmt_amount->get_result()->fetch_assoc()['amount'];

        // الحصول على نوع العملية
        $stmt_type = $conn->prepare("SELECT type FROM borrow_requests WHERE id = ?");
        $stmt_type->bind_param("i", $request_id);
        $stmt_type->execute();
        $type = $stmt_type->get_result()->fetch_assoc()['type'];
        

        $stmt_book = $conn->prepare("SELECT book_id FROM borrow_requests WHERE id = ?");
        $stmt_book->bind_param("i", $request_id);
        $stmt_book->execute();
        $result = $stmt_book->get_result();
        $book_id = $result->fetch_assoc()['book_id']; // book_id هنا يتم استخراج
        
        // التحقق من الرصيد الحالي
        $stmt_balance = $conn->prepare("SELECT balance FROM wallets WHERE user_id = ?");
        $stmt_balance->bind_param("i", $user_id);
        $stmt_balance->execute();
        $balance = $stmt_balance->get_result()->fetch_assoc()['balance'];
        
        if ($balance < $amount) {
            throw new Exception("رصيد المستخدم غير كافٍ لإكمال العملية");
        }
        
        // تنفيذ الخصم
        $stmt_deduct = $conn->prepare("UPDATE wallets SET balance = balance - ? WHERE user_id = ?");
        $stmt_deduct->bind_param("di", $amount, $user_id);
        $stmt_deduct->execute();

        // ━━━━━━━━━━ إضافة المبلغ إلى رصيد المدير ━━━━━━━━━━
        $stmt_add_admin = $conn->prepare("
        INSERT INTO wallets (user_id, balance)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE balance = balance + ?
        ");
        $stmt_add_admin->bind_param("idd", $admin_id, $amount, $amount);
        $stmt_add_admin->execute();

        
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $conn->begin_transaction();
        
        $new_status = ($action === 'approve') ? 'approved' : 'rejected';
        
        if($type === 'borrow' ||$type === 'renew'){
            $loan_duration = 14;
        }else{ $loan_duration = 1000;}
        if ($action === 'approve') {
            // تحديث حالة الطلب إلى pending_payment
            $stmt = $conn->prepare("
                UPDATE borrow_requests 
                SET 
                    status = ?, 
                    processed_at = NOW(),
                    loan_duration = ?,
                    due_date = DATE_ADD(NOW(), INTERVAL ? DAY)
                WHERE id = ?
            ");
            $stmt->bind_param("siii", $new_status, $loan_duration, $loan_duration, $request_id);
            $stmt->execute();
            $exp_date = date('Y-m-d H:i:s', strtotime("+$loan_duration days"));

            // إضافة سجل دفع جديد مع التعديلات
            $transaction_id = 'TRX_' . bin2hex(random_bytes(8));
            $stmt_payment = $conn->prepare("
                INSERT INTO payments (
                    user_id,    
                    request_id, 
                    book_id,
                    amount, 
                    status,
                    payment_date,
                    payment_type,
                    transaction_id
                ) VALUES (?,?,?,?, 'completed', NOW(), ?,?)
            ");
            $stmt_payment->bind_param("siidss",$user_id, $request_id,$book_id, $amount,$type,$transaction_id); // 'd' لـ decimal
            $stmt_payment->execute();

            // الحصول على user_id من الطلب
            $stmt_user = $conn->prepare("SELECT user_id FROM borrow_requests WHERE id = ?");
            $stmt_user->bind_param("i", $request_id);
            $stmt_user->execute();
            $user_id = $stmt_user->get_result()->fetch_assoc()['user_id'];

            $stmt_book = $conn->prepare("
                SELECT b.title 
                FROM borrow_requests br
                JOIN books b ON br.book_id = b.id
                WHERE br.id = ?
            ");
            $stmt_book->bind_param("i", $request_id);
            $stmt_book->execute();
            $book_title = $stmt_book->get_result()->fetch_assoc()['title'];


            // إضافة إشعار للمستخدم
            $message = "يمكنك تصفح كتاب $book_title على الرابط التالي"; 
            $payment_link = BASE_URL . "read_book.php?request_id=" . $request_id;
            
            $stmt_notif = $conn->prepare("
                INSERT INTO notifications (user_id, message, link,request_id,expires_at)
                VALUES (?, ?, ?, ?,?)
            ");
            $stmt_notif->bind_param("issis", $user_id, $message, $payment_link,$request_id,$exp_date);
            $stmt_notif->execute();

        } else {
            // ... (نفس كود الرفض السابق)
        }

        $conn->commit();
        $_SESSION['success'] = "تم تحديث حالة الطلب بنجاح!";
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "خطأ: " . $e->getMessage();
    }
    
    header("Location: " . BASE_URL . "admin/dashboard.php");
    exit();
}