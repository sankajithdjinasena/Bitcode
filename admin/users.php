<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

/* ── Toggle user status ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id'])) {
    header('Content-Type: application/json');

    $id = (int)$_POST['toggle_id'];

    $stmt = $conn->prepare("SELECT status FROM users WHERE id=?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['ok'=>false,'msg'=>'User not found']);
        exit;
    }

    $newStatus = ($user['status'] === 'active') ? 'deactivated' : 'active';

    $up = $conn->prepare("UPDATE users SET status=? WHERE id=?");
    $up->execute([$newStatus, $id]);

    echo json_encode([
        'ok' => true,
        'msg' => "User " . ($newStatus === 'active' ? "activated" : "deactivated")
    ]);
    exit;
}

/* ── Fetch users ── */
$stmt = $conn->query("SELECT * FROM users ORDER BY id DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Users - SwiftDrop</title>

    <link rel="stylesheet" href="/assets/admin.css">

    <!-- same icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css">
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
            --warning:   #f59e0b;
        }

        html, body {
            min-height: 100%;
            background: var(--bg);
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-weight: 400;
            line-height: 1.6;
        }

        /* Grid background */
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

        /* Glow blob */
        body::after {
            content: '';
            position: fixed;
            top: -20%;
            right: -10%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(255,77,28,0.10) 0%, transparent 70%);
            pointer-events: none;
            z-index: 0;
        }

        /* ── Layout ── */
        .admin-layout {
            position: relative;
            z-index: 1;
            display: flex;
            min-height: 100vh;
        }

        /* ── Sidebar ── */
        .sidebar {
            width: 220px;
            flex-shrink: 0;
            background: var(--surface);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            padding: 1.5rem 0;
            position: sticky;
            top: 0;
            height: 100vh;
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 1.25rem 1.5rem;
            text-decoration: none;
            color: inherit;
            border-bottom: 1px solid var(--border);
            margin-bottom: 1rem;
        }

        .logo-icon {
            width: 32px;
            height: 32px;
            background: var(--accent);
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
            flex-shrink: 0;
        }

        .logo-name {
            font-family: 'Syne', sans-serif;
            font-size: 18px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .sidebar-label {
            font-size: 10px;
            font-weight: 500;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--muted);
            padding: 0 1.25rem;
            margin: 0.75rem 0 0.35rem;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0.55rem 1.25rem;
            font-size: 14px;
            color: var(--muted);
            text-decoration: none;
            transition: color 0.15s, background 0.15s;
            border-radius: 0;
        }

        .sidebar-link i { font-size: 17px; }

        .sidebar-link:hover {
            color: var(--text);
            background: rgba(255,255,255,0.04);
        }

        .sidebar-link.active {
            color: var(--accent);
            background: rgba(255,77,28,0.08);
        }

        .sidebar-bottom {
            margin-top: auto;
            border-top: 1px solid var(--border);
            padding-top: 1rem;
        }

        /* ── Main content ── */
        .main {
            flex: 1;
            padding: 2.5rem;
            min-width: 0;
        }

        /* ── Page header ── */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
        }

        .page-header-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .page-icon {
            width: 44px;
            height: 44px;
            background: var(--accent);
            border-radius: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }

        .page-title {
            font-family: 'Syne', sans-serif;
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            line-height: 1.1;
        }

        .page-sub {
            font-size: 13px;
            color: var(--muted);
            margin-top: 3px;
        }

        .back-link {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: var(--muted);
            text-decoration: none;
            transition: color 0.15s;
        }

        .back-link:hover { color: var(--text); }

        /* ── Alert ── */
        .alert {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 0.85rem 1rem;
            border-radius: 12px;
            font-size: 14px;
            margin-bottom: 1.5rem;
            animation: fade-in 0.3s ease;
        }

        @keyframes fade-in {
            from { opacity: 0; transform: translateY(-4px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .alert-error {
            background: rgba(239,68,68,0.08);
            border: 1px solid rgba(239,68,68,0.25);
            color: #fca5a5;
        }

        .alert i { font-size: 17px; flex-shrink: 0; margin-top: 1px; }

        /* ── Form card ── */
        .form-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 2rem;
            max-width: 680px;
        }

        /* ── Section label ── */
        .section-label {
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 1rem;
        }

        .section-divider {
            border: none;
            border-top: 1px solid var(--border);
            margin: 1.75rem 0;
        }

        /* ── Fields ── */
        .field { margin-bottom: 1.25rem; }

        .field-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 1.25rem;
        }

        label.field-label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: var(--muted);
            margin-bottom: 7px;
            letter-spacing: 0.2px;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap i {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 16px;
            color: var(--muted);
            pointer-events: none;
        }

        .field-input {
            width: 100%;
            padding: 0.7rem 0.9rem 0.7rem 2.6rem;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
        }

        .field-input::placeholder { color: var(--muted); }

        .field-input:hover {
            border-color: var(--border-hi);
            background: rgba(255,255,255,0.06);
        }

        .field-input:focus {
            border-color: var(--accent);
            background: rgba(255,77,28,0.04);
            box-shadow: 0 0 0 3px rgba(255,77,28,0.12);
        }

        /* Date/time inputs */
        input[type="date"]::-webkit-calendar-picker-indicator,
        input[type="time"]::-webkit-calendar-picker-indicator {
            filter: invert(0.5);
            cursor: pointer;
        }

        .char-count {
            font-size: 12px;
            color: var(--muted);
            text-align: right;
            margin-top: 4px;
            transition: color 0.15s;
        }

        .char-count.warn { color: var(--warning); }
        .char-count.danger { color: var(--danger); }

        /* ── Upload zone ── */
        .upload-zone {
            border: 1.5px dashed var(--border-hi);
            border-radius: 12px;
            background: rgba(255,255,255,0.02);
            padding: 2rem 1rem;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.15s, background 0.15s;
            position: relative;
        }

        .upload-zone:hover,
        .upload-zone.drag-over {
            border-color: var(--accent);
            background: rgba(255,77,28,0.04);
        }

        .upload-zone input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        .upload-icon {
            font-size: 30px;
            color: var(--muted);
            margin-bottom: 8px;
            display: block;
        }

        .upload-title {
            font-size: 14px;
            font-weight: 500;
            color: var(--text);
        }

        .upload-hint {
            font-size: 12px;
            color: var(--muted);
            margin-top: 4px;
        }

        /* ── Preview row ── */
        .preview-row {
            display: none;
            align-items: center;
            gap: 12px;
            margin-top: 10px;
            padding: 0.75rem 1rem;
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--border);
            border-radius: 10px;
        }

        .preview-row.visible { display: flex; }

        .preview-thumb {
            width: 52px;
            height: 52px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid var(--border);
            flex-shrink: 0;
        }

        .preview-info { flex: 1; min-width: 0; }

        .preview-name {
            font-size: 13px;
            font-weight: 500;
            color: var(--text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .preview-size {
            font-size: 12px;
            color: var(--muted);
            margin-top: 2px;
        }

        .remove-btn {
            background: none;
            border: 1px solid var(--border);
            border-radius: 7px;
            color: var(--muted);
            cursor: pointer;
            padding: 5px 7px;
            display: flex;
            align-items: center;
            transition: color 0.15s, border-color 0.15s;
            flex-shrink: 0;
        }

        .remove-btn:hover {
            color: var(--danger);
            border-color: rgba(239,68,68,0.4);
        }

        .remove-btn i { font-size: 15px; }

        /* ── Status radio group ── */
        .status-group {
            display: flex;
            gap: 8px;
        }

        .status-opt {
            flex: 1;
            position: relative;
        }

        .status-opt input[type="radio"] {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .status-pill {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 0.6rem 0.5rem;
            border: 1px solid var(--border);
            border-radius: 10px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            color: var(--muted);
            background: rgba(255,255,255,0.02);
            transition: border-color 0.15s, color 0.15s, background 0.15s;
            user-select: none;
        }

        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .dot-locked { background: var(--muted); }
        .dot-live   { background: var(--success); }
        .dot-ended  { background: var(--danger); }

        .status-opt input:checked + .status-pill {
            border-color: var(--accent);
            color: var(--accent);
            background: rgba(255,77,28,0.07);
        }

        /* ── Footer ── */
        .form-footer {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
        }

        .btn-cancel {
            padding: 0.7rem 1.25rem;
            background: none;
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--muted);
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: border-color 0.15s, color 0.15s;
        }

        .btn-cancel:hover {
            border-color: var(--border-hi);
            color: var(--text);
        }

        .btn-submit {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 0.7rem 1.5rem;
            background: var(--accent);
            border: none;
            border-radius: 10px;
            color: #fff;
            font-family: 'Syne', sans-serif;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.2px;
            cursor: pointer;
            transition: background 0.15s, transform 0.1s, box-shadow 0.2s;
            position: relative;
            overflow: hidden;
        }

        .btn-submit:hover {
            background: #ff6635;
            box-shadow: 0 4px 20px rgba(255,77,28,0.3);
        }

        .btn-submit:active { transform: scale(0.98); }

        .btn-submit::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, transparent 60%);
            pointer-events: none;
        }

        .btn-submit i { font-size: 16px; }

        /* Loading state */
        .btn-submit.loading { pointer-events: none; opacity: 0.8; }

        .spinner {
            display: none;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        .btn-submit.loading .spinner { display: block; }
        .btn-submit.loading .btn-icon { display: none; }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main { padding: 1.5rem; }
            .field-row { grid-template-columns: 1fr; }
            .status-group { flex-direction: column; }
        }
    </style>
    <style>
        .user-table {
            background: #111118;
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 12px;
            overflow: hidden;
        }

        .row {
            display: grid;
            grid-template-columns: 60px 1fr 1fr 120px 120px;
            padding: 12px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.07);
            align-items: center;
        }

        .row:hover {
            background: rgba(255,255,255,0.03);
        }

        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
        }

        .active { background: rgba(34,197,94,0.15); color: #86efac; }
        .inactive { background: rgba(239,68,68,0.15); color: #fca5a5; }

        .btn {
            padding: 5px 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
        }

        .btn-toggle {
            background: rgba(255,77,28,0.15);
            color: #ff8c42;
        }
    </style>
</head>

<body>

<div class="admin-layout">

<?php include "aside.php"; ?>

<main class="main">

    <h2 style="margin-bottom:20px;">Users</h2>

    <div class="user-table">

        <div class="row" style="font-weight:bold;">
            <div>ID</div>
            <div>Name</div>
            <div>Email</div>
            <div>Status</div>
            <div>Action</div>
        </div>

        <?php foreach ($users as $u): ?>
        <div class="row">
            <div>#<?= $u['id'] ?></div>
            <div><?= htmlspecialchars($u['name']) ?></div>
            <div><?= htmlspecialchars($u['email']) ?></div>

            <div>
                <span class="badge <?= $u['status'] ?>">
                    <?= $u['status'] ?>
                </span>
            </div>

            <div>
                <button class="btn btn-toggle"
                    onclick="toggleUser(<?= $u['id'] ?>)">
                    Toggle
                </button>
            </div>
        </div>
        <?php endforeach; ?>

    </div>

</main>
</div>

<script>
async function toggleUser(id) {
    const fd = new FormData();
    fd.append('toggle_id', id);

    const res = await fetch('users.php', {
        method: 'POST',
        body: fd
    });

    const data = await res.json();

    alert(data.msg);
    if (data.ok) location.reload();
}
</script>

</body>
</html>