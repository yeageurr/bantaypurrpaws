<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/notifications.php';
require_once __DIR__ . '/includes/google-oauth.php';
startSession();

if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? url('admin/dashboard.php') : url('dashboard.php')));
    exit;
}

$error = flash('error') ?? '';

// Google Sign-In redirect
if (isset($_GET['google'])) {
    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth_state'] = $state;
    header('Location: ' . googleAuthUrl($state));
    exit;
}

// Standard email/password login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill out all required fields.';
    } else {
        // ← CHANGED: uses loginUser() instead of PDO
        $result = loginUser($email, $password);

        if (is_array($result)) {
            // Success — notify and redirect
            createSystemNotification('system', 'You logged in successfully.', null, null, (int)$result['id']);

            $redirect = in_array($result['role'], ['admin', 'staff'])
                ? url('admin/dashboard.php')
                : url('dashboard.php');
            header("Location: $redirect");
            exit;
        } else {
            $error = $result; // error string from loginUser()
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — BantayPurrPaws</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="<?= url('css/style.css') ?>">
    <link rel="stylesheet" href="<?= url('css/responsive.css') ?>">
    <style>
        .divider{display:flex;align-items:center;gap:12px;margin:18px 0;color:var(--text-muted);font-size:13px;}
        .divider::before,.divider::after{content:'';flex:1;height:1px;background:var(--border);}
        .btn-google{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;padding:10px 16px;border:1.5px solid var(--border);background:#fff;color:var(--text-primary);border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;text-decoration:none;transition:border-color .2s,box-shadow .2s;}
        .btn-google:hover{border-color:#4285f4;box-shadow:0 0 0 3px rgba(66,133,244,.12);}
        .forgot-link{display:block;text-align:right;font-size:13px;margin-top:4px;}
    </style>
</head>
<body>
<div class="auth-page">
    <div class="auth-panel fade-in">
        <div class="auth-logo">
            <img src="<?= url('assets/logo.png') ?>" alt="Bantay PurrPaws" class="auth-logo-img">
            <p>Stray Animal Rescue &amp; Adoption System</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">✕ <?= sanitize($error) ?></div>
        <?php endif; ?>

        <a href="?google=1" class="btn-google">
            <svg width="18" height="18" viewBox="0 0 18 18"><path fill="#4285F4" d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844a4.14 4.14 0 01-1.796 2.716v2.259h2.908c1.702-1.567 2.684-3.875 2.684-6.615z"/><path fill="#34A853" d="M9 18c2.43 0 4.467-.806 5.956-2.184l-2.908-2.259c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332A8.997 8.997 0 009 18z"/><path fill="#FBBC05" d="M3.964 10.706A5.41 5.41 0 013.682 9c0-.593.102-1.17.282-1.706V4.962H.957A8.996 8.996 0 000 9c0 1.452.348 2.827.957 4.038l3.007-2.332z"/><path fill="#EA4335" d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0A8.997 8.997 0 00.957 4.962L3.964 7.294C4.672 5.163 6.656 3.58 9 3.58z"/></svg>
            Sign in with Google
        </a>

        <div class="divider">or sign in with email</div>

        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label" for="email">Email Address <span class="req">*</span></label>
                <input type="email" id="email" name="email" class="form-control"
                       placeholder="you@example.com"
                       value="<?= sanitize($_POST['email'] ?? '') ?>"
                       required autocomplete="email">
            </div>
            <div class="form-group">
                <label class="form-label" for="password">Password <span class="req">*</span></label>
                <input type="password" id="password" name="password" class="form-control"
                       placeholder="••••••••" required autocomplete="current-password">
                <a href="<?= url('forgot-password.php') ?>" class="forgot-link">Forgot password?</a>
            </div>
            <button type="submit" class="btn btn-primary w-full" style="justify-content:center;padding:11px;">
                Sign In
            </button>
        </form>

        <div class="auth-footer">
            Don't have an account? <a href="<?= url('register.php') ?>">Create one</a>
        </div>
        <div class="auth-footer" style="margin-top:12px;">
            <a href="<?= url('index.php') ?>">← Back to home</a>
        </div>
    </div>
</div>
</body>
</html>
