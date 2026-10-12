<?php
require_once 'config.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'لطفاً یوزرنیم و پسورد را وارد کنید';
    } else {
        $stmt = $db->prepare("SELECT id, username, password, fullname, is_admin FROM smart_users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            // مقایسه مستقیم رمز (در تولید از password_hash استفاده کنید)
            if ($password === $row['password']) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['fullname'] = $row['fullname'];
                $_SESSION['is_admin'] = $row['is_admin'];
                redirect('dashboard.php');
            }
        }
        $error = 'یوزرنیم یا پسورد اشتباه است';
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود - آرتیمان</title>
    <link rel="stylesheet" href="static/css/style.css">
</head>
<body>
    <div class="login-page">
        <div class="login-card">
            <h2>ورود به سیستم آرتیمان</h2>
            <?php if ($error): ?>
                <div class="alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="form-group">
                    <label for="username">یوزرنیم</label>
                    <input type="text" name="username" id="username" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password">پسورد</label>
                    <input type="password" name="password" id="password" required>
                </div>
                <button type="submit" class="login-btn">ورود</button>
            </form>
            <p class="footer-text">© 2026 آرتیمان - سیستم طراحی هوشمند</p>
        </div>
    </div>
</body>
</html>