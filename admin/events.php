<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$stmt   = $conn->query("SELECT * FROM events ORDER BY id DESC");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

$user     = $_SESSION['user'];
$initials = strtoupper(substr($user['name'] ?? $user['username'], 0, 1));
if (!empty($user['name']) && strpos($user['name'], ' ') !== false) {
    $parts    = explode(' ', $user['name']);
    $initials = strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));
}

date_default_timezone_set('Asia/Colombo');
$now = new DateTime('now', new DateTimeZone('Asia/Colombo'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events — SwiftDrop Admin</title>
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
            position: fixed; inset: 0;
            background-image:
                linear-gradient(rgba(255,77,28,0.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,77,28,0.025) 1px, transparent 1px);
            background-size: 56px 56px;
            pointer-events: none; z-index: 0;
        }

        /* ─── Nav ─── */
        nav {
            position: sticky; top: 0; z-index: 100;
            background: rgba(7,7,13,0.9);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center;
            justify-content: space-between;
            padding: 1rem 2.5rem;
        }

        .nav-left { display: flex; align-items: center; gap: 10px; }

        .nav-icon {
            width: 32px; height: 32px;
            background: var(--accent); border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px; flex-shrink: 0;
        }

        .nav-logo {
            font-family: 'Syne', sans-serif;
            font-size: 18px; font-weight: 800; letter-spacing: -0.5px;
        }

        .admin-chip {
            padding: 3px 10px;
            background: rgba(255,77,28,0.12);
            border: 1px solid rgba(255,77,28,0.25);
            border-radius: 100px;
            font-size: 11px; font-weight: 500;
            color: var(--accent2);
            text-transform: uppercase; letter-spacing: 0.5px;
        }

        .nav-right { display: flex; align-items: center; gap: 10px; }

        .nav-user {
            display: flex; align-items: center; gap: 8px;
            padding: 5px 13px 5px 7px;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border); border-radius: 9px;
        }

        .nav-avatar {
            width: 26px; height: 26px; border-radius: 50%;
            background: var(--accent);
            display: flex; align-items: center; justify-content: center;
            font-size: 11px; font-weight: 700; color: #fff; flex-shrink: 0;
        }

        .nav-username { font-size: 13px; font-weight: 500; }

        .nav-logout {
            padding: 6px 15px;
            background: rgba(255,77,28,0.1);
            border: 1px solid rgba(255,77,28,0.22);
            border-radius: 8px;
            color: #ff7a55; font-size: 13px; font-weight: 500;
            text-decoration: none; transition: all 0.2s;
        }

        .nav-logout:hover {
            background: rgba(255,77,28,0.2);
            border-color: rgba(255,77,28,0.45); color: #ff6035;
        }

        /* ─── Main ─── */
        main {
            position: relative; z-index: 1;
            max-width: 960px; margin: 0 auto;
            padding: 2.5rem 2rem 5rem;
        }

        /* ─── Breadcrumb ─── */
        .breadcrumb {
            display: flex; align-items: center; gap: 6px;
            font-size: 12px; color: var(--muted2); margin-bottom: 1.5rem;
        }

        .breadcrumb a { color: var(--muted2); text-decoration: none; transition: color 0.15s; }
        .breadcrumb a:hover { color: var(--text); }
        .breadcrumb-sep { color: var(--muted); }

        /* ─── Page header ─── */
        .page-eyebrow {
            font-size: 11px; font-weight: 500;
            letter-spacing: 2px; text-transform: uppercase;
            color: var(--accent); margin-bottom: 0.5rem;
            display: flex; align-items: center; gap: 8px;
        }

        .page-eyebrow::before {
            content: ''; display: block;
            width: 18px; height: 1px; background: var(--accent);
        }

        .page-header {
            display: flex; align-items: flex-end;
            justify-content: space-between;
            flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem;
        }

        .page-title {
            font-family: 'Syne', sans-serif;
            font-size: clamp(1.75rem, 4vw, 2.25rem);
            font-weight: 800; letter-spacing: -1px;
        }

        .page-sub { color: var(--muted2); font-size: 13px; font-weight: 300; margin-top: 5px; }

        /* ─── Create button ─── */
        .btn-create {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 0.7rem 1.4rem;
            background: var(--accent); border: none; border-radius: 10px;
            color: #fff; font-family: 'Syne', sans-serif;
            font-size: 14px; font-weight: 700;
            text-decoration: none; cursor: pointer; transition: all 0.2s;
            white-space: nowrap;
        }

        .btn-create:hover {
            background: #ff6635;
            box-shadow: 0 4px 20px rgba(255,77,28,0.3);
            transform: translateY(-1px);
        }

        /* ─── Table card ─── */
        .table-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px; overflow: hidden;
        }

        .table-head {
            display: grid;
            grid-template-columns: 60px 1fr 190px 120px 130px;
            padding: 0.75rem 1.5rem;
            border-bottom: 1px solid var(--border);
            background: rgba(255,255,255,0.02);
        }

        .th {
            font-size: 11px; font-weight: 500;
            letter-spacing: 1px; text-transform: uppercase;
            color: var(--muted2);
        }

        .table-row {
            display: grid;
            grid-template-columns: 60px 1fr 190px 120px 130px;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border);
            align-items: center; gap: 0.5rem;
            transition: background 0.2s;
        }

        .table-row:last-child { border-bottom: none; }
        .table-row:hover { background: var(--surface2); }

        .row-id {
            font-family: 'Syne', sans-serif;
            font-size: 13px; font-weight: 800; color: var(--muted2);
        }

        .row-name {
            font-family: 'Syne', sans-serif;
            font-size: 15px; font-weight: 800;
        }

        .row-date { font-size: 13px; color: var(--muted2); font-weight: 300; }

        /* Status tags */
        .status-tag {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 4px 10px; border-radius: 100px;
            font-size: 11px; font-weight: 500;
        }

        .tag-active   { background: rgba(34,197,94,0.1);  color: #86efac;       border: 1px solid rgba(34,197,94,0.2); }
        .tag-upcoming { background: rgba(255,140,66,0.1); color: var(--accent2); border: 1px solid rgba(255,140,66,0.2); }
        .tag-ended    { background: rgba(255,255,255,0.04); color: var(--muted2); border: 1px solid var(--border); }

        .live-dot {
            width: 5px; height: 5px; border-radius: 50%;
            background: #22c55e; animation: pulse-dot 2s infinite;
        }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50%       { opacity: .5; transform: scale(1.4); }
        }

        /* Action button */
        .btn-items {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 5px 14px;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text); font-size: 12px; font-weight: 500;
            text-decoration: none; transition: all 0.2s; white-space: nowrap;
        }

        .btn-items:hover {
            background: rgba(255,77,28,0.1);
            border-color: rgba(255,77,28,0.3);
            color: var(--accent2);
        }

        /* Empty state */
        .empty-state {
            padding: 4rem 1.5rem; text-align: center; color: var(--muted2);
        }

        .empty-icon { font-size: 32px; margin-bottom: 0.75rem; }
        .empty-state p { font-size: 13px; font-weight: 300; }

        @media (max-width: 700px) {
            nav { padding: 1rem 1.25rem; }
            main { padding: 1.5rem 1rem 4rem; }
            .nav-logo { display: none; }
            .table-head,
            .table-row { grid-template-columns: 48px 1fr 100px 90px; }
            .col-date { display: none; }
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
    <div class="breadcrumb">
        <a href="index.php">Dashboard</a>
        <span class="breadcrumb-sep">›</span>
        <span>Events</span>
    </div>

    <?php
    $activeCount = 0;
    foreach ($events as $e) {
        $goLive = new DateTime($e['go_live_at']);
        if ($goLive <= $now && $e['status'] !== 'ended') $activeCount++;
    }
    ?>

    <div class="page-header">
        <div>
            <div class="page-eyebrow">Flash Sales</div>
            <h1 class="page-title">Events</h1>
            <p class="page-sub">
                <?= count($events) ?> event<?= count($events) !== 1 ? 's' : '' ?> total
                <?= $activeCount ? "· {$activeCount} currently live" : '' ?>
            </p>
        </div>
        <a href="create_event.php" class="btn-create">+ Create Event</a>
    </div>

    <div class="table-card">

        <?php if (empty($events)): ?>
            <div class="empty-state">
                <div class="empty-icon">🗓</div>
                <p>No events yet. Create your first flash sale.</p>
            </div>
        <?php else: ?>

            <div class="table-head">
                <div class="th">ID</div>
                <div class="th">Name</div>
                <div class="th col-date">Go Live</div>
                <div class="th">Status</div>
                <div class="th">Actions</div>
            </div>

            <?php foreach ($events as $event):
                $goLive = new DateTime($event['go_live_at']);
                $isLive = $goLive <= $now;

                if ($event['status'] === 'ended') {
                    $tagClass = 'tag-ended';
                    $tagLabel = 'Ended';
                    $dot      = false;
                } elseif ($isLive) {
                    $tagClass = 'tag-active';
                    $tagLabel = 'Active';
                    $dot      = true;
                } else {
                    $tagClass = 'tag-upcoming';
                    $tagLabel = 'Upcoming';
                    $dot      = false;
                }
            ?>
            <div class="table-row">
                <div class="row-id">#<?= $event['id'] ?></div>
                <div class="row-name"><?= htmlspecialchars($event['name']) ?></div>
                <div class="row-date col-date">
                    <?= date('M j, Y · g:i A', strtotime($event['go_live_at'])) ?>
                </div>
                <div>
                    <span class="status-tag <?= $tagClass ?>">
                        <?php if ($dot): ?><div class="live-dot"></div><?php endif; ?>
                        <?= $tagLabel ?>
                    </span>
                </div>
                <div>
                    <a href="items.php?event_id=<?= $event['id'] ?>" class="btn-items">
                        Add Items →
                    </a>
                </div>
            </div>
            <?php endforeach; ?>

        <?php endif; ?>
    </div>
</main>

</body>
</html>