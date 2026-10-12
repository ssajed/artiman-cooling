<?php
require_once 'config.php';
if (!isLoggedIn()) redirect('index.php');
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>داشبورد - آرتیمان</title>
    <link rel="stylesheet" href="static/css/style.css">
</head>
<body>
    <div class="container">
        <?php include 'templates/header.php'; ?>

        <div class="content card">
            <h2 style="text-align:center; color:#2e7d32; margin:40px 0;">انتخاب بخش</h2>

            <div class="form-row" style="justify-content:center; gap:40px; margin:50px 0;">
                <a href="design.php" style="text-decoration:none; flex:1; max-width:450px;">
                    <button class="dashboard-btn design-btn">
                        🏗️ طراحی نام برج خنک‌کننده
                    </button>
                </a>

                <a href="smart.php" style="text-decoration:none; flex:1; max-width:450px;">
                    <button class="dashboard-btn smart-btn">
                        🤖 سیستم هوشمند برج خنک‌کننده
                    </button>
                </a>
            </div>

            <?php if ($_SESSION['is_admin'] ?? false): ?>
            <div style="text-align:center; margin:30px 0;">
                <a href="admin_users.php" style="text-decoration:none;">
                    <button class="dashboard-btn" style="background:linear-gradient(135deg, #1a56db, #3b82f6); width:auto; padding:15px 40px; font-size:18px;">
                        👥 مدیریت کاربران
                    </button>
                </a>
            </div>
            <?php endif; ?>

            <div class="button-group" style="justify-content:center; margin-top:30px;">
                <a href="logout.php">
                    <button class="dashboard-logout">🚪 خروج از حساب</button>
                </a>
            </div>
        </div>

        <footer>
            <p>© 2026 آرتیمان - سیستم طراحی هوشمند</p>
        </footer>
    </div>
</body>
</html>