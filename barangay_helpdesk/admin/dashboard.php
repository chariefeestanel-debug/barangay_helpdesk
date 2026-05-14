<?php
require_once dirname(__DIR__) . '/includes/init.php';
$pageTitle = 'Dashboard';
$breadcrumb = 'Dashboard';
requireAdmin();

$pdo = db();

// Stats
$totalConcerns   = $pdo->query("SELECT COUNT(*) FROM concerns")->fetchColumn();
$pendingConcerns = $pdo->query("SELECT COUNT(*) FROM concerns WHERE status='pending'")->fetchColumn();
$resolvedToday   = $pdo->query("SELECT COUNT(*) FROM concerns WHERE status='resolved' AND DATE(resolved_at)=CURDATE()")->fetchColumn();
$totalResidents  = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active=1")->fetchColumn();
$urgentConcerns  = $pdo->query("SELECT COUNT(*) FROM concerns WHERE priority='urgent' AND status NOT IN ('resolved','rejected')")->fetchColumn();

// Concern status breakdown for chart
$byStatus = $pdo->query("SELECT status, COUNT(*) as n FROM concerns GROUP BY status")->fetchAll();
$byPriority = $pdo->query("SELECT priority, COUNT(*) as n FROM concerns GROUP BY priority")->fetchAll();

// Recent concerns
$recentConcerns = $pdo->query("SELECT c.*,cat.name as cat_name,u.full_name as resident FROM concerns c JOIN concern_categories cat ON c.category_id=cat.id JOIN users u ON c.user_id=u.id ORDER BY c.created_at DESC LIMIT 8")->fetchAll();

// Monthly trend (last 6 months)
$trend = $pdo->query("SELECT DATE_FORMAT(created_at,'%b %Y') as month, COUNT(*) as n FROM concerns WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY YEAR(created_at),MONTH(created_at) ORDER BY created_at ASC")->fetchAll();

// Recent announcements
$recentAnn = $pdo->query("SELECT title,published_at FROM announcements WHERE is_published=1 ORDER BY published_at DESC LIMIT 3")->fetchAll();

include dirname(__DIR__) . '/includes/header_admin.php';
?>
<h4 class="fw-bold mb-4">Dashboard Overview</h4>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
<?php $cards = [
    ['Total Concerns',   $totalConcerns,   'bi-collection',          '#1a3c5e', '#e8f0f8'],
    ['Pending',          $pendingConcerns,  'bi-hourglass-split',    '#f59e0b', '#fff8e7'],
    ['Resolved Today',   $resolvedToday,    'bi-check-circle',       '#10b981', '#e8f9f4'],
    ['Total Residents',  $totalResidents,   'bi-people',             '#6366f1', '#f0f0ff'],
    ['Urgent Cases',     $urgentConcerns,   'bi-exclamation-triangle','#ef4444','#fff0f0'],
];
foreach($cards as $_item): list($label,$val,$icon,$color,$bg) = $_item; ?>
<div class="col-6 col-md-4 col-xl">
    <div class="stat-card">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="stat-icon" style="background:<?= $bg ?>;color:<?= $color ?>">
                <i class="bi <?= $icon ?>"></i>
            </div>
        </div>
        <div class="fs-2 fw-bold" style="color:<?= $color ?>"><?= number_format($val) ?></div>
        <div class="text-muted small"><?= $label ?></div>
    </div>
</div>
<?php endforeach; ?>
</div>

<!-- Charts Row -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-bar-chart me-2 text-primary"></i>Monthly Concern Trend</h6>
            </div>
            <div class="card-body"><canvas id="trendChart" height="120"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-pie-chart me-2 text-primary"></i>Status Breakdown</h6>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center"><canvas id="statusChart" height="220"></canvas></div>
        </div>
    </div>
</div>

<!-- Recent Concerns + Announcements -->
<div class="row g-4">
<div class="col-lg-8">
    <div class="card">
        <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-exclamation-circle me-2 text-primary"></i>Recent Concerns</h6>
            <a href="<?= BASE_URL ?>/admin/concerns.php" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
        <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr>
                <th>Code</th><th>Title</th><th>Priority</th><th>Status</th><th>Date</th><th></th>
            </tr></thead>
            <tbody>
            <?php foreach($recentConcerns as $c): ?>
            <tr class="priority-<?= $c['priority'] ?>">
                <td><code class="small"><?= sanitize($c['tracking_code']) ?></code></td>
                <td class="fw-medium small"><?= sanitize(substr($c['title'],0,40)) ?>...</td>
                <td><?= priorityBadge($c['priority']) ?></td>
                <td><?= statusBadge($c['status']) ?></td>
                <td><small class="text-muted"><?= timeAgo($c['created_at']) ?></small></td>
                <td><a href="<?= BASE_URL ?>/admin/concern_detail.php?id=<?= $c['id'] ?>" class="btn btn-xs btn-outline-secondary btn-sm">View</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
<div class="col-lg-4">
    <div class="card">
        <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-megaphone me-2 text-primary"></i>Announcements</h6>
            <a href="<?= BASE_URL ?>/admin/announcements.php" class="btn btn-sm btn-outline-primary">Manage</a>
        </div>
        <div class="card-body">
            <?php if(empty($recentAnn)): ?>
                <p class="text-muted small text-center py-3">No announcements yet.</p>
            <?php else: ?>
                <?php foreach($recentAnn as $a): ?>
                <div class="border-bottom pb-2 mb-2">
                    <div class="fw-medium small"><?= sanitize($a['title']) ?></div>
                    <div class="text-muted" style="font-size:.75rem"><?= formatDate($a['published_at'],'M d, Y') ?></div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/admin/announcements.php?action=create" class="btn btn-sm btn-warning w-100 mt-2"><i class="bi bi-plus me-1"></i>New Announcement</a>
        </div>
    </div>
    <!-- Priority Breakdown -->
    <div class="card mt-4">
        <div class="card-header bg-white py-3"><h6 class="mb-0 fw-semibold"><i class="bi bi-flag me-2 text-primary"></i>By Priority</h6></div>
        <div class="card-body">
            <?php $pColors = ['urgent'=>'danger','high'=>'warning','medium'=>'primary','low'=>'secondary'];
            foreach($byPriority as $bp):
                $pct = $totalConcerns>0 ? round($bp['n']/$totalConcerns*100) : 0; ?>
            <div class="mb-2">
                <div class="d-flex justify-content-between small mb-1">
                    <span class="text-capitalize"><?= $bp['priority'] ?></span>
                    <span><?= $bp['n'] ?> (<?= $pct ?>%)</span>
                </div>
                <div class="progress" style="height:6px">
                    <div class="progress-bar bg-<?= $pColors[$bp['priority']]??'secondary' ?>" style="width:<?= $pct ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
</div>

<script>
const trendCtx = document.getElementById('trendChart');
const trendData = <?= json_encode(array_column($trend,'n')) ?>;
const trendLabels = <?= json_encode(array_column($trend,'month')) ?>;
new Chart(trendCtx, {
    type:'line',
    data:{labels:trendLabels,datasets:[{label:'Concerns',data:trendData,borderColor:'#1a3c5e',backgroundColor:'rgba(26,60,94,.1)',fill:true,tension:.4,pointBackgroundColor:'#1a3c5e'}]},
    options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{stepSize:1}}}}
});
const statusCtx = document.getElementById('statusChart');
const statusData = <?= json_encode(array_column($byStatus,'n')) ?>;
const statusLabels = <?= json_encode(array_map(function($r){ return ucfirst(str_replace('_',' ',$r['status'])); }, $byStatus)) ?>;
new Chart(statusCtx, {
    type:'doughnut',
    data:{labels:statusLabels,datasets:[{data:statusData,backgroundColor:['#f59e0b','#3b82f6','#10b981','#ef4444'],borderWidth:0}]},
    options:{responsive:true,plugins:{legend:{position:'bottom',labels:{font:{size:11}}}},cutout:'65%'}
});
</script>

<?php include dirname(__DIR__) . '/includes/footer_admin.php'; ?>