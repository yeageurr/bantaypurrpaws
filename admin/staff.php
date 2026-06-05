<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/users.php';

requireCanManageAccounts();

$pageTitle     = 'Manage Staff Accounts';
$useSweetAlert = true;
$me            = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_staff') {
    $targetId = (int) ($_POST['user_id'] ?? 0);
    $result   = deleteUserAccount($targetId, $me);

    if ($result['ok']) {
        flash('success', 'Staff account deleted successfully.');
    } else {
        flash('error', $result['error']);
    }

    header('Location: ' . url('admin/staff.php'));
    exit;
}

$staffMembers = array_map('hydrateUserSensitiveFields', db_select('users', 'role=eq.staff&order=created_at.desc'));

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header page-header-row">
    <div class="page-header-text">
        <h2>Manage Staff Accounts</h2>
        <p>View and manage staff member accounts.</p>
    </div>
    <a href="<?= url('admin/staff-create.php') ?>" class="btn btn-accent">＋ Create Staff Account</a>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title"><?= count($staffMembers) ?> Staff Members</span>
    </div>
    <div class="table-wrapper table-responsive-stack">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Username</th>
                    <th>Phone</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($staffMembers)): ?>
                <tr>
                    <td colspan="6" class="text-secondary text-center" style="padding:2rem;">No staff accounts yet.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($staffMembers as $u): ?>
                <tr>
                    <td>
                        <div class="flex items-center gap-2">
                            <?php $pic = userHasProfilePicture($u) ? profilePictureUrl($u) : ''; ?>
                            <?php if ($pic): ?>
                                <img src="<?= $pic ?>" alt="" style="width:28px;height:28px;border-radius:50%;object-fit:cover;flex-shrink:0;">
                            <?php else: ?>
                                <div class="user-avatar" style="width:28px;height:28px;font-size:0.7rem;flex-shrink:0;">
                                    <?= strtoupper(substr($u['full_name'], 0, 2)) ?>
                                </div>
                            <?php endif; ?>
                            <?= sanitize($u['full_name']) ?>
                            <?php if ((int) $u['id'] === (int) $me['id']): ?>
                                <span class="text-xs text-muted">(You)</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="text-secondary"><?= sanitize($u['email']) ?></td>
                    <td><code class="staff-username"><?= sanitize($u['username'] ?? '') !== '' ? sanitize($u['username']) : '—' ?></code></td>
                    <td class="text-secondary"><?= sanitize($u['phone_number'] ?? '') !== '' ? sanitize($u['phone_number']) : '—' ?></td>
                    <td class="text-secondary text-sm"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <?php if ((int) $u['id'] !== (int) $me['id']): ?>
                        <form method="POST" action="" class="delete-staff-form" style="display:inline;">
                            <input type="hidden" name="action" value="delete_staff">
                            <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                            <button type="button" class="btn btn-ghost btn-sm btn-delete-staff"
                                    data-name="<?= sanitize($u['full_name']) ?>">Delete</button>
                        </form>
                        <?php else: ?>
                            <span class="text-sm text-muted">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.querySelectorAll('.btn-delete-staff').forEach(function (btn) {
    btn.addEventListener('click', function () {
        const form = btn.closest('form');
        const name = btn.dataset.name || 'this staff member';
        const message = 'Are you sure you want to delete this user account?';
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Delete staff account?',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#78716c',
                confirmButtonText: 'Delete'
            }).then(function (r) { if (r.isConfirmed) form.submit(); });
        } else if (confirm(message)) {
            form.submit();
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
