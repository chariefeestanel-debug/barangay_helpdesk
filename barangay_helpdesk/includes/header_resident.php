<?php
// Resident-facing public $flash = getFlash();
$isLoggedIn = isLoggedIn();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' — ' : '' ?><?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/resident.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bhd-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= BASE_URL ?>/index.php">
            <i class="bi bi-shield-fill-check fs-4 text-warning"></i>
            <span class="fw-bold"><?= APP_NAME ?></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage==='index.php'?'active':'' ?>" href="<?= BASE_URL ?>/index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage==='announcements.php'?'active':'' ?>" href="<?= BASE_URL ?>/announcements.php">Announcements</a>
                </li>
                <?php if ($isLoggedIn): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage==='submit_concern.php'?'active':'' ?>" href="<?= BASE_URL ?>/resident/submit_concern.php">Submit Concern</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage==='my_concerns.php'?'active':'' ?>" href="<?= BASE_URL ?>/resident/my_concerns.php">My Concerns</a>
                </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav ms-auto">
                <?php if ($isLoggedIn): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-1" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i>
                        <?= sanitize($_SESSION['user_name'] ?? 'Resident') ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/resident/profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/resident/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </li>
                <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/resident/login.php">Login</a>
                </li>
                <li class="nav-item">
                    <a class="btn btn-warning btn-sm ms-2" href="<?= BASE_URL ?>/resident/register.php">Register</a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<main class="py-4">
<div class="container">
<?php $flash = getFlash(); ?>
<?php if ($flash): ?>
    <?php $t = $flash['type']==='error'?'danger':$flash['type']; ?>
    <div class="alert alert-<?= $t ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
