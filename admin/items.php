<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$event_id = $_GET['event_id'] ?? null;

// Guard: redirect back if no valid event_id
if (!$event_id || !is_numeric($event_id)) {
    header("Location: events.php");
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id  = $_POST['event_id'] ?? null;

    // Confirm the event actually exists before inserting
    $check = $conn->prepare("SELECT id FROM events WHERE id = ?");
    $check->execute([$event_id]);

    if (!$check->fetch()) {
        die("Invalid event. Go back and try again.");
    }

    $name      = $_POST['name'];
    $price     = $_POST['price'];
    $stock_qty = $_POST['stock'];

    $stmt = $conn->prepare("
        INSERT INTO items (event_id, name, price, stock_qty, remaining_stock)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$event_id, $name, $price, $stock_qty, $stock_qty]);

    header("Location: items.php?event_id=" . $event_id);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM items WHERE event_id = ?");
$stmt->execute([$event_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch event name for context
$evStmt = $conn->prepare("SELECT name FROM events WHERE id = ?");
$evStmt->execute([$event_id]);
$event = $evStmt->fetch(PDO::FETCH_ASSOC);

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
    <title>Manage Items — SwiftDrop Admin</title>
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

        .nav-left { display: flex; align-items: center; gap: 10px; }

        .nav-icon {
            width: 32px; height: 32px;
            background: var(--accent);
            border-radius: 8px;
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
            border: 1px solid var(--border);
            border-radius: 9px;
        }

        .nav-avatar {
            width: 26px; height: 26px;
            border-radius: 50%;
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
            border-color: rgba(255,77,28,0.45);
            color: #ff6035;
        }

        /* ─── Main ─── */
        main {
            position: relative;
            z-index: 1;
            max-width: 900px;
            margin: 0 auto;
            padding: 2.5rem 2rem 5rem;
            margin-top: -711px;
        }

        /* ─── Breadcrumb ─── */
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: var(--muted2);
            margin-bottom: 1.5rem;
        }

        .breadcrumb a {
            color: var(--muted2);
            text-decoration: none;
            transition: color 0.15s;
        }

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
            width: 18px; height: 1px;
            background: var(--accent);
        }

        .page-title {
            font-family: 'Syne', sans-serif;
            font-size: clamp(1.75rem, 4vw, 2.25rem);
            font-weight: 800; letter-spacing: -1px;
        }

        .page-sub {
            margin-top: 5px;
            color: var(--muted2);
            font-size: 13px; font-weight: 300;
        }

        /* ─── Layout ─── */
        .layout {
            display: grid;
            grid-template-columns: 1fr 1.6fr;
            gap: 1.5rem;
            margin-top: 2rem;
            align-items: start;
        }

        /* ─── Form card ─── */
        .form-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
        }

        .form-card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; gap: 10px;
        }

        .form-card-icon {
            width: 32px; height: 32px;
            border-radius: 8px;
            background: rgba(255,77,28,0.12);
            display: flex; align-items: center; justify-content: center;
            font-size: 16px;
        }

        .form-card-header h3 {
            font-family: 'Syne', sans-serif;
            font-size: 15px; font-weight: 800;
        }

        .form-body {
            padding: 1.5rem;
            display: flex; flex-direction: column; gap: 1rem;
        }

        .form-group { display: flex; flex-direction: column; gap: 6px; }

        .form-label {
            font-size: 12px; font-weight: 500;
            color: var(--muted2); letter-spacing: 0.3px;
        }

        .form-input {
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            border-radius: 9px;
            padding: 0.65rem 1rem;
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            outline: none;
            width: 100%;
            transition: border-color 0.2s, background 0.2s;
        }

        .form-input::placeholder { color: var(--muted); }

        .form-input:focus {
            border-color: rgba(255,77,28,0.5);
            background: rgba(255,77,28,0.03);
        }

        .form-hint { font-size: 11px; color: var(--muted); font-weight: 300; }

        .btn-submit {
            width: 100%;
            padding: 0.8rem;
            background: var(--accent);
            border: none;
            border-radius: 10px;
            color: #fff;
            font-family: 'Syne', sans-serif;
            font-size: 14px; font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 0.25rem;
        }

        .btn-submit:hover {
            background: #ff6635;
            box-shadow: 0 4px 20px rgba(255,77,28,0.3);
            transform: translateY(-1px);
        }

        /* ─── Items card ─── */
        .items-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
        }

        .items-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
        }

        .items-header h3 {
            font-family: 'Syne', sans-serif;
            font-size: 15px; font-weight: 800;
        }

        .items-count {
            padding: 3px 10px;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border);
            border-radius: 100px;
            font-size: 12px; color: var(--muted2);
        }

        .item-row {
            display: flex; align-items: center;
            justify-content: space-between;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border);
            gap: 1rem;
            transition: background 0.2s;
        }

        .item-row:last-child { border-bottom: none; }
        .item-row:hover { background: var(--surface2); }

        .item-name {
            font-family: 'Syne', sans-serif;
            font-size: 14px; font-weight: 800;
        }

        .item-meta {
            font-size: 12px; color: var(--muted2);
            font-weight: 300; margin-top: 2px;
        }

        .item-right {
            display: flex; align-items: center;
            gap: 0.75rem; flex-shrink: 0;
        }

        .item-price {
            font-family: 'Syne', sans-serif;
            font-size: 15px; font-weight: 800;
        }

        .stock-badge {
            padding: 3px 10px;
            border-radius: 100px;
            font-size: 11px; font-weight: 500;
            white-space: nowrap;
        }

        .stock-ok  { background: rgba(34,197,94,0.1);  color: #86efac; border: 1px solid rgba(34,197,94,0.2); }
        .stock-low { background: rgba(255,140,66,0.1); color: var(--accent2); border: 1px solid rgba(255,140,66,0.2); }
        .stock-out { background: rgba(239,68,68,0.08); color: #fca5a5; border: 1px solid rgba(239,68,68,0.15); }

        .empty-state {
            padding: 3rem 1.5rem;
            text-align: center;
            color: var(--muted2);
        }

        .empty-icon { font-size: 32px; margin-bottom: 0.75rem; }
        .empty-state p { font-size: 13px; font-weight: 300; }

        @media (max-width: 680px) {
            nav { padding: 1rem 1.25rem; }
            main { padding: 1.5rem 1rem 4rem; }
            .layout { grid-template-columns: 1fr; }
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

<?php include 'aside.php'; ?>

<!-- ─── MAIN ─── -->
<main>
    <div class="breadcrumb">
        <a href="index.php">Dashboard</a>
        <span class="breadcrumb-sep">›</span>
        <a href="events.php">Events</a>
        <span class="breadcrumb-sep">›</span>
        <span>Manage Items</span>
    </div>

    <div class="page-eyebrow">Inventory</div>
    <h1 class="page-title">Manage Items</h1>
    <p class="page-sub">
        <?php if ($event): ?>
            Adding items to <strong style="color:var(--text);font-weight:500;"><?= htmlspecialchars($event['name']) ?></strong>
        <?php else: ?>
            Add and manage items for this flash sale event.
        <?php endif; ?>
    </p>

    <div class="layout">

        <!-- ─── Add Item Form ─── -->
        <div class="form-card">
            <div class="form-card-header">
                <div class="form-card-icon">➕</div>
                <h3>Add New Item</h3>
            </div>
            <div class="form-body">
                <form method="POST">
                    <input type="hidden" name="event_id" value="<?= htmlspecialchars($event_id) ?>">

                    <div class="form-group" style="margin-bottom:1rem;">
                        <label class="form-label">Item Name</label>
                        <input class="form-input" type="text" name="name"
                               placeholder="e.g. Sony WH-1000XM6" required>
                    </div>

                    <div class="form-group" style="margin-bottom:1rem;">
                        <label class="form-label">Price (Rs)</label>
                        <input class="form-input" type="number" name="price"
                               placeholder="e.g. 89999" min="0" step="0.01" required>
                    </div>

                    <div class="form-group" style="margin-bottom:1.25rem;">
                        <label class="form-label">Stock Quantity</label>
                        <input class="form-input" type="number" name="stock"
                               placeholder="100 – 500" min="100" max="500" required>
                        <span class="form-hint">Min 100 · Max 500 units</span>
                    </div>

                    <button type="submit" class="btn-submit">Add Item →</button>
                </form>
            </div>
        </div>

        <!-- ─── Existing Items ─── -->
        <div class="items-card">
            <div class="items-header">
                <h3>Existing Items</h3>
                <span class="items-count"><?= count($items) ?> item<?= count($items) !== 1 ? 's' : '' ?></span>
            </div>

            <?php if (empty($items)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📦</div>
                    <p>No items yet. Add your first item using the form.</p>
                </div>
            <?php else: ?>
                <?php foreach ($items as $item):
                    $stock = (int) $item['remaining_stock'];
                    if ($stock === 0)     $badgeClass = 'stock-out';
                    elseif ($stock <= 10) $badgeClass = 'stock-low';
                    else                  $badgeClass = 'stock-ok';

                    if ($stock === 0)     $badgeText = 'Sold Out';
                    elseif ($stock <= 10) $badgeText = 'Low Stock';
                    else                  $badgeText = 'In Stock';
                ?>
                <div class="item-row">
                    <div>
                        <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                        <div class="item-meta"><?= number_format($stock) ?> remaining</div>
                    </div>
                    <div class="item-right">
                        <div class="item-price">Rs <?= number_format($item['price']) ?></div>
                        <span class="stock-badge <?= $badgeClass ?>"><?= $badgeText ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</main>

</body>
</html>