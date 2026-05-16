<?php
session_start();
require_once "../config/database.php";

/* =========================
   1. ADMIN AUTH CHECK
========================= */
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$error   = "";
$success = "";

/* =========================
   2. HANDLE FORM SUBMIT
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name   = trim($_POST['name'] ?? '');
    $date   = trim($_POST['go_live_date'] ?? '');
    $time   = trim($_POST['go_live_time'] ?? '');
    $status = in_array($_POST['status'] ?? '', ['locked', 'live', 'ended'])
                ? $_POST['status']
                : 'locked';

    if (empty($name) || empty($date) || empty($time)) {
        $error = "Please fill in all required fields.";
    } elseif (!isset($_FILES['cover_image']) || $_FILES['cover_image']['error'] !== 0) {
        $error = "Image upload failed. Please try again.";
    } elseif ($_FILES['cover_image']['size'] > 2 * 1024 * 1024) {
        $error = "Image is too large. Maximum size is 2 MB.";
    } else {

        /* =========================
           3. VERIFY REAL IMAGE
        ========================= */
        $imageInfo = getimagesize($_FILES['cover_image']['tmp_name']);

        if ($imageInfo === false) {
            $error = "Invalid image file.";
        } else {

            $allowedMimes = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp',
            ];

            if (!isset($allowedMimes[$imageInfo['mime']])) {
                $error = "Only JPG, PNG, and WEBP images are allowed.";
            } else {

                /* =========================
                   4. SAFE FILE NAME + MOVE
                ========================= */
                $ext       = $allowedMimes[$imageInfo['mime']];
                $fileName  = bin2hex(random_bytes(16)) . '.' . $ext;
                $uploadDir = __DIR__ . '/../uploads/events/';

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $targetPath = $uploadDir . $fileName;

                if (!move_uploaded_file($_FILES['cover_image']['tmp_name'], $targetPath)) {
                    $error = "Failed to save image. Please try again.";
                } else {

                    /* =========================
                       5. BUILD DATETIME + INSERT
                    ========================= */
                    $go_live_at = $date . ' ' . $time . ':00';

                    $stmt = $conn->prepare("
                        INSERT INTO events (name, cover_image, go_live_at, status)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$name, $fileName, $go_live_at, $status]);

                    header("Location: events.php?created=1");
                    exit;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SwiftDrop — Create Event</title>
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
</head>
<body>
<div class="admin-layout">

    <!-- ── Sidebar ── -->
    <aside class="sidebar">
        <a href="dashboard.php" class="sidebar-logo">
            <div class="logo-icon">⚡</div>
            <span class="logo-name">SwiftDrop</span>
        </a>

        <span class="sidebar-label">Main</span>
        <a href="dashboard.php" class="sidebar-link">
            <i class="ti ti-layout-dashboard" aria-hidden="true"></i> Dashboard
        </a>
        <a href="events.php" class="sidebar-link active">
            <i class="ti ti-bolt" aria-hidden="true"></i> Events
        </a>
        <a href="products.php" class="sidebar-link">
            <i class="ti ti-package" aria-hidden="true"></i> Products
        </a>
        <a href="orders.php" class="sidebar-link">
            <i class="ti ti-shopping-cart" aria-hidden="true"></i> Orders
        </a>

        <span class="sidebar-label">Manage</span>
        <a href="users.php" class="sidebar-link">
            <i class="ti ti-users" aria-hidden="true"></i> Users
        </a>
        <a href="settings.php" class="sidebar-link">
            <i class="ti ti-settings" aria-hidden="true"></i> Settings
        </a>

        <div class="sidebar-bottom">
            <a href="../logout.php" class="sidebar-link">
                <i class="ti ti-logout" aria-hidden="true"></i> Logout
            </a>
        </div>
    </aside>

    <!-- ── Main ── -->
    <main class="main">

        <!-- Page header -->
        <div class="page-header">
            <div class="page-header-left">
                <div class="page-icon">
                    <i class="ti ti-bolt" style="font-size:22px; color:#fff;" aria-hidden="true"></i>
                </div>
                <div>
                    <div class="page-title">Create Event</div>
                    <div class="page-sub">Set up a new flash sale drop</div>
                </div>
            </div>
            <a href="events.php" class="back-link">
                <i class="ti ti-arrow-left" style="font-size:15px;" aria-hidden="true"></i>
                Back to events
            </a>
        </div>

        <!-- Error alert -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-error" role="alert">
                <i class="ti ti-alert-circle" aria-hidden="true"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Form card -->
        <div class="form-card">
            <form method="POST" enctype="multipart/form-data" id="createForm" novalidate>

                <!-- Event details -->
                <p class="section-label">Event details</p>

                <div class="field">
                    <label class="field-label" for="ev-name">Event name <span style="color:var(--accent)">*</span></label>
                    <div class="input-wrap">
                        <i class="ti ti-tag" aria-hidden="true"></i>
                        <input
                            class="field-input"
                            type="text"
                            id="ev-name"
                            name="name"
                            placeholder="e.g. Midnight Tech Drop"
                            maxlength="60"
                            value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                            oninput="updateCharCount(this, 'name-count', 60)"
                            required
                            autofocus
                        >
                    </div>
                    <div class="char-count" id="name-count">0 / 60</div>
                </div>

                <div class="field-row">
                    <div>
                        <label class="field-label" for="ev-date">Go live date <span style="color:var(--accent)">*</span></label>
                        <div class="input-wrap">
                            <i class="ti ti-calendar" aria-hidden="true"></i>
                            <input
                                class="field-input"
                                type="date"
                                id="ev-date"
                                name="go_live_date"
                                value="<?= htmlspecialchars($_POST['go_live_date'] ?? date('Y-m-d')) ?>"
                                required
                            >
                        </div>
                    </div>
                    <div>
                        <label class="field-label" for="ev-time">Go live time <span style="color:var(--accent)">*</span></label>
                        <div class="input-wrap">
                            <i class="ti ti-clock" aria-hidden="true"></i>
                            <input
                                class="field-input"
                                type="time"
                                id="ev-time"
                                name="go_live_time"
                                value="<?= htmlspecialchars($_POST['go_live_time'] ?? '12:00') ?>"
                                required
                            >
                        </div>
                    </div>
                </div>

                <hr class="section-divider">

                <!-- Cover image -->
                <p class="section-label">Cover image</p>

                <div class="upload-zone" id="dropZone">
                    <input
                        type="file"
                        id="ev-image"
                        name="cover_image"
                        accept="image/jpeg,image/png,image/webp"
                        onchange="handleFile(this)"
                        required
                        aria-label="Upload cover image"
                    >
                    <i class="ti ti-cloud-upload upload-icon" aria-hidden="true"></i>
                    <div class="upload-title">Drop image here or click to browse</div>
                    <div class="upload-hint">JPG, PNG or WEBP · max 2 MB</div>
                </div>

                <div class="preview-row" id="previewRow">
                    <img class="preview-thumb" id="previewThumb" src="" alt="Cover preview">
                    <div class="preview-info">
                        <div class="preview-name" id="previewName"></div>
                        <div class="preview-size" id="previewSize"></div>
                    </div>
                    <button type="button" class="remove-btn" onclick="removeFile()" aria-label="Remove image">
                        <i class="ti ti-x" aria-hidden="true"></i>
                    </button>
                </div>

                <hr class="section-divider">

                <!-- Status -->
                <p class="section-label">Initial status</p>

                <div class="status-group">
                    <?php
                    $currentStatus = $_POST['status'] ?? 'locked';
                    $statuses = [
                        'locked' => ['dot' => 'dot-locked', 'label' => 'Locked'],
                        'live'   => ['dot' => 'dot-live',   'label' => 'Live'],
                        'ended'  => ['dot' => 'dot-ended',  'label' => 'Ended'],
                    ];
                    foreach ($statuses as $val => $s):
                        $checked = $currentStatus === $val ? 'checked' : '';
                    ?>
                        <div class="status-opt">
                            <input type="radio" name="status" id="st-<?= $val ?>" value="<?= $val ?>" <?= $checked ?>>
                            <label class="status-pill" for="st-<?= $val ?>">
                                <span class="status-dot <?= $s['dot'] ?>"></span>
                                <?= $s['label'] ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Footer -->
                <div class="form-footer">
                    <a href="events.php" class="btn-cancel">Cancel</a>
                    <button type="submit" class="btn-submit" id="submitBtn">
                        <span class="spinner" aria-hidden="true"></span>
                        <i class="ti ti-bolt btn-icon" aria-hidden="true"></i>
                        <span class="btn-text">Create Event</span>
                    </button>
                </div>

            </form>
        </div>

    </main>
</div>

<script>
    /* ── Character counter ── */
    function updateCharCount(input, countId, max) {
        const len   = input.value.length;
        const el    = document.getElementById(countId);
        el.textContent = len + ' / ' + max;
        el.classList.toggle('warn',   len > max * 0.8);
        el.classList.toggle('danger', len >= max);
    }

    /* Init counter on page load (for repopulated values) */
    const nameInput = document.getElementById('ev-name');
    if (nameInput.value) updateCharCount(nameInput, 'name-count', 60);

    /* ── File preview ── */
    function handleFile(input) {
        const file = input.files[0];
        if (!file) return;
        const url = URL.createObjectURL(file);
        document.getElementById('previewThumb').src = url;
        document.getElementById('previewName').textContent = file.name;
        const kb = file.size / 1024;
        document.getElementById('previewSize').textContent =
            kb < 1024 ? Math.round(kb) + ' KB' : (kb / 1024).toFixed(1) + ' MB';
        document.getElementById('previewRow').classList.add('visible');
    }

    function removeFile() {
        document.getElementById('ev-image').value = '';
        document.getElementById('previewRow').classList.remove('visible');
    }

    /* ── Drag-and-drop ── */
    const dz = document.getElementById('dropZone');
    dz.addEventListener('dragover',  e => { e.preventDefault(); dz.classList.add('drag-over'); });
    dz.addEventListener('dragleave', ()  => dz.classList.remove('drag-over'));
    dz.addEventListener('drop', e => {
        e.preventDefault();
        dz.classList.remove('drag-over');
        const dt = new DataTransfer();
        if (e.dataTransfer.files[0]) {
            dt.items.add(e.dataTransfer.files[0]);
            const input = document.getElementById('ev-image');
            input.files = dt.files;
            handleFile(input);
        }
    });

    /* ── Loading state on submit ── */
    document.getElementById('createForm').addEventListener('submit', function(e) {
        const name  = document.getElementById('ev-name').value.trim();
        const date  = document.getElementById('ev-date').value;
        const time  = document.getElementById('ev-time').value;
        const file  = document.getElementById('ev-image').files[0];

        if (!name || !date || !time || !file) {
            e.preventDefault();
            return;
        }

        const btn = document.getElementById('submitBtn');
        btn.classList.add('loading');
        btn.querySelector('.btn-text').textContent = 'Creating…';
    });
</script>
</body>
</html>