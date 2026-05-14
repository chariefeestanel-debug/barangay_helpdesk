<?php
require_once dirname(__DIR__) . '/includes/init.php';
$pageTitle = 'Categories';
$breadcrumb = 'Categories';
requireAdmin();

$pdo = db();

if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!verifyCsrfToken($_POST['csrf_token']??'')) { setFlash('error','Invalid token.'); redirect(BASE_URL.'/admin/categories.php'); }
    requireRole('staff');
    $act  = $_POST['action'] ?? '';
    $name = sanitize($_POST['name'] ?? '');
    $desc = sanitize($_POST['description'] ?? '');
    $icon = sanitize($_POST['icon'] ?? 'bi-question-circle');

    if ($act==='create' && $name) {
        $pdo->prepare("INSERT INTO concern_categories (name,description,icon) VALUES (?,?,?)")->execute([$name,$desc,$icon]);
        setFlash('success','Category created.');
    } elseif ($act==='update') {
        $catId = (int)($_POST['cat_id']??0);
        $active = isset($_POST['is_active'])?1:0;
        $pdo->prepare("UPDATE concern_categories SET name=?,description=?,icon=?,is_active=? WHERE id=?")->execute([$name,$desc,$icon,$active,$catId]);
        setFlash('success','Category updated.');
    } elseif ($act==='delete') {
        $catId = (int)($_POST['cat_id']??0);
        $inUse = $pdo->prepare("SELECT COUNT(*) FROM concerns WHERE category_id=?");
        $inUse->execute([$catId]);
        if ($inUse->fetchColumn()>0) { setFlash('error','Cannot delete: category is in use.'); }
        else { $pdo->prepare("DELETE FROM concern_categories WHERE id=?")->execute([$catId]); setFlash('success','Category deleted.'); }
    }
    redirect(BASE_URL.'/admin/categories.php');
}

$editId  = (int)($_GET['edit']??0);
$cats    = $pdo->query("SELECT *,(SELECT COUNT(*) FROM concerns WHERE category_id=concern_categories.id) as usage_count FROM concern_categories ORDER BY name")->fetchAll();
$editCat = $editId ? $pdo->prepare("SELECT * FROM concern_categories WHERE id=?") : null;
if ($editCat) { $editCat->execute([$editId]); $editCat=$editCat->fetch(); }

$icons = ['bi-lightbulb','bi-water','bi-trash','bi-volume-up','bi-sign-stop','bi-shield-exclamation','bi-house','bi-tree','bi-droplet','bi-bug','bi-camera','bi-wifi','bi-question-circle','bi-three-dots'];

include dirname(__DIR__) . '/includes/header_admin.php';
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0">Concern Categories</h4>
    <button class="btn btn-warning" data-bs-toggle="collapse" data-bs-target="#createForm"><i class="bi bi-plus-circle me-1"></i>Add Category</button>
</div>

<!-- Create Form -->
<div class="collapse <?= !$editId?'':'' ?>" id="createForm">
<div class="card mb-4">
    <div class="card-header bg-white"><h6 class="mb-0 fw-semibold">New Category</h6></div>
    <div class="card-body">
        <form method="POST" class="row g-3">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="create">
            <div class="col-md-4"><label class="form-label small fw-medium">Name *</label><input type="text" name="name" class="form-control form-control-sm" required maxlength="100"></div>
            <div class="col-md-5"><label class="form-label small fw-medium">Description</label><input type="text" name="description" class="form-control form-control-sm" maxlength="255"></div>
            <div class="col-md-3">
                <label class="form-label small fw-medium">Icon</label>
                <select name="icon" class="form-select form-select-sm">
                    <?php foreach($icons as $ic): ?><option value="<?= $ic ?>"><?= $ic ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-12"><button class="btn btn-primary btn-sm px-4">Create Category</button></div>
        </form>
    </div>
</div>
</div>

<!-- Edit Form -->
<?php if ($editCat): ?>
<div class="card mb-4 border-warning">
    <div class="card-header bg-warning bg-opacity-10"><h6 class="mb-0 fw-semibold">Edit: <?= sanitize($editCat['name']) ?></h6></div>
    <div class="card-body">
        <form method="POST" class="row g-3">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="cat_id" value="<?= $editCat['id'] ?>">
            <div class="col-md-4"><label class="form-label small fw-medium">Name *</label><input type="text" name="name" class="form-control form-control-sm" value="<?= htmlspecialchars($editCat['name']) ?>" required></div>
            <div class="col-md-4"><label class="form-label small fw-medium">Description</label><input type="text" name="description" class="form-control form-control-sm" value="<?= htmlspecialchars($editCat['description']) ?>"></div>
            <div class="col-md-2"><label class="form-label small fw-medium">Icon</label><select name="icon" class="form-select form-select-sm"><?php foreach($icons as $ic): ?><option value="<?= $ic ?>" <?= $editCat['icon']===$ic?'selected':'' ?>><?= $ic ?></option><?php endforeach; ?></select></div>
            <div class="col-md-2 d-flex align-items-end"><div class="form-check"><input type="checkbox" class="form-check-input" name="is_active" id="ca" <?= $editCat['is_active']?'checked':'' ?>><label for="ca" class="form-check-label small">Active</label></div></div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary btn-sm px-4">Save Changes</button>
                <a href="?" class="btn btn-outline-secondary btn-sm">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Table -->
<div class="admin-table">
<table class="table table-hover align-middle mb-0">
    <thead><tr><th>Icon</th><th>Name</th><th>Description</th><th>Usage</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach($cats as $cat): ?>
    <tr>
        <td><i class="bi <?= sanitize($cat['icon']) ?> fs-4 text-primary"></i></td>
        <td class="fw-medium"><?= sanitize($cat['name']) ?></td>
        <td class="text-muted small"><?= sanitize($cat['description']) ?></td>
        <td><span class="badge bg-secondary"><?= $cat['usage_count'] ?> concerns</span></td>
        <td><?= $cat['is_active']?'<span class="badge bg-success">Active</span>':'<span class="badge bg-secondary">Inactive</span>' ?></td>
        <td>
            <a href="?edit=<?= $cat['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
            <?php if($cat['usage_count']==0 && $_SESSION['admin_role']==='super_admin'): ?>
            <form method="POST" class="d-inline">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="cat_id" value="<?= $cat['id'] ?>">
                <button class="btn btn-sm btn-outline-danger" data-confirm="Delete this category?"><?= '<i class="bi bi-trash"></i>' ?></button>
            </form>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php include dirname(__DIR__) . '/includes/footer_admin.php'; ?>
