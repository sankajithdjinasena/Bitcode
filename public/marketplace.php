<?php
require_once "../app/middleware/auth.php";
require_once "../config/database.php";

// ITEMS ALREADY BOUGHT (orders table)
$boughtItems = [];

$boughtStmt = $conn->prepare("
    SELECT item_id
    FROM orders
    WHERE user_id = ? AND status = 'confirmed'
");
$boughtStmt->execute([$userId]);

foreach ($boughtStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $boughtItems[$row['item_id']] = true;
}


// Fetch all events with their items
$stmt = $conn->query("
    SELECT
        events.id        AS event_id,
        events.name      AS event_name,
        events.go_live_at,
        events.status    AS event_status,
        events.cover_image,
        items.id         AS item_id,
        items.name       AS item_name,
        items.price,
        items.remaining_stock
    FROM events
    JOIN items ON items.event_id = events.id
    ORDER BY events.go_live_at ASC, items.id ASC
");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group by event
$events = [];
foreach ($rows as $row) {
    $eid = $row['event_id'];
    if (!isset($events[$eid])) {
        $events[$eid] = [
            'event_id'     => $eid,
            'event_name'   => $row['event_name'],
            'go_live_at'   => $row['go_live_at'],
            'event_status' => $row['event_status'],
            'cover_image'  => $row['cover_image'],
            'items'        => [],
        ];
    }
    $events[$eid]['items'][] = [
        'item_id'         => $row['item_id'],
        'item_name'       => $row['item_name'],
        'price'           => $row['price'],
        'remaining_stock' => $row['remaining_stock'],
    ];
}

date_default_timezone_set('Asia/Colombo');
$now = new DateTime('now', new DateTimeZone('Asia/Colombo'));

// Current user
$user     = $_SESSION['user'];
$userId   = (int) $user['id'];
$initials = strtoupper(substr($user['name'] ?? $user['username'], 0, 1));
if (strpos($user['name'] ?? '', ' ') !== false) {
    $parts    = explode(' ', $user['name']);
    $initials = strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));
}

// Cart count badge
$cartCountStmt = $conn->prepare("SELECT COUNT(*) FROM cart WHERE user_id = ?");
$cartCountStmt->execute([$userId]);
$cartCount = (int) $cartCountStmt->fetchColumn();

// Which event_ids has this user already bought or carted?
$boughtStmt = $conn->prepare("
    SELECT event_id FROM orders WHERE user_id = ? AND status = 'confirmed'
    UNION
    SELECT event_id FROM cart    WHERE user_id = ?
");
$boughtStmt->execute([$userId, $userId]);
$takenEvents = $boughtStmt->fetchAll(PDO::FETCH_COLUMN);
$takenEvents = array_flip($takenEvents); // use as hash-set
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketplace — SwiftDrop</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,300&display=swap" rel="stylesheet">
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
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }

        .nav-logo-name {
            font-family: 'Syne', sans-serif;
            font-size: 19px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .nav-right {
            position: relative;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* Cart button */
        .nav-cart {
            position: relative;
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 7px 16px;
            background: rgba(255,77,28,0.08);
            border: 1px solid rgba(255,77,28,0.2);
            border-radius: 9px;
            color: var(--accent2);
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
        }

        .nav-cart:hover {
            background: rgba(255,77,28,0.15);
            border-color: rgba(255,77,28,0.35);
        }

        .cart-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 18px;
            height: 18px;
            background: var(--accent);
            border-radius: 50%;
            font-size: 10px;
            font-weight: 700;
            color: #fff;
            line-height: 1;
        }

        .cart-badge[data-count="0"] { display: none; }

        .nav-user {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 6px 14px 6px 8px;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            border-radius: 10px;
            cursor: pointer;
            user-select: none;
        }

        .nav-avatar {
            width: 28px; height: 28px;
            border-radius: 50%;
            background: var(--accent);
            display: flex; align-items: center; justify-content: center;
            font-size: 11px;
            font-weight: 700;
            color: #fff;
            flex-shrink: 0;
        }

        .nav-username { font-size: 13px; font-weight: 500; color: var(--text); }

        .user-menu {
            position: absolute;
            top: 52px;
            right: 95px;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 12px;
            min-width: 180px;
            overflow: hidden;
            opacity: 0;
            visibility: hidden;
            transform: translateY(8px);
            transition: all 0.2s ease;
            z-index: 200;
        }

        .user-menu.show { opacity: 1; visibility: visible; transform: translateY(0); }

        .user-menu a {
            display: block;
            padding: 12px 16px;
            text-decoration: none;
            color: var(--text);
            font-size: 14px;
            transition: background 0.2s;
        }

        .user-menu a:hover { background: rgba(255,255,255,0.05); }

        .nav-logout {
            padding: 7px 16px;
            background: rgba(255,77,28,0.1);
            border: 1px solid rgba(255,77,28,0.25);
            border-radius: 9px;
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
            max-width: 1160px;
            margin: 0 auto;
            padding: 2.5rem 2rem 6rem;
        }

        /* ─── Page header ─── */
        .page-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 2.5rem;
        }

        .page-title {
            font-family: 'Syne', sans-serif;
            font-size: clamp(1.75rem, 4vw, 2.5rem);
            font-weight: 800;
            letter-spacing: -1px;
            line-height: 1.1;
        }

        .page-title span { color: var(--accent); }

        .page-sub {
            margin-top: 5px;
            color: var(--muted2);
            font-size: 13px;
            font-weight: 300;
        }

        .live-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 6px 14px;
            border: 1px solid rgba(34,197,94,0.3);
            border-radius: 100px;
            font-size: 12px;
            font-weight: 500;
            color: #86efac;
        }

        .live-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: #22c55e;
            animation: pulse-dot 2s infinite;
        }

        @keyframes pulse-dot {
            0%, 100% { opacity:1; transform:scale(1); }
            50%       { opacity:.5; transform:scale(1.4); }
        }

        /* ─── Event block ─── */
        .event-block { margin-bottom: 3rem; }

        /* ─── Hero banner ─── */
        .event-hero {
            position: relative;
            width: 100%;
            border-radius: 16px 16px 0 0;
            overflow: hidden;
            aspect-ratio: 21 / 6;
            background: var(--surface2);
        }

        .event-hero img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .event-hero-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: var(--muted);
        }

        .event-hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(
                to right,
                rgba(7,7,13,0.85) 0%,
                rgba(7,7,13,0.45) 55%,
                rgba(7,7,13,0.05) 100%
            );
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 1.5rem 2rem;
        }

        .event-hero-label {
            font-size: 10px;
            font-weight: 500;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--accent2);
            margin-bottom: 4px;
        }

        .event-hero-title {
            font-family: 'Syne', sans-serif;
            font-size: clamp(1.2rem, 3vw, 1.9rem);
            font-weight: 800;
            letter-spacing: -0.5px;
            line-height: 1.1;
            color: var(--text);
        }

        .event-hero-meta {
            margin-top: 7px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .event-hero-time {
            font-size: 12px;
            color: var(--muted2);
            font-weight: 300;
        }

        /* Status tags */
        .status-tag {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 100px;
            font-size: 11px;
            font-weight: 500;
            flex-shrink: 0;
        }

        .tag-live   { background: rgba(34,197,94,0.12);  color: #86efac;       border: 1px solid rgba(34,197,94,0.25); }
        .tag-soon   { background: rgba(255,140,66,0.10); color: var(--accent2); border: 1px solid rgba(255,140,66,0.2); }
        .tag-sold   { background: rgba(239,68,68,0.08);  color: #fca5a5;        border: 1px solid rgba(239,68,68,0.15); }
        .tag-locked { background: rgba(255,255,255,0.05);color: var(--muted2);  border: 1px solid var(--border); }

        /* ─── Items row ─── */
        .items-row {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            border: 1px solid var(--border);
            border-top: none;
            border-radius: 0 0 16px 16px;
            overflow: hidden;
            background: var(--border);
            gap: 1px;
        }

        /* ─── Item card ─── */
        .item-card {
            background: var(--surface);
            padding: 1.35rem 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
            transition: background 0.2s;
        }

        .item-card:hover { background: var(--surface2); }

        .item-name {
            font-family: 'Syne', sans-serif;
            font-size: 16px;
            font-weight: 800;
            letter-spacing: -0.2px;
            line-height: 1.2;
        }

        .card-divider { height: 1px; background: var(--border); }

        .card-meta {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .price-label, .stock-label {
            font-size: 10px;
            color: var(--muted);
            font-weight: 300;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }

        .price {
            font-family: 'Syne', sans-serif;
            font-size: 20px;
            font-weight: 800;
        }

        .stock-wrap { text-align: right; }

        .stock-num {
            font-family: 'Syne', sans-serif;
            font-size: 18px;
            font-weight: 800;
        }

        .stock-num.low  { color: var(--accent); }
        .stock-num.ok   { color: #86efac; }
        .stock-num.none { color: var(--muted); }

        .stock-bar-track {
            height: 3px;
            background: rgba(255,255,255,0.06);
            border-radius: 100px;
            margin-top: 6px;
            width: 64px;
            margin-left: auto;
        }

        .stock-bar-fill {
            height: 100%;
            border-radius: 100px;
            transition: width 0.5s;
        }

        .countdown-box {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 9px 12px;
            background: rgba(255,140,66,0.05);
            border: 1px solid rgba(255,140,66,0.15);
            border-radius: 8px;
            color: var(--accent2);
        }

        .countdown-sub {
            font-size: 10px;
            color: var(--muted2);
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .countdown-val {
            font-family: 'Syne', sans-serif;
            font-size: 14px;
            font-weight: 800;
        }

        /* ─── Buttons ─── */
        .btn-buy {
            width: 100%;
            padding: 0.7rem 1rem;
            background: var(--accent);
            border: none;
            border-radius: 9px;
            color: #fff;
            font-family: 'Syne', sans-serif;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.2px;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: auto;
        }

        .btn-buy:hover:not(:disabled) {
            background: #ff6635;
            box-shadow: 0 4px 18px rgba(255,77,28,0.35);
            transform: translateY(-1px);
        }

        .btn-buy:disabled {
            background: rgba(255,255,255,0.04);
            color: var(--muted);
            cursor: not-allowed;
        }

        .btn-soldout {
            width: 100%;
            padding: 0.7rem 1rem;
            background: rgba(239,68,68,0.05);
            border: 1px solid rgba(239,68,68,0.15);
            border-radius: 9px;
            color: #fca5a5;
            font-family: 'Syne', sans-serif;
            font-size: 13px;
            font-weight: 700;
            cursor: not-allowed;
            margin-top: auto;
        }

        /* Already-in-cart state */
        .btn-incart {
            width: 100%;
            padding: 0.7rem 1rem;
            background: rgba(34,197,94,0.06);
            border: 1px solid rgba(34,197,94,0.2);
            border-radius: 9px;
            color: #86efac;
            font-family: 'Syne', sans-serif;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            margin-top: auto;
            transition: all 0.2s;
            text-decoration: none;
            display: block;
            text-align: center;
        }

        .btn-incart:hover {
            background: rgba(34,197,94,0.12);
            border-color: rgba(34,197,94,0.35);
        }

        /* Empty state */
        .empty-state {
            padding: 5rem 2rem;
            text-align: center;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
        }

        .empty-state .empty-icon { font-size: 40px; margin-bottom: 1rem; }
        .empty-state h3 { font-family: 'Syne', sans-serif; font-size: 18px; font-weight: 800; margin-bottom: 0.5rem; }
        .empty-state p { color: var(--muted2); font-size: 14px; font-weight: 300; }

        /* Toast */
        #toast {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 999;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            max-width: 320px;
            opacity: 0;
            transform: translateY(8px);
            transition: all 0.3s;
            pointer-events: none;
        }

        #toast.show { opacity: 1; transform: translateY(0); }
        #toast.success { background: rgba(34,197,94,0.15); border: 1px solid rgba(34,197,94,0.3); color: #86efac; }
        #toast.error   { background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.25); color: #fca5a5; }

        @media (max-width: 640px) {
            nav { padding: 1rem 1.25rem; }
            main { padding: 1.5rem 1rem 4rem; }
            .nav-logo-name { display: none; }
            .event-hero { aspect-ratio: 16 / 7; }
            .event-hero-overlay { padding: 1rem 1.25rem; }
        }
    </style>
</head>
<body>

<!-- ─── NAV ─── -->
<nav>
    <a href="index.php" class="nav-logo">
        <div class="nav-logo-icon">⚡</div>
        <span class="nav-logo-name">SwiftDrop</span>
    </a>
    <div class="nav-right">
        <!-- Cart button with item count badge -->
        <a href="cart.php" class="nav-cart">
            🛒 Cart
            <span class="cart-badge" id="cartBadge" data-count="<?= $cartCount ?>">
                <?= $cartCount > 0 ? $cartCount : '' ?>
            </span>
        </a>

        <div class="nav-user" onclick="toggleUserMenu()">
            <div class="nav-avatar"><?= htmlspecialchars($initials) ?></div>
            <span class="nav-username"><?= htmlspecialchars($user['name'] ?? $user['username']) ?></span>
        </div>
        <div class="user-menu" id="userMenu">
            <a href="change_password.php">Change Password</a>
            <a href="cart.php">View Cart</a>
        </div>
        <a href="logout.php" class="nav-logout">Sign Out</a>
    </div>
</nav>

<!-- ─── MAIN ─── -->
<main>
    <div class="page-header">
        <div>
            <h1 class="page-title">Marketplace <span>Drops</span></h1>
            <p class="page-sub">
                <?= count($events) ?> event<?= count($events) !== 1 ? 's' : '' ?> ·
                <?= array_sum(array_map(fn($e) => count($e['items']), $events)) ?> items listed ·
                Stock refreshes every 2s
            </p>
        </div>
        <div class="live-badge">
            <div class="live-dot"></div>
            Live now
        </div>
    </div>

    <?php if (empty($events)): ?>
        <div class="empty-state">
            <div class="empty-icon">📦</div>
            <h3>No drops right now</h3>
            <p>Check back soon — new events drop regularly.</p>
        </div>
    <?php endif; ?>

    <?php foreach ($events as $event):
        $goLive     = new DateTime($event['go_live_at']);
        $isLive     = $goLive <= $now;
        $isEnded    = $event['event_status'] === 'ended';
        $alreadyHas = isset($takenEvents[$event['event_id']]);

        $cover = !empty($event['cover_image'])
                    ? '../uploads/events/' . htmlspecialchars($event['cover_image'])
                    : null;

        if (!$isLive) {
            $eventTagHtml = '<div class="status-tag tag-soon">Upcoming</div>';
        } elseif ($isEnded) {
            $eventTagHtml = '<div class="status-tag tag-sold">Ended</div>';
        } else {
            $eventTagHtml = '<div class="status-tag tag-live"><div class="live-dot"></div> Live</div>';
        }
    ?>
    <div class="event-block">

        <!-- Hero -->
        <div class="event-hero">
            <?php if ($cover): ?>
                <img src="<?= $cover ?>" alt="<?= htmlspecialchars($event['event_name']) ?>">
            <?php else: ?>
                <div class="event-hero-placeholder">⚡</div>
            <?php endif; ?>

            <div class="event-hero-overlay">
                <div class="event-hero-label">Flash Drop</div>
                <div class="event-hero-title"><?= htmlspecialchars($event['event_name']) ?></div>
                <div class="event-hero-meta">
                    <?= $eventTagHtml ?>
                    <?php if (!$isLive): ?>
                        <span class="event-hero-time">
                            Goes live <?= (new DateTime($event['go_live_at']))->format('M j, Y · g:i A') ?>
                        </span>
                    <?php else: ?>
                        <span class="event-hero-time">
                            <?= count($event['items']) ?> item<?= count($event['items']) !== 1 ? 's' : '' ?> available
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Items -->
        <div class="items-row">
            <?php foreach ($event['items'] as $item):

                $stock      = (int) $item['remaining_stock'];
                $stockClass = $stock === 0 ? 'none' : ($stock <= 10 ? 'low' : 'ok');
                $barPct     = min(100, max(0, $stock));
                $barColor   = $stock === 0 ? 'var(--muted)' : ($stock <= 10 ? 'var(--accent)' : '#22c55e');

                // ITEM STATES
                $itemId = $item['item_id'];

                $thisItemTaken  = isset($takenItems[$itemId]);   // in cart
                $thisItemBought  = isset($boughtItems[$itemId]); // already purchased
            ?>

            <div class="item-card">
                <div class="item-name"><?= htmlspecialchars($item['item_name']) ?></div>
                <div class="card-divider"></div>

                <?php if (!$isLive): ?>
                    <div class="countdown-box">
                        <span>⏱</span>
                        <div>
                            <div class="countdown-sub">Starts in</div>
                            <div class="countdown-val countdown"
                                data-time="<?= $event['go_live_at'] ?>">--h --m --s</div>
                        </div>
                    </div>

                <?php else: ?>
                    <div class="card-meta">
                        <div>
                            <div class="price-label">Price</div>
                            <div class="price" <?= $stock === 0 ? 'style="color:var(--muted)"' : '' ?>>
                                Rs <?= number_format($item['price']) ?>
                            </div>
                        </div>

                        <div class="stock-wrap">
                            <div class="stock-label">Remaining</div>
                            <div class="stock-num <?= $stockClass ?>"
                                id="stock-<?= $itemId ?>">
                                <?= $stock ?>
                            </div>

                            <div class="stock-bar-track">
                                <div class="stock-bar-fill"
                                    style="width:<?= $barPct ?>%;background:<?= $barColor ?>"></div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!$isLive): ?>

                    <button class="btn-buy" disabled>Starts Soon</button>

                <?php elseif ($thisItemBought): ?>

                    <button class="btn-soldout" disabled>Already Bought</button>

                <?php elseif ($thisItemTaken): ?>

                    <a href="cart.php" class="btn-incart">✓ In Cart — View Cart →</a>

                <?php elseif ($stock > 0): ?>

                    <button class="btn-buy" onclick="buyItem(<?= $itemId ?>, this)">
                        Add to Cart →
                    </button>

                <?php else: ?>

                    <button class="btn-soldout" disabled>Sold Out</button>

                <?php endif; ?>

            </div>

            <?php endforeach; ?>
        </div>

    </div>
    <?php endforeach; ?>

</main>

<div id="toast"></div>

<script>
async function buyItem(itemId, button) {
    button.disabled    = true;
    button.textContent = 'Adding…';

    const formData = new FormData();
    formData.append('item_id', itemId);

    try {
        const res  = await fetch('purchase.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            showToast(data.message, 'success');
            // Update cart badge count
            const badge = document.getElementById('cartBadge');
            const cur   = parseInt(badge.dataset.count || '0', 10);
            const next  = cur + 1;
            badge.dataset.count = next;
            badge.textContent   = next;

            // Redirect to cart after brief moment
            setTimeout(() => { window.location.href = 'cart.php'; }, 900);
        } else {
            showToast(data.message, 'error');
            button.disabled    = false;
            button.textContent = 'Add to Cart →';
        }
    } catch (e) {
        showToast('Something went wrong. Try again.', 'error');
        button.disabled    = false;
        button.textContent = 'Add to Cart →';
    }
}

// Live stock poll
async function loadStocks() {
    try {
        const res  = await fetch('stock.php');
        const data = await res.json();
        data.forEach(item => {
            const el = document.getElementById('stock-' + item.id);
            if (el) el.textContent = item.remaining_stock;
        });
    } catch (e) { /* silent */ }
}
setInterval(loadStocks, 2000);

// Countdowns
function startCountdowns() {
    document.querySelectorAll('.countdown').forEach(el => {
        const target = new Date(el.dataset.time).getTime();
        function update() {
            const diff = target - Date.now();
            if (diff <= 0) {
                el.textContent = 'LIVE NOW 🔥';
                setTimeout(() => location.reload(), 1500);
                return;
            }
            const h = Math.floor(diff / 3600000);
            const m = Math.floor((diff % 3600000) / 60000);
            const s = Math.floor((diff % 60000) / 1000);
            el.textContent =
                String(h).padStart(2,'0') + 'h ' +
                String(m).padStart(2,'0') + 'm ' +
                String(s).padStart(2,'0') + 's';
        }
        update();
        setInterval(update, 1000);
    });
}
startCountdowns();

function toggleUserMenu() {
    document.getElementById('userMenu').classList.toggle('show');
}

document.addEventListener('click', function(e) {
    const menu = document.getElementById('userMenu');
    const user = document.querySelector('.nav-user');
    if (!user.contains(e.target) && !menu.contains(e.target)) {
        menu.classList.remove('show');
    }
});

let toastTimer;
function showToast(msg, type = 'success') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className   = 'show ' + type;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { t.className = ''; }, 3500);
}
</script>
</body>
</html>
