<?php
session_start();
require_once "../config/database.php";

// Redirect authenticated users straight to the marketplace
if (isset($_SESSION['user'])) {
    header("Location: marketplace.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SwiftDrop — Flash Sale Platform</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:       #07070d;
            --surface:  #0f0f18;
            --border:   rgba(255,255,255,0.07);
            --accent:   #ff4d1c;
            --accent2:  #ff8c42;
            --text:     #f0ede8;
            --muted:    #5a5768;
            --muted2:   #888098;
        }

        html { scroll-behavior: smooth; }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-weight: 400;
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* ─── Background grid ─── */
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

        /* ─── Glow orbs ─── */
        .orb {
            position: fixed;
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
            filter: blur(80px);
        }

        .orb-1 {
            width: 700px; height: 700px;
            top: -200px; right: -150px;
            background: radial-gradient(circle, rgba(255,77,28,0.1) 0%, transparent 70%);
        }

        .orb-2 {
            width: 500px; height: 500px;
            bottom: -100px; left: -100px;
            background: radial-gradient(circle, rgba(255,140,66,0.07) 0%, transparent 70%);
        }

        /* ─── Nav ─── */
        nav {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.25rem 2.5rem;
            border-bottom: 1px solid transparent;
            transition: border-color 0.3s, background 0.3s, backdrop-filter 0.3s;
        }

        nav.scrolled {
            border-color: var(--border);
            background: rgba(7,7,13,0.85);
            backdrop-filter: blur(16px);
        }

        .nav-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: var(--text);
        }

        .nav-logo-icon {
            width: 34px; height: 34px;
            background: var(--accent);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .nav-logo-name {
            font-family: 'Syne', sans-serif;
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-links a {
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            padding: 0.5rem 1.1rem;
            border-radius: 8px;
            transition: background 0.2s, color 0.2s;
        }

        .nav-links a.ghost {
            color: var(--muted2);
            border: 1px solid var(--border);
        }

        .nav-links a.ghost:hover {
            color: var(--text);
            background: rgba(255,255,255,0.05);
            border-color: rgba(255,255,255,0.12);
        }

        .nav-links a.solid {
            background: var(--accent);
            color: #fff;
        }

        .nav-links a.solid:hover {
            background: #ff6635;
            box-shadow: 0 4px 20px rgba(255,77,28,0.35);
        }

        /* ─── Hero ─── */
        .hero {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 8rem 2rem 4rem;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border: 1px solid rgba(255,77,28,0.35);
            border-radius: 100px;
            font-size: 12px;
            font-weight: 500;
            color: var(--accent2);
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 2rem;
            animation: fade-up 0.6s ease both;
        }

        .live-dot {
            width: 7px; height: 7px;
            border-radius: 50%;
            background: #22c55e;
            animation: pulse-dot 2s infinite;
        }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50%       { opacity: 0.5; transform: scale(1.4); }
        }

        .hero h1 {
            font-family: 'Syne', sans-serif;
            font-size: clamp(3rem, 8vw, 6.5rem);
            font-weight: 800;
            line-height: 1.0;
            letter-spacing: -3px;
            max-width: 900px;
            animation: fade-up 0.6s 0.1s ease both;
        }

        .hero h1 .accent { color: var(--accent); }

        .hero h1 .outline {
            -webkit-text-stroke: 1px rgba(255,255,255,0.3);
            color: transparent;
        }

        .hero-sub {
            margin-top: 1.75rem;
            font-size: clamp(15px, 2vw, 18px);
            font-weight: 300;
            color: var(--muted2);
            max-width: 540px;
            line-height: 1.7;
            animation: fade-up 0.6s 0.2s ease both;
        }

        .hero-cta {
            margin-top: 2.5rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
            animation: fade-up 0.6s 0.3s ease both;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 0.85rem 1.75rem;
            border-radius: 10px;
            font-family: 'Syne', sans-serif;
            font-size: 15px;
            font-weight: 700;
            text-decoration: none;
            letter-spacing: 0.2px;
            transition: all 0.2s;
        }

        .btn-primary {
            background: var(--accent);
            color: #fff;
        }

        .btn-primary:hover {
            background: #ff6635;
            box-shadow: 0 6px 28px rgba(255,77,28,0.4);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: rgba(255,255,255,0.05);
            color: var(--text);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            background: rgba(255,255,255,0.09);
            border-color: rgba(255,255,255,0.15);
            transform: translateY(-1px);
        }

        /* Arrow icon */
        .arrow { font-style: normal; }

        /* ─── Ticker ─── */
        .ticker-wrap {
            position: relative;
            width: 100%;
            overflow: hidden;
            margin-top: 4rem;
            padding: 1rem 0;
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            animation: fade-up 0.6s 0.4s ease both;
        }

        .ticker-wrap::before,
        .ticker-wrap::after {
            content: '';
            position: absolute;
            top: 0; bottom: 0;
            width: 120px;
            z-index: 2;
            pointer-events: none;
        }

        .ticker-wrap::before { left: 0;  background: linear-gradient(90deg, var(--bg), transparent); }
        .ticker-wrap::after  { right: 0; background: linear-gradient(-90deg, var(--bg), transparent); }

        .ticker {
            display: flex;
            gap: 0;
            animation: ticker 28s linear infinite;
            width: max-content;
        }

        .ticker-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 2.5rem;
            font-size: 13px;
            font-weight: 500;
            color: var(--muted2);
            white-space: nowrap;
        }

        .ticker-item span.tag {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 100px;
            font-size: 11px;
            font-weight: 500;
        }

        .tag-sold { background: rgba(239,68,68,0.15); color: #fca5a5; }
        .tag-live { background: rgba(34,197,94,0.15); color: #86efac; }
        .tag-soon { background: rgba(255,140,66,0.15); color: var(--accent2); }

        .ticker-sep { color: rgba(255,255,255,0.1); }

        @keyframes ticker {
            from { transform: translateX(0); }
            to   { transform: translateX(-50%); }
        }

        /* ─── Stats section ─── */
        .stats {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: center;
            gap: 0;
            padding: 4rem 2rem;
            max-width: 900px;
            margin: 0 auto;
        }

        .stat {
            flex: 1;
            text-align: center;
            padding: 2rem;
            border-right: 1px solid var(--border);
            animation: fade-up 0.5s ease both;
        }

        .stat:last-child { border-right: none; }

        .stat-num {
            font-family: 'Syne', sans-serif;
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 800;
            color: var(--text);
            letter-spacing: -1px;
        }

        .stat-num span { color: var(--accent); }

        .stat-label {
            margin-top: 4px;
            font-size: 13px;
            color: var(--muted);
            font-weight: 300;
        }

        /* ─── Features ─── */
        .features {
            position: relative;
            z-index: 1;
            padding: 5rem 2rem;
            max-width: 1100px;
            margin: 0 auto;
        }

        .section-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--accent);
            margin-bottom: 1rem;
        }

        .section-label::before {
            content: '';
            display: block;
            width: 20px; height: 1px;
            background: var(--accent);
        }

        .features h2 {
            font-family: 'Syne', sans-serif;
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 800;
            letter-spacing: -1px;
            line-height: 1.1;
            max-width: 500px;
            margin-bottom: 3rem;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1px;
            background: var(--border);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
        }

        .feature-card {
            background: var(--surface);
            padding: 2rem;
            transition: background 0.2s;
        }

        .feature-card:hover { background: #141420; }

        .feature-icon {
            width: 44px; height: 44px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px;
            margin-bottom: 1.25rem;
        }

        .feature-card h3 {
            font-family: 'Syne', sans-serif;
            font-size: 17px;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .feature-card p {
            font-size: 14px;
            color: var(--muted2);
            line-height: 1.65;
            font-weight: 300;
        }

        /* ─── How it works ─── */
        .how {
            position: relative;
            z-index: 1;
            padding: 5rem 2rem;
            max-width: 900px;
            margin: 0 auto;
            text-align: center;
        }

        .how h2 {
            font-family: 'Syne', sans-serif;
            font-size: clamp(2rem, 4vw, 2.8rem);
            font-weight: 800;
            letter-spacing: -1px;
            margin-bottom: 3.5rem;
        }

        .steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            position: relative;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }

        .step-num {
            width: 52px; height: 52px;
            border-radius: 50%;
            border: 1px solid var(--border);
            background: var(--surface);
            display: flex; align-items: center; justify-content: center;
            font-family: 'Syne', sans-serif;
            font-size: 18px;
            font-weight: 800;
            color: var(--accent);
            flex-shrink: 0;
        }

        .step h4 {
            font-family: 'Syne', sans-serif;
            font-size: 15px;
            font-weight: 700;
        }

        .step p {
            font-size: 13px;
            color: var(--muted2);
            font-weight: 300;
            line-height: 1.6;
        }

        /* ─── CTA Banner ─── */
        .cta-banner {
            position: relative;
            z-index: 1;
            margin: 2rem auto 6rem;
            max-width: 800px;
            padding: 0 2rem;
        }

        .cta-inner {
            background: var(--surface);
            border: 1px solid rgba(255,77,28,0.2);
            border-radius: 20px;
            padding: 3.5rem 2.5rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .cta-inner::before {
            content: '';
            position: absolute;
            top: 0; left: 50%;
            transform: translateX(-50%);
            width: 300px; height: 1px;
            background: linear-gradient(90deg, transparent, var(--accent), transparent);
        }

        .cta-inner h2 {
            font-family: 'Syne', sans-serif;
            font-size: clamp(1.75rem, 4vw, 2.5rem);
            font-weight: 800;
            letter-spacing: -1px;
            margin-bottom: 1rem;
        }

        .cta-inner p {
            color: var(--muted2);
            font-size: 15px;
            font-weight: 300;
            margin-bottom: 2rem;
        }

        /* ─── Footer ─── */
        footer {
            position: relative;
            z-index: 1;
            border-top: 1px solid var(--border);
            padding: 2rem 2.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        footer .foot-logo {
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: 'Syne', sans-serif;
            font-size: 16px;
            font-weight: 800;
        }

        footer p {
            font-size: 13px;
            color: var(--muted);
        }

        /* ─── Animations ─── */
        @keyframes fade-up {
            from { opacity: 0; transform: translateY(18px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .stat:nth-child(1) { animation-delay: 0.05s; }
        .stat:nth-child(2) { animation-delay: 0.15s; }
        .stat:nth-child(3) { animation-delay: 0.25s; }
        .stat:nth-child(4) { animation-delay: 0.35s; }

        /* ─── Responsive ─── */
        @media (max-width: 600px) {
            nav { padding: 1rem 1.25rem; }
            .stats { flex-direction: column; gap: 0; }
            .stat { border-right: none; border-bottom: 1px solid var(--border); }
            .stat:last-child { border-bottom: none; }
        }
    </style>
</head>
<body>

<div class="orb orb-1"></div>
<div class="orb orb-2"></div>

<!-- ─── NAV ─── -->
<nav id="mainNav">
    <a href="index.php" class="nav-logo">
        <div class="nav-logo-icon">⚡</div>
        <span class="nav-logo-name">SwiftDrop</span>
    </a>
    <div class="nav-links">
        <a href="login.php" class="ghost">Sign In</a>
        <a href="register.php" class="solid">Get Started</a>
    </div>
</nav>

<!-- ─── HERO ─── -->
<section class="hero">
    <div class="hero-badge">
        <div class="live-dot"></div>
        Flash sales live now
    </div>

    <h1>
        Drop in.<br>
        <span class="accent">Grab fast.</span><br>
        <span class="outline">Win big.</span>
    </h1>

    <p class="hero-sub">
        SwiftDrop is a high-concurrency flash sale platform built to handle hundreds of buyers competing for limited drops — fairly, securely, and instantly.
    </p>

    <div class="hero-cta">
        <a href="register.php" class="btn btn-primary">
            Join the Drop <span class="arrow">→</span>
        </a>
        <a href="login.php" class="btn btn-secondary">
            Sign In
        </a>
    </div>

    <!-- Live ticker -->
    <div class="ticker-wrap">
        <div class="ticker" id="ticker">
            <!-- Items duplicated in JS for seamless loop -->
            <div class="ticker-item"><span class="tag tag-sold">Sold Out</span> Nike Air Max 97 — 120 units in 4.2s <span class="ticker-sep">·</span></div>
            <div class="ticker-item"><span class="tag tag-live">Live Now</span> Sony WH-1000XM6 — 43 remaining <span class="ticker-sep">·</span></div>
            <div class="ticker-item"><span class="tag tag-soon">Upcoming</span> MacBook Air M4 Drop — 11:00 PM <span class="ticker-sep">·</span></div>
            <div class="ticker-item"><span class="tag tag-sold">Sold Out</span> PS5 Bundle — 60 units in 8.7s <span class="ticker-sep">·</span></div>
            <div class="ticker-item"><span class="tag tag-live">Live Now</span> DJI Mini 4 Pro — 12 remaining <span class="ticker-sep">·</span></div>
            <div class="ticker-item"><span class="tag tag-soon">Upcoming</span> iPad Pro M4 — Tomorrow 9:00 AM <span class="ticker-sep">·</span></div>
            <div class="ticker-item"><span class="tag tag-sold">Sold Out</span> RTX 5080 — 30 units in 1.9s <span class="ticker-sep">·</span></div>
            <div class="ticker-item"><span class="tag tag-live">Live Now</span> AirPods Pro 3 — 7 remaining <span class="ticker-sep">·</span></div>
        </div>
    </div>
</section>

<!-- ─── STATS ─── -->
<div class="stats">
    <div class="stat">
        <div class="stat-num">12<span>K+</span></div>
        <div class="stat-label">Registered Users</div>
    </div>
    <div class="stat">
        <div class="stat-num">340<span>+</span></div>
        <div class="stat-label">Flash Events Run</div>
    </div>
    <div class="stat">
        <div class="stat-num">0<span>%</span></div>
        <div class="stat-label">Oversell Rate</div>
    </div>
    <div class="stat">
        <div class="stat-num">&lt;2<span>s</span></div>
        <div class="stat-label">Avg. Checkout Time</div>
    </div>
</div>

<!-- ─── FEATURES ─── -->
<section class="features">
    <div class="section-label">Platform Features</div>
    <h2>Everything a drop needs</h2>

    <div class="feature-grid">
        <div class="feature-card">
            <div class="feature-icon" style="background:rgba(255,77,28,0.12);">⚡</div>
            <h3>Flash Sale Events</h3>
            <p>Create timed drops with countdown timers. Items release at the exact second — locked, live, or sold out.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon" style="background:rgba(34,197,94,0.1);">🔒</div>
            <h3>Atomic Transactions</h3>
            <p>Every purchase uses MySQL row-level locking and atomic stock deduction. Zero overselling, guaranteed.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon" style="background:rgba(59,130,246,0.1);">📡</div>
            <h3>Real-Time Stock</h3>
            <p>Live stock counters update via AJAX polling — buyers see remaining inventory without a page reload.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon" style="background:rgba(168,85,247,0.1);">🛡️</div>
            <h3>Concurrency Safe</h3>
            <p>Handles hundreds of simultaneous purchase requests without race conditions or duplicate orders.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon" style="background:rgba(234,179,8,0.1);">📦</div>
            <h3>Order Management</h3>
            <p>Full purchase history and order records. Every transaction is tracked, timestamped, and linked to users.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon" style="background:rgba(239,68,68,0.1);">👤</div>
            <h3>Secure Auth</h3>
            <p>Password hashing, session regeneration on login, role-based access, and deactivation controls built in.</p>
        </div>
    </div>
</section>

<!-- ─── HOW IT WORKS ─── -->
<section class="how">
    <h2>Three steps to the drop</h2>
    <div class="steps">
        <div class="step">
            <div class="step-num">01</div>
            <h4>Create an Account</h4>
            <p>Sign up in seconds. No credit card needed to browse upcoming drops.</p>
        </div>
        <div class="step">
            <div class="step-num">02</div>
            <h4>Watch the Countdown</h4>
            <p>Browse the marketplace. Track live events and set reminders for upcoming drops.</p>
        </div>
        <div class="step">
            <div class="step-num">03</div>
            <h4>Grab Your Item</h4>
            <p>When the clock hits zero — move fast. The platform handles the rest, safely.</p>
        </div>
    </div>
</section>

<!-- ─── CTA BANNER ─── -->
<div class="cta-banner">
    <div class="cta-inner">
        <h2>Ready for the next drop?</h2>
        <p>Join thousands of buyers competing in real-time flash sales. Free to join.</p>
        <a href="register.php" class="btn btn-primary" style="font-size:16px; padding:1rem 2.25rem;">
            Create Free Account <span class="arrow">→</span>
        </a>
    </div>
</div>

<!-- ─── FOOTER ─── -->
<footer>
    <div class="foot-logo">
        <span>⚡</span> SwiftDrop
    </div>
    <p>Flash sale platform · Built with PHP 8 + MySQL</p>
    <p style="color:var(--muted);">© <?= date('Y') ?> SwiftDrop</p>
</footer>

<script>
    // Nav scroll effect
    const nav = document.getElementById('mainNav');
    window.addEventListener('scroll', () => {
        nav.classList.toggle('scrolled', window.scrollY > 40);
    });

    // Ticker: duplicate items for seamless loop
    const ticker = document.getElementById('ticker');
    ticker.innerHTML += ticker.innerHTML;
</script>
</body>
</html>