<?php
session_start();
require __DIR__ . '/../includes/config.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// معالجة إرسال الطلب
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_book'])) {
    try {
        $book_id = (int)$_POST['book_id'];
        $user_id = (int)$_SESSION['user_id'];

        // التحقق من توفر الكتاب
        $stmt = $conn->prepare("SELECT quantity FROM books WHERE id = ?");
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        $book = $stmt->get_result()->fetch_assoc();

        if ($book['quantity'] > 0) {
            $stmt = $conn->prepare("INSERT INTO borrow_requests (user_id, book_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $user_id, $book_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "تم إرسال طلب الاستعارة بنجاح!";
            }
        } else {
            $_SESSION['error'] = "الكتاب غير متوفر حاليًا!";
        }
    } catch (Exception $e) {
        error_log("Request Error: " . $e->getMessage());
        $_SESSION['error'] = "حدث خطأ أثناء إرسال الطلب";
    }
    header("Location: books.php");
    exit();
}