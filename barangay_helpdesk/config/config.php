<?php
// ============================================================
// BarangayHelpDesk - Configuration
// ============================================================

define('APP_NAME',    'BarangayHelpDesk');
define('APP_VERSION', '1.0.0');
define('BASE_URL',    'http://localhost/barangay_helpdesk');
define('BASE_PATH',   dirname(__DIR__));

// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'barangay_helpdesk');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Upload settings
define('UPLOAD_PATH',     BASE_PATH . '/public/uploads/concerns/');
define('UPLOAD_URL',      BASE_URL  . '/public/uploads/concerns/');
define('MAX_FILE_SIZE',   5 * 1024 * 1024); // 5MB
define('ALLOWED_TYPES',   ['image/jpeg','image/png','image/gif','image/webp']);
define('MAX_ATTACHMENTS', 5);

// Session
define('SESSION_NAME',    'bhd_session');
define('SESSION_LIFETIME', 3600); // 1 hour

// Pagination
define('ITEMS_PER_PAGE', 10);

// Tracking code prefix
define('TRACKING_PREFIX', 'BHD');

// Timezone
date_default_timezone_set('Asia/Manila');

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
