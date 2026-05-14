<?php
require_once dirname(__DIR__) . '/includes/init.php';
$pageTitle = 'Concern Detail';
$breadcrumb = 'Concern Detail';
requireAdmin();

$pdo = db();
$id  = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT c.*,cat.name as cat_name,u.full_name as resident,u.email as res_email,u.phone as res_phone,a.full_name as assigned_name FROM concerns c JOIN concern_categories cat ON c.category_id=cat.id JOIN users u ON c.user_id=u.id LEFT JOIN admins a ON c.assigned_to=a.id WHERE c.id=?");
$stmt->execute([$id]);
$concern = $stmt->fetch();
if (!$concern) { setFlash('error','Concern not found.'); redirect(BASE_URL.'/admin/concerns.php'); }

// Handle POST actions
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!verifyCsrfToken($_POST['csrf_token']??'')) { setFlash('error','Invalid token.'); redirect(BASE_URL.'/admin/concern_detail.php?id='.$id); }
    
    $action = $_POST['action'] ?? '';

    // Update status
    if ($action === 'update_status') {
        $newStatus  = $_POST['status'] ?? '';
        $newPriority= $_POST['priority'] ?? '';
        $assignedTo = (int)($_POST['assigned_to'] ?? 0);
        $note       = sanitize($_POST['note'] ?? '');
        $validStatus = ['pending','in_progress','resolved','rejected'];
        if (in_array($newStatus, $validStatus)) {
            $resolvedAt = ($newStatus==='resolved') ? date('Y-m-d H:i:s') : null;
            $pdo->prepare("UPDATE concerns SET status=?,priority=?,assigned_to=?,resolved_at=?,updated_at=NOW() WHERE id=?")->execute([$newStatus,$newPriority,$assignedTo?:null,$resolvedAt,$id]);
            $pdo->prepare("INSERT INTO concern_status_history (concern_id,changed_by,changer_type,old_status,new_status,note) VALUES (?,?,'admin',?,?,?)")->execute([$id,$_SESSION['admin_id'],$concern['status'],$newStatus,$note]);
            logActivity('admin',$_SESSION['admin_id'],'Updated concern status','concern',$id);
            setFlash('success','Concern updated successfully.');
        }
    }

    // Add reply
    if ($action === 'reply') {
        $msg = sanitize($_POST['message'] ?? '');
        if (strlen($msg) >= 2) {
            $pdo->prepare("INSERT INTO concern_replies (concern_id,sender_type,sender_id,message) VALUES (?,'admin',?,?)")->execute([$id,$_SESSION['admin_id'],$msg]);
            logActivity('admin',$_SESSION['admin_id'],'Replied to concern','concern',$id);
            setFlash('success','Reply sent to resident.');
        }
    }

    redirect(BASE_URL.'/admin/concern_detail.php?id='.$id);
}

// Mark user replies as read
$pdo->prepare("UPDATE concern_replies SET is_read=1 WHERE concern_id=? AND sender_type='user'")->execute([$id]);

$replies     = $pdo->prepare("SELECT r.*,CASE WHEN r.sender_type='admin' THEN a.full_name ELSE u.full_name END as sender_name FROM concern_replies r LEFT JOIN admins a ON r.sender_type='admin' AND r.sender_id=a.id LEFT JOIN users u ON r.sender_type='user' AND r.sender_id=u.id WHERE r.concern_id=? ORDER BY r.created_at ASC");
$replies->execute([$id]); $replies=$replies->fetchAll();

$attachments = $pdo->prepare("SELECT * FROM concern_attachments WHERE concern_id=?");
$attachments->execute([$id]); $attachments=$attachments->fetchAll();

$history = $pdo->prepare("SELECT h.*,CASE WHEN h.changer_type='admin' THEN a.full_name ELSE u.full_name END as changer_name FROM concern_status_history h LEFT JOIN admins a ON h.changer_type='admin' AND h.changed_by=a.id LEFT JOIN users u ON h.changer_type='user' AND h.changed_by=u.id WHERE h.concern_id=? ORDER BY h.changed_at ASC");
$history->execute([$id]); $history=$history->fetchAll();

$admins     = $pdo->query("SELECT id,full_name,position FROM admins WHERE is_active=1 ORDER BY full_name")->fetchAll();
$categories_list = $pdo->query("SELECT * FROM concern_categories WHERE is_active=1")->fetchAll();

include dirname(__DIR__) . '/includes/header_admin.php';
?>
<div class="mb-3"><a href="<?= BASE_URL ?>/admin/concerns.php" class="text-decoration-none small"><i class="bi bi-arrow-left me-1"></i>Back to Concerns</a></div>

<!-- Header -->
<div class="d-flex flex-wrap align-items-center gap-3 mb-4">
    <div>
        <h5 class="fw-bold mb-1"><?= sanitize($concern['title']) ?></h5>
        <code class="small"><?= sanitize($concern['tracking_code']) ?></code>
    </div>
    <div class="ms-auto d-flex gap-2 flex-wrap">
        <?= priorityBadge($concern['priority']) ?>
        <?= statusBadge($concern['status']) ?>
    </div>
</div>

<div class="row g-4">
<!-- Left Column -->
<div class="col-lg-8">

    <!-- Concern Info -->
    <div class="card mb-4">
        <div class="card-header bg-white"><h6 class="mb-0 fw-semibold"><i class="bi bi-file-text me-2 text-primary"></i>Concern Details</h6></div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-sm-6"><span class="text-muted small">Category:</span><br><strong><?= sanitize($concern['cat_name']) ?></strong></div>
                <div class="col-sm-6"><span class="text-muted small">Location:</span><br><strong><?= $concern['location'] ? sanitize($concern['location']) : '—' ?></strong></div>
            </div>
            <div class="text-muted small mb-1">Description:</div>
            <p class="mb-0" style="white-space:pre-wrap"><?= nl2br(sanitize($concern['description'])) ?></p>
        </div>
    </div>

    <!-- Attachments -->
    <?php if($attachments): ?>
    <div class="card mb-4">
        <div class="card-header bg-white"><h6 class="mb-0 fw-semibold"><i class="bi bi-paperclip me-2 text-primary"></i>Attachments (<?= count($attachments) ?>)</h6></div>
        <div class="card-body d-flex flex-wrap gap-2">
            <?php foreach($attachments as $att): ?>
            <div class="text-center">
                <img src="<?= UPLOAD_URL.sanitize($att['file_path']) ?>" class="attachment-thumb d-block" alt="<?= sanitize($att['file_name']) ?>">
                <small class="text-muted d-block mt-1"><?= fileSize($att['file_size']) ?></small>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Replies -->
    <div class="card">
        <div class="card-header bg-white"><h6 class="mb-0 fw-semibold"><i class="bi bi-chat-dots me-2 text-primary"></i>Conversation</h6></div>
        <div class="card-body">
            <div class="chat-panel mb-3">
                <?php if(empty($replies)): ?>
                    <div class="text-muted text-center small py-4">No messages yet.</div>
                <?php else: ?>
                    <?php foreach($replies as $r): ?>
                    <div class="d-flex flex-column <?= $r['sender_type']==='admin'?'align-items-start':'align-items-end' ?>">
                        <div class="reply-bubble <?= $r['sender_type']==='admin'?'admin-bubble':'user-bubble' ?>">
                            <div class="fw-semibold small mb-1">
                                <?= sanitize($r['sender_name']) ?>
                                <?php if($r['sender_type']==='admin'): ?><span class="badge bg-warning text-dark ms-1">Staff</span><?php endif; ?>
                            </div>
                            <?= nl2br(sanitize($r['message'])) ?>
                            <div class="reply-meta"><?= timeAgo($r['created_at']) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php if($_SESSION['admin_role']!=='viewer'): ?>
            <form method="POST" class="d-flex gap-2">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="reply">
                <textarea name="message" class="form-control form-control-sm" rows="2" placeholder="Send a response to the resident..." required></textarea>
                <button class="btn btn-primary px-3"><i class="bi bi-send-fill"></i></button>
            </form>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- Right Column -->
<div class="col-lg-4">

    <!-- Resident Info -->
    <div class="card mb-4">
        <div class="card-header bg-white"><h6 class="mb-0 fw-semibold"><i class="bi bi-person me-2 text-primary"></i>Resident Info</h6></div>
        <div class="card-body">
            <div class="fw-medium"><?= sanitize($concern['resident']) ?></div>
            <div class="small text-muted"><?= sanitize($concern['res_email']) ?></div>
            <?php if($concern['res_phone']): ?><div class="small text-muted"><?= sanitize($concern['res_phone']) ?></div><?php endif; ?>
            <div class="mt-2 text-muted small">Submitted: <?= formatDate($concern['created_at']) ?></div>
        </div>
    </div>

    <!-- Update Status Panel -->
    <?php if($_SESSION['admin_role']!=='viewer'): ?>
    <div class="card mb-4">
        <div class="card-header bg-white"><h6 class="mb-0 fw-semibold"><i class="bi bi-sliders me-2 text-primary"></i>Update Concern</h6></div>
        <div class="card-body">
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update_status">
                <div class="mb-3">
                    <label class="form-label small fw-medium">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <?php foreach(['pending','in_progress','resolved','rejected'] as $s): ?>
                            <option value="<?= $s ?>" <?= $concern['status']===$s?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-medium">Priority</label>
                    <select name="priority" class="form-select form-select-sm">
                        <?php foreach(['low','medium','high','urgent'] as $p): ?>
                            <option value="<?= $p ?>" <?= $concern['priority']===$p?'selected':'' ?>><?= ucfirst($p) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-medium">Assign To</label>
                    <select name="assigned_to" class="form-select form-select-sm">
                        <option value="">Unassigned</option>
                        <?php foreach($admins as $adm): ?>
                            <option value="<?= $adm['id'] ?>" <?= $concern['assigned_to']==$adm['id']?'selected':'' ?>><?= sanitize($adm['full_name']) ?><?= $adm['position']?' ('.$adm['position'].')':'' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-medium">Note (optional)</label>
                    <textarea name="note" class="form-control form-control-sm" rows="2" placeholder="Add a note for the status change..."></textarea>
                </div>
                <button class="btn btn-primary btn-sm w-100">Update Concern</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Status History -->
    <div class="card">
        <div class="card-header bg-white"><h6 class="mb-0 fw-semibold"><i class="bi bi-clock-history me-2 text-primary"></i>History</h6></div>
        <div class="card-body">
            <?php if(empty($history)): ?>
                <p class="text-muted small">No history.</p>
            <?php else: ?>
            <div class="timeline">
                <?php foreach($history as $h): ?>
                <div class="timeline-item">
                    <div class="small fw-medium"><?= $h['new_status'] ? ucfirst(str_replace('_',' ',$h['new_status'])) : 'Submitted' ?></div>
                    <?php if($h['note']): ?><div class="small text-muted"><?= sanitize($h['note']) ?></div><?php endif; ?>
                    <div class="text-muted" style="font-size:.72rem"><?= timeAgo($h['changed_at']) ?> &bull; <?= sanitize($h['changer_name']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>

<?php include dirname(__DIR__) . '/includes/footer_admin.php'; ?>
