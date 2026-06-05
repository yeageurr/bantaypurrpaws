<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/users.php';

requireCanManageAccounts();

$pageTitle     = 'Create Staff Account';
$useSweetAlert = true;
$extraJs       = ['js/password-strength.js'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_staff') {
    $fullName   = trim($_POST['full_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $username   = trim($_POST['username'] ?? '');
    $password   = $_POST['password'] ?? '';
    $confirmPwd = $_POST['confirm_password'] ?? '';

    if ($password !== $confirmPwd) {
        flash('error', 'Passwords do not match.');
    } else {
        $result = createStaffAccount($fullName, $email, $password, $username !== '' ? $username : null);
        if ($result['ok']) {
            flash('success', 'Staff account created successfully for ' . sanitize($result['user']['full_name']) . '.');
            header('Location: ' . url('admin/staff.php'));
            exit;
        }
        flash('error', $result['error']);
    }

    header('Location: ' . url('admin/staff-create.php'));
    exit;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h2>Create Staff Account</h2>
    <p>Manually register a new staff member with login credentials.</p>
</div>

<div class="card form-narrow" style="max-width:640px;">
    <div class="card-header">
        <span class="card-title">Staff Details</span>
    </div>
    <div class="card-body">
        <form method="POST" action="" autocomplete="off">
            <input type="hidden" name="action" value="create_staff">

            <div class="form-group">
                <label class="form-label" for="full_name">Full Name <span class="req">*</span></label>
                <input type="text" id="full_name" name="full_name" class="form-control" required
                       value="<?= sanitize($_POST['full_name'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email Address <span class="req">*</span></label>
                <input type="email" id="email" name="email" class="form-control" required
                       value="<?= sanitize($_POST['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control"
                       placeholder="Optional"
                       value="<?= sanitize($_POST['username'] ?? '') ?>">
            </div>

            <div class="form-group" id="pw-field-group">
                <label class="form-label" for="password">Password <span class="req">*</span></label>
                <input type="password" id="password" name="password" class="form-control" required
                       autocomplete="new-password">
            </div>

            <div class="form-group">
                <label class="form-label" for="confirm_password">Confirm Password <span class="req">*</span></label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required
                       autocomplete="new-password">
            </div>

            <div class="form-group">
                <label class="form-label">Role</label>
                <input type="text" class="form-control" value="Staff" readonly disabled>
                <p class="text-sm text-secondary" style="margin-top:6px;">Role is automatically set to Staff.</p>
            </div>

            <div class="flex items-center gap-2" style="margin-top:1.5rem;">
                <button type="submit" class="btn btn-accent">Create Staff Account</button>
                <a href="<?= url('admin/staff.php') ?>" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof initPasswordStrength === 'function') {
        initPasswordStrength('password', 'confirm_password', 'pw-field-group');
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
