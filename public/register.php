<?php
session_start();
require_once "../config/database.php";

// Already logged in — go to marketplace
if (isset($_SESSION['user'])) {
    header("Location: marketplace.php");
    exit;
}

$message = "";
$msgType = "error";

// Preserve field values on error
$old = ['name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name            = trim($_POST['name']             ?? '');
    $email           = trim($_POST['email']            ?? '');
    $password        = $_POST['password']              ?? '';
    $confirmPassword = $_POST['confirm_password']      ?? '';

    $old = ['name' => $name, 'email' => $email];

    // ── Validation ──
    if (empty($name) || empty($email) || empty($password) || empty($confirmPassword)) {
        $message = "All fields are required.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";

    } elseif (strlen($password) < 8) {
        $message = "Password must be at least 8 characters.";

    } elseif ($password !== $confirmPassword) {
        $message = "Passwords do not match.";

    } else {

        // ── Duplicate email check ──
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);

        if ($check->fetch()) {
            $message = "An account with that email already exists.";
        } else {

            // ── Insert ──
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("
                INSERT INTO users (name, email, password, role, status)
                VALUES (?, ?, ?, 'customer', 'active')
            ");
            $stmt->execute([$name, $email, $hashedPassword]);

            header("Location: login.php?registered=1");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SwiftDrop — Create Account</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:       #07070d;
            --surface:  #0f0f18;
            --surface2: #141420;
            --border:   rgba(255,255,255,0.07);
            --border-hi:rgba(255,255,255,0.14);
            --accent:   #ff4d1c;
            --accent2:  #ff8c42;
            --text:     #f0ede8;
            --muted:    #5a5768;
            --muted2:   #888098;
            --success:  #22c55e;
            --danger:   #ef4444;
        }

        html, body { height: 100%; }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-weight: 400;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Grid background */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,77,28,0.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,77,28,0.025) 1px, transparent 1px);
            background-size: 56px 56px;
            pointer-events: none;
            z-index: 0;
        }

        /* Glow orbs */
        .orb {
            position: fixed;
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
            filter: blur(80px);
        }
        .orb-1 { width: 600px; height: 600px; top: -150px; left: -100px;
                  background: radial-gradient(circle, rgba(255,77,28,0.09) 0%, transparent 70%); }
        .orb-2 { width: 400px; height: 400px; bottom: 0; right: -80px;
                  background: radial-gradient(circle, rgba(255,140,66,0.06) 0%, transparent 70%); }

        /* ── Nav ── */
        nav {
            position: relative;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.25rem 2.5rem;
            border-bottom: 1px solid var(--border);
        }

        .nav-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: var(--text);
        }

        .nav-logo-icon {
            width: 32px; height: 32px;
            background: var(--accent);
            border-radius: 7px;
            display: flex; align-items: center; justify-content: center;
            font-size: 17px;
        }

        .nav-logo-name {
            font-family: 'Syne', sans-serif;
            font-size: 19px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .nav-signin {
            font-size: 13px;
            color: var(--muted2);
            text-decoration: none;
            transition: color 0.2s;
        }

        .nav-signin:hover { color: var(--text); }
        .nav-signin span { color: var(--accent2); font-weight: 500; }

        /* ── Page layout ── */
        .page {
            position: relative;
            z-index: 1;
            flex: 1;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            gap: 5rem;
            padding: 3rem 2rem 5rem;
        }

        /* ── Left panel (desktop) ── */
        .side-panel {
            display: none;
            flex-direction: column;
            justify-content: center;
            width: 340px;
            flex-shrink: 0;
            padding-top: 1rem;
        }

        @media (min-width: 960px) { .side-panel { display: flex; } }

        .side-panel h2 {
            font-family: 'Syne', sans-serif;
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -1px;
            line-height: 1.1;
            margin-bottom: 2.5rem;
        }

        .side-panel h2 span { color: var(--accent); }

        .perk-list {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .perk {
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }

        .perk-icon {
            width: 38px; height: 38px;
            border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .perk-text h4 {
            font-family: 'Syne', sans-serif;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .perk-text p {
            font-size: 13px;
            color: var(--muted2);
            font-weight: 300;
            line-height: 1.55;
        }

        .side-footer {
            margin-top: 3rem;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: var(--muted);
        }

        .live-dot {
            width: 7px; height: 7px;
            border-radius: 50%;
            background: var(--success);
            animation: pulse-dot 2s infinite;
        }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50%       { opacity: 0.5; transform: scale(1.4); }
        }

        /* ── Card ── */
        .card {
            width: 100%;
            max-width: 420px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 2.5rem;
            animation: card-in 0.5s cubic-bezier(0.22,1,0.36,1) both;
        }

        @keyframes card-in {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .card-header { margin-bottom: 2rem; }

        .card-header h2 {
            font-family: 'Syne', sans-serif;
            font-size: 1.55rem;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .card-header p {
            margin-top: 6px;
            font-size: 14px;
            color: var(--muted2);
        }

        /* ── Alert ── */
        .alert {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 1.5rem;
            animation: fade-in 0.3s ease;
            line-height: 1.5;
        }

        @keyframes fade-in {
            from { opacity: 0; transform: translateY(-4px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .alert-error {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.25);
            color: #fca5a5;
        }

        .alert svg { flex-shrink: 0; margin-top: 1px; }

        /* ── Fields ── */
        .field { margin-bottom: 1.1rem; }

        .field label {
            display: block;
            font-size: 12px;
            font-weight: 500;
            color: var(--muted2);
            margin-bottom: 7px;
            letter-spacing: 0.4px;
            text-transform: uppercase;
        }

        .input-wrap { position: relative; }

        .input-wrap svg.field-icon {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            pointer-events: none;
        }

        .field input {
            width: 100%;
            padding: 0.72rem 1rem 0.72rem 2.6rem;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-size: 15px;
            outline: none;
            transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
        }

        .field input::placeholder { color: var(--muted); }

        .field input:hover {
            border-color: var(--border-hi);
            background: rgba(255,255,255,0.055);
        }

        .field input:focus {
            border-color: var(--accent);
            background: rgba(255,77,28,0.05);
            box-shadow: 0 0 0 3px rgba(255,77,28,0.12);
        }

        .field input.invalid {
            border-color: rgba(239,68,68,0.5);
            background: rgba(239,68,68,0.05);
        }

        /* Password toggle */
        .pw-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--muted);
            cursor: pointer;
            padding: 2px;
            transition: color 0.2s;
            line-height: 0;
        }
        .pw-toggle:hover { color: var(--text); }

        /* Password strength */
        .pw-strength {
            margin-top: 8px;
            display: none;
        }

        .pw-strength.visible { display: block; }

        .strength-bar {
            height: 3px;
            border-radius: 2px;
            background: var(--border);
            overflow: hidden;
            margin-bottom: 5px;
        }

        .strength-fill {
            height: 100%;
            border-radius: 2px;
            transition: width 0.3s, background 0.3s;
            width: 0%;
        }

        .strength-label {
            font-size: 11px;
            color: var(--muted2);
        }

        /* Match indicator */
        .match-hint {
            margin-top: 6px;
            font-size: 12px;
            display: none;
            align-items: center;
            gap: 5px;
        }

        .match-hint.visible { display: flex; }
        .match-hint.ok   { color: var(--success); }
        .match-hint.fail { color: #fca5a5; }

        /* ── Row layout for password fields ── */
        .field-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin-bottom: 1.1rem;
        }

        @media (max-width: 480px) {
            .field-row { grid-template-columns: 1fr; }
        }

        .field-row .field { margin-bottom: 0; }

        /* ── Terms note ── */
        .terms-note {
            font-size: 12px;
            color: var(--muted);
            margin-bottom: 1.25rem;
            line-height: 1.5;
        }

        .terms-note a { color: var(--muted2); text-decoration: underline; }

        /* ── Submit ── */
        .btn-submit {
            width: 100%;
            padding: 0.85rem;
            background: var(--accent);
            border: none;
            border-radius: 10px;
            color: #fff;
            font-family: 'Syne', sans-serif;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s, box-shadow 0.2s;
            position: relative;
            overflow: hidden;
            letter-spacing: 0.2px;
        }

        .btn-submit::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.08) 0%, transparent 60%);
            pointer-events: none;
        }

        .btn-submit:hover {
            background: #ff6635;
            box-shadow: 0 5px 24px rgba(255,77,28,0.35);
        }

        .btn-submit:active { transform: scale(0.985); }

        .btn-submit.loading { pointer-events: none; }

        .spinner {
            display: none;
            width: 16px; height: 16px;
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

        /* ── Divider + footer ── */
        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 1.5rem 0 0;
        }

        .divider span {
            font-size: 12px;
            color: var(--muted);
            white-space: nowrap;
        }

        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .card-footer {
            margin-top: 1.25rem;
            text-align: center;
            font-size: 14px;
            color: var(--muted2);
        }

        .card-footer a {
            color: var(--accent2);
            text-decoration: none;
            font-weight: 500;
        }

        .card-footer a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="orb orb-1"></div>
<div class="orb orb-2"></div>

<!-- ── Nav ── -->
<nav>
    <a href="index.php" class="nav-logo">
        <div class="nav-logo-icon">⚡</div>
        <span class="nav-logo-name">SwiftDrop</span>
    </a>
    <a href="login.php" class="nav-signin">Already have an account? <span>Sign in →</span></a>
</nav>

<!-- ── Page ── -->
<div class="page">

    <!-- Left panel -->
    <div class="side-panel">
        <h2>Join the<br>next <span>drop.</span></h2>

        <div class="perk-list">
            <div class="perk">
                <div class="perk-icon" style="background:rgba(255,77,28,0.12);">⚡</div>
                <div class="perk-text">
                    <h4>Instant Access to Flash Sales</h4>
                    <p>Get in the queue the moment a drop goes live — no waiting lists.</p>
                </div>
            </div>
            <div class="perk">
                <div class="perk-icon" style="background:rgba(34,197,94,0.1);">🔒</div>
                <div class="perk-text">
                    <h4>Zero Overselling</h4>
                    <p>Atomic transactions mean if you got it, it's yours — guaranteed.</p>
                </div>
            </div>
            <div class="perk">
                <div class="perk-icon" style="background:rgba(59,130,246,0.1);">📡</div>
                <div class="perk-text">
                    <h4>Real-Time Stock Tracking</h4>
                    <p>Watch inventory drop in real time. Know when to move.</p>
                </div>
            </div>
            <div class="perk">
                <div class="perk-icon" style="background:rgba(168,85,247,0.1);">📦</div>
                <div class="perk-text">
                    <h4>Full Order History</h4>
                    <p>Every purchase tracked and accessible in your account.</p>
                </div>
            </div>
        </div>

        <div class="side-footer">
            <div class="live-dot"></div>
            Platform live — flash sales running now
        </div>
    </div>

    <!-- Card -->
    <div class="card">
        <div class="card-header">
            <h2>Create your account</h2>
            <p>Free to join. Takes under a minute.</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-error" role="alert">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="regForm" novalidate>

            <!-- Name -->
            <div class="field">
                <label for="name">Display Name</label>
                <div class="input-wrap">
                    <svg class="field-icon" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                    </svg>
                    <input type="text" id="name" name="name"
                        placeholder="Your name"
                        value="<?= htmlspecialchars($old['name']) ?>"
                        autocomplete="name" required>
                </div>
            </div>

            <!-- Email -->
            <div class="field">
                <label for="email">Email Address</label>
                <div class="input-wrap">
                    <svg class="field-icon" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 7l10 7 10-7"/>
                    </svg>
                    <input type="email" id="email" name="email"
                        placeholder="you@example.com"
                        value="<?= htmlspecialchars($old['email']) ?>"
                        autocomplete="email" required>
                </div>
            </div>

            <!-- Password row -->
            <div class="field-row">
                <div class="field">
                    <label for="password">Password</label>
                    <div class="input-wrap">
                        <svg class="field-icon" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                        <input type="password" id="password" name="password"
                            placeholder="Min. 8 chars"
                            autocomplete="new-password" required>
                        <button type="button" class="pw-toggle" id="pwToggle1" aria-label="Show password">
                            <svg id="eye1" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                    <!-- Strength meter -->
                    <div class="pw-strength" id="strengthWrap">
                        <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                        <div class="strength-label" id="strengthLabel"></div>
                    </div>
                </div>

                <div class="field">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="input-wrap">
                        <svg class="field-icon" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M9 12l2 2 4-4"/><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                        <input type="password" id="confirm_password" name="confirm_password"
                            placeholder="Repeat password"
                            autocomplete="new-password" required>
                        <button type="button" class="pw-toggle" id="pwToggle2" aria-label="Show confirm password">
                            <svg id="eye2" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                    <div class="match-hint" id="matchHint">
                        <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" id="matchIcon">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        <span id="matchText">Passwords match</span>
                    </div>
                </div>
            </div>

            <p class="terms-note">
                By creating an account you agree to our
                <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>.
            </p>

            <button type="submit" class="btn-submit" id="submitBtn">
                <span class="spinner" aria-hidden="true"></span>
                <span class="btn-text">Create Account →</span>
            </button>

        </form>

        <div class="divider"><span>have an account?</span></div>

        <div class="card-footer">
            <a href="login.php">Sign in instead</a>
        </div>
    </div>

</div>

<script>
    // ── Password visibility toggles ──
    const eyeOpenPath  = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
    const eyeClosePath = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>';

    function makeToggle(btnId, inputId, eyeId) {
        const btn   = document.getElementById(btnId);
        const input = document.getElementById(inputId);
        const eye   = document.getElementById(eyeId);
        btn.addEventListener('click', () => {
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            eye.innerHTML = show ? eyeClosePath : eyeOpenPath;
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        });
    }

    makeToggle('pwToggle1', 'password', 'eye1');
    makeToggle('pwToggle2', 'confirm_password', 'eye2');

    // ── Password strength ──
    const pwInput      = document.getElementById('password');
    const strengthWrap = document.getElementById('strengthWrap');
    const strengthFill = document.getElementById('strengthFill');
    const strengthLabel= document.getElementById('strengthLabel');

    const levels = [
        { label: 'Too short',  color: '#ef4444', width: '15%' },
        { label: 'Weak',       color: '#ef4444', width: '30%' },
        { label: 'Fair',       color: '#f59e0b', width: '55%' },
        { label: 'Good',       color: '#84cc16', width: '75%' },
        { label: 'Strong',     color: '#22c55e', width: '100%'},
    ];

    function scorePassword(pw) {
        if (pw.length < 8)  return 0;
        let score = 1;
        if (pw.length >= 12)              score++;
        if (/[A-Z]/.test(pw))            score++;
        if (/[0-9]/.test(pw))            score++;
        if (/[^A-Za-z0-9]/.test(pw))     score++;
        return Math.min(score, 4);
    }

    pwInput.addEventListener('input', () => {
        const val = pwInput.value;
        if (!val) { strengthWrap.classList.remove('visible'); return; }
        strengthWrap.classList.add('visible');
        const s = scorePassword(val);
        const lvl = levels[s];
        strengthFill.style.width      = lvl.width;
        strengthFill.style.background = lvl.color;
        strengthLabel.textContent     = lvl.label;
        strengthLabel.style.color     = lvl.color;
        checkMatch();
    });

    // ── Password match ──
    const confirmInput = document.getElementById('confirm_password');
    const matchHint    = document.getElementById('matchHint');
    const matchIcon    = document.getElementById('matchIcon');
    const matchText    = document.getElementById('matchText');

    const checkMark = '<polyline points="20 6 9 17 4 12"/>';
    const crossMark = '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>';

    function checkMatch() {
        const pw  = pwInput.value;
        const cpw = confirmInput.value;
        if (!cpw) { matchHint.classList.remove('visible'); return; }

        matchHint.classList.add('visible');
        const ok = pw === cpw;
        matchHint.classList.toggle('ok',   ok);
        matchHint.classList.toggle('fail', !ok);
        matchIcon.innerHTML  = ok ? checkMark : crossMark;
        matchText.textContent = ok ? 'Passwords match' : 'Passwords do not match';
    }

    confirmInput.addEventListener('input', checkMatch);

    // ── Loading state on submit ──
    const form      = document.getElementById('regForm');
    const submitBtn = document.getElementById('submitBtn');

    form.addEventListener('submit', () => {
        submitBtn.classList.add('loading');
        submitBtn.querySelector('.btn-text').textContent = 'Creating account…';
    });
</script>
</body>
</html>