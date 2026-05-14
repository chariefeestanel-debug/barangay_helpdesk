<?php
// ============================================================
// BarangayHelpDesk - Global Helper Functions
// ============================================================

// ── Session ──────────────────────────────────────────────────
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params(SESSION_LIFETIME, '/', '', false, true);
        session_start();
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function requireLogin($redirect = '/resident/login.php') {
    if (!isLoggedIn()) {
        setFlash('error', 'Please log in to access that page.');
        redirect(BASE_URL . $redirect);
    }
}

function requireAdmin($redirect = '/admin/login.php') {
    if (!isAdminLoggedIn()) {
        setFlash('error', 'Unauthorized access.');
        redirect(BASE_URL . $redirect);
    }
}

function requireRole($role) {
    requireAdmin();
    if ($_SESSION['admin_role'] !== 'super_admin' && $_SESSION['admin_role'] !== $role) {
        setFlash('error', 'You do not have permission to perform this action.');
        redirect(BASE_URL . '/admin/dashboard.php');
    }
}

// ── Flash Messages ────────────────────────────────────────────
function setFlash($type, $message) {
    startSession();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    startSession();
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function showFlash() {
    $flash = getFlash();
    if (!$flash) return;
    $type = $flash['type'] === 'error' ? 'danger' : htmlspecialchars($flash['type']);
    $msg  = htmlspecialchars($flash['message']);
    echo "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
            {$msg}
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
          </div>";
}

// ── Redirect ──────────────────────────────────────────────────
function redirect($url) {
    header("Location: $url");
    exit;
}

// ── Security ──────────────────────────────────────────────────
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim((string)$input)), ENT_QUOTES, 'UTF-8');
}

function generateCsrfToken() {
    startSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    startSession();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . generateCsrfToken() . '">';
}

// ── Tracking Code Generator ───────────────────────────────────
function generateTrackingCode() {
    $pdo  = db();
    do {
        $code = TRACKING_PREFIX . '-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
        $stmt = $pdo->prepare("SELECT id FROM concerns WHERE tracking_code = ?");
        $stmt->execute([$code]);
    } while ($stmt->fetch());
    return $code;
}

// ── File Upload ───────────────────────────────────────────────
function handleFileUpload($file, $concernId) {
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    if ($file['size'] > MAX_FILE_SIZE) return false;

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, ALLOWED_TYPES)) return false;

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $safeName = 'concern_' . $concernId . '_' . uniqid() . '.' . strtolower($ext);
    $dest     = UPLOAD_PATH . $safeName;

    if (!move_uploaded_file($file['tmp_name'], $dest)) return false;

    return [
        'file_name' => $file['name'],
        'file_path' => $safeName,
        'file_type' => $mime,
        'file_size' => $file['size'],
    ];
}

// ── Formatting ────────────────────────────────────────────────
function formatDate($date, $format = 'M d, Y h:i A') {
    return date($format, strtotime($date));
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    if ($time < 60)     return 'just now';
    if ($time < 3600)   return floor($time/60) . ' min ago';
    if ($time < 86400)  return floor($time/3600) . ' hr ago';
    if ($time < 604800) return floor($time/86400) . ' days ago';
    return formatDate($datetime, 'M d, Y');
}

function priorityBadge($priority) {
    $map = [
        'low'    => ['class' => 'bg-secondary', 'label' => 'Low'],
        'medium' => ['class' => 'bg-info text-dark', 'label' => 'Medium'],
        'high'   => ['class' => 'bg-warning text-dark', 'label' => 'High'],
        'urgent' => ['class' => 'bg-danger', 'label' => 'Urgent'],
    ];
    $p = $map[$priority] ?? $map['medium'];
    return "<span class='badge {$p['class']}'>{$p['label']}</span>";
}

function statusBadge($status) {
    $map = [
        'pending'     => ['class' => 'bg-warning text-dark', 'label' => 'Pending'],
        'in_progress' => ['class' => 'bg-primary',           'label' => 'In Progress'],
        'resolved'    => ['class' => 'bg-success',           'label' => 'Resolved'],
        'rejected'    => ['class' => 'bg-danger',            'label' => 'Rejected'],
    ];
    $s = $map[$status] ?? $map['pending'];
    return "<span class='badge {$s['class']}'>{$s['label']}</span>";
}



// ── Activity Logger ───────────────────────────────────────────
function logActivity($actorType, $actorId, $action, $targetType = '', $targetId = 0) {
    try {
        $pdo  = db();
        $stmt = $pdo->prepare("INSERT INTO activity_logs (actor_type, actor_id, action, target_type, target_id, ip_address, user_agent) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([
            $actorType, $actorId, $action, $targetType, $targetId ?: null,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? '',
        ]);
    } catch (Exception $e) { /* silent fail for logging */ }
}

// ── Pagination ────────────────────────────────────────────────
function paginate($total, $page, $perPage = ITEMS_PER_PAGE) {
    $totalPages = max(1, (int)ceil($total / $perPage));
    $page       = max(1, min($page, $totalPages));
    $offset     = ($page - 1) * $perPage;
    return ['total' => $total, 'page' => $page, 'per_page' => $perPage, 'total_pages' => $totalPages, 'offset' => $offset];
}

function renderPagination($pag, $baseUrl) {
    if ($pag['total_pages'] <= 1) return '';
    $html = "<nav><ul class='pagination pagination-sm justify-content-center mb-0'>";
    $html .= "<li class='page-item " . ($pag['page'] <= 1 ? 'disabled' : '') . "'><a class='page-link' href='{$baseUrl}&page=" . ($pag['page']-1) . "'>‹</a></li>";
    for ($i = 1; $i <= $pag['total_pages']; $i++) {
        $active = $i === $pag['page'] ? 'active' : '';
        $html  .= "<li class='page-item $active'><a class='page-link' href='{$baseUrl}&page={$i}'>{$i}</a></li>";
    }
    $html .= "<li class='page-item " . ($pag['page'] >= $pag['total_pages'] ? 'disabled' : '') . "'><a class='page-link' href='{$baseUrl}&page=" . ($pag['page']+1) . "'>›</a></li>";
    $html .= "</ul></nav>";
    return $html;
}

// ── Input validation ──────────────────────────────────────────
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePassword($password) {
    $errors = [];
    if (strlen($password) < 8)         $errors[] = 'At least 8 characters required.';
    if (!preg_match('/[A-Z]/', $password)) $errors[] = 'At least one uppercase letter required.';
    if (!preg_match('/[0-9]/', $password)) $errors[] = 'At least one number required.';
    return $errors;
}
