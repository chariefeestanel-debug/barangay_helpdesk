<?php
require_once dirname(__DIR__) . '/includes/init.php';
$pageTitle = 'Register';
if (isLoggedIn()) redirect(BASE_URL . '/resident/my_concerns.php');

$errors = [];
$input  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    } else {
        $input = [
            'full_name' => sanitize($_POST['full_name'] ?? ''),
            'email'     => trim($_POST['email'] ?? ''),
            'phone'     => sanitize($_POST['phone'] ?? ''),
            'address'   => sanitize($_POST['address'] ?? ''),
            'purok'     => sanitize($_POST['purok'] ?? ''),
            'password'  => $_POST['password'] ?? '',
            'confirm'   => $_POST['confirm_password'] ?? '',
        ];

        if (empty($input['full_name'])) $errors[] = 'Full name is required.';
        if (!validateEmail($input['email'])) $errors[] = 'Invalid email address.';
        $pwErrors = validatePassword($input['password']);
        $errors   = array_merge($errors, $pwErrors);
        if ($input['password'] !== $input['confirm']) $errors[] = 'Passwords do not match.';

        if (empty($errors)) {
            $pdo  = db();
            $dupe = $pdo->prepare("SELECT id FROM users WHERE email=?");
            $dupe->execute([$input['email']]);
            if ($dupe->fetch()) {
                $errors[] = 'An account with this email already exists.';
            } else {
                $hash = password_hash($input['password'], PASSWORD_BCRYPT, ['cost'=>12]);
                $stmt = $pdo->prepare("INSERT INTO users (full_name,email,password,phone,address,purok) VALUES (?,?,?,?,?,?)");
                $stmt->execute([$input['full_name'],$input['email'],$hash,$input['phone'],$input['address'],$input['purok']]);
                $newId = $pdo->lastInsertId();
                logActivity('user', $newId, 'Registered account');
                setFlash('success', 'Account created! Please log in.');
                redirect(BASE_URL . '/resident/login.php');
            }
        }
    }
}

include dirname(__DIR__) . '/includes/header_resident.php';
?>
<div class="row justify-content-center">
<div class="col-md-7 col-lg-6">
<div class="bhd-card p-4 p-md-5">
    <div class="text-center mb-4">
        <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width:64px;height:64px">
            <i class="bi bi-person-plus-fill fs-2 text-primary"></i>
        </div>
        <h3 class="fw-bold">Create Account</h3>
        <p class="text-muted">Register to submit and track your barangay concerns</p>
    </div>
    <?php if($errors): ?>
        <div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul></div>
    <?php endif; ?>
    <form method="POST" novalidate>
        <?= csrfField() ?>
        <div class="mb-3">
            <label class="form-label fw-medium">Full Name <span class="text-danger">*</span></label>
            <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($input['full_name']??'') ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-medium">Email Address <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($input['email']??'') ?>" required>
        </div>
        <div class="row g-3 mb-3">
            <div class="col">
                <label class="form-label fw-medium">Phone Number</label>
                <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($input['phone']??'') ?>">
            </div>
            <div class="col">
                <label class="form-label fw-medium">Purok / Zone</label>
                <input type="text" name="purok" class="form-control" value="<?= htmlspecialchars($input['purok']??'') ?>">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label fw-medium">Home Address</label>
            <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($input['address']??'') ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label fw-medium">Password <span class="text-danger">*</span></label>
            <input type="password" name="password" class="form-control" required>
            <div class="form-text">Min. 8 characters, 1 uppercase, 1 number.</div>
        </div>
        <div class="mb-4">
            <label class="form-label fw-medium">Confirm Password <span class="text-danger">*</span></label>
            <input type="password" name="confirm_password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Create Account</button>
    </form>
    <p class="text-center mt-3 mb-0 small">Already have an account? <a href="<?= BASE_URL ?>/resident/login.php">Log in</a></p>
</div>
</div>
</div>
<?php include dirname(__DIR__) . '/includes/footer_resident.php'; ?>
