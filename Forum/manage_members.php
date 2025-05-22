<?php 
session_start(); 
require __DIR__ . '/../includes/config.php'; // التحقق من وجود 
if (!isset($_GET['group_id'])) { 
    header("Location: manage_groups.php");
 exit(); 
} 
$group_id = intval($_GET['group_id']); // التحقق من أن المستخدم هو مالك المجموعة$
$stmt = $conn->prepare("SELECT owner_id FROM users_groups WHERE group_id = ?");
$stmt->bind_param("i", $group_id); 
$stmt->execute(); 
$result = $stmt->get_result()->fetch_assoc(); 
if (!$result || $result['owner_id'] != $_SESSION['user_id']) {
     die("<div class='alert alert-danger m-3'>ليس لديك صلاحية لإدارة هذه المجموعة!</div>"); 
    } 
    // معالجة حذف العضو 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_member'])){ 
    $user_id = intval($_POST['user_id']); // منع حذف المالك 
    if ($user_id != $_SESSION['user_id']) { 
        $stmt = $conn->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $group_id, $user_id);
        $stmt->execute(); 
        $_SESSION['message'] = "تم إزالة العضو بنجاح!"; }
    header("Location: manage_members.php?group_id=" . $group_id); exit();
 } 
 // جلب أعضاء المجموعة 
    $stmt = $conn->prepare(" SELECT u.id, u.name FROM group_members gm JOIN users u ON gm.user_id = u.id WHERE gm.group_id = ? "); 
    $stmt->bind_param("i", $group_id); $stmt->execute();
    $members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    require __DIR__ . '/../includes/header.php'; ?>
<div class="container mt-4">
    <h3 class="mb-4"> إدارة أعضاء المجموعة <a href="manage_groups.php"
            class="btn btn-secondary btn-sm float-left">العودة للمجموعات</a> </h3>

    <?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-success"><?= $_SESSION['message'] ?></div>
    <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>اسم العضو</th>
                <th>الإجراء</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($members as $member): ?>
            <tr>
                <td><?= htmlspecialchars($member['name']) ?></td>
                <td>
                    <?php if ($member['id'] != $_SESSION['user_id']): ?>
                    <form method="POST" onsubmit="return confirm('هل أنت متأكد من إزالة هذا العضو؟');">
                        <input type="hidden" name="user_id" value="<?= $member['id'] ?>">
                        <button type="submit" name="remove_member" class="btn btn-danger btn-sm">
                            <i class="fas fa-trash"></i> إزالة
                        </button>
                    </form>
                    <?php else: ?>
                    <span class="text-muted">مالك المجموعة</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div><?php require __DIR__ . '/../includes/footer.php'; ?>