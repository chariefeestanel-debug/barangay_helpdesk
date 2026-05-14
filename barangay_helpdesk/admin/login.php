<?php
require_once dirname(__DIR__) . '/includes/init.php';
$pageTitle = 'Admin Login';
if (isAdminLoggedIn()) redirect(BASE_URL . '/admin/dashboard.php');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if (empty($email) || empty($password)) {
            $errors[] = 'Email and password are required.';
        } else {
            $pdo  = db();
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE email=? AND is_active=1");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();
            if ($admin && password_verify($password, $admin['password'])) {
                session_regenerate_id(true);
                $_SESSION['admin_id']   = $admin['id'];
                $_SESSION['admin_name'] = $admin['full_name'];
                $_SESSION['admin_email']= $admin['email'];
                $_SESSION['admin_role'] = $admin['role'];
                logActivity('admin', $admin['id'], 'Admin logged in');
                setFlash('success', 'Welcome, ' . $admin['full_name'] . '!');
                redirect(BASE_URL . '/admin/dashboard.php');
            } else {
                $errors[] = 'Invalid credentials or account inactive.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin Login — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/admin.css">
    <style>
        body{background:linear-gradient(135deg,#0f1e2e 0%,#1a3c5e 100%);min-height:100vh;display:flex;align-items:center;justify-content:center}
        .login-card{background:#fff;border-radius:16px;padding:2.5rem;width:100%;max-width:400px;box-shadow:0 24px 48px rgba(0,0,0,.3)}
        .admin-badge{background:rgba(240,165,0,.15);color:#f0a500;border:1px solid rgba(240,165,0,.3);border-radius:6px;padding:.25rem .75rem;font-size:.8rem;font-weight:600}
    </style>
</head>
<body>
<div class="login-card">
    <div class="text-center mb-4">
        <i class="bi bi-shield-fill-check fs-1 text-warning d-block mb-2"></i>
        <h4 class="fw-bold mb-1"><?= APP_NAME ?></h4>
        <span class="admin-badge"><i class="bi bi-lock me-1"></i>Admin Access</span>
    </div>
    <?php if($errors): ?>
        <div class="alert alert-danger small py-2"><?= htmlspecialchars($errors[0]) ?></div>
    <?php endif; ?>
    <?php $flash = getFlash(); if($flash): ?>
        <div class="alert alert-<?= $flash['type']==='error'?'danger':$flash['type'] ?> small py-2"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>
    <form method="POST" novalidate autocomplete="off">
        <?= csrfField() ?>
        <div class="mb-3">
            <label class="form-label fw-medium small">Email Address</label>
            <input type="email" name="email" class="form-control" value="" autocomplete="off" autofocus required>
        </div>
        <div class="mb-4">
            <label class="form-label fw-medium small">Password</label>
            <input type="password" name="password" class="form-control" autocomplete="new-password" required>
        </div>
        <button type="submit" class="btn btn-warning w-100 fw-semibold">Login to Admin Panel</button>
    </form>
    <p class="text-center mt-3 mb-0 small"><a href="<?= BASE_URL ?>/resident/login.php" class="text-muted"><i class="bi bi-arrow-left me-1"></i>Back to Resident Login</a></p>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>