<?php
require_once dirname(__DIR__) . '/includes/init.php';
$pageTitle = 'Submit a Concern';
requireLogin();

$pdo = db();
$userId = $_SESSION['user_id'] ?? 0;

// Verify user exists in database
$checkUser = $pdo->prepare("SELECT id FROM users WHERE id = ? AND is_active = 1");
$checkUser->execute([$userId]);
if (!$checkUser->fetch()) {
    // Invalid user - clear session and redirect
    session_destroy();
    setFlash('danger', 'Session expired. Please login again.');
    redirect(BASE_URL . '/login.php');
}

$categories = $pdo->query("SELECT * FROM concern_categories WHERE is_active=1 ORDER BY name")->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    } else {
        $title       = sanitize($_POST['title'] ?? '');
        $categoryId  = (int)($_POST['category_id'] ?? 0);
        $description = sanitize($_POST['description'] ?? '');
        $location    = sanitize($_POST['location'] ?? '');
        $priority    = in_array($_POST['priority']??'', ['low','medium','high','urgent']) ? $_POST['priority'] : 'medium';

        if (strlen($title) < 5)       $errors[] = 'Title must be at least 5 characters.';
        if (!$categoryId)             $errors[] = 'Please select a category.';
        if (strlen($description) < 20) $errors[] = 'Description must be at least 20 characters.';

        if (empty($errors)) {
            try {
                $trackingCode = generateTrackingCode();
                
                // Start transaction
                $pdo->beginTransaction();
                
                $stmt = $pdo->prepare("INSERT INTO concerns (tracking_code, user_id, category_id, title, description, location, priority, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
                $stmt->execute([$trackingCode, $userId, $categoryId, $title, $description, $location, $priority]);
                $concernId = (int)$pdo->lastInsertId();

                // Handle file uploads
                if (!empty($_FILES['attachments']['name'][0])) {
                    $files   = $_FILES['attachments'];
                    $count   = min(count($files['name']), MAX_ATTACHMENTS);
                    
                    for ($i = 0; $i < $count; $i++) {
                        if ($files['error'][$i] === UPLOAD_ERR_OK) {
                            $file = [
                                'name' => $files['name'][$i],
                                'type' => $files['type'][$i],
                                'tmp_name' => $files['tmp_name'][$i],
                                'error' => $files['error'][$i],
                                'size' => $files['size'][$i]
                            ];
                            $result = handleFileUpload($file, $concernId);
                            if ($result) {
                                $ins = $pdo->prepare("INSERT INTO concern_attachments (concern_id, file_name, file_path, file_type, file_size) VALUES (?, ?, ?, ?, ?)");
                                $ins->execute([$concernId, $result['file_name'], $result['file_path'], $result['file_type'], $result['file_size']]);
                            }
                        }
                    }
                }

                // Log status history
                $historyStmt = $pdo->prepare("INSERT INTO concern_status_history (concern_id, changed_by, changer_type, old_status, new_status, note, changed_at) VALUES (?, ?, 'user', NULL, 'pending', 'Concern submitted', NOW())");
                $historyStmt->execute([$concernId, $userId]);
                
                logActivity('user', $userId, 'Submitted concern', 'concern', $concernId);
                
                // Commit transaction
                $pdo->commit();

                setFlash('success', "Concern submitted! Your tracking code is: <strong>{$trackingCode}</strong>");
                redirect(BASE_URL . '/resident/concern_detail.php?id=' . $concernId);
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log("Submit concern error: " . $e->getMessage());
                
                if ($e->errorInfo[1] == 1452) {
                    $errors[] = 'Account error. Please log out and log in again.';
                } else {
                    $errors[] = 'Failed to submit concern. Please try again.';
                }
            }
        }
    }
}

include dirname(__DIR__) . '/includes/header_resident.php';
?>
<div class="row justify-content-center">
<div class="col-lg-8">
<div class="bhd-card p-4 p-md-5">
    <h3 class="fw-bold mb-1"><i class="bi bi-plus-circle text-primary me-2"></i>Submit a Concern</h3>
    <p class="text-muted mb-4">Provide as much detail as possible so we can address your concern quickly.</p>
    <?php if($errors): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data" novalidate>
        <?= csrfField() ?>
        <div class="mb-3">
            <label class="form-label fw-medium">Concern Title <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($_POST['title']??'') ?>" placeholder="Brief title of your concern" maxlength="255" required>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label fw-medium">Category <span class="text-danger">*</span></label>
                <select name="category_id" class="form-select" required>
                    <option value="">-- Select Category --</option>
                    <?php foreach($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= (($_POST['category_id']??'')==$cat['id'])?'selected':'' ?>><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-medium">Priority Level</label>
                <select name="priority" class="form-select">
                    <?php foreach(['low'=>'Low','medium'=>'Medium','high'=>'High','urgent'=>'Urgent 🚨'] as $v=>$l): ?>
                        <option value="<?= $v ?>" <?= (($_POST['priority']??'medium')===$v)?'selected':'' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label fw-medium">Description <span class="text-danger">*</span></label>
            <textarea name="description" class="form-control" rows="5" placeholder="Describe the issue in detail..." maxlength="2000" required><?= htmlspecialchars($_POST['description']??'') ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label fw-medium">Exact Location / Landmark</label>
            <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($_POST['location']??'') ?>" placeholder="e.g. Near the basketball court, Purok 3">
        </div>
        <div class="mb-4">
            <label class="form-label fw-medium">Photo Attachments <small class="text-muted">(up to <?= MAX_ATTACHMENTS ?>, max 5MB each)</small></label>
            <div class="drop-zone" id="dropZone" style="border: 2px dashed #dee2e6; border-radius: 8px; padding: 40px 20px; text-align: center; cursor: pointer; transition: all 0.3s;">
                <i class="bi bi-cloud-upload fs-2 text-muted mb-2 d-block"></i>
                <p class="mb-1 fw-medium">Drag &amp; drop photos here or click to browse</p>
                <small class="text-muted">JPG, PNG, GIF, WEBP accepted</small>
            </div>
            <input type="file" name="attachments[]" id="attachments" multiple accept="image/*" class="d-none">
            <div id="fileList" class="mt-2"></div>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold"><i class="bi bi-send me-2"></i>Submit Concern</button>
            <a href="<?= BASE_URL ?>/resident/my_concerns.php" class="btn btn-outline-secondary px-4">Cancel</a>
        </div>
    </form>
</div>
</div>
</div>

<script>
// File upload UI handling
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('attachments');
const fileList = document.getElementById('fileList');

// Click to browse
dropZone.addEventListener('click', function() {
    fileInput.click();
});

// File selection change
fileInput.addEventListener('change', function(e) {
    updateFileList(e.target.files);
});

// Drag and drop handlers
dropZone.addEventListener('dragover', function(e) {
    e.preventDefault();
    this.style.borderColor = '#0d6efd';
    this.style.backgroundColor = '#f8f9fa';
});

dropZone.addEventListener('dragleave', function(e) {
    e.preventDefault();
    this.style.borderColor = '#dee2e6';
    this.style.backgroundColor = 'transparent';
});

dropZone.addEventListener('drop', function(e) {
    e.preventDefault();
    this.style.borderColor = '#dee2e6';
    this.style.backgroundColor = 'transparent';
    
    const files = e.dataTransfer.files;
    fileInput.files = files;
    updateFileList(files);
});

function updateFileList(files) {
    fileList.innerHTML = '';
    if (files.length === 0) return;
    
    const list = document.createElement('div');
    list.className = 'list-group mt-2';
    
    const maxFiles = <?= MAX_ATTACHMENTS ?>;
    const fileCount = Math.min(files.length, maxFiles);
    
    for (let i = 0; i < fileCount; i++) {
        const file = files[i];
        const listItem = document.createElement('div');
        listItem.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
        
        // File size formatting
        let fileSize = (file.size / 1024).toFixed(1) + ' KB';
        if (file.size > 1048576) {
            fileSize = (file.size / 1048576).toFixed(1) + ' MB';
        }
        
        listItem.innerHTML = `
            <div>
                <i class="bi bi-file-image me-2"></i>
                <span>${escapeHtml(file.name)}</span>
                <small class="text-muted ms-2">(${fileSize})</small>
            </div>
            <span class="badge bg-secondary">${file.type || 'image'}</span>
        `;
        list.appendChild(listItem);
    }
    
    if (files.length > maxFiles) {
        const warning = document.createElement('div');
        warning.className = 'alert alert-warning mt-2 mb-0 py-2';
        warning.innerHTML = `<small><i class="bi bi-exclamation-triangle"></i> Only first ${maxFiles} files will be uploaded. Maximum ${maxFiles} attachments allowed.</small>`;
        list.appendChild(warning);
    }
    
    // Check file sizes
    const maxSize = 5 * 1024 * 1024; // 5MB
    let hasLargeFile = false;
    for (let i = 0; i < files.length; i++) {
        if (files[i].size > maxSize) {
            hasLargeFile = true;
            break;
        }
    }
    
    if (hasLargeFile) {
        const sizeWarning = document.createElement('div');
        sizeWarning.className = 'alert alert-danger mt-2 mb-0 py-2';
        sizeWarning.innerHTML = `<small><i class="bi bi-exclamation-triangle"></i> Some files exceed the 5MB size limit and will not be uploaded.</small>`;
        list.appendChild(sizeWarning);
    }
    
    fileList.appendChild(list);
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

// Add hover effect styles
dropZone.addEventListener('mouseenter', function() {
    if (!this.classList.contains('dragover')) {
        this.style.backgroundColor = '#f8f9fa';
    }
});

dropZone.addEventListener('mouseleave', function() {
    if (!this.classList.contains('dragover')) {
        this.style.backgroundColor = 'transparent';
    }
});
</script>

<style>
.drop-zone {
    transition: all 0.3s ease;
}
.drop-zone:hover {
    border-color: #0d6efd !important;
    background-color: #f8f9fa !important;
}
</style>

<?php include dirname(__DIR__) . '/includes/footer_resident.php'; ?>