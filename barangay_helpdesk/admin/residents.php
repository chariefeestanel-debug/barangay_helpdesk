<?php
require_once dirname(__DIR__) . '/includes/init.php';
$pageTitle = 'Residents';
$breadcrumb = 'Residents';
requireAdmin();
requireRole('super_admin');

$pdo    = db();
$search = $_GET['search'] ?? '';
$page   = max(1,(int)($_GET['page']??1));

// Toggle active
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['toggle_id'])) {
    if (!verifyCsrfToken($_POST['csrf_token']??'')) { setFlash('error','Invalid token.'); redirect(BASE_URL.'/admin/residents.php'); }
    $tid = (int)$_POST['toggle_id'];
    $pdo->prepare("UPDATE users SET is_active = 1 - is_active WHERE id=?")->execute([$tid]);
    logActivity('admin',$_SESSION['admin_id'],'Toggled resident status','user',$tid);
    setFlash('success','Resident status updated.');
    redirect(BASE_URL.'/admin/residents.php');
}

$where  = "WHERE 1=1";
$params = [];
if ($search) { $where .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR u.purok LIKE ?)"; $s="%$search%"; $params=[$s,$s,$s]; }

$total = $pdo->prepare("SELECT COUNT(*) FROM users u $where");
$total->execute($params); $total=(int)$total->fetchColumn();
$pag   = paginate($total,$page);

$stmt  = $pdo->prepare("SELECT u.*,(SELECT COUNT(*) FROM concerns WHERE user_id=u.id) as concern_count,(SELECT COUNT(*) FROM concerns WHERE user_id=u.id AND status='resolved') as resolved_count FROM users u $where ORDER BY u.created_at DESC LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params,[$pag['per_page'],$pag['offset']]));
$residents = $stmt->fetchAll();

include dirname(__DIR__) . '/includes/header_admin.php';
?>
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <h4 class="fw-bold mb-0">Registered Residents <span class="badge bg-secondary"><?= $total ?></span></h4>
</div>

<!-- Search -->
<div class="filter-bar mb-4">
    <form class="d-flex gap-2" method="GET">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by name, email, purok..." value="<?= htmlspecialchars($search) ?>" style="max-width:320px">
        <button class="btn btn-sm btn-primary">Search</button>
        <?php if($search): ?><a href="?" class="btn btn-sm btn-outline-secondary">Clear</a><?php endif; ?>
    </form>
</div>

<div class="admin-table mb-3">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0">
    <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Purok</th><th>Concerns</th><th>Resolved</th><th>Registered</th><th>Status</th><th>Action</th></tr></thead>
    <tbody>
    <?php if(empty($residents)): ?>
        <tr><td colspan="9" class="text-center py-4 text-muted">No residents found.</td></tr>
    <?php else: foreach($residents as $r): ?>
    <tr>
        <td class="fw-medium"><?= sanitize($r['full_name']) ?></td>
        <td class="small text-muted"><?= sanitize($r['email']) ?></td>
        <td class="small"><?= $r['phone'] ? sanitize($r['phone']) : '—' ?></td>
        <td class="small"><?= $r['purok'] ? sanitize($r['purok']) : '—' ?></td>
        <td><span class="badge bg-primary"><?= $r['concern_count'] ?></span></td>
        <td><span class="badge bg-success"><?= $r['resolved_count'] ?></span></td>
        <td class="small text-muted"><?= formatDate($r['created_at'],'M d, Y') ?></td>
        <td><?= $r['is_active']?'<span class="badge bg-success">Active</span>':'<span class="badge bg-danger">Suspended</span>' ?></td>
        <td>
            <form method="POST" class="d-inline">
                <?= csrfField() ?>
                <input type="hidden" name="toggle_id" value="<?= $r['id'] ?>">
                <button class="btn btn-sm <?= $r['is_active']?'btn-outline-danger':'btn-outline-success' ?>" data-confirm="<?= $r['is_active']?'Suspend':'Activate' ?> this resident?">
                    <?= $r['is_active']?'Suspend':'Activate' ?>
                </button>
            </form>
        </td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>
</div>
</div>
<?= renderPagination($pag,'?search='.urlencode($search)) ?>

<?php include dirname(__DIR__) . '/includes/footer_admin.php'; ?>
