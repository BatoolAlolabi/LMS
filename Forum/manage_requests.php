<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/../includes/config.php';

// التحقق من وجود group_id في الرابط
$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : null;

// ------ تحقق الأمان: التأكد من أن المستخدم مالك المجموعة ------
if ($group_id) {
    $stmt = $conn->prepare("SELECT owner_id FROM users_groups WHERE group_id = ?");
    $stmt->bind_param("i", $group_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if (!$result || $result['owner_id'] != $_SESSION['user_id']) {
        die("<div class='alert alert-danger m-3'>ليس لديك صلاحية لإدارة هذه المجموعة!</div>");
    }
}

// ------ جلب الطلبات بناءً على group_id ------
$sql = "
    SELECT r.request_id, u.name, g.group_name 
    FROM join_requests r
    JOIN users u ON r.user_id = u.id
    JOIN users_groups g ON r.group_id = g.group_id
    WHERE g.owner_id = ? 
    AND r.status = 'pending'
";

// إضافة شرط التصفية إذا وُجد group_id
if ($group_id) {
    $sql .= " AND g.group_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $_SESSION['user_id'], $group_id);
} else {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['user_id']);
}

$stmt->execute();
$requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ------ معالجة طلبات الموافقة/الرفض ------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestId = intval($_POST['request_id']);
    $action = $_POST['action'];

    // تحديث حالة الطلب
    $stmt = $conn->prepare("UPDATE join_requests SET status = ? WHERE request_id = ?");
    $status = ($action === 'approve') ? 'approved' : 'rejected';
    $stmt->bind_param("si", $status, $requestId);
    $stmt->execute();

    // إذا تمت الموافقة: إضافة العضو للمجموعة
    if ($action === 'approve') {
        $requestStmt = $conn->prepare("SELECT group_id, user_id FROM join_requests WHERE request_id = ?");
        $requestStmt->bind_param("i", $requestId);
        $requestStmt->execute();
        $requestData = $requestStmt->get_result()->fetch_assoc();

        $insertStmt = $conn->prepare("INSERT INTO group_members (group_id, user_id) VALUES (?, ?)");
        $insertStmt->bind_param("ii", $requestData['group_id'], $requestData['user_id']);
        $insertStmt->execute();
    }

    header("Location: " . BASE_URL . "Forum/manage_requests.php" . ($group_id ? "?group_id=$group_id" : ""));
    exit();
}

require __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <h3 class="mb-4">
        <?= $group_id ? "طلبات انضمام للمجموعة" : "جميع طلبات الانضمام المعلقة" ?>
        <a href="manage_groups.php" class="btn btn-secondary btn-sm float-left">العودة للمجموعات</a>
    </h3>

    <?php if (empty($requests)): ?>
        <div class="alert alert-info">لا توجد طلبات جديدة</div>
    <?php else: ?>
        <table class="table table-bordered table-hover">
            <thead class="bg-light">
                <tr>
                    <th>اسم المستخدم</th>
                    <th>اسم المجموعة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $request): ?>
                <tr>
                    <td><?= htmlspecialchars($request['name']) ?></td>
                    <td><?= htmlspecialchars($request['group_name']) ?></td>
                    <td>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="request_id" value="<?= $request['request_id'] ?>">
                            <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">
                                <i class="fas fa-check"></i> موافقة
                            </button>
                            <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">
                                <i class="fas fa-times"></i> رفض
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>