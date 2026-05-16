<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

/* ─── Handle AJAX actions ─── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    $id = (int)($_POST['event_id'] ?? 0);
    if (!$id) { echo json_encode(['ok'=>false,'msg'=>'Invalid event ID']); exit; }

    switch ($_POST['action']) {

        /* ── Edit all fields (only locked/upcoming events) ── */
       case 'edit':

    $name        = trim($_POST['name'] ?? '');
    $date        = trim($_POST['go_live_date'] ?? '');
    $time        = trim($_POST['go_live_time'] ?? '');

    if (!$name || !$date || !$time) {
        echo json_encode(['ok'=>false,'msg'=>'All required fields must be filled']); exit;
    }

    $go_live_at = $date . ' ' . $time . ':00';

    // check status lock rules
    $chk = $conn->prepare("SELECT status FROM events WHERE id=?");
    $chk->execute([$id]);
    $ev = $chk->fetch(PDO::FETCH_ASSOC);

    if (!$ev) {
        echo json_encode(['ok'=>false,'msg'=>'Event not found']); exit;
    }

    if ($ev['status'] === 'ended') {
        echo json_encode(['ok'=>false,'msg'=>'Ended event cannot be edited']); exit;
    }

    $stmt = $conn->prepare("
        UPDATE events 
        SET name=?, go_live_at=?
        WHERE id=?
    ");

    $stmt->execute([$name, $go_live_at, $id]);

    echo json_encode(['ok'=>true,'msg'=>'Event updated']);
    exit;


        /* ── Force open a locked/upcoming event ── */
        case 'force_open':
            $chk = $conn->prepare("SELECT status FROM events WHERE id=?");
            $chk->execute([$id]);
            $ev = $chk->fetch(PDO::FETCH_ASSOC);
            if (!$ev) { echo json_encode(['ok'=>false,'msg'=>'Event not found']); exit; }
            if ($ev['status'] === 'ended') {
                echo json_encode(['ok'=>false,'msg'=>'Cannot reopen an ended event']); exit;
            }
            if ($ev['status'] === 'live') {
                echo json_encode(['ok'=>false,'msg'=>'Event is already live']); exit;
            }

            $stmt = $conn->prepare("UPDATE events SET status='live' WHERE id=?");
            $stmt->execute([$id]);
            echo json_encode(['ok'=>true,'msg'=>'Event force-opened — now live']);
            exit;

        /* ── Force close a live event ── */
        case 'force_close':
            $stmt = $conn->prepare("UPDATE events SET status='ended' WHERE id=? AND status='live'");
            $stmt->execute([$id]);
            if ($stmt->rowCount() === 0) {
                echo json_encode(['ok'=>false,'msg'=>'Event is not live or already ended']); exit;
            }
            echo json_encode(['ok'=>true,'msg'=>'Event force-closed']);
            exit;

        default:
            echo json_encode(['ok'=>false,'msg'=>'Unknown action']); exit;
    }
}

/* ─── Normal page load ─── */
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
            max-width: 1060px; margin: 0 auto;
            padding: 2.5rem 2rem 5rem;
            margin-top: -711px;
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
            grid-template-columns: 54px 1fr 185px 110px 1fr;
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
            grid-template-columns: 54px 1fr 185px 110px 1fr;
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

        /* ─── Action buttons row ─── */
        .actions-cell {
            display: flex; align-items: center; gap: 6px; flex-wrap: wrap;
        }

        .btn-action {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 11px; font-weight: 500;
            text-decoration: none; cursor: pointer;
            transition: all 0.18s; white-space: nowrap;
            border: 1px solid transparent;
            font-family: 'DM Sans', sans-serif;
        }

        /* Items */
        .btn-items {
            background: rgba(255,255,255,0.05);
            border-color: var(--border);
            color: var(--text);
        }
        .btn-items:hover {
            background: rgba(255,77,28,0.1);
            border-color: rgba(255,77,28,0.3);
            color: var(--accent2);
        }

        /* Edit */
        .btn-edit {
            background: rgba(99,102,241,0.08);
            border-color: rgba(99,102,241,0.2);
            color: #a5b4fc;
        }
        .btn-edit:hover {
            background: rgba(99,102,241,0.18);
            border-color: rgba(99,102,241,0.4);
        }
        .btn-edit[disabled], .btn-edit.disabled {
            opacity: 0.3; cursor: not-allowed; pointer-events: none;
        }

        /* Force open */
        .btn-force-open {
            background: rgba(34,197,94,0.08);
            border-color: rgba(34,197,94,0.2);
            color: #86efac;
        }
        .btn-force-open:hover {
            background: rgba(34,197,94,0.18);
            border-color: rgba(34,197,94,0.4);
        }

        /* Force close */
        .btn-force-close {
            background: rgba(239,68,68,0.08);
            border-color: rgba(239,68,68,0.2);
            color: #fca5a5;
        }
        .btn-force-close:hover {
            background: rgba(239,68,68,0.18);
            border-color: rgba(239,68,68,0.4);
        }

        .btn-action[disabled], .btn-action.disabled {
            opacity: 0.28; cursor: not-allowed; pointer-events: none;
        }

        /* Empty state */
        .empty-state {
            padding: 4rem 1.5rem; text-align: center; color: var(--muted2);
        }

        .empty-icon { font-size: 32px; margin-bottom: 0.75rem; }
        .empty-state p { font-size: 13px; font-weight: 300; }

        /* ══════════════════════════════
           MODAL
        ══════════════════════════════ */
        .modal-overlay {
            position: fixed; inset: 0; z-index: 999;
            background: rgba(0,0,0,0.65);
            backdrop-filter: blur(6px);
            display: flex; align-items: center; justify-content: center;
            padding: 1rem;
            opacity: 0; pointer-events: none;
            transition: opacity 0.22s ease;
        }

        .modal-overlay.open {
            opacity: 1; pointer-events: all;
        }

        .modal {
            background: var(--surface);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 18px;
            width: 100%; max-width: 480px;
            padding: 2rem;
            box-shadow: 0 32px 80px rgba(0,0,0,0.6);
            transform: translateY(14px) scale(0.98);
            transition: transform 0.25s ease;
        }

        .modal-overlay.open .modal {
            transform: translateY(0) scale(1);
        }

        .modal-header {
            display: flex; align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .modal-title {
            font-family: 'Syne', sans-serif;
            font-size: 18px; font-weight: 800;
        }

        .modal-close {
            width: 30px; height: 30px;
            background: rgba(255,255,255,0.06);
            border: 1px solid var(--border);
            border-radius: 8px; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            color: var(--muted2); font-size: 16px;
            transition: all 0.15s;
        }

        .modal-close:hover { background: rgba(255,77,28,0.15); color: var(--text); }

        /* Form fields */
        .field { margin-bottom: 1.25rem; }

        .field label {
            display: block;
            font-size: 11px; font-weight: 500;
            letter-spacing: 1px; text-transform: uppercase;
            color: var(--muted2); margin-bottom: 0.5rem;
        }

        .field input,
        .field textarea {
            width: 100%;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 0.65rem 0.9rem;
            color: var(--text); font-family: 'DM Sans', sans-serif; font-size: 14px;
            outline: none; transition: border-color 0.15s, box-shadow 0.15s;
            resize: vertical;
        }

        .field input:focus,
        .field textarea:focus {
            border-color: rgba(255,77,28,0.5);
            box-shadow: 0 0 0 3px rgba(255,77,28,0.1);
        }

        .field textarea { min-height: 80px; }

        .modal-notice {
            display: flex; gap: 8px; align-items: flex-start;
            background: rgba(255,140,66,0.07);
            border: 1px solid rgba(255,140,66,0.18);
            border-radius: 10px; padding: 0.75rem 1rem;
            font-size: 12px; color: var(--accent2); margin-bottom: 1.25rem;
            line-height: 1.5;
        }

        .modal-notice-icon { flex-shrink: 0; margin-top: 1px; }

        .modal-footer {
            display: flex; gap: 10px; justify-content: flex-end; margin-top: 1.5rem;
        }

        .btn-modal-cancel {
            padding: 0.6rem 1.2rem;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border); border-radius: 9px;
            color: var(--muted2); font-size: 13px; font-weight: 500;
            cursor: pointer; transition: all 0.15s;
            font-family: 'DM Sans', sans-serif;
        }
        .btn-modal-cancel:hover { background: rgba(255,255,255,0.08); color: var(--text); }

        .btn-modal-save {
            padding: 0.6rem 1.4rem;
            background: var(--accent); border: none; border-radius: 9px;
            color: #fff; font-family: 'Syne', sans-serif;
            font-size: 13px; font-weight: 700; cursor: pointer;
            transition: all 0.2s;
        }
        .btn-modal-save:hover { background: #ff6635; box-shadow: 0 4px 16px rgba(255,77,28,0.3); }
        .btn-modal-save:disabled { opacity: 0.5; cursor: not-allowed; }

        /* Toast */
        .toast-wrap {
            position: fixed; bottom: 2rem; left: 50%; transform: translateX(-50%);
            z-index: 9999; display: flex; flex-direction: column; gap: 8px;
            pointer-events: none;
        }

        .toast {
            padding: 0.65rem 1.2rem;
            border-radius: 10px; font-size: 13px; font-weight: 500;
            animation: toast-in 0.25s ease forwards;
            box-shadow: 0 8px 30px rgba(0,0,0,0.4);
        }

        .toast-ok  { background: #166534; border: 1px solid #15803d; color: #bbf7d0; }
        .toast-err { background: #7f1d1d; border: 1px solid #991b1b; color: #fecaca; }

        @keyframes toast-in {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 700px) {
            nav { padding: 1rem 1.25rem; }
            main { padding: 1.5rem 1rem 4rem; }
            .nav-logo { display: none; }
            .table-head,
            .table-row { grid-template-columns: 40px 1fr 90px 1fr; }
            .col-date { display: none; }
        }
    </style>
</head>
<body>

<!-- ─── NAV ─── -->
<!-- <nav>
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
</nav> -->

<!-- ─── MAIN ─── -->


<?php include 'aside.php'; ?>



<main>
    <div class="breadcrumb">
        <a href="index.php">Dashboard</a>
        <span class="breadcrumb-sep">›</span>
        <span>Events</span>
    </div>

    <?php
    $activeCount = 0;
    foreach ($events as $e) {
        if ($e['status'] === 'live') $activeCount++;
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
                // Status comes directly from DB: 'live', 'locked', 'ended'
                $isLive   = $event['status'] === 'live';
                $isEnded  = $event['status'] === 'ended';
                $isLocked = $event['status'] === 'locked';
                // $safeGoLive = date('Y-m-d\TH:i', strtotime($event['go_live_at']));
                $safeGoLive = $event['go_live_at'];

                if ($isEnded) {
                    $tagClass = 'tag-ended'; $tagLabel = 'Ended'; $dot = false;
                } elseif ($isLive) {
                    $tagClass = 'tag-active'; $tagLabel = 'Live'; $dot = true;
                } else {
                    $tagClass = 'tag-upcoming'; $tagLabel = 'Locked'; $dot = false;
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

                <div class="actions-cell">
                    <!-- Items -->
                    <a href="items.php?event_id=<?= $event['id'] ?>" class="btn-action btn-items">
                        Items →
                    </a>

                    <!-- Edit (locked/upcoming only) — data stored in data-* attributes, no inline JSON -->
                    <button
                        class="btn-action btn-edit<?= ($isLive || $isEnded) ? ' disabled' : '' ?>"
                        data-id="<?= $event['id'] ?>"
                        data-name="<?= htmlspecialchars($event['name'], ENT_QUOTES) ?>"
                        data-golive="<?= $safeGoLive ?>"
                        onclick="openEdit(this)"
                        title="<?= $isLive ? 'Force-close event first to edit' : ($isEnded ? 'Ended events cannot be edited' : 'Edit event') ?>"
                        <?= ($isLive || $isEnded) ? 'disabled' : '' ?>>
                        ✏ Edit
                    </button>

                    <!-- Force Open (upcoming only) -->
                    <?php if ($isLocked): ?>
                    <button
                        class="btn-action btn-force-open"
                        data-id="<?= $event['id'] ?>"
                        data-name="<?= htmlspecialchars($event['name'], ENT_QUOTES) ?>"
                        onclick="forceAction(this, 'force_open')"
                        title="Force-open this event now">
                        ▶ Force Open
                    </button>
                    <?php endif; ?>

                    <!-- Force Close (live only) -->
                    <?php if ($isLive): ?>
                    <button
                        class="btn-action btn-force-close"
                        data-id="<?= $event['id'] ?>"
                        data-name="<?= htmlspecialchars($event['name'], ENT_QUOTES) ?>"
                        onclick="forceAction(this, 'force_close')"
                        title="Force-close this live event">
                        ■ Force Close
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>

        <?php endif; ?>
    </div>
</main>

<!-- ══════════════════════════════
     EDIT MODAL
══════════════════════════════ -->
<div class="modal-overlay" id="editModal" onclick="closeOnBackdrop(event)">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">Edit Event</span>
            <button class="modal-close" onclick="closeModal()">✕</button>
        </div>

        <div class="modal-notice">
            <span class="modal-notice-icon">ℹ</span>
            <span>Only <strong>locked / upcoming</strong> events can be edited. Force-close a live event first if needed.</span>
        </div>

        <input type="hidden" id="editId">

        <div class="field">
            <label>Event Name</label>
            <input type="text" id="editName">
        </div>

        <div class="field">
            <label>Go Live Date</label>
            <input type="date" id="editDate">
        </div>

        <div class="field">
            <label>Go Live Time</label>
            <input type="time" id="editTime">
        </div>

     
        <div class="modal-footer">
            <button class="btn-modal-cancel" onclick="closeModal()">Cancel</button>
            <button class="btn-modal-save" id="saveBtn" onclick="saveEdit()">Save Changes</button>
        </div>
    </div>
</div>

<!-- Toast container -->
<div class="toast-wrap" id="toastWrap"></div>

<script>
/* ─── The PHP file path, used for all AJAX calls ─── */
const SELF_URL = '<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>';

/* ─── Modal helpers ─── */
// btn is the <button> element; reads data-* attributes — avoids any HTML quoting issues
function openEdit(btn) {
    document.getElementById('editId').value = btn.dataset.id;
    document.getElementById('editName').value = btn.dataset.name;

    const goLive = btn.dataset.golive; 
    // expected: "2026-05-16 12:00:00"

    if (goLive) {
        const [datePart, timePart] = goLive.split(' ');

        document.getElementById('editDate').value = datePart;

        // safely extract HH:MM
        document.getElementById('editTime').value =
            timePart ? timePart.substring(0, 5) : '';
    }

    document.getElementById('editModal').classList.add('open');
}



function closeModal() {
    document.getElementById('editModal').classList.remove('open');
}

function closeOnBackdrop(e) {
    if (e.target === document.getElementById('editModal')) closeModal();
}

/* ─── Save edit ─── */
async function saveEdit() {

    const btn  = document.getElementById('saveBtn');

    const id   = document.getElementById('editId').value;
    const name = document.getElementById('editName').value.trim();
    const date = document.getElementById('editDate').value;
    const time = document.getElementById('editTime').value;

    if (!name || !date || !time) {
        showToast('All fields are required', false);
        return;
    }

    btn.disabled = true;
    btn.textContent = 'Saving…';

    const fd = new FormData();
    fd.append('action', 'edit');
    fd.append('event_id', id);
    fd.append('name', name);
    fd.append('go_live_date', date);
    fd.append('go_live_time', time);

  let res;
try {
    res = await postData(fd);
} catch (e) {
    showToast('Network error', false);
    return;
}

    btn.disabled = false;
    btn.textContent = 'Save Changes';

    if (res.ok) {
        showToast('✓ ' + res.msg, true);
        closeModal();
        setTimeout(() => location.reload(), 800);
    } else {
        showToast('✗ ' + res.msg, false);
    }
}



/* ─── Force actions ─── */
// btn is the <button> element; reads data-id and data-name
function forceAction(btn, action) {
    const id   = btn.dataset.id;
    const name = btn.dataset.name;
    const label = action === 'force_open' ? 'Force-open' : 'Force-close';

    if (!confirm(`${label} event "${name}"?`)) return;

    const fd = new FormData();
    fd.append('action',   action);
    fd.append('event_id', id);

    postData(fd).then(res => {
        if (res.ok) {
            showToast('✓ ' + res.msg, true);
            setTimeout(() => location.reload(), 800);
        } else {
            showToast('✗ ' + res.msg, false);
        }
    });
}

/* ─── Shared fetch — posts to this same PHP file ─── */
async function postData(formData) {
    try {
        const r = await fetch(SELF_URL, {
            method: 'POST',
            body: formData
        });
        if (!r.ok) return { ok: false, msg: 'Server error ' + r.status };
        return await r.json();
    } catch (e) {
        return { ok: false, msg: 'Network error: ' + e.message };
    }
}

/* ─── Toast ─── */
function showToast(msg, ok) {
    const wrap = document.getElementById('toastWrap');
    const t = document.createElement('div');
    t.className = 'toast ' + (ok ? 'toast-ok' : 'toast-err');
    t.textContent = msg;
    wrap.appendChild(t);
    setTimeout(() => t.remove(), 3200);
}
</script>

</body>
</html>