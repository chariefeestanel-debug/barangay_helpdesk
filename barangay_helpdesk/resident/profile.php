<?php
require_once dirname(__DIR__) . '/includes/init.php';
$pageTitle = 'My Profile';
requireLogin();

$pdo    = db();
$userId = $_SESSION['user_id'];
$user   = $pdo->prepare("SELECT * FROM users WHERE id=?");
$user->execute([$userId]); $user=$user->fetch();
$errors = [];

if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!verifyCsrfToken($_POST['csrf_token']??'')) { setFlash('error','Invalid token.'); redirect(BASE_URL.'/resident/profile.php'); }
    $act = $_POST['action'] ?? '';

    if ($act==='update_info') {
        $fullName = sanitize($_POST['full_name']??'');
        $phone    = sanitize($_POST['phone']??'');
        $address  = sanitize($_POST['address']??'');
        $purok    = sanitize($_POST['purok']??'');
        if (!$fullName) $errors[]='Full name required.';
        if (empty($errors)) {
            $pdo->prepare("UPDATE users SET full_name=?,phone=?,address=?,purok=?,updated_at=NOW() WHERE id=?")->execute([$fullName,$phone,$address,$purok,$userId]);
            $_SESSION['user_name']=$fullName;
            setFlash('success','Profile updated.');
            redirect(BASE_URL.'/resident/profile.php');
        }
    } elseif ($act==='change_password') {
        $current = $_POST['current_password']??'';
        $new     = $_POST['new_password']??'';
        $confirm = $_POST['confirm_password']??'';
        if (!password_verify($current,$user['password'])) { $errors[]='Current password is incorrect.'; }
        $pwErrors = validatePassword($new); $errors=array_merge($errors,$pwErrors);
        if ($new!==$confirm) $errors[]='New passwords do not match.';
        if (empty($errors)) {
            $pdo->prepare("UPDATE users SET password=?,updated_at=NOW() WHERE id=?")->execute([password_hash($new,PASSWORD_BCRYPT,['cost'=>12]),$userId]);
            setFlash('success','Password changed successfully.');
            redirect(BASE_URL.'/resident/profile.php');
        }
    }
}

// Stats
$stats = $pdo->prepare("SELECT COUNT(*) as total, SUM(status='resolved') as resolved, SUM(status='pending') as pending FROM concerns WHERE user_id=?");
$stats->execute([$userId]); $stats=$stats->fetch();

include dirname(__DIR__) . '/includes/header_resident.php';
?>
<div class="row justify-content-center">
<div class="col-lg-9">
<h3 class="fw-bold mb-4"><i class="bi bi-person-circle text-primary me-2"></i>My Profile</h3>

<!-- Stats -->
<div class="row g-3 mb-4">
    <?php foreach([[$stats['total']??0,'Total Concerns','primary','collection'],[$stats['pending']??0,'Pending','warning','hourglass-split'],[$stats['resolved']??0,'Resolved','success','check-circle']] as $_item): $_vars = $_item; list($v,$l,$c,$ico) = $_vars; ?>
    <div class="col-4">
        <div class="bhd-card p-3 text-center">
            <i class="bi bi-<?= $ico ?> fs-2 text-<?= $c ?> d-block mb-1"></i>
            <div class="fw-bold fs-4"><?= $v ?></div>
            <small class="text-muted"><?= $l ?></small>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if($errors): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-md-7">
        <div class="bhd-card p-4">
            <h6 class="fw-semibold mb-3"><i class="bi bi-person me-2 text-primary"></i>Personal Information</h6>
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update_info">
                <div class="mb-3"><label class="form-label small fw-medium">Full Name *</label><input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required></div>
                <div class="mb-3"><label class="form-label small fw-medium">Email</label><input type="email" class="form-control bg-light" value="<?= htmlspecialchars($user['email']) ?>" disabled></div>
                <div class="row g-2 mb-3">
                    <div class="col"><label class="form-label small fw-medium">Phone</label><input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone']??'') ?>"></div>
                    <div class="col"><label class="form-label small fw-medium">Purok / Zone</label><input type="text" name="purok" class="form-control" value="<?= htmlspecialchars($user['purok']??'') ?>"></div>
                </div>
                <div class="mb-4"><label class="form-label small fw-medium">Home Address</label><textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($user['address']??'') ?></textarea></div>
                <button class="btn btn-primary">Save Changes</button>
            </form>
        </div>
    </div>
    <div class="col-md-5">
        <div class="bhd-card p-4">
            <h6 class="fw-semibold mb-3"><i class="bi bi-lock me-2 text-primary"></i>Change Password</h6>
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="change_password">
                <div class="mb-3"><label class="form-label small fw-medium">Current Password</label><input type="password" name="current_password" class="form-control" required></div>
                <div class="mb-3"><label class="form-label small fw-medium">New Password</label><input type="password" name="new_password" class="form-control" required></div>
                <div class="mb-4"><label class="form-label small fw-medium">Confirm Password</label><input type="password" name="confirm_password" class="form-control" required></div>
                <button class="btn btn-warning w-100">Change Password</button>
            </form>
        </div>
        <div class="bhd-card p-4 mt-4">
            <h6 class="fw-semibold mb-3"><i class="bi bi-info-circle me-2 text-primary"></i>Account Details</h6>
            <dl class="row small mb-0">
                <dt class="col-5 text-muted">Member Since</dt><dd class="col-7"><?= formatDate($user['created_at'],'M d, Y') ?></dd>
                <dt class="col-5 text-muted">Account Status</dt><dd class="col-7"><span class="badge bg-success">Active</span></dd>
            </dl>
        </div>
    </div>
</div>
</div>
</div>
<?php include dirname(__DIR__) . '/includes/footer_resident.php'; ?>
