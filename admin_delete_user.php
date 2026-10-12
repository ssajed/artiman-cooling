<?php
require_once 'config.php';
if (!isLoggedIn() || !($_SESSION['is_admin'] ?? false)) redirect('dashboard.php');

$id = (int)($_GET['id'] ?? 0);
if ($id && $id != $_SESSION['user_id']) {
    $db->query("DELETE FROM smart_users WHERE id = $id");
}
redirect('admin_users.php');