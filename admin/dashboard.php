<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

require_once "../config/database.php";

$user     = $_SESSION['user'];
$initials = strtoupper(substr($user['name'] ?? $user['username'], 0, 1));
if (!empty($user['name']) && strpos($user['name'], ' ') !== false) {
    $parts    = explode(' ', $user['name']);
    $initials = strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — SwiftDrop</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:      #07070d;
            --surface: #0f0f18;
            --surface2:#141420;
            --border:  rgba(255,255,255,0.07);
            --accent:  #ff4d1c;
            --accent2: #ff8c42;
            --text:    #f0ede8;
            --muted:   #5a5768;
            --muted2:  #888098;
        }

        html { scroll-behavior: smooth; }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-weight: 400;
            line-height: 1.6;
            min-height: 100vh;
        }

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

        /* ─── Nav ─── */
        nav {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(7,7,13,0.9);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 2.5rem;
        }

        .nav-left {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: var(--text);
        }

        .nav-icon {
            width: 32px; height: 32px;
            background: var(--accent);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }

        .nav-logo {
            font-family: 'Syne', sans-serif;
            font-size: 18px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .admin-chip {
            padding: 3px 10px;
            background: rgba(255,77,28,0.12);
            border: 1px solid rgba(255,77,28,0.25);
            border-radius: 100px;
            font-size: 11px;
            font-weight: 500;
            color: var(--accent2);
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-user {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 5px 13px 5px 7px;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            border-radius: 9px;
        }

        .nav-avatar {
            width: 26px; height: 26px;
            border-radius: 50%;
            background: var(--accent);
            display: flex; align-items: center; justify-content: center;
            font-size: 11px;
            font-weight: 700;
            color: #fff;
            flex-shrink: 0;
        }

        .nav-username {
            font-size: 13px;
            font-weight: 500;
        }

        .nav-logout {
            padding: 6px 15px;
            background: rgba(255,77,28,0.1);
            border: 1px solid rgba(255,77,28,0.22);
            border-radius: 8px;
            color: #ff7a55;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
        }

        .nav-logout:hover {
            background: rgba(255,77,28,0.2);
            border-color: rgba(255,77,28,0.45);
            color: #ff6035;
        }

        /* ─── Main ─── */
        main {
            position: relative;
            z-index: 1;
            max-width: 960px;
            margin: 0 auto;
            padding: 2.5rem 2rem 5rem;
        }

        /* ─── Page header ─── */
        .page-eyebrow {
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--accent);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .page-eyebrow::before {
            content: '';
            display: block;
            width: 18px; height: 1px;
            background: var(--accent);
        }

        .page-title {
            font-family: 'Syne', sans-serif;
            font-size: clamp(1.75rem, 4vw, 2.5rem);
            font-weight: 800;
            letter-spacing: -1px;
        }

        .page-sub {
            margin-top: 6px;
            color: var(--muted2);
            font-size: 13px;
            font-weight: 300;
        }

        /* ─── Section label ─── */
        .section-label {
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--muted2);
            margin: 2rem 0 1rem;
        }

        /* ─── Nav cards ─── */
        .nav-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 1px;
            background: var(--border);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
        }

        .nav-card {
            background: var(--surface);
            padding: 2rem;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            text-decoration: none;
            color: var(--text);
            transition: background 0.2s;
            position: relative;
        }

        .nav-card:hover { background: var(--surface2); }

        .nav-card-icon {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }

        .nav-card h3 {
            font-family: 'Syne', sans-serif;
            font-size: 17px;
            font-weight: 800;
            letter-spacing: -0.3px;
            margin-bottom: 4px;
        }

        .nav-card p {
            font-size: 13px;
            color: var(--muted2);
            font-weight: 300;
            line-height: 1.6;
        }

        .nav-card-arrow {
            position: absolute;
            bottom: 1.75rem;
            right: 1.75rem;
            width: 30px; height: 30px;
            border-radius: 50%;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            display: flex; align-items: center; justify-content: center;
            font-size: 14px;
            color: var(--muted2);
            transition: all 0.2s;
        }

        .nav-card:hover .nav-card-arrow {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
        }

        /* ─── Stats row ─── */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 1px;
            background: var(--border);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }

        .stat {
            background: var(--surface);
            padding: 1.5rem;
            text-align: center;
        }

        .stat-num {
            font-family: 'Syne', sans-serif;
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -1px;
        }

        .stat-num span { color: var(--accent); }

        .stat-label {
            margin-top: 3px;
            font-size: 12px;
            color: var(--muted);
            font-weight: 300;
        }

        @media (max-width: 600px) {
            nav { padding: 1rem 1.25rem; }
            main { padding: 1.5rem 1rem 4rem; }
            .nav-logo { display: none; }
        }
    </style>
</head>
<body>

<!-- ─── NAV ─── -->
<nav>
    <div class="nav-left">
        <div class="nav-icon">⚡</div>
        <span class="nav-logo">SwiftDrop</span>
        <span class="admin-chip">Admin</span>
    </div>
    <div class="nav-right">
        <div class="nav-user">
            <div class="nav-avatar"><?= htmlspecialchars($initials) ?></div>
            <span class="nav-username"><?= htmlspecialchars($user['name'] ?? $user['username']) ?></span>
        </div>
        <a href="/public/logout.php" class="nav-logout">Sign Out</a>
    </div>
</nav>

<!-- ─── MAIN ─── -->
<main>
    <div style="margin-bottom:2.5rem;">
        <div class="page-eyebrow">Control Panel</div>
        <h1 class="page-title">Admin Dashboard</h1>
        <p class="page-sub">Manage your flash sale events, items, and platform settings.</p>
    </div>

    <div class="section-label">Quick Actions</div>
    <div class="nav-cards">
        <a href="events.php" class="nav-card">
            <div class="nav-card-icon" style="background:rgba(255,77,28,0.12);">🗓</div>
            <div>
                <h3>Manage Events</h3>
                <p>Create, schedule, and control flash sale events. Set go-live times and status.</p>
            </div>
            <div class="nav-card-arrow">→</div>
        </a>
        <a href="items.php" class="nav-card">
            <div class="nav-card-icon" style="background:rgba(255,140,66,0.1);">📦</div>
            <div>
                <h3>Manage Items</h3>
                <p>Add products, set prices, and control stock levels for each drop event.</p>
            </div>
            <div class="nav-card-arrow">→</div>
        </a>
    </div>

    <div class="section-label">Platform Overview</div>
    <div class="stats-row">
        <div class="stat">
            <div class="stat-num">12<span>K</span></div>
            <div class="stat-label">Registered Users</div>
        </div>
        <div class="stat">
            <div class="stat-num">340<span>+</span></div>
            <div class="stat-label">Events Run</div>
        </div>
        <div class="stat">
            <div class="stat-num">0<span>%</span></div>
            <div class="stat-label">Oversell Rate</div>
        </div>
        <div class="stat">
            <div class="stat-num">&lt;2<span>s</span></div>
            <div class="stat-label">Avg Checkout</div>
        </div>
    </div>
</main>

</body>
</html>