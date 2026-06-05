<?php
require_once __DIR__ . '/includes/paths.php';

// If Google Console redirect URI points at the site root, forward to the real callback.
if (!empty($_GET['code']) && isset($_GET['state'])) {
    $qs = $_SERVER['QUERY_STRING'] ?? '';
    header('Location: ' . absolute_url('auth/google-callback.php') . ($qs !== '' ? '?' . $qs : ''));
    exit;
}

require_once __DIR__ . '/includes/auth.php';
startSession();

if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? url('admin/dashboard.php') : url('dashboard.php')));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BantayPurrPaws — Stray Animal Rescue &amp; Adoption</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="<?= url('css/style.css') ?>">
    <link rel="stylesheet" href="<?= url('css/landing.css') ?>">
    <link rel="stylesheet" href="<?= url('css/responsive.css') ?>">
</head>
<body>
<div class="landing-page fade-in">
    <header class="landing-header">
        <div class="landing-brand">
            <img src="<?= url('assets/logo.png') ?>" alt="Bantay PurrPaws">
            <span>BantayPurrPaws</span>
        </div>
        <a href="<?= url('login.php') ?>" class="landing-header-link landing-header-link-signin">Sign in</a>
    </header>

    <main class="landing-main">
        <span class="landing-eyebrow">Community rescue platform</span>
        <h1 class="landing-title">Help strays find safety<br>and loving homes</h1>
        <p class="landing-lead">
            BantayPurrPaws connects concerned citizens, rescue staff, and adopters.
            Report animals in need, track rescue progress, and browse pets ready for adoption, all in one place.
        </p>
        <a href="<?= url('login.php') ?>" class="landing-cta">Get Started</a>
        <p class="landing-cta-secondary">
            New here? <a href="<?= url('register.php') ?>">Create an account</a>
        </p>
    </main>

    <section class="landing-features" aria-label="Features">
        <article class="landing-feature">
            <div class="landing-feature-icon" aria-hidden="true"></div>
            <h3>Report rescues</h3>
            <p>Submit location, details, and photos so our team can respond quickly.</p>
        </article>
        <article class="landing-feature">
            <div class="landing-feature-icon" aria-hidden="true"></div>
            <h3>Adopt a pet</h3>
            <p>Browse available rescues and apply to give a stray a permanent home.</p>
        </article>
        <article class="landing-feature">
            <div class="landing-feature-icon" aria-hidden="true"></div>
            <h3>Track progress</h3>
            <p>Follow report status from pending through rescue, with updates along the way.</p>
        </article>
    </section>

    <footer class="landing-footer">
        &copy; <?= date('Y') ?> BantayPurrPaws — Stray Animal Rescue &amp; Adoption System
    </footer>
</div>
</body>
</html>
