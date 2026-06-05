<?php
require_once __DIR__ . '/auth.php';
$navUser = currentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

function navItem($href, $icon, $label, $page, $current) {
    $active = ($current === $page || str_starts_with($current, $page)) ? 'active' : '';
    echo "<a href=\"$href\" class=\"nav-item $active\">
            <span class=\"nav-icon\">$icon</span>
            <span>$label</span>
          </a>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'BantayPurrPaws' ?> — BantayPurrPaws</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="<?= url('css/style.css') ?>">
    <link rel="stylesheet" href="<?= url('css/theme.css') ?>">
    <link rel="stylesheet" href="<?= url('css/responsive.css') ?>">
    <?php if (!empty($useBootstrap)): ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php endif; ?>
    <?php foreach ($extraCss ?? [] as $cssFile): ?>
    <link rel="stylesheet" href="<?= url($cssFile) ?>">
    <?php endforeach; ?>
</head>
<body>
<div class="app-layout">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <a href="<?= dashboardHomeUrl() ?>" class="logo-mark" title="Bantay PurrPaws">
                <img src="<?= url('assets/logo.png') ?>" alt="Bantay PurrPaws" class="sidebar-logo-img">
            </a>
            <p>Rescue &amp; Adoption System</p>
        </div>

        <nav class="sidebar-nav">
            <?php if (isAdministrator()): ?>
                <div class="nav-section-label">Overview</div>
                <?php navItem(url('admin/dashboard.php'), '⊞', 'Dashboard', 'dashboard', $currentPage); ?>

                <div class="nav-section-label">Rescue</div>
                <?php navItem(url('admin/reports.php'), '📋', 'Rescue Reports', 'reports', $currentPage); ?>

                <div class="nav-section-label">Adoption</div>
                <?php navItem(url('admin/pets.php'), '🐾', 'Pet Management', 'pets', $currentPage); ?>
                <?php navItem(url('admin/adoption-requests.php'), '📝', 'Adoption Requests', 'adoption-requests', $currentPage); ?>

                <div class="nav-section-label">System</div>
                <?php navItem(url('admin/announcements.php'), '📢', 'Announcements', 'announcements', $currentPage); ?>
                <?php navItem(url('admin/staff.php'), '🛡', 'Staff Accounts', 'staff', $currentPage); ?>
                <?php navItem(url('admin/users.php'), '👥', 'User Accounts', 'users', $currentPage); ?>

                <div class="nav-section-label">Account</div>
                <?php navItem(url('profile.php'), '👤', 'My Profile', 'profile', $currentPage); ?>

            <?php elseif (isStaff()): ?>
                <div class="nav-section-label">Overview</div>
                <?php navItem(url('admin/dashboard.php'), '⊞', 'Dashboard', 'dashboard', $currentPage); ?>

                <div class="nav-section-label">Rescue</div>
                <?php navItem(url('admin/reports.php'), '📋', 'Rescue Reports', 'reports', $currentPage); ?>

                <div class="nav-section-label">Shelter &amp; Adoption</div>
                <?php navItem(url('admin/pets.php'), '🐾', 'Animal Records', 'pets', $currentPage); ?>
                <?php navItem(url('admin/adoption-requests.php'), '📊', 'Adoption Monitor', 'adoption-requests', $currentPage); ?>

                <div class="nav-section-label">Account</div>
                <?php navItem(url('profile.php'), '👤', 'My Profile', 'profile', $currentPage); ?>

            <?php else: ?>
                <div class="nav-section-label">Overview</div>
                <?php navItem(url('dashboard.php'), '⊞', 'Dashboard', 'dashboard', $currentPage); ?>

                <div class="nav-section-label">Rescue</div>
                <?php navItem(url('report.php'), '＋', 'Submit Report', 'report', $currentPage); ?>
                <?php navItem(url('my-reports.php'), '📋', 'My Reports', 'my-reports', $currentPage); ?>

                <div class="nav-section-label">Adoption</div>
                <?php navItem(url('adoption.php'), '❤', 'Adopt a Pet', 'adoption', $currentPage); ?>

                <div class="nav-section-label">Updates</div>
                <?php navItem(url('announcements.php'), '📢', 'Announcements', 'announcements', $currentPage); ?>

                <div class="nav-section-label">Account</div>
                <?php navItem(url('profile.php'), '👤', 'My Profile', 'profile', $currentPage); ?>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <div class="user-card">
                <?php if (!empty($navUser['avatar'])): ?>
                <img src="<?= sanitize($navUser['avatar']) ?>" alt="" class="user-avatar" style="object-fit:cover;padding:0;">
                <?php else: ?>
                <div class="user-avatar"><?= strtoupper(substr($navUser['name'], 0, 2)) ?></div>
                <?php endif; ?>
                <div class="user-info">
                    <div class="name"><?= sanitize($navUser['name']) ?></div>
                    <div class="role-badge <?= roleBadgeClass() ?>"><?= roleLabel() ?></div>
                </div>
                <a href="<?= url('logout.php') ?>" class="logout-btn" title="Logout">⎋</a>
            </div>
        </div>
    </aside>

    <div class="main-content">
        <header class="topbar">
            <div class="flex items-center gap-3">
                <button class="btn btn-ghost btn-icon sidebar-toggle" id="sidebarToggle" type="button" aria-label="Open menu" aria-expanded="false">☰</button>
                <span class="topbar-title"><?= sanitize($pageTitle ?? 'BantayPurrPaws') ?></span>
            </div>
            <div class="topbar-actions">
                <?php require __DIR__ . '/notification-bell.php'; ?>
                <script>
                function headerMarkRead(id, el) {
                    if (!el.classList.contains('unread')) return;
                    el.classList.remove('unread');
                    fetch('<?= url('api/notifications.php') ?>', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'action=mark_read&id=' + id
                    });
                    const badge = document.querySelector('.notif-badge');
                    if (badge) {
                        const n = parseInt(badge.textContent, 10) - 1;
                        if (n > 0) { badge.textContent = n > 99 ? '99+' : n; }
                        else { badge.textContent = ''; badge.style.display = 'none'; }
                    }
                }
                function headerMarkAllRead() {
                    fetch('<?= url('api/notifications.php') ?>', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'action=mark_all_read'
                    });
                    document.querySelectorAll('.notification-item.unread').forEach(function (el) {
                        el.classList.remove('unread');
                    });
                    const badge = document.querySelector('.notif-badge');
                    if (badge) { badge.textContent = ''; badge.style.display = 'none'; }
                }
                </script>
                <span class="text-sm text-secondary"><?= sanitize($navUser['name']) ?></span>
                <span class="role-badge <?= roleBadgeClass() ?>"><?= roleLabel() ?></span>
            </div>
        </header>

        <main class="page-body">
            <?php
            if ($success = flash('success')) {
                echo "<div class=\"alert alert-success\">✓ " . sanitize($success) . "</div>";
            }
            if ($error = flash('error')) {
                echo "<div class=\"alert alert-error\">✕ " . sanitize($error) . "</div>";
            }
            ?>
