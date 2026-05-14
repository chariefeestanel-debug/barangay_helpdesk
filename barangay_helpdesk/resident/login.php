<?php
require_once dirname(__DIR__) . '/includes/init.php';
$pageTitle = 'Login';
if (isLoggedIn()) redirect(BASE_URL . '/resident/my_concerns.php');

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
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email=? AND is_active=1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email']= $user['email'];
                logActivity('user', $user['id'], 'Logged in');
                setFlash('success', 'Welcome back, ' . $user['full_name'] . '!');
                redirect($_SESSION['redirect_after_login'] ?? BASE_URL . '/resident/my_concerns.php');
            } else {
                $errors[] = 'Invalid email or password.';
            }
        }
    }
}

include dirname(__DIR__) . '/includes/header_resident.php';
?>
<div class="row justify-content-center">
<div class="col-md-5 col-lg-4">
<div class="bhd-card p-4 p-md-5">
    <div class="text-center mb-4">
        <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width:64px;height:64px">
            <i class="bi bi-shield-lock-fill fs-2 text-primary"></i>
        </div>
        <h3 class="fw-bold">Resident Login</h3>
    </div>
    <?php if($errors): ?>
        <div class="alert alert-danger small"><?= htmlspecialchars($errors[0]) ?></div>
    <?php endif; ?>
    <form method="POST" novalidate autocomplete="off">
        <?= csrfField() ?>
        <div class="mb-3">
            <label class="form-label fw-medium">Email Address</label>
            <input type="email" name="email" class="form-control" value="" autocomplete="off" required autofocus>
        </div>
        <div class="mb-4">
            <label class="form-label fw-medium">Password</label>
            <input type="password" name="password" class="form-control" autocomplete="new-password" required>
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Login</button>
    </form>
    <p class="text-center mt-3 mb-0 small">No account yet? <a href="<?= BASE_URL ?>/resident/register.php">Register here</a></p>
    <p class="text-center mt-1 mb-0 small"><a href="<?= BASE_URL ?>/admin/login.php" class="text-muted">Admin Login</a></p>
</div>
</div>
</div>
<?php include dirname(__DIR__) . '/includes/footer_resident.php'; ?>
