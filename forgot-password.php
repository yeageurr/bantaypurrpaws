<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/otp.php';
startSession();

if (isLoggedIn()) {
    header('Location: ' . url('dashboard.php'));
    exit;
}

$step  = $_SESSION['reset_step'] ?? 'request';
$error = '';
$info  = '';

// ── Step 1: Request OTP ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'request') {
    $email = sanitize($_POST['email'] ?? '');
    if (!$email) {
        $error = 'Please enter your email address.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT id, full_name FROM users WHERE email = ? AND auth_provider = 'local' LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Always show the same message to avoid email enumeration
        if ($user) {
            $result = issueAndSendOtp($email, $user['full_name'], 'password_reset');
            if ($result !== true) {
                $error = is_string($result) ? $result : 'Failed to send OTP. Please try again.';
            } else {
                $_SESSION['reset_email'] = $email;
                $_SESSION['reset_step']  = 'verify';
                $step = 'verify';
                $info = "A reset code was sent to <strong>{$email}</strong>.";
            }
        } else {
            // Still show info to prevent enumeration
            $_SESSION['reset_step'] = 'verify';
            $step = 'verify';
            $info = "If that email is registered, a reset code has been sent.";
        }
    }
}

// ── Step 2: Verify OTP ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'verify') {
    $email = $_SESSION['reset_email'] ?? '';
    $code  = trim($_POST['otp_code'] ?? '');
    if (!$email || !$code) {
        $error = 'Session expired. Please start over.';
        $step  = 'request';
        unset($_SESSION['reset_step'], $_SESSION['reset_email']);
    } else {
        $db     = getDB();
        $status = verifyOtp($email, $code, 'password_reset');
        if ($status === 'valid') {
            $_SESSION['reset_step']     = 'new_password';
            $_SESSION['reset_verified'] = true;
            $step = 'new_password';
        } elseif ($status === 'expired') {
            $error = 'OTP expired. Please request a new one.';
            $step  = 'verify';
        } else {
            $error = 'Invalid OTP code.';
            $step  = 'verify';
        }
    }
}

// ── Step 3: Set new password ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'new_password') {
    $email    = $_SESSION['reset_email']    ?? '';
    $verified = $_SESSION['reset_verified'] ?? false;
    $pwd      = $_POST['password']          ?? '';
    $confirm  = $_POST['confirm_password']  ?? '';

    // ── Strong password policy ────────────────────────────────────────────
    $pwErrors = [];
    if (strlen($pwd) < 12)                                                     $pwErrors[] = 'at least 12 characters';
    if (!preg_match('/[A-Z]/', $pwd))                                          $pwErrors[] = 'an uppercase letter (A-Z)';
    if (!preg_match('/[a-z]/', $pwd))                                          $pwErrors[] = 'a lowercase letter (a-z)';
    if (!preg_match('/[0-9]/', $pwd))                                          $pwErrors[] = 'a number (0-9)';
    if (!preg_match('/[!@#$%^&*()\-_=+\[\]{};:,\.?\/]/', $pwd))         $pwErrors[] = 'a special character';

    if (!$email || !$verified) {
        $error = 'Session invalid. Please start over.';
        $step  = 'request';
        unset($_SESSION['reset_step'], $_SESSION['reset_email'], $_SESSION['reset_verified']);
    } elseif (!empty($pwErrors)) {
        $error = 'Password must contain: ' . implode(', ', $pwErrors) . '.';
        $step  = 'new_password';
    } elseif ($pwd !== $confirm) {
        $error = 'Passwords do not match.';
        $step  = 'new_password';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([password_hash($pwd, PASSWORD_DEFAULT), $email]);

        unset($_SESSION['reset_step'], $_SESSION['reset_email'], $_SESSION['reset_verified']);
        flash('success', 'Your password has been reset. Please sign in.');
        header('Location: ' . url('login.php'));
        exit;
    }
}

// Resend OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'resend') {
    $email = $_SESSION['reset_email'] ?? '';
    if ($email) {
        $db   = getDB();
        $stmt = $db->prepare("SELECT full_name FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        if ($row) {
            $result = issueAndSendOtp($email, $row['full_name'], 'password_reset');
            $info   = $result === true ? 'A new code has been sent.' : (is_string($result) ? $result : 'Failed to resend.');
        }
    }
    $step = 'verify';
}

$step = $_SESSION['reset_step'] ?? $step;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — BantayPurrPaws</title>
    <link rel="stylesheet" href="<?= url('css/style.css') ?>">
    <link rel="stylesheet" href="<?= url('css/responsive.css') ?>">
    <style>
        .otp-inputs{display:flex;gap:10px;justify-content:center;margin:20px 0;}
        .otp-inputs input{width:48px;height:56px;text-align:center;font-size:22px;font-weight:700;border:2px solid var(--border);border-radius:8px;background:var(--surface-1);color:var(--text-primary);}
        .otp-inputs input:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(124,111,91,.15);}
        .resend-row{text-align:center;margin-top:12px;font-size:13px;color:var(--text-muted);}
        .resend-row button{background:none;border:none;color:var(--primary);font-size:13px;cursor:pointer;text-decoration:underline;padding:0;}
        .step-badge{text-align:center;font-size:12px;letter-spacing:1px;text-transform:uppercase;color:var(--text-muted);margin-bottom:6px;}
    </style>
</head>
<body>
<div class="auth-page">
    <div class="auth-panel fade-in">
        <div class="auth-logo">
            <img src="<?= url('assets/logo.png') ?>" alt="Bantay PurrPaws" class="auth-logo-img">
            <h1>Reset Password</h1>
            <p>
                <?php if ($step === 'request') echo 'Enter your email to receive a reset code.';
                elseif ($step === 'verify')   echo 'Enter the 6-digit code sent to your email.';
                else                          echo 'Create your new password.'; ?>
            </p>
        </div>

        <?php if ($error): ?><div class="alert alert-error">✕ <?= $error ?></div><?php endif; ?>
        <?php if ($info):  ?><div class="alert alert-info">ℹ <?= $info ?></div><?php endif; ?>

        <?php if ($step === 'request'): ?>
        <form method="POST" action="">
            <input type="hidden" name="action" value="request">
            <div class="form-group">
                <label class="form-label">Email Address <span class="req">*</span></label>
                <input type="email" name="email" class="form-control" placeholder="you@example.com" required autofocus>
            </div>
            <button type="submit" class="btn btn-primary w-full" style="justify-content:center;padding:11px;">
                Send Reset Code
            </button>
        </form>

        <?php elseif ($step === 'verify'): ?>
        <form method="POST" action="" id="otpForm">
            <input type="hidden" name="action" value="verify">
            <input type="hidden" name="otp_code" id="otp_code_hidden">
            <div class="otp-inputs">
                <?php for ($i = 0; $i < 6; $i++): ?>
                    <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]"
                           class="otp-digit" <?= $i === 0 ? 'autofocus' : '' ?>>
                <?php endfor; ?>
            </div>
            <button type="submit" class="btn btn-primary w-full" style="justify-content:center;padding:11px;">
                Verify Code
            </button>
        </form>
        <div class="resend-row">
            Didn't get it?
            <form method="POST" style="display:inline;">
                <input type="hidden" name="action" value="resend">
                <button type="submit">Resend Code</button>
            </form>
        </div>
        <script>
        (function(){
            const digits=document.querySelectorAll('.otp-digit'),hidden=document.getElementById('otp_code_hidden');
            digits.forEach((el,i)=>{
                el.addEventListener('input',()=>{el.value=el.value.replace(/\D/,'');if(el.value&&i<digits.length-1)digits[i+1].focus();sync();});
                el.addEventListener('keydown',e=>{if(e.key==='Backspace'&&!el.value&&i>0)digits[i-1].focus();});
                el.addEventListener('paste',e=>{e.preventDefault();const p=(e.clipboardData.getData('text')||'').replace(/\D/g,'').slice(0,6);p.split('').forEach((c,j)=>{if(digits[j])digits[j].value=c;});if(p.length<6&&digits[p.length])digits[p.length].focus();sync();});
            });
            function sync(){hidden.value=Array.from(digits).map(d=>d.value).join('');}
            document.getElementById('otpForm').addEventListener('submit',e=>{sync();if(hidden.value.length<6){e.preventDefault();alert('Enter all 6 digits.');}});
        })();
        </script>

        <?php else: ?>
        <form method="POST" action="">
            <input type="hidden" name="action" value="new_password">
            <div class="form-group" id="fp-pw-field-group">
                <label class="form-label">New Password <span class="req">*</span></label>
                <input type="password" id="fp_password" name="password" class="form-control"
                       placeholder="Min. 12 characters" autocomplete="new-password" required autofocus>
            </div>
            <!-- Password strength widget injected here by JS -->
            <div class="form-group">
                <label class="form-label">Confirm Password <span class="req">*</span></label>
                <input type="password" id="fp_confirm_password" name="confirm_password" class="form-control"
                       placeholder="Repeat password" autocomplete="new-password" required>
            </div>
            <button type="submit" class="btn btn-primary w-full" style="justify-content:center;padding:11px;">
                Set New Password
            </button>
        </form>
        <?php endif; ?>

        <div class="auth-footer"><a href="<?= url('login.php') ?>">← Back to Login</a></div>
    </div>
</div>
<script src="<?= url('js/password-strength.js') ?>"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    // Only init when the new_password step is shown
    if (document.getElementById("fp_password")) {
        initPasswordStrength("fp_password", "fp_confirm_password", "fp-pw-field-group");
    }
});
</script>
</body>
</html>
