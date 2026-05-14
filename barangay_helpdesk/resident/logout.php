<?php
require_once dirname(__DIR__) . '/includes/init.php';
if (isLoggedIn()) logActivity('user', $_SESSION['user_id'], 'Logged out');
session_unset(); session_destroy();
setcookie(SESSION_NAME, '', time()-3600, '/');
session_start();
setFlash('success', 'You have been logged out.');
redirect(BASE_URL . '/resident/login.php');
