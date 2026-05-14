<?php
require_once __DIR__ . '/includes/init.php';
$pageTitle = 'Announcements';
$pdo = db();

$page  = max(1, (int)($_GET['page'] ?? 1));
$total = $pdo->query("SELECT COUNT(*) FROM announcements WHERE is_published=1 AND (expires_at IS NULL OR expires_at > NOW())")->fetchColumn();
$pag   = paginate($total, $page);

$stmt = $pdo->prepare("SELECT a.*, ad.full_name as author FROM announcements a JOIN admins ad ON a.admin_id=ad.id WHERE a.is_published=1 AND (a.expires_at IS NULL OR a.expires_at > NOW()) ORDER BY a.is_pinned DESC, a.published_at DESC LIMIT ? OFFSET ?");
$stmt->execute([$pag['per_page'], $pag['offset']]);
$announcements = $stmt->fetchAll();

include 'includes/header_resident.php';
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h2 class="fw-bold mb-0"><i class="bi bi-megaphone text-warning me-2"></i>Announcements</h2>
    <span class="text-muted small"><?= $total ?> announcement(s)</span>
</div>

<?php if (empty($announcements)): ?>
    <div class="bhd-card p-5 text-center text-muted"><i class="bi bi-megaphone fs-1 mb-3 d-block"></i>No announcements yet.</div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach($announcements as $ann): ?>
        <div class="col-12">
            <div class="bhd-card p-4 announcement-card <?= $ann['is_pinned']?'pinned':'' ?>">
                <div class="d-flex align-items-start gap-3">
                    <?php if($ann['image']): ?>
                        <img src="<?= BASE_URL ?>/public/uploads/concerns/<?= sanitize($ann['image']) ?>" class="rounded" style="width:100px;height:80px;object-fit:cover">
                    <?php endif; ?>
                    <div class="flex-grow-1">
                        <div class="d-flex flex-wrap gap-2 mb-1">
                            <?php if($ann['is_pinned']): ?><span class="badge bg-primary"><i class="bi bi-pin-fill me-1"></i>Pinned</span><?php endif; ?>
                            <?php if(!isLoggedIn()): ?><span class="badge bg-secondary">Preview</span><?php endif; ?>
                        </div>
                        <h5 class="fw-semibold mb-1"><?= sanitize($ann['title']) ?></h5>
                        <?php if(isLoggedIn()): ?>
                            <p class="text-muted mb-2"><?= nl2br(sanitize($ann['content'])) ?></p>
                        <?php else: ?>
                            <p class="text-muted mb-2"><?= nl2br(sanitize(substr($ann['content'],0,200))) ?>... <a href="<?= BASE_URL ?>/resident/login.php">Login to read full announcement</a></p>
                        <?php endif; ?>
                        <small class="text-muted"><i class="bi bi-person me-1"></i><?= sanitize($ann['author']) ?> &bull; <i class="bi bi-calendar me-1"></i><?= formatDate($ann['published_at']) ?></small>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="mt-4"><?= renderPagination($pag, '?') ?></div>
<?php endif; ?>

<?php include 'includes/footer_resident.php'; ?>
