<?php
// تمكين إعدادات التصحيح
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// بدء الجلسة والتحقق من الصلاحيات
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/includes/config.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    
    header("HTTP/1.1 403 Forbidden");
    exit(json_encode(['success' => false, 'error' => 'غير مصرح بالوصول']));
}

$notificationId = (int)$_GET['id'];
$userId = (int)$_SESSION['user_id'];


try {
    // التحقق من ملكية الإشعار
    $checkSql = "SELECT notification_id FROM notifications WHERE notification_id = ? AND user_id = ?";
    $checkStmt = $conn->prepare($checkSql);

    if ($checkStmt === false) {
        
        throw new Exception("فشل إعداد الاستعلام: " . htmlspecialchars($conn->error));
    }

    $checkStmt->bind_param("ii", $notificationId, $userId); // ربط user_id من الجلسة
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows === 0) {
        
        header("HTTP/1.1 404 Not Found");
        exit(json_encode(['success' => false, 'error' => 'الإشعار غير موجود أو غير مصرح']));
    }

    // تحديث حالة الإشعار
    $updateSql = "UPDATE notifications SET is_read = 1 WHERE notification_id = ?";
    $updateStmt = $conn->prepare($updateSql);

    if ($updateStmt === false) {
        
        throw new Exception("فشل إعداد استعلام التحديث: " . htmlspecialchars($conn->error));
    }

    $updateStmt->bind_param("i", $notificationId);
    $updateStmt->execute();

    if ($updateStmt->affected_rows === 0) {
        
        throw new Exception("لم يتم العثور على الإشعار أو تحديثه");
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log("Error in mark_read.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>