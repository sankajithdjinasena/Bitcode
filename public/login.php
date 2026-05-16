<?php
session_start();
require_once "../config/database.php";

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate input
    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {

        // Get user by email
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify password
        if ($user && password_verify($password, $user['password'])) {

            // Check account status
            if ($user['status'] === 'deactivated') {
                $error = "This account has been deactivated.";
            } else {

                // Secure session
                session_regenerate_id(true);

                // Remove password before storing in session
                unset($user['password']);

                // Store user in session
                $_SESSION['user'] = $user;

                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header("Location: ../admin/dashboard.php");
                    exit;
                } else {
                    header("Location: marketplace.php");
                    exit;
                }
            }

        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SwiftDrop — Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:        #0a0a0f;
            --surface:   #111118;
            --border:    rgba(255,255,255,0.07);
            --border-hi: rgba(255,255,255,0.15);
            --accent:    #ff4d1c;
            --accent2:   #ff8c42;
            --text:      #f0ede8;
            --muted:     #6e6b7a;
            --success:   #22c55e;
            --danger:    #ef4444;
        }

        html, body {
            height: 100%;
            background: var(--bg);
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-weight: 400;
            line-height: 1.6;
            overflow: hidden;
        }

        /* ── Grid background ── */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,77,28,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,77,28,0.03) 1px, transparent 1px);
            background-size: 48px 48px;
            pointer-events: none;
            z-index: 0;
        }

        /* ── Glow blob ── */
        body::after {
            content: '';
            position: fixed;
            top: -20%;
            right: -10%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(255,77,28,0.12) 0%, transparent 70%);
            pointer-events: none;
            z-index: 0;
        }

        .page {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 2rem;
        }

        /* ── Left brand panel ── */
        .brand-panel {
            display: none;
            flex-direction: column;
            justify-content: space-between;
            width: 420px;
            flex-shrink: 0;
            padding: 3rem;
            margin-right: 4rem;
            height: 560px;
        }

        @media (min-width: 900px) {
            .brand-panel { display: flex; }
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo-icon {
            width: 36px;
            height: 36px;
            background: var(--accent);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .logo-name {
            font-family: 'Syne', sans-serif;
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .brand-tagline {
            margin-top: 3rem;
        }

        .brand-tagline h1 {
            font-family: 'Syne', sans-serif;
            font-size: clamp(2rem, 4vw, 2.8rem);
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -1px;
        }

        .brand-tagline h1 span {
            color: var(--accent);
        }

        .brand-tagline p {
            margin-top: 1rem;
            color: var(--muted);
            font-size: 15px;
            font-weight: 300;
            max-width: 300px;
            line-height: 1.7;
        }

        /* ── Live indicator ── */
        .live-bar {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--muted);
        }

        .live-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--success);
            animation: pulse-dot 2s infinite;
        }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50%       { opacity: 0.5; transform: scale(1.3); }
        }

        /* ── Login card ── */
        .card {
            width: 100%;
            max-width: 400px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 2.5rem;
            animation: card-in 0.5s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        @keyframes card-in {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Mobile logo inside card */
        .mobile-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 2rem;
        }

        @media (min-width: 900px) {
            .mobile-logo { display: none; }
        }

        .card-header {
            margin-bottom: 2rem;
        }

        .card-header h2 {
            font-family: 'Syne', sans-serif;
            font-size: 1.6rem;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .card-header p {
            margin-top: 6px;
            font-size: 14px;
            color: var(--muted);
        }

        /* ── Error alert ── */
        .alert {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 1.5rem;
            animation: fade-in 0.3s ease;
        }

        @keyframes fade-in {
            from { opacity: 0; transform: translateY(-4px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .alert-error {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.3);
            color: #fca5a5;
        }

        .alert-icon { font-size: 16px; flex-shrink: 0; }

        /* ── Form fields ── */
        .field {
            margin-bottom: 1.25rem;
        }

        .field label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: var(--muted);
            margin-bottom: 8px;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap svg {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            pointer-events: none;
        }

        .field input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.75rem;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-size: 15px;
            outline: none;
            transition: border-color 0.2s, background 0.2s;
        }

        .field input::placeholder { color: var(--muted); }

        .field input:hover {
            border-color: var(--border-hi);
            background: rgba(255,255,255,0.06);
        }

        .field input:focus {
            border-color: var(--accent);
            background: rgba(255,77,28,0.05);
            box-shadow: 0 0 0 3px rgba(255,77,28,0.12);
        }

        /* ── Password toggle ── */
        .pw-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--muted);
            cursor: pointer;
            padding: 2px;
            transition: color 0.2s;
        }

        .pw-toggle:hover { color: var(--text); }

        /* ── Submit button ── */
        .btn-submit {
            width: 100%;
            padding: 0.85rem;
            margin-top: 0.5rem;
            background: var(--accent);
            border: none;
            border-radius: 10px;
            color: #fff;
            font-family: 'Syne', sans-serif;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.3px;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s, box-shadow 0.2s;
            position: relative;
            overflow: hidden;
        }

        .btn-submit:hover {
            background: #ff6635;
            box-shadow: 0 4px 24px rgba(255,77,28,0.35);
        }

        .btn-submit:active { transform: scale(0.98); }

        .btn-submit::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, transparent 60%);
            pointer-events: none;
        }

        /* ── Loading state ── */
        .btn-submit.loading {
            pointer-events: none;
        }

        .spinner {
            display: none;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            vertical-align: middle;
            margin-right: 6px;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        .btn-submit.loading .spinner { display: inline-block; }
        .btn-submit.loading .btn-text { opacity: 0.7; }

        /* ── Footer link ── */
        .card-footer {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 14px;
            color: var(--muted);
        }

        .card-footer a {
            color: var(--accent2);
            text-decoration: none;
            font-weight: 500;
        }

        .card-footer a:hover { text-decoration: underline; }

        /* ── Divider ── */
        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 1.5rem 0;
        }

        .divider span {
            font-size: 12px;
            color: var(--muted);
            white-space: nowrap;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }
    </style>
</head>
<body>
<div class="page">

    <!-- Left brand panel (desktop only) -->
    <div class="brand-panel">
        <div>
            <a href="index.php" class="logo" style="text-decoration:none; color:inherit;">
                <div class="logo-icon">⚡</div>
                <span class="logo-name">SwiftDrop</span>
            </a>

            <div class="brand-tagline">
                <h1>Drop in.<br>Grab fast.<br><span>Win big.</span></h1>
                <p>Flash sales. Limited stock. Zero slowdowns. Built for the rush.</p>
            </div>
        </div>

        <div>
            <div class="live-bar">
                <div class="live-dot"></div>
                <span>Platform live — sales active now</span>
            </div>
            <a href="index.php" style="display:inline-flex; align-items:center; gap:6px; margin-top:14px; font-size:13px; color:var(--muted); text-decoration:none; transition:color 0.2s;" onmouseover="this.style.color='var(--text)'" onmouseout="this.style.color='var(--muted)'">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                Back to home
            </a>
        </div>
    </div>

    <!-- Login card -->
    <div class="card">

        <!-- Mobile logo -->
        <a href="index.php" class="mobile-logo" style="text-decoration:none; color:inherit;">
            <div class="logo-icon">⚡</div>
            <span class="logo-name">SwiftDrop</span>
        </a>

        <div class="card-header">
            <h2>Welcome back</h2>
            <p>Sign in to access flash sales</p>
        </div>

        <!-- Mobile-only back to home -->
        <a href="index.php" class="mobile-home-link">
            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            Back to home
        </a>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error" role="alert">
                <span class="alert-icon">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                </span>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="loginForm" novalidate>

            <div class="field">
                <label for="email">Email</label>
                <div class="input-wrap">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 7l10 7 10-7"/>
                    </svg>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="you@example.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        autocomplete="email"
                        required
                        autofocus
                    >
                </div>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <div class="input-wrap">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        required
                    >
                    <button type="button" class="pw-toggle" id="pwToggle" aria-label="Show password">
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-submit" id="submitBtn">
                <span class="spinner" aria-hidden="true"></span>
                <span class="btn-text">Sign In</span>
            </button>

        </form>

        <div class="divider"><span>new here?</span></div>

        <div class="card-footer">
            Don't have an account? <a href="register.php">Create one free</a>
        </div>

    </div>
</div>

<script>
    // Password visibility toggle
    const pwToggle = document.getElementById('pwToggle');
    const pwInput  = document.getElementById('password');
    const eyeIcon  = document.getElementById('eyeIcon');

    const eyeOpenPath  = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
    const eyeClosePath = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>';

    pwToggle.addEventListener('click', () => {
        const show = pwInput.type === 'password';
        pwInput.type = show ? 'text' : 'password';
        eyeIcon.innerHTML = show ? eyeClosePath : eyeOpenPath;
        pwToggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });

    // Loading state on submit
    const form      = document.getElementById('loginForm');
    const submitBtn = document.getElementById('submitBtn');

    form.addEventListener('submit', () => {
        submitBtn.classList.add('loading');
        submitBtn.querySelector('.btn-text').textContent = 'Signing in…';
    });
</script>
</body>
</html>