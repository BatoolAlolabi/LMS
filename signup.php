<!-- تسجيل جديد -->

<div style="background:rgb(19, 180, 212);
           position:absolute; 
            border: 5px solid;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            padding: 10px;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <form method="get" action="fun/add_user.php">
            <h2>تسجيل جديد</h2>
            <div class="form-group">
                <input type="text" name="name" placeholder="الاسم الكامل" required>
            </div>
            <div class="form-group">
                <input type="email" name="email" placeholder="البريد الإلكتروني" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="كلمة المرور" required>
            </div>
            <div class="form-group">
                <input type="password" name="password_rep" placeholder="تأكيد كلمة المرور" required>
            </div>
            <button type="submit" name="signup" class="btn primary-btn">تسجيل</button>
        </form>
    </div>
</div>