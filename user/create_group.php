<?php
session_start();
require __DIR__ . '/../includes/config.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $groupName = $_POST['group_name'];
    $ownerId = $_SESSION['user_id'];
    $uniqueCode = bin2hex(random_bytes(16)); // توليد رمز فريد

    // إدخال المجموعة في قاعدة البيانات
    $stmt = $conn->prepare("INSERT INTO users_groups (group_name, owner_id, unique_code) VALUES (?, ?, ?)");
    $stmt->bind_param("sis", $groupName, $ownerId, $uniqueCode);
    $stmt->execute();

    // إضافة المالك كعضو في المجموعة
    $group_id = $stmt->insert_id;
    $stmt = $conn->prepare("INSERT INTO group_members (group_id, user_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $group_id, $ownerId);
    $stmt->execute();

    // إنشاء الرابط
    $groupLink = "join_group.php?code=" . $uniqueCode;
    echo "تم إنشاء المجموعة! الرابط: " . $groupLink;
}
?>

<form method="POST">
    <input type="text" name="group_name" placeholder="اسم المجموعة" required>
    <button type="submit">إنشاء المجموعة</button>
</form>