<header>
    <div class="header-content">
        <h1>
            <?php
            $current = basename($_SERVER['PHP_SELF']);
            if ($current == 'design.php') echo 'طراحی نام برج خنک‌کننده آرتیمان';
            elseif ($current == 'smart.php') echo 'سیستم هوشمند برج خنک‌کننده';
            elseif ($current == 'admin_users.php' || $current == 'admin_add_user.php' || $current == 'admin_edit_user.php') echo 'مدیریت کاربران - آرتیمان';
            else echo 'داشبورد - آرتیمان';
            ?>
        </h1>
        <div class="header-buttons">
            <?php if ($current != 'dashboard.php'): ?>
                <a href="dashboard.php"><button class="header-btn">📊 داشبورد</button></a>
            <?php endif; ?>
            <a href="logout.php"><button class="header-btn logout">🚪 خروج</button></a>
        </div>
    </div>
</header>