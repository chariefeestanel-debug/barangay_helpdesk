<?php
require_once dirname(__DIR__) . '/includes/init.php';
$pageTitle = 'My Concerns';
requireLogin();

$pdo    = db();
$userId = $_SESSION['user_id'];

// Filters
$status   = $_GET['status'] ?? '';
$priority = $_GET['priority'] ?? '';
$page     = max(1,(int)($_GET['page'] ?? 1));

$where  = "WHERE c.user_id=?";
$params = [$userId];
if ($status && in_array($status,['pending','in_progress','resolved','rejected'])) { $where .= " AND c.status=?"; $params[] = $status; }
if ($priority && in_array($priority,['low','medium','high','urgent']))             { $where .= " AND c.priority=?"; $params[] = $priority; }

$total = $pdo->prepare("SELECT COUNT(*) FROM concerns c $where"); $total->execute($params); $total = (int)$total->fetchColumn();
$pag   = paginate($total, $page);

$stmt = $pdo->prepare("SELECT c.*,cat.name as cat_name,(SELECT COUNT(*) FROM concern_replies WHERE concern_id=c.id AND is_read=0 AND sender_type='admin') as unread FROM concerns c JOIN concern_categories cat ON c.category_id=cat.id $where ORDER BY c.created_at DESC LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params,[$pag['per_page'],$pag['offset']]));
$concerns = $stmt->fetchAll();

// Stats
$statStmt = $pdo->prepare("SELECT status,COUNT(*) as n FROM concerns WHERE user_id=? GROUP BY status");
$statStmt->execute([$userId]);
$stats = array_column($statStmt->fetchAll(),'n','status');

include dirname(__DIR__) . '/includes/header_resident.php';
?>
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <h2 class="fw-bold mb-0"><i class="bi bi-list-check text-primary me-2"></i>My Concerns</h2>
    <a href="<?= BASE_URL ?>/resident/submit_concern.php" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>New Concern</a>
</div>

<!-- Stats Strip -->
<div class="row g-3 mb-4">
    <?php foreach([['pending','Pending','warning','clock'],['in_progress','In Progress','primary','arrow-repeat'],['resolved','Resolved','success','check-circle'],['rejected','Rejected','danger','x-circle']] as $item): list($s,$l,$c,$icon) = $item; ?>
    <div class="col-6 col-md-3">
        <a href="?status=<?= $s ?>" class="text-decoration-none">
            <div class="bhd-card p-3 text-center <?= ($status===$s)?'border-'.$c:'border-0' ?>" style="border:2px solid transparent!important;<?= ($status===$s)?'border-color:var(--bs-'.$c.')!important':'' ?>">
                <i class="bi bi-<?= $icon ?> fs-3 text-<?= $c ?>"></i>
                <div class="fw-bold fs-4"><?= $stats[$s] ?? 0 ?></div>
                <small class="text-muted"><?= $l ?></small>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filters -->
<div class="filter-bar mb-4 d-flex flex-wrap gap-2 align-items-center">
    <form class="d-flex flex-wrap gap-2 align-items-center w-100" method="GET">
        <select name="status" class="form-select form-select-sm" style="width:auto">
            <option value="">All Status</option>
            <?php foreach(['pending','in_progress','resolved','rejected'] as $s): ?>
                <option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="priority" class="form-select form-select-sm" style="width:auto">
            <option value="">All Priority</option>
            <?php foreach(['low','medium','high','urgent'] as $p): ?>
                <option value="<?= $p ?>" <?= $priority===$p?'selected':'' ?>><?= ucfirst($p) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-sm btn-outline-primary">Filter</button>
        <?php if($status||$priority): ?><a href="?" class="btn btn-sm btn-outline-secondary">Clear</a><?php endif; ?>
    </form>
</div>

<?php if(empty($concerns)): ?>
    <div class="bhd-card p-5 text-center text-muted">
        <i class="bi bi-inbox fs-1 d-block mb-3"></i>
        <h5>No concerns found</h5>
        <p class="mb-3">You haven't submitted any concerns yet, or none match the current filter.</p>
        <a href="<?= BASE_URL ?>/resident/submit_concern.php" class="btn btn-primary">Submit Your First Concern</a>
    </div>
<?php else: ?>
    <div class="admin-table rounded-3 overflow-hidden mb-3">
        <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr>
                <th>Tracking Code</th>
                <th>Title</th>
                <th>Category</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Date</th>
                <th>Action</th>
            </tr></thead>
            <tbody>
            <?php foreach($concerns as $c): ?>
            <tr class="priority-<?= $c['priority'] ?>">
                <td><code class="small"><?= sanitize($c['tracking_code']) ?></code></td>
                <td>
                    <a href="<?= BASE_URL ?>/resident/concern_detail.php?id=<?= $c['id'] ?>" class="fw-medium text-decoration-none">
                        <?= sanitize(substr($c['title'],0,50)) ?><?= strlen($c['title'])>50?'...':'' ?>
                    </a>
                    <?php if($c['unread']>0): ?><span class="badge bg-danger ms-1"><?= $c['unread'] ?> new</span><?php endif; ?>
                </td>
                <td><small><?= sanitize($c['cat_name']) ?></small></td>
                <td><?= priorityBadge($c['priority']) ?></td>
                <td><?= statusBadge($c['status']) ?></td>
                <td><small class="text-muted"><?= formatDate($c['created_at'],'M d, Y') ?></small></td>
                <td><a href="<?= BASE_URL ?>/resident/concern_detail.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary">View</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?= renderPagination($pag, '?status='.urlencode($status).'&priority='.urlencode($priority)) ?>
<?php endif; ?>
<?php include dirname(__DIR__) . '/includes/footer_resident.php'; ?>
