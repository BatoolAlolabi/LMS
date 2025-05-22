<?php
session_start();
require __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// التحقق من وجود الرمز الفريد للمجموعة
if (!isset($_GET['code'])) {
    header("Location: /Forum/manage_groups.php");
    exit();
}

$code = $_GET['code'];
$user_id = $_SESSION['user_id'];

// جلب بيانات المجموعة باستخدام الرمز الفريد
$stmt = $conn->prepare("
    SELECT g.group_id, g.owner_id 
    FROM users_groups g 
    WHERE g.unique_code = ?
");
$stmt->bind_param("s", $code);
$stmt->execute();
$group = $stmt->get_result()->fetch_assoc();

if (!$group) {
    die("المجموعة غير موجودة!");
}

// التحقق من عضوية المستخدم في المجموعة
$stmt = $conn->prepare("
    SELECT * FROM group_members 
    WHERE group_id = ? AND user_id = ?
");
$stmt->bind_param("ii", $group['group_id'], $user_id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    die("ليس لديك صلاحية الوصول!");
}

// إذا كان المستخدم هو المالك، معالجة إضافة الكتاب
$isOwner = ($user_id == $group['owner_id']);
if ($isOwner && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_link'])) {
    $bookLink = $_POST['book_link'];
    
    $stmt = $conn->prepare("
        INSERT INTO group_books (group_id, book_link, added_by) 
        VALUES (?, ?, ?)
    ");
    $stmt->bind_param("isi", $group['group_id'], $bookLink, $user_id);
    $stmt->execute();
    $_SESSION['message'] = "تم إضافة الكتاب بنجاح!";
    header("Location: /Forum/group_books.php?code=" . $code);
    exit();
}

// جلب جميع كتب المجموعة
$stmt = $conn->prepare("
    SELECT b.book_link, u.name as added_by, b.added_at 
    FROM group_books b
    JOIN users u ON b.added_by = u.id
    WHERE b.group_id = ?
");
$stmt->bind_param("i", $group['group_id']);
$stmt->execute();
$books = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <h3>
        كتب المجموعة
        <a href="<?= BASE_URL ?>Forum/manage_groups.php" class="btn btn-secondary btn-sm float-left">العودة للمجموعات</a>
    
    </h3>
    
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success"><?= $_SESSION['message'] ?></div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <!-- نموذج إضافة كتاب (للمالك فقط) -->
    <?php if ($isOwner): ?>
    <form method="POST" class="mb-4">
        <div class="input-group">
            <input type="url" name="book_link" class="form-control" placeholder="رابط الكتاب" required>
            <button type="submit" class="btn btn-primary">إضافة كتاب</button>
        </div>
    </form>
    <?php endif; ?>

    <!-- جدول الكتب -->
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>الرابط</th>
                <th>أضيف بواسطة</th>
                <th>تاريخ الإضافة</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($books as $book): ?>
            <tr>
                <td>
                    <a href="<?= htmlspecialchars($book['book_link']) ?>" target="_blank">
                        <?= htmlspecialchars($book['book_link']) ?>
                    </a>
                </td>
                <td><?= htmlspecialchars($book['added_by']) ?></td>
                <td><?= $book['added_at'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>