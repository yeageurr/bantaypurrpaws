<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/otp.php';
require_once __DIR__ . '/includes/notifications.php';
startSession();

if (isLoggedIn()) {
    header('Location: ' . url('dashboard.php'));
    exit;
}

$step  = $_SESSION['reg_step'] ?? 'form';
$error = '';
$info  = '';

// ── STEP 1: Collect details and send OTP ─────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'register') {
    $fullName   = sanitize($_POST['full_name']       ?? '');
    $email      = sanitize($_POST['email']           ?? '');
    $password   = $_POST['password']                ?? '';
    $confirmPwd = $_POST['confirm_password']        ?? '';

    $pwErrors = [];
    if (strlen($password) < 12)                                            $pwErrors[] = 'at least 12 characters';
    if (!preg_match('/[A-Z]/', $password))                                 $pwErrors[] = 'an uppercase letter (A-Z)';
    if (!preg_match('/[a-z]/', $password))                                 $pwErrors[] = 'a lowercase letter (a-z)';
    if (!preg_match('/[0-9]/', $password))                                 $pwErrors[] = 'a number (0-9)';
    if (!preg_match('/[!@#$%^&*()\-_=+\[\]{};:,\.?\/]/', $password))  $pwErrors[] = 'a special character';

    if (empty($fullName) || empty($email) || empty($password) || empty($confirmPwd)) {
        $error = 'Please fill out all required fields.';
    } elseif ($password !== $confirmPwd) {
        $error = 'Passwords do not match.';
    } elseif (!empty($pwErrors)) {
        $error = 'Password must contain: ' . implode(', ', $pwErrors) . '.';
    } else {
        // ← CHANGED: uses emailExists() instead of PDO
        if (emailExists($email)) {
            $error = 'An account with that email already exists.';
        } else {
            $_SESSION['reg_pending'] = [
                'full_name' => $fullName,
                'email'     => $email,
                'password'  => password_hash($password, PASSWORD_DEFAULT),
            ];

            // ← otp.php will be updated next — still works same way
            $result = issueAndSendOtp($email, $fullName, 'registration');
            if ($result === true) {
                $_SESSION['reg_step'] = 'otp_pending';
                $step = 'otp_pending';
                $info = "A 6-digit verification code was sent to <strong>{$email}</strong>. It expires in 5 minutes.";
            } else {
                $error = is_string($result) ? $result : 'Could not send OTP. Please try again.';
            }
        }
    }
}

// ── STEP 2: Verify OTP and create account ────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'verify_otp') {
    $code    = trim($_POST['otp_code'] ?? '');
    $pending = $_SESSION['reg_pending'] ?? null;

    if (!$pending) {
        $error = 'Session expired. Please start again.';
        $_SESSION['reg_step'] = 'form';
        $step = 'form';
    } else {
        // ← CHANGED: verifyOtp() no longer needs $db
        $status = verifyOtp($pending['email'], $code, 'registration');

        if ($status === 'valid') {
            // ← CHANGED: uses createUser() instead of PDO INSERT
            $userId = createUser($pending['full_name'], $pending['email'], $pending['password']);

            if ($userId) {
                unset($_SESSION['reg_pending'], $_SESSION['reg_step']);

                require_once __DIR__ . '/includes/users.php';
                $newUser = getUserById($userId);
                if ($newUser) {
                    refreshUserSession($newUser);
                }

                createSystemNotification('system', 'Welcome to BantayPurrPaws! Your email was verified.', null, null, $userId);

                flash('success', 'Welcome! Your account has been created and your email verified.');
                header('Location: ' . url('dashboard.php'));
                exit;
            } else {
                $error = 'Could not create account. Please try again.';
            }
        } elseif ($status === 'expired') {
            $error = 'Your OTP has expired. Please request a new code.';
        } else {
            $error = 'Invalid code. Please check and try again.';
        }
        $step = 'otp_pending';
    }
}

// ── STEP 2b: Resend OTP ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'resend_otp') {
    $pending = $_SESSION['reg_pending'] ?? null;
    if ($pending) {
        $result = issueAndSendOtp($pending['email'], $pending['full_name'], 'registration');
        $info   = ($result === true) ? 'A new OTP has been sent to your email.' : 'Could not resend OTP.';
    }
    $step = 'otp_pending';
}

$step = $_SESSION['reg_step'] ?? $step;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — BantayPurrPaws</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="<?= url('css/style.css') ?>">
    <link rel="stylesheet" href="<?= url('css/responsive.css') ?>">
    <style>
        .otp-inputs{display:flex;gap:10px;justify-content:center;margin:20px 0;}
        .otp-inputs input{width:48px;height:56px;text-align:center;font-size:22px;font-weight:700;border:2px solid var(--border);border-radius:8px;background:var(--surface-1);color:var(--text-primary);}
        .otp-inputs input:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(124,111,91,.15);}
        #otp_code_hidden{display:none;}
        .resend-row{text-align:center;margin-top:12px;font-size:13px;color:var(--text-muted);}
        .resend-row button{background:none;border:none;color:var(--primary);font-size:13px;cursor:pointer;text-decoration:underline;padding:0;}
    </style>
</head>
<body>
<div class="auth-page">
    <div class="auth-panel fade-in">
        <div class="auth-logo">
            <img src="<?= url('assets/logo.png') ?>" alt="Bantay PurrPaws" class="auth-logo-img">
            <?php if ($step === 'form'): ?>
                <h1>Create Account</h1>
                <p>Join BantayPurrPaws and help save animals</p>
            <?php else: ?>
                <h1>Verify Your Email</h1>
                <p>Enter the 6-digit code we sent you</p>
            <?php endif; ?>
        </div>

        <?php if ($error): ?><div class="alert alert-error">✕ <?= $error ?></div><?php endif; ?>
        <?php if ($info):  ?><div class="alert alert-info">ℹ <?= $info ?></div><?php endif; ?>

        <?php if ($step === 'form'): ?>
        <form method="POST" action="">
            <input type="hidden" name="action" value="register">
            <div class="form-group">
                <label class="form-label" for="full_name">Full Name <span class="req">*</span></label>
                <input type="text" id="full_name" name="full_name" class="form-control"
                       placeholder="Juan dela Cruz"
                       value="<?= sanitize($_POST['full_name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="email">Email Address <span class="req">*</span></label>
                <input type="email" id="email" name="email" class="form-control"
                       placeholder="you@example.com"
                       value="<?= sanitize($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="form-group" id="pw-field-group">
                <label class="form-label" for="password">Password <span class="req">*</span></label>
                <input type="password" id="password" name="password" class="form-control"
                       placeholder="Min. 12 characters" autocomplete="new-password" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="confirm_password">Confirm Password <span class="req">*</span></label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                       placeholder="Repeat password" autocomplete="new-password" required>
            </div>
            <button type="submit" class="btn btn-primary w-full" style="justify-content:center;padding:11px;">
                Create Account &amp; Send OTP
            </button>
        </form>
        <div class="auth-footer">
            Already have an account? <a href="<?= url('login.php') ?>">Sign in</a>
        </div>

        <?php else: ?>
        <form method="POST" action="" id="otpForm">
            <input type="hidden" name="action" value="verify_otp">
            <input type="hidden" name="otp_code" id="otp_code_hidden">
            <div class="otp-inputs" id="otpBoxes">
                <?php for ($i = 0; $i < 6; $i++): ?>
                    <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]"
                           class="otp-digit" autocomplete="one-time-code"
                           <?= $i === 0 ? 'autofocus' : '' ?>>
                <?php endfor; ?>
            </div>
            <button type="submit" class="btn btn-primary w-full" style="justify-content:center;padding:11px;">
                Verify &amp; Create Account
            </button>
        </form>
        <div class="resend-row">
            Didn't receive the code?
            <form method="POST" action="" style="display:inline;">
                <input type="hidden" name="action" value="resend_otp">
                <button type="submit">Resend OTP</button>
            </form>
        </div>
        <div class="auth-footer" style="margin-top:16px;">
            <a href="<?= url('register.php') ?>" onclick="<?= "return confirm('Start over? Your details will be lost.')" ?>">← Start over</a>
        </div>
        <script>
        (function () {
            const digits = document.querySelectorAll('.otp-digit');
            const hidden = document.getElementById('otp_code_hidden');
            digits.forEach((el, i) => {
                el.addEventListener('input', () => {
                    el.value = el.value.replace(/\D/, '');
                    if (el.value && i < digits.length - 1) digits[i + 1].focus();
                    syncHidden();
                });
                el.addEventListener('keydown', e => {
                    if (e.key === 'Backspace' && !el.value && i > 0) digits[i - 1].focus();
                });
                el.addEventListener('paste', e => {
                    e.preventDefault();
                    const pasted = (e.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, 6);
                    pasted.split('').forEach((ch, j) => { if (digits[j]) digits[j].value = ch; });
                    if (pasted.length < 6 && digits[pasted.length]) digits[pasted.length].focus();
                    syncHidden();
                });
            });
            function syncHidden() {
                hidden.value = Array.from(digits).map(d => d.value).join('');
            }
            document.getElementById('otpForm').addEventListener('submit', e => {
                syncHidden();
                if (hidden.value.length < 6) { e.preventDefault(); alert('Please enter all 6 digits.'); }
            });
        })();
        </script>
        <?php endif; ?>
    </div>
</div>
<script src="<?= url('js/password-strength.js') ?>"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    initPasswordStrength("password", "confirm_password", "pw-field-group");
});
</script>
</body>
</html>
