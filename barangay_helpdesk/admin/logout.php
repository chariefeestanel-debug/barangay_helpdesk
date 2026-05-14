<?php
require_once dirname(__DIR__) . '/includes/init.php';
if (isAdminLoggedIn()) logActivity('admin', $_SESSION['admin_id'], 'Admin logged out');
session_unset(); session_destroy();
setcookie(SESSION_NAME, '', time()-3600, '/');
session_start();
setFlash('success', 'Admin session ended.');
redirect(BASE_URL . '/admin/login.php');
