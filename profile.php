<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/users.php';

requireLogin();

$pageTitle     = 'My Profile';
$useSweetAlert = true;
$extraJs       = ['js/password-strength.js', 'js/phone-input.js'];
$me            = currentUser();
$profileUser   = getUserById((int) $me['id']);

if ($profileUser) {
    $profileUser = normalizeUserRecord($profileUser, $me);
}

if (!$profileUser || $profileUser['id'] <= 0) {
    flash('error', 'Account not found.');
    header('Location: ' . url(isAdmin() ? 'admin/dashboard.php' : 'dashboard.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $result = updateOwnProfile((int) $me['id'], [
            'full_name'    => trim($_POST['full_name'] ?? ''),
            'email'        => trim($_POST['email'] ?? ''),
            'phone_number' => trim($_POST['phone_number'] ?? ''),
            'username'     => trim($_POST['username'] ?? ''),
        ]);
        flash($result['ok'] ? 'success' : 'error', $result['ok'] ? 'Profile updated successfully.' : $result['error']);
        if ($result['ok']) {
            $profileUser = normalizeUserRecord($result['user'], $me);
        }
    }

    if ($action === 'change_password') {
        $result = changeOwnPassword(
            (int) $me['id'],
            $_POST['current_password'] ?? '',
            $_POST['new_password'] ?? '',
            $_POST['confirm_new_password'] ?? ''
        );
        flash($result['ok'] ? 'success' : 'error', $result['ok'] ? 'Password changed successfully.' : $result['error']);
    }

    if ($action === 'upload_picture' && !empty($_FILES['profile_picture']['name'])) {
        $result = updateOwnProfilePicture((int) $me['id'], $_FILES['profile_picture']);
        flash($result['ok'] ? 'success' : 'error', $result['ok'] ? 'Profile picture updated.' : $result['error']);
        if ($result['ok']) {
            $profileUser = normalizeUserRecord($result['user'], $me);
        }
    }

    header('Location: ' . url('profile.php'));
    exit;
}

$picUrl      = '';
if (userHasProfilePicture($profileUser)) {
    $picUrl = profilePictureUrl($profileUser);
    $relative = ltrim(str_replace('\\', '/', $profileUser['profile_picture'] ?? ''), '/');
    $fullPath = __DIR__ . '/' . $relative;
    if (is_file($fullPath)) {
        $picUrl .= '?v=' . filemtime($fullPath);
    }
}
$hasPassword = !empty($profileUser['password']);

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <h2>My Profile</h2>
    <p>View and manage your account information.</p>
</div>

<div class="profile-layout">
    <div class="card">
        <div class="card-header">
            <span class="card-title">Profile Overview</span>
        </div>
        <div class="card-body" style="text-align:center;">
            <div style="margin-bottom:1rem;">
                <?php if ($picUrl): ?>
                    <img src="<?= $picUrl ?>" alt="Profile picture"
                         style="width:120px;height:120px;border-radius:50%;object-fit:cover;border:3px solid var(--border);">
                <?php else: ?>
                    <div class="user-avatar" style="width:120px;height:120px;font-size:2rem;margin:0 auto;">
                        <?= strtoupper(substr($profileUser['full_name'] !== '' ? $profileUser['full_name'] : ($profileUser['email'] ?: 'U'), 0, 2)) ?>
                    </div>
                <?php endif; ?>
            </div>

            <h3 style="margin:0 0 0.25rem;"><?= sanitize($profileUser['full_name']) ?></h3>
            <p class="text-secondary" style="margin:0 0 0.75rem;"><?= sanitize($profileUser['email']) ?></p>
            <span class="role-badge <?= roleBadgeClass($profileUser['role']) ?>"><?= roleLabel($profileUser['role']) ?></span>

            <dl class="profile-details-list">
                <div class="profile-detail-row">
                    <dt class="text-secondary">Username</dt>
                    <dd><?= sanitize($profileUser['username'] ?? '') !== '' ? sanitize($profileUser['username']) : 'Not set' ?></dd>
                </div>
                <div class="profile-detail-row">
                    <dt class="text-secondary">Phone</dt>
                    <dd><?= sanitize($profileUser['phone_number'] ?? '') !== '' ? sanitize($profileUser['phone_number']) : 'Not set' ?></dd>
                </div>
                <div class="profile-detail-row">
                    <dt class="text-secondary">Account type</dt>
                    <dd><?= roleLabel($profileUser['role'] ?? 'user') ?></dd>
                </div>
                <div class="profile-detail-row">
                    <dt class="text-secondary">Member since</dt>
                    <dd><?= !empty($profileUser['created_at']) ? date('F j, Y', strtotime($profileUser['created_at'])) : '—' ?></dd>
                </div>
            </dl>
        </div>
    </div>

    <div style="display:flex;flex-direction:column;gap:1.5rem;">
        <div class="card">
            <div class="card-header">
                <span class="card-title">Edit Profile</span>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_profile">

                    <div class="form-group">
                        <label class="form-label" for="full_name">Full Name <span class="req">*</span></label>
                        <input type="text" id="full_name" name="full_name" class="form-control" required
                               value="<?= sanitize($profileUser['full_name']) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Email Address <span class="req">*</span></label>
                        <input type="email" id="email" name="email" class="form-control" required
                               value="<?= sanitize($profileUser['email']) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="username">Username</label>
                        <input type="text" id="username" name="username" class="form-control"
                               placeholder="Optional"
                               value="<?= sanitize($profileUser['username'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="phone_number">Phone Number</label>
                        <input type="tel" id="phone_number" name="phone_number" class="form-control"
                               placeholder="Optional — digits only"
                               data-phone-numeric
                               value="<?= sanitize($profileUser['phone_number'] ?? '') ?>">
                    </div>

                    <button type="submit" class="btn btn-accent">Save Changes</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <span class="card-title">Profile Picture</span>
            </div>
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload_picture">

                    <div class="form-group">
                        <label class="form-label" for="profile_picture">Upload Image</label>
                        <input type="file" id="profile_picture" name="profile_picture" class="form-control"
                               accept="image/jpeg,image/png,image/webp" required>
                        <p class="text-sm text-secondary" style="margin-top:6px;">JPG, PNG, or WEBP. Max 5MB.</p>
                    </div>

                    <button type="submit" class="btn btn-accent">Upload Picture</button>
                </form>
            </div>
        </div>

        <?php if ($hasPassword): ?>
        <div class="card">
            <div class="card-header">
                <span class="card-title">Change Password</span>
            </div>
            <div class="card-body">
                <form method="POST" action="" autocomplete="off">
                    <input type="hidden" name="action" value="change_password">

                    <div class="form-group">
                        <label class="form-label" for="current_password">Current Password <span class="req">*</span></label>
                        <input type="password" id="current_password" name="current_password" class="form-control" required
                               autocomplete="current-password">
                    </div>

                    <div class="form-group" id="pw-field-group">
                        <label class="form-label" for="new_password">New Password <span class="req">*</span></label>
                        <input type="password" id="new_password" name="new_password" class="form-control" required
                               autocomplete="new-password">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirm_new_password">Confirm New Password <span class="req">*</span></label>
                        <input type="password" id="confirm_new_password" name="confirm_new_password" class="form-control" required
                               autocomplete="new-password">
                    </div>

                    <button type="submit" class="btn btn-accent">Update Password</button>
                </form>
            </div>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="card-body">
                <p class="text-secondary text-sm" style="margin:0;">This account uses Google Sign-In. Password changes are managed through your Google account.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($hasPassword): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof initPasswordStrength === 'function') {
        initPasswordStrength('new_password', 'confirm_new_password', 'pw-field-group');
    }
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
