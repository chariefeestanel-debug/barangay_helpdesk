<?php
require_once dirname(__DIR__) . '/includes/init.php';
$pageTitle = 'My Profile';
$breadcrumb = 'Profile';
requireAdmin();

$pdo    = db();
$adminId= $_SESSION['admin_id'];
$admin  = $pdo->prepare("SELECT * FROM admins WHERE id=?");
$admin->execute([$adminId]); $admin=$admin->fetch();
$errors = [];

if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!verifyCsrfToken($_POST['csrf_token']??'')) { setFlash('error','Invalid token.'); redirect(BASE_URL.'/admin/profile.php'); }
    $act = $_POST['action'] ?? '';

    if ($act==='update_info') {
        $fullName = sanitize($_POST['full_name']??'');
        $phone    = sanitize($_POST['phone']??'');
        $position = sanitize($_POST['position']??'');
        if (!$fullName) $errors[]='Full name required.';
        if (empty($errors)) {
            $pdo->prepare("UPDATE admins SET full_name=?,phone=?,position=?,updated_at=NOW() WHERE id=?")->execute([$fullName,$phone,$position,$adminId]);
            $_SESSION['admin_name']=$fullName;
            setFlash('success','Profile updated.');
            redirect(BASE_URL.'/admin/profile.php');
        }
    } elseif ($act==='change_password') {
        $current = $_POST['current_password']??'';
        $new     = $_POST['new_password']??'';
        $confirm = $_POST['confirm_password']??'';
        if (!password_verify($current,$admin['password'])) { $errors[]='Current password is incorrect.'; }
        $pwErrors = validatePassword($new); $errors=array_merge($errors,$pwErrors);
        if ($new!==$confirm) $errors[]='New passwords do not match.';
        if (empty($errors)) {
            $pdo->prepare("UPDATE admins SET password=?,updated_at=NOW() WHERE id=?")->execute([password_hash($new,PASSWORD_BCRYPT,['cost'=>12]),$adminId]);
            logActivity('admin',$adminId,'Changed password');
            setFlash('success','Password changed successfully.');
            redirect(BASE_URL.'/admin/profile.php');
        }
    }
}

include dirname(__DIR__) . '/includes/header_admin.php';
?>
<h4 class="fw-bold mb-4">My Profile</h4>
<?php if($errors): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul></div>
<?php endif; ?>

<div class="row g-4">
<div class="col-md-6">
    <div class="card">
        <div class="card-header bg-white"><h6 class="mb-0 fw-semibold"><i class="bi bi-person me-2 text-primary"></i>Profile Information</h6></div>
        <div class="card-body">
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update_info">
                <div class="mb-3"><label class="form-label small fw-medium">Full Name *</label><input type="text" name="full_name" class="form-control form-control-sm" value="<?= htmlspecialchars($admin['full_name']) ?>" required></div>
                <div class="mb-3"><label class="form-label small fw-medium">Email</label><input type="email" class="form-control form-control-sm" value="<?= htmlspecialchars($admin['email']) ?>" disabled></div>
                <div class="mb-3"><label class="form-label small fw-medium">Position</label><input type="text" name="position" class="form-control form-control-sm" value="<?= htmlspecialchars($admin['position']??'') ?>"></div>
                <div class="mb-3"><label class="form-label small fw-medium">Phone</label><input type="tel" name="phone" class="form-control form-control-sm" value="<?= htmlspecialchars($admin['phone']??'') ?>"></div>
                <div class="mb-3">
                    <label class="form-label small fw-medium">Role</label>
                    <div class="form-control form-control-sm bg-light"><?= ucfirst(str_replace('_',' ',$admin['role'])) ?></div>
                </div>
                <button class="btn btn-primary btn-sm px-4">Update Profile</button>
            </form>
        </div>
    </div>
</div>
<div class="col-md-6">
    <div class="card">
        <div class="card-header bg-white"><h6 class="mb-0 fw-semibold"><i class="bi bi-lock me-2 text-primary"></i>Change Password</h6></div>
        <div class="card-body">
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="change_password">
                <div class="mb-3"><label class="form-label small fw-medium">Current Password *</label><input type="password" name="current_password" class="form-control form-control-sm" required></div>
                <div class="mb-3"><label class="form-label small fw-medium">New Password *</label><input type="password" name="new_password" class="form-control form-control-sm" required><div class="form-text">Min 8 chars, 1 uppercase, 1 number.</div></div>
                <div class="mb-4"><label class="form-label small fw-medium">Confirm New Password *</label><input type="password" name="confirm_password" class="form-control form-control-sm" required></div>
                <button class="btn btn-warning btn-sm px-4">Change Password</button>
            </form>
        </div>
    </div>
    <div class="card mt-4">
        <div class="card-header bg-white"><h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2 text-primary"></i>Account Info</h6></div>
        <div class="card-body">
            <dl class="row small mb-0">
                <dt class="col-5 text-muted">Account ID</dt><dd class="col-7">#<?= $admin['id'] ?></dd>
                <dt class="col-5 text-muted">Role</dt><dd class="col-7"><?= ucfirst(str_replace('_',' ',$admin['role'])) ?></dd>
                <dt class="col-5 text-muted">Member Since</dt><dd class="col-7"><?= formatDate($admin['created_at'],'M d, Y') ?></dd>
                <dt class="col-5 text-muted">Last Updated</dt><dd class="col-7"><?= formatDate($admin['updated_at'],'M d, Y') ?></dd>
            </dl>
        </div>
    </div>
</div>
</div>

<?php include dirname(__DIR__) . '/includes/footer_admin.php'; ?>
