<?php
session_start();
require __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['code'])) {
    $code = $_GET['code'];
    $user_id = $_SESSION['user_id'];

    // البحث عن المجموعة بالرمز الفريد
    $stmt = $conn->prepare("SELECT group_id FROM users_groups WHERE unique_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $group = $stmt->get_result()->fetch_assoc();

    if ($group) {
        // التحقق من عدم وجود المستخدم في المجموعة مسبقًا
        $stmt = $conn->prepare("SELECT * FROM group_members WHERE group_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $group['group_id'], $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            // إضافة المستخدم إلى المجموعة
            $stmt = $conn->prepare("INSERT INTO group_members (group_id, user_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $group['group_id'], $user_id);
            $stmt->execute();
            echo "تم الانضمام إلى المجموعة!";
        } else {
            echo "أنت بالفعل عضو في هذه المجموعة!";
        }
    } else {
        echo "الرمز غير صحيح!";
    }
}