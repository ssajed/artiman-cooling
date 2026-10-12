<?php
require_once 'config.php';
if (!isLoggedIn() || !($_SESSION['is_admin'] ?? false)) redirect('dashboard.php');

$id = (int)($_GET['id'] ?? 0);
$user = $db->query("SELECT * FROM smart_users WHERE id = $id")->fetch_assoc();
if (!$user) redirect('admin_users.php');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    $password = $_POST['password'] ?? '';

    if ($password && strlen($password) < 4) {
        $error = 'رمز عبور باید حداقل 4 کاراکتر باشد.';
    } else {
        if ($password) {
            $stmt = $db->prepare("UPDATE smart_users SET fullname = ?, email = ?, is_admin = ?, password = ? WHERE id = ?");
            $stmt->bind_param("ssisi", $fullname, $email, $is_admin, $password, $id);
        } else {
            $stmt = $db->prepare("UPDATE smart_users SET fullname = ?, email = ?, is_admin = ? WHERE id = ?");
            $stmt->bind_param("ssii", $fullname, $email, $is_admin, $id);
        }
        if ($stmt->execute()) {
            $success = "✅ اطلاعات کاربر به‌روزرسانی شد.";
            $user = $db->query("SELECT * FROM smart_users WHERE id = $id")->fetch_assoc();
        } else {
            $error = 'خطا در به‌روزرسانی.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ویرایش کاربر</title>
    <link rel="stylesheet" href="static/css/style.css">
</head>
<body>
    <div class="container">
        <div class="glass-card">
            <div class="header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;">
                <div>
                    <h1>✏️ ویرایش کاربر</h1>
                    <p>تغییر اطلاعات کاربر <?= htmlspecialchars($user['username']) ?></p>
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
                        <label>👤 نام کاربری</label>
                        <input type="text" value="<?= htmlspecialchars($user['username']) ?>" disabled style="background:#f3f4f6;">
                    </div>
                    <div class="form-group">
                        <label>📛 نام و نام خانوادگی</label>
                        <input type="text" name="fullname" value="<?= htmlspecialchars($user['fullname'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>📧 ایمیل</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>🔒 رمز عبور جدید</label>
                        <input type="password" name="password" placeholder="برای تغییر رمز وارد کنید (حداقل 4 کاراکتر)">
                        <small style="color:#6c757d; font-size:12px;">در صورت تمایل به تغییر رمز، مقدار جدید را وارد کنید.</small>
                    </div>
                    <div class="form-group">
                        <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                            <input type="checkbox" name="is_admin" <?= $user['is_admin'] ? 'checked' : '' ?> style="width:20px; height:20px; accent-color:#2e7d32;">
                            👑 دسترسی مدیر سیستم
                        </label>
                    </div>
                    <div class="button-group">
                        <button type="submit" class="btn-smart">💾 ذخیره تغییرات</button>
                        <a href="admin_users.php" class="btn-secondary" style="text-decoration:none; display:inline-block; padding:14px 30px; border-radius:30px; background:#6c757d; color:white;">↩️ انصراف</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>