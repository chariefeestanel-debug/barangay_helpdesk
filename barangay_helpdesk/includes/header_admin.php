<?php
$flash = getFlash();
$currentPage = basename($_SERVER['PHP_SELF']);
$adminName = sanitize($_SESSION['admin_name'] ?? 'Admin');
$adminRole = $_SESSION['admin_role'] ?? 'staff';
$isSuperAdmin = $adminRole === 'super_admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' — ' : '' ?>Admin | <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/admin.css">
</head>
<body class="admin-body">

<!-- Sidebar -->
<div class="admin-sidebar d-flex flex-column" id="adminSidebar">
    <div class="sidebar-brand p-3 d-flex align-items-center gap-2">
        <i class="bi bi-shield-fill-check fs-3 text-warning"></i>
        <div>
            <div class="fw-bold text-white"><?= APP_NAME ?></div>
            <small class="text-muted" style="font-size:.7rem">Admin Panel</small>
        </div>
    </div>
    <hr class="border-secondary m-0">
    <nav class="sidebar-nav flex-grow-1 p-2">
        <ul class="nav flex-column gap-1">
            <li><a class="nav-link <?= $currentPage==='dashboard.php'?'active':'' ?>" href="<?= BASE_URL ?>/admin/dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
            <li><a class="nav-link <?= $currentPage==='concerns.php'?'active':'' ?>" href="<?= BASE_URL ?>/admin/concerns.php"><i class="bi bi-exclamation-circle"></i> Concerns</a></li>
            <li><a class="nav-link <?= $currentPage==='announcements.php'?'active':'' ?>" href="<?= BASE_URL ?>/admin/announcements.php"><i class="bi bi-megaphone"></i> Announcements</a></li>
            <li><a class="nav-link <?= $currentPage==='categories.php'?'active':'' ?>" href="<?= BASE_URL ?>/admin/categories.php"><i class="bi bi-tags"></i> Categories</a></li>
            <?php if ($isSuperAdmin): ?>
            <li><a class="nav-link <?= $currentPage==='officials.php'?'active':'' ?>" href="<?= BASE_URL ?>/admin/officials.php"><i class="bi bi-people"></i> Officials</a></li>
            <li><a class="nav-link <?= $currentPage==='residents.php'?'active':'' ?>" href="<?= BASE_URL ?>/admin/residents.php"><i class="bi bi-person-lines-fill"></i> Residents</a></li>
            <?php endif; ?>
            <li><a class="nav-link <?= $currentPage==='reports.php'?'active':'' ?>" href="<?= BASE_URL ?>/admin/reports.php"><i class="bi bi-file-earmark-bar-graph"></i> Reports</a></li>
            <?php if ($isSuperAdmin): ?>
            <li><a class="nav-link <?= $currentPage==='activity_logs.php'?'active':'' ?>" href="<?= BASE_URL ?>/admin/activity_logs.php"><i class="bi bi-journal-text"></i> Activity Logs</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <div class="sidebar-footer p-3 border-top border-secondary">
        <div class="d-flex align-items-center gap-2 mb-2">
            <div class="admin-avatar"><i class="bi bi-person-fill"></i></div>
            <div>
                <div class="text-white small fw-semibold"><?= $adminName ?></div>
                <div class="text-muted" style="font-size:.7rem"><?= ucfirst(str_replace('_',' ',$adminRole)) ?></div>
            </div>
        </div>
        <a href="<?= BASE_URL ?>/admin/profile.php" class="btn btn-outline-secondary btn-sm w-100 mb-1"><i class="bi bi-gear me-1"></i>Settings</a>
        <a href="<?= BASE_URL ?>/admin/logout.php" class="btn btn-outline-danger btn-sm w-100"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
    </div>
</div>

<!-- Main Content -->
<div class="admin-main" id="adminMain">
    <!-- Top Bar -->
    <div class="admin-topbar d-flex align-items-center px-4 py-2 gap-3">
        <button class="btn btn-sm btn-outline-secondary" id="sidebarToggle"><i class="bi bi-list fs-5"></i></button>
        <nav aria-label="breadcrumb" class="flex-grow-1">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard.php">Admin</a></li>
                <?php if (isset($breadcrumb)): ?>
                    <li class="breadcrumb-item active"><?= sanitize($breadcrumb) ?></li>
                <?php endif; ?>
            </ol>
        </nav>
        <a href="<?= BASE_URL ?>/index.php" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-globe me-1"></i>View Site</a>
    </div>
    <div class="admin-content p-4">
    <?php if ($flash): ?>
        <?php $t = $flash['type']==='error'?'danger':$flash['type']; ?>
        <div class="alert alert-<?= $t ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>