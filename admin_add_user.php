<?php
require_once 'config.php';
if (!isLoggedIn() || !($_SESSION['is_admin'] ?? false)) redirect('dashboard.php');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;

    if (!$username || !$password) {
        $error = 'نام کاربری و رمز عبور الزامی است.';
    } elseif (strlen($password) < 4) {
        $error = 'رمز عبور باید حداقل 4 کاراکتر باشد.';
    } else {
        $stmt = $db->prepare("SELECT id FROM smart_users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'این نام کاربری قبلاً ثبت شده است.';
        } else {
            $stmt = $db->prepare("INSERT INTO smart_users (username, password, fullname, email, is_admin) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $username, $password, $fullname, $email, $is_admin);
            if ($stmt->execute()) {
                $success = "✅ کاربر $username با موفقیت ایجاد شد.";
            } else {
                $error = 'خطا در ایجاد کاربر.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>افزودن کاربر</title>
    <link rel="stylesheet" href="static/css/style.css">
</head>
<body>
    <div class="container">
        <div class="glass-card">
            <div class="header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;">
                <div>
                    <h1>➕ افزودن کاربر جدید</h1>
                    <p>ایجاد حساب کاربری جدید در سیستم</p>
                </div>
                <div style="display:flex; gap:12px; flex-wrap:wrap;">
                    <a href="admin_users.php" style="text-decoration:none;"><button class="header-btn">↩️ بازگشت</button></a>
                    <a href="logout.php" style="text-decoration:none;"><button class="header-btn logout">🚪 خروج</button></a>
                </div>
            </div>
            <div class="form-container">
                <?php if ($error): ?>
                    <div class="alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div style="background:#dcfce7; color:#166534; padding:12px 20px; border-radius:12px; margin-bottom:20px;"><?= $success ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="form-group">
                        <label>👤 نام کاربری <span class="required-star">*</span></label>
                        <input type="text" name="username" required autofocus>
                    </div>
                    <div class="form-group">
                        <label>🔒 رمز عبور <span class="required-star">*</span></label>
                        <input type="password" name="password" required minlength="4">
                    </div>
                    <div class="form-group">
                        <label>📛 نام و نام خانوادگی</label>
                        <input type="text" name="fullname">
                    </div>
                    <div class="form-group">
                        <label>📧 ایمیل</label>
                        <input type="email" name="email">
                    </div>
                    <div class="form-group">
                        <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                            <input type="checkbox" name="is_admin" style="width:20px; height:20px; accent-color:#2e7d32;">
                            👑 دسترسی مدیر سیستم
                        </label>
                    </div>
                    <div class="button-group">
                        <button type="submit" class="btn-smart">✅ افزودن کاربر</button>
                        <a href="admin_users.php" class="btn-secondary" style="text-decoration:none; display:inline-block; padding:14px 30px; border-radius:30px; background:#6c757d; color:white;">↩️ انصراف</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>