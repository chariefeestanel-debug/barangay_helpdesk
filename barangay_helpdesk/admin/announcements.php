<?php
require_once dirname(__DIR__) . '/includes/init.php';
$pageTitle = 'Announcements';
$breadcrumb = 'Announcements';
requireAdmin();

$pdo    = db();
$action = $_GET['action'] ?? '';
$editId = (int)($_GET['edit'] ?? 0);

// Handle POST
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!verifyCsrfToken($_POST['csrf_token']??'')) { setFlash('error','Invalid token.'); redirect(BASE_URL.'/admin/announcements.php'); }
    if ($_SESSION['admin_role']==='viewer') { setFlash('error','No permission.'); redirect(BASE_URL.'/admin/announcements.php'); }
    
    $postAction = $_POST['action'] ?? '';
    $title      = sanitize($_POST['title'] ?? '');
    $content    = sanitize($_POST['content'] ?? '');
    $isPinned   = isset($_POST['is_pinned']) ? 1 : 0;
    $isPublished= isset($_POST['is_published']) ? 1 : 0;
    $expiresAt  = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;

    if ($postAction==='create') {
        $pdo->prepare("INSERT INTO announcements (admin_id,title,content,is_pinned,is_published,expires_at) VALUES (?,?,?,?,?,?)")->execute([$_SESSION['admin_id'],$title,$content,$isPinned,$isPublished,$expiresAt]);
        logActivity('admin',$_SESSION['admin_id'],'Created announcement');
        setFlash('success','Announcement created.');
    } elseif ($postAction==='update' && $editId) {
        $pdo->prepare("UPDATE announcements SET title=?,content=?,is_pinned=?,is_published=?,expires_at=?,updated_at=NOW() WHERE id=?")->execute([$title,$content,$isPinned,$isPublished,$expiresAt,$editId]);
        setFlash('success','Announcement updated.');
    } elseif ($postAction==='delete') {
        $delId = (int)($_POST['delete_id'] ?? 0);
        $pdo->prepare("DELETE FROM announcements WHERE id=?")->execute([$delId]);
        logActivity('admin',$_SESSION['admin_id'],'Deleted announcement','announcement',$delId);
        setFlash('success','Announcement deleted.');
    }
    redirect(BASE_URL.'/admin/announcements.php');
}

$announcements = $pdo->query("SELECT a.*,ad.full_name as author FROM announcements a JOIN admins ad ON a.admin_id=ad.id ORDER BY a.is_pinned DESC, a.published_at DESC")->fetchAll();
$editAnn = $editId ? $pdo->prepare("SELECT * FROM announcements WHERE id=?") : null;
if ($editAnn) { $editAnn->execute([$editId]); $editAnn=$editAnn->fetch(); }

include dirname(__DIR__) . '/includes/header_admin.php';
?>
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <h4 class="fw-bold mb-0">Announcements</h4>
    <a href="?action=create" class="btn btn-warning"><i class="bi bi-plus-circle me-1"></i>New Announcement</a>
</div>

<!-- Create / Edit Form -->
<?php if ($action==='create' || $editId): ?>
<div class="card mb-4">
    <div class="card-header bg-white"><h6 class="mb-0 fw-semibold"><?= $editId?'Edit':'Create' ?> Announcement</h6></div>
    <div class="card-body">
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="<?= $editId?'update':'create' ?>">
            <div class="mb-3">
                <label class="form-label fw-medium small">Title <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control form-control-sm" value="<?= htmlspecialchars($editAnn['title']??'') ?>" required maxlength="255">
            </div>
            <div class="mb-3">
                <label class="form-label fw-medium small">Content <span class="text-danger">*</span></label>
                <textarea name="content" class="form-control form-control-sm" rows="6" required><?= htmlspecialchars($editAnn['content']??'') ?></textarea>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label fw-medium small">Expires At (optional)</label>
                    <input type="datetime-local" name="expires_at" class="form-control form-control-sm" value="<?= isset($editAnn['expires_at'])?date('Y-m-d\TH:i',$editAnn['expires_at']?strtotime($editAnn['expires_at']):0):'' ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end gap-3">
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="is_pinned" id="isPinned" <?= ($editAnn['is_pinned']??0)?'checked':'' ?>><label class="form-check-label small" for="isPinned">Pin to top</label></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="is_published" id="isPublished" <?= ($editAnn['is_published']??1)?'checked':'' ?>><label class="form-check-label small" for="isPublished">Published</label></div>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary btn-sm px-4"><?= $editId?'Update':'Publish' ?> Announcement</button>
                <a href="?" class="btn btn-outline-secondary btn-sm">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- List -->
<div class="admin-table">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0">
    <thead><tr><th>Title</th><th>Author</th><th>Published</th><th>Pinned</th><th>Expires</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php if(empty($announcements)): ?>
        <tr><td colspan="7" class="text-center py-4 text-muted">No announcements yet.</td></tr>
    <?php else: ?>
    <?php foreach($announcements as $ann): ?>
    <tr>
        <td class="fw-medium"><?= sanitize($ann['title']) ?></td>
        <td class="small"><?= sanitize($ann['author']) ?></td>
        <td class="small text-muted"><?= formatDate($ann['published_at'],'M d, Y') ?></td>
        <td><?= $ann['is_pinned']?'<span class="badge bg-primary">Pinned</span>':'—' ?></td>
        <td class="small text-muted"><?= $ann['expires_at']?formatDate($ann['expires_at'],'M d, Y'):'Never' ?></td>
        <td><?= $ann['is_published']?'<span class="badge bg-success">Published</span>':'<span class="badge bg-secondary">Draft</span>' ?></td>
        <td>
            <?php if($_SESSION['admin_role']!=='viewer'): ?>
            <a href="?edit=<?= $ann['id'] ?>" class="btn btn-xs btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
            <form method="POST" class="d-inline" onsubmit="return confirm('Delete this announcement?')">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="delete_id" value="<?= $ann['id'] ?>">
                <button class="btn btn-xs btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>
</div>
</div>

<?php include dirname(__DIR__) . '/includes/footer_admin.php'; ?>
