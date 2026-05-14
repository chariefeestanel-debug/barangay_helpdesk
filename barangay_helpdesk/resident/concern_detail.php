<?php
require_once dirname(__DIR__) . '/includes/init.php';
$pageTitle = 'Concern Detail';
requireLogin();

$pdo = db();
$id  = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT c.*,cat.name as cat_name,u.full_name as resident_name,a.full_name as assigned_name FROM concerns c JOIN concern_categories cat ON c.category_id=cat.id JOIN users u ON c.user_id=u.id LEFT JOIN admins a ON c.assigned_to=a.id WHERE c.id=? AND c.user_id=?");
$stmt->execute([$id, $_SESSION['user_id']]);
$concern = $stmt->fetch();
if (!$concern) { setFlash('error','Concern not found.'); redirect(BASE_URL.'/resident/my_concerns.php'); }

// Mark admin replies as read
$pdo->prepare("UPDATE concern_replies SET is_read=1 WHERE concern_id=? AND sender_type='admin'")->execute([$id]);

// Post reply
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['message'])) {
    if (!verifyCsrfToken($_POST['csrf_token']??'')) { setFlash('error','Invalid token.'); redirect(BASE_URL.'/resident/concern_detail.php?id='.$id); }
    $msg = sanitize($_POST['message']);
    if (strlen($msg) >= 2) {
        $pdo->prepare("INSERT INTO concern_replies (concern_id,sender_type,sender_id,message) VALUES (?,'user',?,?)")->execute([$id,$_SESSION['user_id'],$msg]);
        logActivity('user',$_SESSION['user_id'],'Added reply to concern','concern',$id);
        setFlash('success','Reply sent.');
    }
    redirect(BASE_URL.'/resident/concern_detail.php?id='.$id);
}

$replies     = $pdo->prepare("SELECT r.*,CASE WHEN r.sender_type='admin' THEN a.full_name ELSE u.full_name END as sender_name FROM concern_replies r LEFT JOIN admins a ON r.sender_type='admin' AND r.sender_id=a.id LEFT JOIN users u ON r.sender_type='user' AND r.sender_id=u.id WHERE r.concern_id=? ORDER BY r.created_at ASC");
$replies->execute([$id]); $replies = $replies->fetchAll();

$attachments = $pdo->prepare("SELECT * FROM concern_attachments WHERE concern_id=?");
$attachments->execute([$id]); $attachments = $attachments->fetchAll();

$history = $pdo->prepare("SELECT h.*,CASE WHEN h.changer_type='admin' THEN a.full_name ELSE u.full_name END as changer_name FROM concern_status_history h LEFT JOIN admins a ON h.changer_type='admin' AND h.changed_by=a.id LEFT JOIN users u ON h.changer_type='user' AND h.changed_by=u.id WHERE h.concern_id=? ORDER BY h.changed_at ASC");
$history->execute([$id]); $history = $history->fetchAll();

include dirname(__DIR__) . '/includes/header_resident.php';
?>
<div class="mb-3"><a href="<?= BASE_URL ?>/resident/my_concerns.php" class="text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Back to My Concerns</a></div>

<!-- Tracking Banner -->
<div class="tracking-banner p-3 mb-4 d-flex align-items-center gap-3">
    <i class="bi bi-qr-code fs-2 text-primary"></i>
    <div>
        <div class="fw-bold text-primary">Tracking Code</div>
        <div class="fs-5 fw-bold font-monospace"><?= sanitize($concern['tracking_code']) ?></div>
    </div>
    <div class="ms-auto text-end">
        <?= statusBadge($concern['status']) ?>
        <div class="small text-muted mt-1"><?= formatDate($concern['created_at']) ?></div>
    </div>
</div>

<div class="row g-4">
<!-- Left: Concern Details -->
<div class="col-lg-8">
    <div class="bhd-card p-4 mb-4">
        <div class="d-flex flex-wrap gap-2 mb-3">
            <?= priorityBadge($concern['priority']) ?>
            <span class="badge bg-secondary"><?= sanitize($concern['cat_name']) ?></span>
        </div>
        <h4 class="fw-bold mb-2"><?= sanitize($concern['title']) ?></h4>
        <?php if($concern['location']): ?>
            <p class="text-muted small mb-2"><i class="bi bi-geo-alt me-1"></i><?= sanitize($concern['location']) ?></p>
        <?php endif; ?>
        <hr>
        <p class="mb-0" style="white-space:pre-wrap"><?= nl2br(sanitize($concern['description'])) ?></p>
    </div>

    <!-- Attachments -->
    <?php if($attachments): ?>
    <div class="bhd-card p-4 mb-4">
        <h6 class="fw-semibold mb-3"><i class="bi bi-paperclip me-1"></i>Attachments</h6>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach($attachments as $att): ?>
                <div class="text-center">
                    <img src="<?= UPLOAD_URL . sanitize($att['file_path']) ?>" class="attachment-thumb d-block" alt="<?= sanitize($att['file_name']) ?>">
                    <small class="text-muted d-block mt-1"><?= fileSize($att['file_size']) ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Replies -->
    <div class="bhd-card p-4">
        <h6 class="fw-semibold mb-3"><i class="bi bi-chat-dots me-1"></i>Conversation</h6>
        <div class="d-flex flex-column gap-3 mb-4" style="max-height:400px;overflow-y:auto">
            <?php if(empty($replies)): ?>
                <p class="text-muted text-center small py-3">No messages yet. Ask a question below.</p>
            <?php else: ?>
                <?php foreach($replies as $r): ?>
                <div class="d-flex flex-column <?= $r['sender_type']==='admin'?'align-items-start':'align-items-end' ?>">
                    <div class="reply-bubble <?= $r['sender_type']==='admin'?'admin-bubble':'user-bubble' ?>">
                        <div class="fw-semibold small mb-1"><?= sanitize($r['sender_name']) ?> <?= $r['sender_type']==='admin'?'<span class="badge bg-warning text-dark">Official</span>':'' ?></div>
                        <?= nl2br(sanitize($r['message'])) ?>
                        <div class="small mt-1 opacity-75"><?= timeAgo($r['created_at']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php if(in_array($concern['status'],['pending','in_progress'])): ?>
        <form method="POST" class="mt-3">
            <?= csrfField() ?>
            <div class="input-group">
                <textarea name="message" class="form-control" rows="2" placeholder="Type your message..." required></textarea>
                <button class="btn btn-primary px-3"><i class="bi bi-send-fill"></i></button>
            </div>
        </form>
        <?php else: ?>
            <div class="alert alert-secondary small mb-0"><i class="bi bi-lock me-1"></i>This concern is <?= $concern['status'] ?>. Replies are closed.</div>
        <?php endif; ?>
    </div>
</div>

<!-- Right: Status Timeline -->
<div class="col-lg-4">
    <div class="bhd-card p-4 mb-4">
        <h6 class="fw-semibold mb-3"><i class="bi bi-info-circle me-1"></i>Concern Info</h6>
        <dl class="row small mb-0">
            <dt class="col-5 text-muted">Status</dt>     <dd class="col-7"><?= statusBadge($concern['status']) ?></dd>
            <dt class="col-5 text-muted">Priority</dt>   <dd class="col-7"><?= priorityBadge($concern['priority']) ?></dd>
            <dt class="col-5 text-muted">Category</dt>   <dd class="col-7"><?= sanitize($concern['cat_name']) ?></dd>
            <dt class="col-5 text-muted">Submitted</dt>  <dd class="col-7"><?= formatDate($concern['created_at'],'M d, Y') ?></dd>
            <?php if($concern['assigned_name']): ?>
            <dt class="col-5 text-muted">Assigned To</dt><dd class="col-7"><?= sanitize($concern['assigned_name']) ?></dd>
            <?php endif; ?>
            <?php if($concern['resolved_at']): ?>
            <dt class="col-5 text-muted">Resolved</dt>   <dd class="col-7"><?= formatDate($concern['resolved_at'],'M d, Y') ?></dd>
            <?php endif; ?>
        </dl>
    </div>

    <div class="bhd-card p-4">
        <h6 class="fw-semibold mb-3"><i class="bi bi-clock-history me-1"></i>Status History</h6>
        <?php if(empty($history)): ?>
            <p class="text-muted small">No history yet.</p>
        <?php else: ?>
        <div class="timeline">
            <?php foreach($history as $h): ?>
            <div class="timeline-item">
                <div class="small fw-medium"><?= $h['new_status'] ? ucfirst(str_replace('_',' ',$h['new_status'])) : 'Submitted' ?></div>
                <?php if($h['note']): ?><div class="small text-muted"><?= sanitize($h['note']) ?></div><?php endif; ?>
                <div class="text-muted" style="font-size:.75rem"><?= timeAgo($h['changed_at']) ?> &bull; <?= sanitize($h['changer_name']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
</div>

<?php include dirname(__DIR__) . '/includes/footer_resident.php'; ?>
