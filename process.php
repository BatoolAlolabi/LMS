<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);  
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/db_logger.php';
//if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 // if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
   //    die("Invalid CSRF Token");
// }
//}
// إيقاف عرض الأخطاء للمستخدمين

// التحقق من صلاحيات الأدمن
function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

// ======== معالجة تسجيل المستخدم ========
if (isset($_POST['register'])) {
    // إعادة تعيين متغيرات الجلسة للتأكد من نظافتها
    unset($_SESSION['error']);
    unset($_SESSION['success']);

    try {
        // التحقق من البيانات الأساسية
        $name = $_POST['name'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        // ----------- التحقق من البريد الإلكتروني واسم المستخدم -----------
        $check_stmt = $conn->prepare("SELECT email, name FROM users WHERE email = ? OR name = ?");
        $check_stmt->bind_param("ss", $email, $name);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        if ($check_result->num_rows > 0) {
            $row = $check_result->fetch_assoc();
            if ($row['email'] === $email) {
                throw new Exception("البريد الإلكتروني مسجل مسبقًا");
            } elseif ($row['name'] === $name) {
                throw new Exception("اسم المستخدم مسجل مسبقًا");
            }
        }
        $check_stmt->close();

        // ----------- إضافة المستخدم -----------
        $insert_stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $insert_stmt->bind_param("sss", $name, $email, $password);
        
        if (!$insert_stmt->execute()) {
            throw new Exception("فشل في تسجيل المستخدم");
        }
        
        $user_id = $insert_stmt->insert_id;
        $insert_stmt->close();

        // ----------- إضافة التصنيفات المفضلة -----------
        if (isset($_POST['categories'])) {
            $category_stmt = $conn->prepare("INSERT INTO user_categories (user_id, category_id) VALUES (?, ?)");
            
            foreach ($_POST['categories'] as $category_id) {
                $category_id = (int)$category_id;
                $category_stmt->bind_param("ii", $user_id, $category_id);
                
                if (!$category_stmt->execute()) {
                    throw new Exception("فشل في إضافة التصنيفات");
                }
            }
            $category_stmt->close();
        }

        $_SESSION['success'] = "تم التسجيل بنجاح!";
        header("Location: login.php");
        exit();
    } catch (Exception $e) {
        // إغلاق أي statements مفتوحة في حالة الخطأ
        if (isset($check_stmt)) $check_stmt->close();
        if (isset($insert_stmt)) $insert_stmt->close();
        if (isset($category_stmt)) $category_stmt->close();
        
        $_SESSION['error'] = $e->getMessage();
        header("Location: register.php");
        exit();
    }
}

// ======== معالجة تسجيل الدخول ========
if (isset($_POST['login'])) {

    $name = $_POST['name'];
    $password = $_POST['password'];
    
    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE name = ?");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $_SESSION['erroruser'] = "اسم المستخدم غير موجود";
            throw new Exception("المستخدم غير موجود");
        }
                
        $user = $result->fetch_assoc();
        if ($user['status'] === 0) {
            $_SESSION['error'] = "الحساب غير مفعل, يرجى رفع شكوى و إعادة المحاولة بعد 24 ساعة ";
            throw new Exception("المستخدم غير مفعل");
        }
        
        if (!password_verify($password, $user['password'])) {
            $_SESSION['errorpass'] = "كلمة المرور خاطئة";
            throw new Exception("كلمة المرور خاطئة");
        }
        
        // تعيين بيانات الجلسة
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_type'] = $user['user_type'];

       // ━━━━━━━━━━ تفعيل تذكرني ━━━━━━━━━━
        if (isset($_POST['remember_me'])) {
            // توليد token فريد
            $token = bin2hex(random_bytes(64));
            $expiry = time() + 30 * 24 * 3600; // 30 يومًا

            // تخزين الtoken في قاعدة البيانات
            $update_stmt = $conn->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
            $update_stmt->bind_param("si", $token, $user['id']);
            $update_stmt->execute();
            $update_stmt->close();

            // تعيين الكوكي (Secure و HttpOnly)
            setcookie(
                'remember_me',
                $token,
                $expiry,
                '/',
                '',
                true, // Secure (يجب أن يكون HTTPS مفعل)
                true  // HttpOnly
            );
        }
        
        DatabaseLogger::log(
            'login_success',
            $user['name'],
            'تم تسجيل الدخول بنجاح'
        );
        
        header("Location: " . BASE_URL . ($user['user_type'] == 'admin' ? 'admin/dashboard.php' : 'user/dashboard.php'));
        
    } catch (Exception $e) {
        DatabaseLogger::log(
            'login_failed',
            $name,
            $e->getMessage()
        );
        
        
        header("Location:" . BASE_URL . "login.php");
        exit();
    }
   
}

// ======== معالجة نسيان كلمة المرور ========
if (isset($_POST['forget_password'])) {
    $email = $_POST['email'];

    try {
        // التحقق من وجود البريد الإلكتروني
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("البريد الإلكتروني غير مسجل");
        }

        $user = $result->fetch_assoc();
        $token = bin2hex(random_bytes(50)); // توليد رمز فريد
        $expires = date("Y-m-d H:i:s", strtotime("+1 hour")); // صلاحية ساعة

        // حفظ الرمز في قاعدة البيانات
        $conn->query("
            UPDATE users 
            SET 
                password_reset_token = '$token',
                password_reset_expires = '$expires' 
            WHERE id = {$user['id']}
        ");
       

        // إرسال البريد الإلكتروني (يجب استبدال هذا الجزء بآلية إرسال حقيقية)
        $reset_link = BASE_URL . "reset_password.php?token=$token";
        $_SESSION['reset_link'] = $reset_link; // حفظ الرابط في الجلسة
       

        // mail($email, "استعادة كلمة المرور", "الرجاء الضغط على الرابط: $reset_link");

        $_SESSION['success'] = "تم إرسال رابط الاستعادة إلى بريدك الإلكتروني";
        header("Location: " . BASE_URL . "forget_password_confirmation.php");
        

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: forget_password.php");
    }
    exit();
}

// ======== معالجة تعيين كلمة المرور ========
if (isset($_POST['reset_password'])) {
    $token = $_POST['token'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    try {
        if ($new_password !== $confirm_password) {
            throw new Exception("كلمتا المرور غير متطابقتين");
        }

        // التحقق من الرمز ومدى صلاحيته
        $stmt = $conn->prepare("
            SELECT id 
            FROM users 
            WHERE 
                password_reset_token = ? 
                AND password_reset_expires > NOW()
        ");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("الرابط غير صالح أو منتهي الصلاحية");
        }

        $user = $result->fetch_assoc();
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // تحديث كلمة المرور وإزالة الرمز
        $conn->query("
            UPDATE users 
            SET 
                password = '$hashed_password',
                password_reset_token = NULL,
                password_reset_expires = NULL 
            WHERE id = {$user['id']}
        ");

        $_SESSION['success'] = "تم تعيين كلمة المرور بنجاح!";
        header("Location: login.php");

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: reset_password.php?token=$token");
    }
    exit();
}

// ======== إضافة كتب (للمسؤولين فقط) ========
if (isset($_POST['add_book']) && isAdmin()) {
    try {
        // التحقق من البيانات
       
        $title = htmlspecialchars($_POST['title']);
        $author = htmlspecialchars($_POST['author']);
        $type = in_array($_POST['type'], ['physical', 'e-book']) ? $_POST['type'] : 'physical';
        $quantity = (int)$_POST['quantity'];
        $price = (float)$_POST['price'];
        $category_id = (int)$_POST['category_id'];
        $evaluation = (float)$_POST['evaluation'];
        $description = htmlspecialchars($_POST['description']);
        $material_type = htmlspecialchars($_POST['material_type']);
        $page_count = isset($_POST['page_count']) ? (int)$_POST['page_count'] : null;
        $publication_date = isset($_POST['publication_date']) ? $_POST['publication_date'] : null;
        $isbn = isset($_POST['isbn']) ? htmlspecialchars($_POST['isbn']) : null;

        
        // معالجة تحميل الصورة
        if (!isset($_FILES['cover_image']['error']) || $_FILES['cover_image']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('يجب اختيار صورة غلاف');
        }
        
        $upload_dir = 'assets/images/books/';
        $extension = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid() . '_' . date('YmdHis') . '.' . $extension;
        $target_path = $upload_dir . $new_filename;
        
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        if (!move_uploaded_file($_FILES['cover_image']['tmp_name'], $target_path)) {
            throw new Exception('فشل في حفظ الصورة');
        }

        // ━━━━━━━━━━ معالجة تحميل الملف (file_path) ━━━━━━━━━━
        if (!isset($_FILES['file_path']['error']) || $_FILES['file_path']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('يجب رفع ملف الكتاب');
        }
        
        $file_upload_dir = 'assets/files/'; // مجلد تخزين الملفات
        $file_extension = pathinfo($_FILES['file_path']['name'], PATHINFO_EXTENSION);
        $file_new_name = uniqid() . '_' . date('YmdHis') . '.' . $file_extension;
        $file_target_path = $file_upload_dir . $file_new_name;
        
        if (!is_dir($file_upload_dir)) mkdir($file_upload_dir, 0755, true);
        if (!move_uploaded_file($_FILES['file_path']['tmp_name'], $file_target_path)) {
            throw new Exception('فشل في حفظ الملف');
        }

        
        // إدخال البيانات
                $stmt = $conn->prepare("
            INSERT INTO books 
            (title, author, type, material_type, page_count, publication_date, isbn, quantity, price, cover_image, category_id, file_path, evaluation, description)   
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
                
        if (!$stmt) {
            throw new Exception("خطأ في إعداد الاستعلام: " . $conn->error);
        }
        
        $stmt->bind_param(
            "ssssisssisssds", // أنواع البيانات (s=string, i=integer, d=double)
            $title, 
            $author, 
            $type,
            $material_type,
            $page_count,
            $publication_date,
            $isbn,
            $quantity,
            $price,
            $target_path,
            $category_id,
            $file_target_path,
            $evaluation,
            $description
        );
        if ($stmt->execute()) {
            $_SESSION['success'] = "تمت إضافة الكتاب بنجاح!";
        } else {
            throw new Exception("فشل في إضافة الكتاب");
        }
        
        header("Location: " . BASE_URL . "admin/dashboard.php");
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: " . BASE_URL . "admin/manage_books.php");
    }
    exit();
}

// ======== معالجة تحديث الكتب (مع دعم الملفات) ========
if (isset($_POST['update_book']) && isAdmin()) {
    try {
        // التحقق من CSRF Token
        //if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
         //   throw new Exception('طلب غير مصرح به');
       // }
       

        // جلب البيانات الأساسية
        $book_id = (int)$_POST['book_id'];
        $title = htmlspecialchars($_POST['title']);
        $author = htmlspecialchars($_POST['author']);
        $type = in_array($_POST['type'], ['physical', 'e-book']) ? $_POST['type'] : 'physical';
        $quantity = (int)$_POST['quantity'];
        $price = (float)$_POST['price'];
        $category_id = (int)$_POST['category_id'];
        $evaluation = (float)$_POST['evaluation'];
        $description = htmlspecialchars($_POST['description']);
        $material_type = htmlspecialchars($_POST['material_type']);
        if (!in_array($material_type, ['كتاب', 'مجلة', 'جريدة'])) {
            throw new Exception("نوع المادة غير صحيح!");
        }
        $page_count = isset($_POST['page_count']) ? (int)$_POST['page_count'] : null;
        $publication_date = isset($_POST['publication_date']) ? $_POST['publication_date'] : null;
        $isbn = isset($_POST['isbn']) ? htmlspecialchars($_POST['isbn']) : null;

        // جلب البيانات الحالية
        $stmt = $conn->prepare("SELECT cover_image, file_path FROM books WHERE id = ?");
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        $current_data = $stmt->get_result()->fetch_assoc();

        // ━━━━━━━ معالجة صورة الغلاف ━━━━━━━
        $cover_image = $current_data['cover_image'];
        if (!empty($_FILES['cover_image']['name'])) {
            $upload_dir = 'assets/images/books/';
            $extension = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid() . '_' . date('YmdHis') . '.' . $extension;
            
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            if (!move_uploaded_file($_FILES['cover_image']['tmp_name'], $upload_dir . $new_filename)) {
                throw new Exception('فشل في حفظ الصورة');
            }
            $cover_image = $upload_dir . $new_filename;
        }

        // ━━━━━━━ معالجة ملف الكتاب ━━━━━━━
        $file_path = $current_data['file_path'];
        if (!empty($_FILES['file_path']['name'])) {
            $file_upload_dir = 'assets/files/';
            $file_extension = pathinfo($_FILES['file_path']['name'], PATHINFO_EXTENSION);
            $file_new_name = uniqid() . '_' . date('YmdHis') . '.' . $file_extension;
            
            if (!is_dir($file_upload_dir)) mkdir($file_upload_dir, 0755, true);
            if (!move_uploaded_file($_FILES['file_path']['tmp_name'], $file_upload_dir . $file_new_name)) {
                throw new Exception('فشل في حفظ الملف');
            }
            $file_path = $file_upload_dir . $file_new_name;
        }

        // تحديث البيانات في قاعدة البيانات
        $stmt = $conn->prepare("
            UPDATE books SET 
                title = ?, 
                author = ?, 
                type = ?, 
                quantity = ?, 
                price = ?, 
                category_id = ?,
                cover_image = ?,
                file_path = ?,
                description = ?,
                evaluation = ?,
                material_type = ?,
                page_count = ?,
                publication_date = ?,
                isbn = ? 
            WHERE id = ?
        ");

        if (!$stmt) {
            throw new Exception("❌ خطأ في إعداد الاستعلام: " . $conn->error);
        }

        $stmt->bind_param(
            "sssidissssssssi", 
            $title, 
            $author, 
            $type, 
            $quantity, 
            $price, 
            $category_id,
            $cover_image,
            $file_path,
            $description,
            $evaluation,
            $material_type,
            $page_count,
            $publication_date,
            $isbn,
            $book_id
        );

        if ($stmt->execute()) {
            $_SESSION['success'] = "✅ تم تحديث الكتاب بنجاح!";
            header("Location:" .BASE_URL."admin/dashboard.php?section=books");
        } else {
            throw new Exception("❌ فشل في التحديث: " . $stmt->error);
        }

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location:".BASE_URL."admin/edit_book.php?id=" . $book_id);
    }
    exit();
}
// ======== معالجة طلب الاستعارة/الشراء ========
if (isset($_POST['action'])) {
    try {
        // التحقق من CSRF Token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('طلب غير مصرح به');
        }
        $book_id = (int)$_POST['book_id'];

        // ━━━━━━━━━━ جلب سعر الكتاب من جدول books ━━━━━━━━━━
        $stmt_book = $conn->prepare("SELECT price FROM books WHERE id = ?");
        $stmt_book->bind_param("i", $book_id);
        $stmt_book->execute();
        $result_book = $stmt_book->get_result();
        
        if ($result_book->num_rows === 0) {
            throw new Exception("الكتاب غير موجود!");
        }
        
        $book_data = $result_book->fetch_assoc();
        $book_price = $book_data['price'];
        $stmt_book->close();

        // ━━━━━━━━━━ تحديد المبلغ المطلوب بناءً على نوع العملية ━━━━━━━━━━
        if ($action === 'borrow') {
            // جلب سعر الإعارة من الإعدادات إذا لزم الأمر
            $stmt_rental = $conn->prepare("SELECT value FROM settings WHERE name='purchase_price'");
            $stmt_rental->execute();
            $result_rental = $stmt_rental->get_result();
            $row_rental = $result_rental->fetch_assoc();
            $purchase_price = $row_rental['value'];
            $stmt_rental->close();
        } else {
            // استخدام سعر الكتاب للشراء
            $purchase_price = $book_price;
        }

        // جلب سعر الإعارة
        $stmt_rental = $conn->prepare("SELECT value FROM settings WHERE name='rental_price'");
        $stmt_rental->execute();
        $result_rental = $stmt_rental->get_result();
        $row_rental = $result_rental->fetch_assoc();
        $rental_price = $row_rental['value'];
        $stmt_rental->close();

        // جلب غرامة التأخير
        $stmt_late = $conn->prepare("SELECT value FROM settings WHERE name='late_fee'");
        $stmt_late->execute();
        $result_late = $stmt_late->get_result();
        $row_late = $result_late->fetch_assoc();
        $late_fee = $row_late['value'];
        $stmt_late->close();
        
        // تحديد نوع العملية والمبلغ المطلوب
        $action = $_POST['action'];
        $required_amount = ($action === 'borrow') ? $rental_price : $purchase_price;
        $book_id = (int)$_POST['book_id'];

        // ━━━━━━━━━━ التحقق من عدم وجود استعارة نشطة ━━━━━━━━━━
        if($action === 'borrow'){
        $check_borrow = $conn->prepare("
            SELECT id
            FROM borrow_requests 
            WHERE 
                user_id = ? 
                AND book_id = ? 
                AND status IN ('pending', 'approved')
                AND due_date > NOW() 
                AND reading_completed = 0
                
        ");
        $check_borrow->bind_param("ii", $_SESSION['user_id'], $book_id);
        $check_borrow->execute();
        $borrow_user=$_SESSION['user_id'];

        if ($check_borrow->get_result()->num_rows > 0) {
            $_SESSION['error'] = "لا يمكنك استعارة هذا الكتاب الآن. لديك استعارة نشطة!";
            header("Location:home.php"); // أو الصفحة الحالية
            exit();
        }}
        
        // التحقق من الرصيد
        $stmt_wallet = $conn->prepare("SELECT balance FROM wallets WHERE user_id = ?");
        $stmt_wallet->bind_param("i", $_SESSION['user_id']);
        $stmt_wallet->execute();
        $wallet = $stmt_wallet->get_result()->fetch_assoc();
        
        if ($wallet['balance'] < $required_amount) {
            $_SESSION['required_amount'] = $required_amount;
            $_SESSION['book_id'] = $book_id;
            $_SESSION['action'] = $action;
            header("Location: add_funds.php");
            exit();
        }
        
        // خصم المبلغ
        //$stmt_deduct = $conn->prepare("UPDATE wallets SET balance = balance - ? WHERE user_id = ?");
        //$stmt_deduct->bind_param("di", $required_amount, $_SESSION['user_id']);
       // $stmt_deduct->execute();
        
        // إرسال الطلب إلى المدير
        $stmt_request = $conn->prepare("INSERT INTO borrow_requests (user_id, book_id, type, amount) VALUES (?, ?, ?, ?)");
        $stmt_request->bind_param("iisd", $_SESSION['user_id'], $book_id, $action, $required_amount);
        $stmt_request->execute();
        $request_id = $stmt_request->insert_id; // الحصول على ID الطلب الجديد
        // إرسال إشعار إلى المدير
        $admin = $conn->query("SELECT id FROM users WHERE user_type = 'admin' LIMIT 1")->fetch_assoc();
        if ($admin) {
            $message = "طلب جديد: " . ($action === 'borrow' ? "استعارة" : "شراء") . " كتاب";
            $link = BASE_URL . "/admin/dashboard.php?section=ops";
        
            $stmt_notif = $conn->prepare("
                INSERT INTO notifications 
                (user_id, message, link, request_id, expires_at) 
                VALUES (?, ?, ?, ?, NOW() + INTERVAL 24 HOUR)
            ");
            $stmt_notif->bind_param("issi", $admin['id'], $message, $link, $request_id); // إضافة request_id
            $stmt_notif->execute();
        }
        $_SESSION['success'] = "تم ارسال الطلب بنجاح , يمكنك متابعة الطلب في قسم الطلبات العالقة في لوحة التحكم الخاصة بك";
        header("Location:home.php");

        DatabaseLogger::log(
            'loan_success',
            $borrow_user,
            'تم طلب الاستعارة بنجاح'
        );
        
    } catch (Exception $e) {
        $_SESSION['error'] = "خطأ: " . $e->getMessage();
        header("Location: index.php");
    }
    exit();
}

// ======== معالجة تجديد الاستعارة ========
if (isset($_POST['actions']) && $_POST['actions'] === 'renew') {
    try {
         // التحقق من CSRF Token
         if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('طلب غير مصرح به');
        }
        $actions = $_POST['actions'];
        $request_id = (int)$_POST['request_id'];
        $user_id = $_SESSION['user_id'];

        // ━━━━━━━━━━ التحقق من صلاحية التجديد ━━━━━━━━━━
        $stmt_check = $conn->prepare("
            SELECT due_date, renewed 
            FROM borrow_requests 
            WHERE 
                id = ? 
                AND user_id = ? 
                AND status = 'approved'
                AND reading_completed = 0
        ");
        $stmt_check->bind_param("ii", $request_id, $user_id);
        $stmt_check->execute();
        $request_data = $stmt_check->get_result()->fetch_assoc();

        if (!$request_data) {
            throw new Exception("الطلب غير صالح للتجديد");
        }

        // التحقق من المدة المتبقية (يومين أو أقل)
        $due_date = new DateTime($request_data['due_date']);
        $today = new DateTime();
        $interval = $today->diff($due_date);
        $days_left = $interval->days;
        $is_passed = $due_date < $today;

        if ($days_left > 2 && !$is_passed) {
            throw new Exception("يمكن التجديد قبل يومين من انتهاء المدة فقط");
        }

        // التحقق من عدد التجديدات (مثال: 3 مرات كحد أقصى)
        if ($request_data['renewed'] >= 3) {
            throw new Exception("وصلت إلى الحد الأقصى للتجديد");
        }

        // ━━━━━━━━━━ التحقق من الرصيد ━━━━━━━━━━
        $renew_cost = 5000; // تكلفة التجديد
        $stmt_balance = $conn->prepare("SELECT balance FROM wallets WHERE user_id = ?");
        $stmt_balance->bind_param("i", $user_id);
        $stmt_balance->execute();
        $balance = $stmt_balance->get_result()->fetch_assoc()['balance'];

        if ($balance < $renew_cost) {
            $_SESSION['error'] = "رصيدك غير كافي لتجديد الاستعارة!";
            header("Location: read_book.php?request_id=" . $request_id);
            exit();
        }

        // ━━━━━━━━━━ إرسال إشعار للمدير ━━━━━━━━━━
        $admin_id = 5; // أو جلب id المدير من قاعدة البيانات
        $message = "طلب تجديد استعارة (الطلب #$request_id)";
        $link = "admin/renew_requests.php?request_id=$request_id";

        $stmt_notify = $conn->prepare("
            INSERT INTO notifications 
            (user_id, message, link, request_id) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt_notify->bind_param("issi", $admin_id, $message, $link, $request_id);
        $stmt_notify->execute();

        // ━━━━━━━━━━ تحديث حالة التجديد ━━━━━━━━━━
        $new_renewed = $request_data['renewed'] + 1;
                $stmt_renew = $conn->prepare("
            UPDATE borrow_requests 
            SET 
                renewed = ?,
                status = 'pending',
                processed_at = NOW(),
                due_date = NOW(),
                request_date=NOW(),
                loan_duration = 0,
                type=?
            WHERE id = ?
        ");
        $stmt_renew->bind_param("isi", $new_renewed, $actions,$request_id);
        $stmt_renew->execute();

        $_SESSION['success'] = "تم إرسال طلب التجديد لإدارة المكتبة!";
        header("Location: read_book.php?request_id=" . $request_id);
        exit();

    } catch (Exception $e) {
        $_SESSION['error'] = "خطأ: " . $e->getMessage();
        header("Location: read_book.php?request_id=" . $request_id);
        exit();
    }
}

// ======== معالجة حذف الطلب ========
if (isset($_POST['delete_request']) && isset($_SESSION['user_id'])) {
    // التحقق من CSRF Token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("طلب غير مصرح به");
    }

    $request_id = (int)$_POST['request_id'];
    $user_id = $_SESSION['user_id'];

    try {
        // التحقق من ملكية الطلب
        $stmt = $conn->prepare("DELETE FROM borrow_requests WHERE id = ? AND user_id = ? AND status = 'pending'");
        $stmt->bind_param("ii", $request_id, $user_id);
        
        if ($stmt->execute()) {
            // ━━━━━━━ حذف الإشعارات المرتبطة بالطلب ━━━━━━━
            $delete_notif = $conn->prepare("DELETE FROM notifications WHERE request_id = ?");
            $delete_notif->bind_param("i", $request_id);
            $delete_notif->execute();
            $delete_notif->close();
            $_SESSION['success'] = "تم حذف الطلب بنجاح!";
        } else {
            throw new Exception("فشل في حذف الطلب");
        }
        
        header("Location:".BASE_URL."user/dashboard.php");
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location:".BASE_URL."user/dashboard.php");
    }
    exit();
}

// ======== معالجة إرسال الشكوى ========
if (isset($_POST['submit_complaint'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $complaint = htmlspecialchars($_POST['complaint']);

    try {
        // التحقق من صحة البريد الإلكتروني
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("البريد الإلكتروني غير صالح");
        }

        // إدخال الشكوى في قاعدة البيانات
        $stmt = $conn->prepare("INSERT INTO complaints (email, complaint) VALUES (?, ?)");
        $stmt->bind_param("ss", $email, $complaint);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "تم إرسال الشكوى بنجاح!";
        } else {
            throw new Exception("فشل في إرسال الشكوى");
        }

        header("Location: index.php");
        exit();

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: complaint.php");
        exit();
    }
}
// ======== معالجة تمييز الشكوى كمحلولة ========
if (isset($_GET['resolve_complaint']) && isAdmin()) {
    $complaint_id = (int)$_GET['resolve_complaint'];
    $conn->query("UPDATE complaints SET status = 'resolved' WHERE id = $complaint_id");
    header("Location:" . BASE_URL . "admin/dashboard.php");
    exit();
}
// إذا لم يتم التعرف على أي عملية
die("طلب غير معروف");
?>