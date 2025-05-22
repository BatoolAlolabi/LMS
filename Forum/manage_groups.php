<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
require __DIR__ . '/../includes/config.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'index.php');
    exit();
}
// دالة لجلب عدد أعضاء المجموعة
function get_member_count($group_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM group_members WHERE group_id = ?");
    $stmt->bind_param("i", $group_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['count'] ?? 0;
}
// دالة لجلب عدد كتب المجموعة
function get_book_count($group_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM group_books WHERE group_id = ?");
    $stmt->bind_param("i", $group_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['count'] ?? 0;
}
// ------ معالجة طلبات POST ------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // إنشاء مجموعة جديدة
    if (isset($_POST['group_name'])) {
        $groupName = $_POST['group_name'];
        $ownerId = $_SESSION['user_id'];
        $uniqueCode = bin2hex(random_bytes(16));

        $stmt = $conn->prepare("INSERT INTO users_groups (group_name, owner_id, unique_code) VALUES (?, ?, ?)");
        $stmt->bind_param("sis", $groupName, $ownerId, $uniqueCode);
        $stmt->execute();

        $group_id = $stmt->insert_id;
        $stmt = $conn->prepare("INSERT INTO group_members (group_id, user_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $group_id, $ownerId);
        $stmt->execute();

        $_SESSION['message'] = "تم إنشاء المجموعة! الرابط: " . BASE_URL . "Forum/join_group.php?code=" . $uniqueCode;
        header("Location: manage_groups.php");
        exit();
    }

    // طلب الانضمام إلى مجموعة
    if (isset($_POST['join_group'])) {
        $groupId = intval($_POST['group_id']);
        $userId = $_SESSION['user_id'];

        $stmt = $conn->prepare("INSERT INTO join_requests (group_id, user_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $groupId, $userId);
        $stmt->execute();
        $_SESSION['message'] = 'تم إرسال طلب الانضمام!';
        header("Location: manage_groups.php");
        exit();
    }

    // مغادرة مجموعة
    if (isset($_POST['leave_group'])) {
        $groupId = intval($_POST['group_id']);
        $userId = $_SESSION['user_id'];

        $stmt = $conn->prepare("SELECT owner_id FROM users_groups WHERE group_id = ?");
        $stmt->bind_param("i", $groupId);
        $stmt->execute();
        $ownerId = $stmt->get_result()->fetch_assoc()['owner_id'];

        if ($ownerId != $userId) {
            $stmt = $conn->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $groupId, $userId);
            $stmt->execute();
            $_SESSION['message'] = "تم مغادرة المجموعة بنجاح!";
        } else {
            $_SESSION['message'] = "لا يمكنك مغادرة المجموعة لأنك المالك!";
        }

        header("Location: manage_groups.php");
        exit();
    }
}
// ------ جلب البيانات ------
// جميع المجموعات
$stmt = $conn->prepare("SELECT group_id, group_name, owner_id, unique_code FROM users_groups");
$stmt->execute();
$allGroups = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
// مجموعات المستخدم الحالي
$userGroupsStmt = $conn->prepare("
    SELECT g.group_id 
    FROM group_members gm 
    JOIN users_groups g ON gm.group_id = g.group_id 
    WHERE gm.user_id = ?
");
$userGroupsStmt->bind_param("i", $_SESSION['user_id']);
$userGroupsStmt->execute();
$userGroups = $userGroupsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$userGroupIds = array_column($userGroups, 'group_id');

// الطلبات المعلقة
$pendingRequestsStmt = $conn->prepare("SELECT group_id FROM join_requests WHERE user_id = ? AND status = 'pending'");
$pendingRequestsStmt->bind_param("i", $_SESSION['user_id']);
$pendingRequestsStmt->execute();
$pendingRequests = $pendingRequestsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$pendingGroupIds = array_column($pendingRequests, 'group_id');
// ━━━━━━━━━━ معالجة حذف المجموعة ━━━━━━━━━━
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_group'])) {
    $groupId = (int)$_POST['group_id'];
    $userId = $_SESSION['user_id'];

    // التحقق من ملكية المجموعة
    $stmt = $conn->prepare("SELECT owner_id FROM users_groups WHERE group_id = ?");
    $stmt->bind_param("i", $groupId);
    $stmt->execute();
    $ownerId = $stmt->get_result()->fetch_assoc()['owner_id'];

    if ($ownerId == $userId) {
        try {
            // حذف المجموعة (مع ON DELETE CASCADE في قاعدة البيانات)
            $stmt = $conn->prepare("DELETE FROM users_groups WHERE group_id = ?");
            $stmt->bind_param("i", $groupId);
            $stmt->execute();

            $_SESSION['message'] = "تم حذف المجموعة بنجاح!";
        } catch (mysqli_sql_exception $e) {
            $_SESSION['error'] = "خطأ في الحذف: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "ليس لديك صلاحية حذف هذه المجموعة!";
    }

    header("Location: manage_groups.php");
    exit();
}

require __DIR__ . '/../includes/header.php';
?>
<?php if (isset($_SESSION['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <?= $_SESSION['error'] ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['message'])): ?>
<script>
Swal.fire({
    icon: 'success',
    title: 'مبروك.. !',
    text: '<?= $_SESSION['message'] ?>'
});
</script>
<?php unset($_SESSION['message']); ?>
<?php endif; ?>


<!-- عرض الرسائل -->

<div class="container py-4">
    <!-- رسائل التنبيه -->
    <?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-info alert-dismissible fade show">
        <?= $_SESSION['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <!-- زر إنشاء المجموعة مع مودال -->
    <div class="d-flex justify-content-between mb-4">
        <h3 class="text-dark"><i class="fas fa-users me-2"></i> المجموعات</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createGroupModal">
            <i class="fas fa-plus-circle me-2"></i>مجموعة جديدة
        </button>
    </div>




    <!-- بطاقات المجموعات -->
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php foreach ($allGroups as $group): 
            $isMember = in_array($group['group_id'], $userGroupIds);
            $isPending = in_array($group['group_id'], $pendingGroupIds);
            $isOwner = ($group['owner_id'] == $_SESSION['user_id']);
        ?>

        <div class="col">
            <div class="card shadow-sm h-100 border-0">
                <!-- رأس البطاقة -->
                <div class="card-header bg-gradient-primary text-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <!-- الجانب الأيسر: اسم المجموعة وأيقونة المالك -->
                        <div class="d-flex align-items-center">
                            <?php if ($isOwner): ?>
                            <small class="ms-2"><i class="fas fa-crown me-1 text-warning"></i></small>
                            <?php endif; ?>
                            <h5 class="card-title mb-0"><?= htmlspecialchars($group['group_name']) ?></h5>
                        </div>

                        <!-- الجانب الأيمن: زر الحذف -->
                        <?php if ($isOwner): ?>
                        <form method="POST" class="d-inline" onsubmit="return confirmDelete()">
                            <input type="hidden" name="delete_group" value="1">
                            <input type="hidden" name="group_id" value="<?= $group['group_id'] ?>">
                            <button type="submit" class="btn btn-link p-0">
                                <i class="fas fa-trash-alt text-warning"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- جسم البطاقة -->
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <div>
                            <i class="fas fa-users text-muted me-1"></i>
                            <span class="small"><?= get_member_count($group['group_id']) ?> أعضاء</span>
                        </div>
                        <div>
                            <i class="fas fa-book text-muted me-1"></i>
                            <span class="small"><?= get_book_count($group['group_id']) ?> كتب</span>
                        </div>
                    </div>

                    <!-- أزرار الإجراءات كأيقونات مع Tooltips -->
                    <div class="d-flex gap-2 justify-content-center">
                        <?php if ($isMember): ?>
                        <!-- أيقونة عرض الكتب -->
                        <a href="group_books.php?code=<?= $group['unique_code'] ?>"
                            class="btn btn-sm btn-outline-dark rounded-circle" data-bs-toggle="tooltip"
                            title="عرض الكتب">
                            <i class="fas fa-book-open"></i>
                        </a>

                        <?php if (!$isOwner): ?>
                        <!-- أيقونة مغادرة المجموعة -->
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="group_id" value="<?= $group['group_id'] ?>">
                            <button type="submit" name="leave_group" class="btn btn-sm btn-outline- rounded-circle"
                                data-bs-toggle="tooltip" title="مغادرة المجموعة">
                                <i class="fas fa-sign-out-alt"></i>
                            </button>
                        </form>
                        <?php endif; ?>

                        <?php elseif ($isPending): ?>
                        <!-- أيقونة انتظار الموافقة -->
                        <span class="btn btn-sm btn-dark rounded-circle" data-bs-toggle="tooltip"
                            title="طلب الانضمام قيد المراجعة">
                            <i class="fas fa-clock"></i>
                        </span>
                        <?php else: ?>
                        <!-- أيقونة الانضمام -->
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="group_id" value="<?= $group['group_id'] ?>">
                            <button type="submit" name="join_group" class="btn btn-sm btn-outline-dark rounded-circle"
                                data-bs-toggle="tooltip" title="الانضمام للمجموعة">
                                <i class="fas fa-sign-in-alt"></i>
                            </button>
                        </form>
                        <?php endif; ?>

                        <?php if ($isOwner): ?>
                        <!-- أيقونة إدارة الطلبات -->
                        <a href="manage_requests.php?group_id=<?= $group['group_id'] ?>"
                            class="btn btn-sm btn-outline-dark rounded-circle" data-bs-toggle="tooltip"
                            title="إدارة طلبات الانضمام">
                            <i class="fas fa-tasks"></i>
                        </a>

                        <!-- أيقونة إدارة الأعضاء -->
                        <a href="manage_members.php?group_id=<?= $group['group_id'] ?>"
                            class="btn btn-sm btn-outline-dark rounded-circle" data-bs-toggle="tooltip"
                            title="إدارة أعضاء المجموعة">
                            <i class="fas fa-users-cog"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>



</div>

<!-- مودال إنشاء المجموعة (بنفس تصميم personal.php) -->
<div class="modal fade" id="createGroupModal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>إنشاء مجموعة</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="text" name="group_name" class="form-control rounded-pill" placeholder="اسم المجموعة"
                        required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary rounded-pill">
                        <i class="fas fa-check me-1"></i>تأكيد
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- الأنماط المخصصة -->
<style>
.card {
    background: linear-gradient(to right, #f8f9fa, rgb(219, 224, 230));
    font-family: 'Cairo', sans-serif;
    border-radius: 15px;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}

.bg-gradient-primary {
    background: linear-gradient(135deg, rgb(8, 8, 8) 0%, rgb(0, 0, 1) 100%);
}

.rounded-pill {
    border-radius: 50px !important;
}

.btn.rounded-circle {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
}

.btn.rounded-circle i {
    font-size: 1.1rem;
}
</style>

<script>
function confirmDelete() {
    return confirm('هل أنت متأكد من حذف هذه المجموعة؟ سيتم حذف جميع البيانات المرتبطة بها!');
}
</script>
<script>
$(document).ready(function() {
    $('[data-bs-toggle="tooltip"]').tooltip();
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>