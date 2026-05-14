<?php
require_once dirname(__DIR__) . '/includes/init.php';
$pageTitle = 'Activity Logs';
$breadcrumb = 'Activity Logs';
requireAdmin();
requireRole('super_admin');

$pdo    = db();
$search = $_GET['search'] ?? '';
$type   = $_GET['type']   ?? '';
$page   = max(1,(int)($_GET['page']??1));

$where  = "WHERE 1=1";
$params = [];
if ($type && in_array($type,['admin','user'])) { $where .= " AND l.actor_type=?"; $params[]=$type; }
if ($search) { $where .= " AND (l.action LIKE ? OR l.ip_address LIKE ?)"; $s="%$search%"; $params[]=$s; $params[]=$s; }

$total = $pdo->prepare("SELECT COUNT(*) FROM activity_logs l $where");
$total->execute($params); $total=(int)$total->fetchColumn();
$pag   = paginate($total,$page,20);

$stmt = $pdo->prepare("SELECT l.*,
    CASE 
        WHEN l.actor_type='admin' THEN a.full_name 
        ELSE u.full_name 
    END as actor_name,

    CASE
        WHEN l.actor_type='admin' AND a.role='super_admin' THEN 'Admin'
        WHEN l.actor_type='admin' AND a.role='staff' THEN 'Staff'
        WHEN l.actor_type='admin' AND a.role='viewer' THEN 'Viewer'
        ELSE 'Resident'
    END as actor_role

FROM activity_logs l
LEFT JOIN admins a ON l.actor_type='admin' AND l.actor_id=a.id
LEFT JOIN users  u ON l.actor_type='user'  AND l.actor_id=u.id
$where ORDER BY l.created_at DESC LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params,[$pag['per_page'],$pag['offset']]));
$logs = $stmt->fetchAll();

include dirname(__DIR__) . '/includes/header_admin.php';
?>
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <h4 class="fw-bold mb-0">Activity Logs <span class="badge bg-secondary"><?= number_format($total) ?></span></h4>
</div>

<div class="filter-bar mb-4">
    <form class="d-flex flex-wrap gap-2 align-items-center" method="GET">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search action, IP..." value="<?= htmlspecialchars($search) ?>" style="max-width:260px">
        <select name="type" class="form-select form-select-sm" style="width:auto">
            <option value="">All Actors</option>
            <option value="admin" <?= $type==='admin'?'selected':'' ?>>Admin / Officials</option>
            <option value="user"  <?= $type==='user'?'selected':'' ?>>Residents</option>
        </select>
        <button class="btn btn-sm btn-primary">Filter</button>
        <?php if($search||$type): ?><a href="?" class="btn btn-sm btn-outline-secondary">Clear</a><?php endif; ?>
    </form>
</div>

<div class="admin-table mb-3">
<div class="table-responsive">
<table class="table table-sm align-middle mb-0">
    <thead><tr><th>#</th><th>Actor</th><th>Type</th><th>Action</th><th>Target</th><th>IP Address</th><th>Time</th></tr></thead>
    <tbody>
    <?php if(empty($logs)): ?>
        <tr><td colspan="7" class="text-center py-4 text-muted">No logs found.</td></tr>
    <?php else: foreach($logs as $log): ?>
    <tr>
        <td class="text-muted small"><?= $log['id'] ?></td>
        <td class="fw-medium small"><?= sanitize($log['actor_name'] ?? 'Deleted') ?></td>
        <td>
        <?php
        if ($log['actor_role'] === 'Admin') {
            echo '<span class="badge bg-warning text-dark">Admin</span>';
        } elseif ($log['actor_role'] === 'Staff') {
            echo '<span class="badge bg-primary">Staff</span>';
        } elseif ($log['actor_role'] === 'Viewer') {
            echo '<span class="badge bg-secondary">Viewer</span>';
        } else {
            echo '<span class="badge bg-info text-dark">Resident</span>';
        }
        ?>
        </td>
        <td class="small"><?= sanitize($log['action']) ?></td>
        <td class="small text-muted"><?= $log['target_type'] ? sanitize($log['target_type']).' #'.$log['target_id'] : '—' ?></td>
        <td class="small text-muted"><code><?= sanitize($log['ip_address']) ?></code></td>
        <td class="small text-muted"><?= timeAgo($log['created_at']) ?></td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>
</div>
</div>
<?= renderPagination($pag,'?search='.urlencode($search).'&type='.urlencode($type)) ?>

<?php include dirname(__DIR__) . '/includes/footer_admin.php'; ?>
