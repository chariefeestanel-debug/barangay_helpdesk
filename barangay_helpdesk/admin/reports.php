<?php
require_once dirname(__DIR__) . '/includes/init.php';
$pageTitle = 'Reports';
$breadcrumb = 'Reports';
requireAdmin();

$pdo = db();

// Date range filter
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo   = $_GET['date_to']   ?? date('Y-m-d');
$status   = $_GET['status']    ?? '';
$category = $_GET['category']  ?? '';

// Build WHERE
$where  = "WHERE c.created_at BETWEEN ? AND ?";
$params = [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'];
if ($status && in_array($status, ['pending','in_progress','resolved','rejected'])) {
    $where .= " AND c.status = ?";
    $params[] = $status;
}
if ($category) {
    $where .= " AND c.category_id = ?";
    $params[] = (int)$category;
}

// Summary stats
$summarySql = "
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN c.status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN c.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN c.status = 'resolved' THEN 1 ELSE 0 END) as resolved,
        SUM(CASE WHEN c.status = 'rejected' THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN c.priority = 'urgent' THEN 1 ELSE 0 END) as urgent,
        AVG(CASE WHEN c.resolved_at IS NOT NULL THEN TIMESTAMPDIFF(HOUR, c.created_at, c.resolved_at) END) as avg_resolve_hours
    FROM concerns c
    $where
";
$summary = $pdo->prepare($summarySql);
$summary->execute($params);
$summary = $summary->fetch();

// By category
$byCategorySql = "
    SELECT
        cat.name,
        COUNT(*) as n,
        SUM(CASE WHEN c.status = 'resolved' THEN 1 ELSE 0 END) as resolved
    FROM concerns c
    JOIN concern_categories cat ON c.category_id = cat.id
    $where
    GROUP BY cat.id
    ORDER BY n DESC
";
$byCategory = $pdo->prepare($byCategorySql);
$byCategory->execute($params);
$byCategory = $byCategory->fetchAll();

// By priority
$byPrioritySql = "
    SELECT
        c.priority,
        COUNT(*) as n
    FROM concerns c
    $where
    GROUP BY c.priority
    ORDER BY FIELD(c.priority, 'urgent', 'high', 'medium', 'low')
";
$byPriority = $pdo->prepare($byPrioritySql);
$byPriority->execute($params);
$byPriority = $byPriority->fetchAll();

// Detailed list
$detailSql = "
    SELECT
        c.tracking_code, c.title, c.status, c.priority, c.location,
        c.created_at, c.resolved_at,
        cat.name as category,
        u.full_name as resident, u.email as res_email,
        a.full_name as assigned
    FROM concerns c
    JOIN concern_categories cat ON c.category_id = cat.id
    JOIN users u ON c.user_id = u.id
    LEFT JOIN admins a ON c.assigned_to = a.id
    $where
    ORDER BY c.created_at DESC
";
$detailStmt = $pdo->prepare($detailSql);
$detailStmt->execute($params);
$details = $detailStmt->fetchAll();

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="concerns_report_'.date('Y-m-d').'.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Tracking Code','Title','Category','Status','Priority','Location','Resident','Email','Assigned To','Submitted','Resolved']);
    foreach($details as $d) {
        fputcsv($out, [
            $d['tracking_code'], $d['title'], $d['category'], $d['status'], $d['priority'],
            $d['location'], $d['resident'], $d['res_email'], $d['assigned'] ?? '',
            $d['created_at'], $d['resolved_at'] ?? ''
        ]);
    }
    fclose($out);
    exit;
}

$categories = $pdo->query("SELECT id, name FROM concern_categories WHERE is_active = 1 ORDER BY name")->fetchAll();

include dirname(__DIR__) . '/includes/header_admin.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <h4 class="fw-bold mb-0">Reports & Export</h4>
    <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="btn btn-success"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Export CSV</a>
</div>

<!-- Filters -->
<div class="filter-bar mb-4">
    <form class="row g-2 align-items-end" method="GET">
        <div class="col-md-2">
            <label class="form-label small fw-medium mb-1">From Date</label>
            <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($dateFrom) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-medium mb-1">To Date</label>
            <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($dateTo) ?>">
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
        <div class="col-md-3">
            <label class="form-label small fw-medium mb-1">Category</label>
            <select name="category" class="form-select form-select-sm">
                <option value="">All</option>
                <?php foreach($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $category==$cat['id']?'selected':'' ?>><?= sanitize($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button class="btn btn-primary btn-sm">Generate Report</button>
            <a href="reports.php" class="btn btn-outline-secondary btn-sm">Reset</a>
        </div>
    </form>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
<?php $cards = [
    ['Total',       $summary['total'] ?? 0,       'bi-collection',           '#1a3c5e', '#e8f0f8'],
    ['Pending',     $summary['pending'] ?? 0,     'bi-hourglass-split',      '#f59e0b', '#fff8e7'],
    ['In Progress', $summary['in_progress'] ?? 0, 'bi-arrow-repeat',         '#3b82f6', '#eff6ff'],
    ['Resolved',    $summary['resolved'] ?? 0,    'bi-check-circle',         '#10b981', '#ecfdf5'],
    ['Rejected',    $summary['rejected'] ?? 0,    'bi-x-circle',             '#ef4444', '#fef2f2'],
    ['Urgent',      $summary['urgent'] ?? 0,      'bi-exclamation-triangle', '#ef4444', '#fef2f2'],
];
foreach($cards as $_item): list($lbl, $val, $icon, $color, $bg) = $_item; ?>
<div class="col-6 col-md-4 col-xl-2">
    <div class="stat-card text-center">
        <div class="stat-icon mx-auto mb-2" style="background:<?= $bg ?>;color:<?= $color ?>">
            <i class="bi <?= $icon ?>"></i>
        </div>
        <div class="fs-3 fw-bold" style="color:<?= $color ?>"><?= number_format($val) ?></div>
        <div class="text-muted small"><?= $lbl ?></div>
    </div>
</div>
<?php endforeach; ?>
</div>

<?php if (!empty($summary['avg_resolve_hours'])): ?>
<div class="alert alert-info mb-4">
    <i class="bi bi-clock me-2"></i>
    Average resolution time: <strong><?= number_format($summary['avg_resolve_hours'], 1) ?> hours</strong>
    (<?= number_format($summary['avg_resolve_hours'] / 24, 1) ?> days)
</div>
<?php endif; ?>

<!-- Charts -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header bg-white py-3"><h6 class="mb-0 fw-semibold">By Category</h6></div>
            <div class="card-body"><canvas id="catChart" height="200"></canvas></div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header bg-white py-3"><h6 class="mb-0 fw-semibold">By Priority</h6></div>
            <div class="card-body"><canvas id="prioChart" height="200"></canvas></div>
        </div>
    </div>
</div>

<!-- Category Breakdown Table -->
<div class="card mb-4">
    <div class="card-header bg-white py-3"><h6 class="mb-0 fw-semibold">Breakdown by Category</h6></div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr><th>Category</th><th>Total</th><th>Resolved</th><th>Resolution Rate</th><th>Progress</th></tr>
            </thead>
            <tbody>
            <?php foreach($byCategory as $bc):
                $rate = $bc['n'] > 0 ? round($bc['resolved'] / $bc['n'] * 100) : 0; ?>
                <tr>
                    <td class="fw-medium"><?= sanitize($bc['name']) ?></td>
                    <td><span class="badge bg-primary"><?= $bc['n'] ?></span></td>
                    <td><span class="badge bg-success"><?= $bc['resolved'] ?></span></td>
                    <td><?= $rate ?>%</td>
                    <td style="width:200px">
                        <div class="progress" style="height:6px">
                            <div class="progress-bar bg-success" style="width:<?= $rate ?>%"></div>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Detail Table -->
<div class="admin-table">
    <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-semibold">Detailed List (<?= count($details) ?> records)</h6>
    </div>
    <div class="table-responsive" style="max-height:400px;overflow-y:auto">
        <table class="table table-sm align-middle mb-0">
            <thead class="sticky-top bg-white">
                <tr><th>Code</th><th>Title</th><th>Category</th><th>Status</th><th>Priority</th><th>Resident</th><th>Assigned</th><th>Submitted</th><th>Resolved</th></tr>
            </thead>
            <tbody>
            <?php foreach($details as $d): ?>
                <tr>
                    <td><code class="small"><?= sanitize($d['tracking_code']) ?></code></td>
                    <td class="small"><?= sanitize(substr($d['title'],0,35)) ?>...</td>
                    <td class="small"><?= sanitize($d['category']) ?></td>
                    <td><?= statusBadge($d['status']) ?></td>
                    <td><?= priorityBadge($d['priority']) ?></td>
                    <td class="small"><?= sanitize($d['resident']) ?></td>
                    <td class="small text-muted"><?= $d['assigned'] ? sanitize($d['assigned']) : '—' ?></td>
                    <td class="small text-muted"><?= formatDate($d['created_at'],'M d, Y') ?></td>
                    <td class="small text-muted"><?= $d['resolved_at'] ? formatDate($d['resolved_at'],'M d, Y') : '—' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('catChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($byCategory,'name')) ?>,
        datasets: [{
            label: 'Total',
            data: <?= json_encode(array_column($byCategory,'n')) ?>,
            backgroundColor: 'rgba(26,60,94,.7)',
            borderRadius: 6
        },{
            label: 'Resolved',
            data: <?= json_encode(array_column($byCategory,'resolved')) ?>,
            backgroundColor: 'rgba(16,185,129,.7)',
            borderRadius: 6
        }]
    },
    options: { responsive:true, plugins:{legend:{position:'top'}}, scales:{y:{beginAtZero:true,ticks:{stepSize:1}}} }
});

new Chart(document.getElementById('prioChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_map(function($r){ return ucfirst($r['priority']); }, $byPriority)) ?>,
        datasets: [{
            data: <?= json_encode(array_column($byPriority,'n')) ?>,
            backgroundColor: ['#ef4444','#f97316','#3b82f6','#6b7280'],
            borderWidth: 0
        }]
    },
    options: { responsive:true, plugins:{legend:{position:'bottom'}}, cutout:'60%' }
});
</script>

<?php include dirname(__DIR__) . '/includes/footer_admin.php'; ?>