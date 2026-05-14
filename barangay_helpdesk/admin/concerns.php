<?php
require_once dirname(__DIR__) . '/includes/init.php';
$pageTitle = 'Concerns';
$breadcrumb = 'Concerns';
requireAdmin();

$pdo = db();

// Filters
$status   = $_GET['status']   ?? '';
$priority = $_GET['priority'] ?? '';
$category = $_GET['category'] ?? '';
$search   = $_GET['search']   ?? '';
$page     = max(1,(int)($_GET['page']??1));

$where  = "WHERE 1=1";
$params = [];
if ($status   && in_array($status,  ['pending','in_progress','resolved','rejected'])) { $where .= " AND c.status=?";   $params[]=$status;   }
if ($priority && in_array($priority,['low','medium','high','urgent']))                { $where .= " AND c.priority=?"; $params[]=$priority; }
if ($category)  { $where .= " AND c.category_id=?"; $params[]=(int)$category; }
if ($search)    { $where .= " AND (c.title LIKE ? OR c.tracking_code LIKE ? OR u.full_name LIKE ?)"; $s="%$search%"; $params=array_merge($params,[$s,$s,$s]); }

$total = $pdo->prepare("SELECT COUNT(*) FROM concerns c JOIN users u ON c.user_id=u.id $where");
$total->execute($params); $total=(int)$total->fetchColumn();
$pag = paginate($total,$page);

$stmt = $pdo->prepare("SELECT c.*,cat.name as cat_name,u.full_name as resident,a.full_name as assigned_name,(SELECT COUNT(*) FROM concern_replies WHERE concern_id=c.id AND is_read=0 AND sender_type='user') as unread FROM concerns c JOIN concern_categories cat ON c.category_id=cat.id JOIN users u ON c.user_id=u.id LEFT JOIN admins a ON c.assigned_to=a.id $where ORDER BY FIELD(c.priority,'urgent','high','medium','low'), c.created_at DESC LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params,[$pag['per_page'],$pag['offset']]));
$concerns = $stmt->fetchAll();

$categories = $pdo->query("SELECT id,name FROM concern_categories WHERE is_active=1 ORDER BY name")->fetchAll();

include dirname(__DIR__) . '/includes/header_admin.php';
?>
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <h4 class="fw-bold mb-0">All Concerns <span class="badge bg-secondary ms-2"><?= $total ?></span></h4>
</div>

<!-- Filter Bar -->
<div class="filter-bar mb-4">
    <form class="row g-2 align-items-end" method="GET">
        <div class="col-md-3">
            <label class="form-label small fw-medium mb-1">Search</label>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Title, code, resident..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-medium mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">All</option>
                <?php foreach(['pending','in_progress','resolved','rejected'] as $s): ?>
                    <option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-medium mb-1">Priority</label>
            <select name="priority" class="form-select form-select-sm">
                <option value="">All</option>
                <?php foreach(['low','medium','high','urgent'] as $p): ?>
                    <option value="<?= $p ?>" <?= $priority===$p?'selected':'' ?>><?= ucfirst($p) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-medium mb-1">Category</label>
            <select name="category" class="form-select form-select-sm">
                <option value="">All</option>
                <?php foreach($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $category==$cat['id']?'selected':'' ?>><?= sanitize($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-sm btn-primary w-100">Filter</button>
            <a href="?" class="btn btn-sm btn-outline-secondary">Reset</a>
        </div>
    </form>
</div>

<!-- Table -->
<div class="admin-table mb-3">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0">
    <thead><tr>
        <th>Code</th><th>Title</th><th>Resident</th><th>Category</th><th>Priority</th><th>Status</th><th>Assigned</th><th>Date</th><th>Action</th>
    </tr></thead>
    <tbody>
    <?php if(empty($concerns)): ?>
        <tr><td colspan="9" class="text-center py-4 text-muted">No concerns found.</td></tr>
    <?php else: ?>
    <?php foreach($concerns as $c): ?>
    <tr class="priority-<?= $c['priority'] ?>">
        <td><code class="small"><?= sanitize($c['tracking_code']) ?></code></td>
        <td>
            <div class="fw-medium small"><?= sanitize(substr($c['title'],0,45)) ?>...</div>
            <?php if($c['unread']>0): ?><span class="badge bg-danger" style="font-size:.65rem"><?= $c['unread'] ?> new reply</span><?php endif; ?>
        </td>
        <td><small><?= sanitize($c['resident']) ?></small></td>
        <td><small><?= sanitize($c['cat_name']) ?></small></td>
        <td><?= priorityBadge($c['priority']) ?></td>
        <td><?= statusBadge($c['status']) ?></td>
        <td><small class="text-muted"><?= $c['assigned_name'] ? sanitize($c['assigned_name']) : '—' ?></small></td>
        <td><small class="text-muted"><?= timeAgo($c['created_at']) ?></small></td>
        <td><a href="<?= BASE_URL ?>/admin/concern_detail.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary">View</a></td>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>
</div>
</div>
<?= renderPagination($pag, '?status='.urlencode($status).'&priority='.urlencode($priority).'&category='.urlencode($category).'&search='.urlencode($search)) ?>

<?php include dirname(__DIR__) . '/includes/footer_admin.php'; ?>
