<?php
require_once 'config.php';
if (!isLoggedIn() || !($_SESSION['is_admin'] ?? false)) redirect('dashboard.php');

$users = $db->query("SELECT * FROM smart_users ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت کاربران</title>
    <link rel="stylesheet" href="static/css/style.css">
</head>
<body>
    <div class="container">
        <div class="glass-card">
            <div class="header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;">
                <div>
                    <h1>👥 مدیریت کاربران</h1>
                    <p>افزودن، ویرایش و حذف کاربران سیستم</p>
                </div>
                <div style="display:flex; gap:12px; flex-wrap:wrap;">
                    <a href="admin_add_user.php" style="text-decoration:none;"><button class="header-btn">➕ افزودن کاربر</button></a>
                    <a href="dashboard.php" style="text-decoration:none;"><button class="header-btn">📊 داشبورد</button></a>
                    <a href="logout.php" style="text-decoration:none;"><button class="header-btn logout">🚪 خروج</button></a>
                </div>
            </div>
            <div class="form-container">
                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse:collapse; text-align:right;">
                        <thead>
                            <tr style="background:#2e7d32; color:white;">
                                <th style="padding:12px;">شناسه</th>
                                <th style="padding:12px;">نام کاربری</th>
                                <th style="padding:12px;">نام کامل</th>
                                <th style="padding:12px;">ایمیل</th>
                                <th style="padding:12px;">نوع کاربر</th>
                                <th style="padding:12px;">عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = $users->fetch_assoc()): ?>
                            <tr style="border-bottom:1px solid #e5e7eb;">
                                <td style="padding:12px;"><?= $user['id'] ?></td>
                                <td style="padding:12px; font-weight:bold;"><?= htmlspecialchars($user['username']) ?></td>
                                <td style="padding:12px;"><?= htmlspecialchars($user['fullname'] ?? '—') ?></td>
                                <td style="padding:12px;"><?= htmlspecialchars($user['email'] ?? '—') ?></td>
                                <td style="padding:12px;">
                                    <?php if ($user['is_admin']): ?>
                                        <span style="background:#2e7d32; color:white; padding:4px 12px; border-radius:20px;">👑 مدیر</span>
                                    <?php else: ?>
                                        <span style="background:#6c757d; color:white; padding:4px 12px; border-radius:20px;">👤 کاربر</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding:12px;">
                                    <a href="admin_edit_user.php?id=<?= $user['id'] ?>" style="color:#2e7d32; text-decoration:none; margin-left:12px;">✏️ ویرایش</a>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <a href="admin_delete_user.php?id=<?= $user['id'] ?>" onclick="return confirm('آیا از حذف کاربر <?= htmlspecialchars($user['username']) ?> مطمئن هستید؟')" style="color:#dc2626; text-decoration:none;">🗑️ حذف</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>