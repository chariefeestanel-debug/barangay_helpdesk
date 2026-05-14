<?php
require_once dirname(__DIR__) . '/includes/init.php';
$pageTitle = 'Officials & Staff';
$breadcrumb = 'Officials';
requireAdmin();
requireRole('super_admin');

$pdo    = db();
$editId = (int)($_GET['edit'] ?? 0);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { setFlash('error','Invalid token.'); redirect(BASE_URL.'/admin/officials.php'); }

    $act      = $_POST['action'] ?? '';
    $fullName = sanitize($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = sanitize($_POST['phone'] ?? '');
    $position = sanitize($_POST['position'] ?? '');
    $role     = in_array($_POST['role']??'',['super_admin','staff','viewer']) ? $_POST['role'] : 'staff';
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($act === 'create') {
        $password = $_POST['password'] ?? '';
        $pwErrors = validatePassword($password);
        if (!$fullName)            $errors[] = 'Full name is required.';
        if (!validateEmail($email)) $errors[] = 'Valid email is required.';
        $errors = array_merge($errors, $pwErrors);

        if (empty($errors)) {
            $dupe = $pdo->prepare("SELECT id FROM admins WHERE email=?");
            $dupe->execute([$email]);
            if ($dupe->fetch()) { $errors[] = 'Email already exists.'; }
            else {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost'=>12]);
                $pdo->prepare("INSERT INTO admins (full_name,email,password,role,position,phone) VALUES (?,?,?,?,?,?)")->execute([$fullName,$email,$hash,$role,$position,$phone]);
                logActivity('admin',$_SESSION['admin_id'],'Created official account');
                setFlash('success','Official account created.');
                redirect(BASE_URL.'/admin/officials.php');
            }
        }
    } elseif ($act === 'update' && $editId) {
        if (!$fullName)            $errors[] = 'Full name is required.';
        if (!validateEmail($email)) $errors[] = 'Valid email is required.';

        if (empty($errors)) {
            $dupe = $pdo->prepare("SELECT id FROM admins WHERE email=? AND id!=?");
            $dupe->execute([$email,$editId]);
            if ($dupe->fetch()) { $errors[] = 'Email already used by another account.'; }
            else {
                $pdo->prepare("UPDATE admins SET full_name=?,email=?,role=?,position=?,phone=?,is_active=?,updated_at=NOW() WHERE id=?")->execute([$fullName,$email,$role,$position,$phone,$isActive,$editId]);
                // Change password if provided
                $newPass = $_POST['new_password'] ?? '';
                if ($newPass) {
                    $pwErrors = validatePassword($newPass);
                    if (empty($pwErrors)) { $pdo->prepare("UPDATE admins SET password=? WHERE id=?")->execute([password_hash($newPass,PASSWORD_BCRYPT,['cost'=>12]),$editId]); }
                    else $errors = array_merge($errors, $pwErrors);
                }
                if (empty($errors)) { setFlash('success','Official updated.'); redirect(BASE_URL.'/admin/officials.php'); }
            }
        }
    } elseif ($act === 'delete') {
        $delId = (int)($_POST['del_id'] ?? 0);
        if ($delId === $_SESSION['admin_id']) { setFlash('error','You cannot delete your own account.'); }
        else { $pdo->prepare("DELETE FROM admins WHERE id=?")->execute([$delId]); setFlash('success','Account deleted.'); }
        redirect(BASE_URL.'/admin/officials.php');
    } elseif ($act === 'reset_password') {
        $resetId  = (int)($_POST['reset_id'] ?? 0);
        $newPass  = $_POST['new_password'] ?? '';
        $pwErrors = validatePassword($newPass);
        if (empty($pwErrors)) {
            $pdo->prepare("UPDATE admins SET password=? WHERE id=?")->execute([password_hash($newPass,PASSWORD_BCRYPT,['cost'=>12]),$resetId]);
            setFlash('success','Password reset successfully.');
        } else { setFlash('error', implode(' ',$pwErrors)); }
        redirect(BASE_URL.'/admin/officials.php');
    }
}

$officials = $pdo->query("SELECT a.*,(SELECT COUNT(*) FROM concerns WHERE assigned_to=a.id) as assigned_count FROM admins a ORDER BY a.role ASC, a.full_name ASC")->fetchAll();
$editOfficial = $editId ? $pdo->prepare("SELECT * FROM admins WHERE id=?") : null;
if ($editOfficial) { $editOfficial->execute([$editId]); $editOfficial=$editOfficial->fetch(); }

include dirname(__DIR__) . '/includes/header_admin.php';
?>
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <h4 class="fw-bold mb-0">Officials & Staff Accounts</h4>
    <button class="btn btn-warning" data-bs-toggle="collapse" data-bs-target="#createForm"><i class="bi bi-person-plus me-1"></i>Add Official</button>
</div>

<?php if ($errors): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul></div>
<?php endif; ?>

<!-- Create Form -->
<div class="collapse" id="createForm">
<div class="card mb-4">
    <div class="card-header bg-white"><h6 class="mb-0 fw-semibold">New Official / Staff Account</h6></div>
    <div class="card-body">
        <form method="POST" class="row g-3">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="create">
            <div class="col-md-4"><label class="form-label small fw-medium">Full Name *</label><input type="text" name="full_name" class="form-control form-control-sm" required></div>
            <div class="col-md-4"><label class="form-label small fw-medium">Email *</label><input type="email" name="email" class="form-control form-control-sm" required></div>
            <div class="col-md-4"><label class="form-label small fw-medium">Phone</label><input type="tel" name="phone" class="form-control form-control-sm"></div>
            <div class="col-md-4"><label class="form-label small fw-medium">Position / Title</label><input type="text" name="position" class="form-control form-control-sm" placeholder="e.g. Barangay Kagawad"></div>
            <div class="col-md-4">
                <label class="form-label small fw-medium">Role *</label>
                <select name="role" class="form-select form-select-sm">
                    <option value="staff">Staff — Can manage concerns & announcements</option>
                    <option value="viewer">Viewer — Read-only access</option>
                    <option value="super_admin">Super Admin — Full access</option>
                </select>
            </div>
            <div class="col-md-4"><label class="form-label small fw-medium">Password *</label><input type="password" name="password" class="form-control form-control-sm" required><div class="form-text">Min 8 chars, 1 uppercase, 1 number.</div></div>
            <div class="col-12"><button class="btn btn-primary btn-sm px-4">Create Account</button></div>
        </form>
    </div>
</div>
</div>

<!-- Edit Form -->
<?php if ($editOfficial): ?>
<div class="card mb-4 border-warning">
    <div class="card-header bg-warning bg-opacity-10"><h6 class="mb-0 fw-semibold">Edit: <?= sanitize($editOfficial['full_name']) ?></h6></div>
    <div class="card-body">
        <form method="POST" class="row g-3">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="update">
            <div class="col-md-4"><label class="form-label small fw-medium">Full Name *</label><input type="text" name="full_name" class="form-control form-control-sm" value="<?= htmlspecialchars($editOfficial['full_name']) ?>" required></div>
            <div class="col-md-4"><label class="form-label small fw-medium">Email *</label><input type="email" name="email" class="form-control form-control-sm" value="<?= htmlspecialchars($editOfficial['email']) ?>" required></div>
            <div class="col-md-4"><label class="form-label small fw-medium">Phone</label><input type="tel" name="phone" class="form-control form-control-sm" value="<?= htmlspecialchars($editOfficial['phone']) ?>"></div>
            <div class="col-md-4"><label class="form-label small fw-medium">Position</label><input type="text" name="position" class="form-control form-control-sm" value="<?= htmlspecialchars($editOfficial['position']) ?>"></div>
            <div class="col-md-3">
                <label class="form-label small fw-medium">Role</label>
                <select name="role" class="form-select form-select-sm">
                    <option value="staff"       <?= $editOfficial['role']==='staff'?'selected':'' ?>>Staff</option>
                    <option value="viewer"      <?= $editOfficial['role']==='viewer'?'selected':'' ?>>Viewer</option>
                    <option value="super_admin" <?= $editOfficial['role']==='super_admin'?'selected':'' ?>>Super Admin</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end"><div class="form-check mb-2"><input type="checkbox" class="form-check-input" name="is_active" id="isAct" <?= $editOfficial['is_active']?'checked':'' ?>><label for="isAct" class="form-check-label small">Active</label></div></div>
            <div class="col-md-3"><label class="form-label small fw-medium">New Password <span class="text-muted">(leave blank to keep)</span></label><input type="password" name="new_password" class="form-control form-control-sm"></div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary btn-sm px-4">Save Changes</button>
                <a href="?" class="btn btn-outline-secondary btn-sm">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Officials Table -->
<div class="admin-table">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0">
    <thead><tr><th>Name</th><th>Email</th><th>Position</th><th>Role</th><th>Assigned Concerns</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach($officials as $off): ?>
    <tr>
        <td class="fw-medium"><?= sanitize($off['full_name']) ?> <?= $off['id']===$_SESSION['admin_id']?'<span class="badge bg-info text-dark">You</span>':'' ?></td>
        <td class="small text-muted"><?= sanitize($off['email']) ?></td>
        <td class="small"><?= $off['position'] ? sanitize($off['position']) : '—' ?></td>
        <td>
            <?php $roleBadges=['super_admin'=>'bg-danger','staff'=>'bg-primary','viewer'=>'bg-secondary']; ?>
            <span class="badge <?= $roleBadges[$off['role']]??'bg-secondary' ?>"><?= ucfirst(str_replace('_',' ',$off['role'])) ?></span>
        </td>
        <td><span class="badge bg-secondary"><?= $off['assigned_count'] ?></span></td>
        <td><?= $off['is_active']?'<span class="badge bg-success">Active</span>':'<span class="badge bg-secondary">Inactive</span>' ?></td>
        <td class="d-flex gap-1">
            <a href="?edit=<?= $off['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
            <?php if ($off['id'] !== $_SESSION['admin_id']): ?>
            <form method="POST" class="d-inline">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="del_id" value="<?= $off['id'] ?>">
                <button class="btn btn-sm btn-outline-danger" data-confirm="Delete account for <?= htmlspecialchars($off['full_name']) ?>?"><i class="bi bi-trash"></i></button>
            </form>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>

<?php include dirname(__DIR__) . '/includes/footer_admin.php'; ?>
