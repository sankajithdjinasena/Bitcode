<?php
require_once "../app/middleware/auth.php";
require_once "../config/database.php";

$stmt = $conn->query("
    SELECT 
        events.id as event_id,
        events.name as event_name,
        events.go_live_at,
        events.status,
        items.id as item_id,
        items.name as item_name,
        items.price,
        items.remaining_stock
    FROM events
    JOIN items ON items.event_id = events.id
");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

date_default_timezone_set('Asia/Colombo');
$now = new DateTime('now', new DateTimeZone('Asia/Colombo'));

// Current user from session
$user = $_SESSION['user'];
$initials = strtoupper(substr($user['name'] ?? $user['username'], 0, 1));
if (strpos($user['name'] ?? '', ' ') !== false) {
    $parts = explode(' ', $user['name']);
    $initials = strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketplace — SwiftDrop</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
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
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .nav-user {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 6px 14px 6px 8px;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            border-radius: 10px;
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

        .nav-username {
            font-size: 13px;
            font-weight: 500;
            color: var(--text);
        }

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
            max-width: 1100px;
            margin: 0 auto;
            padding: 2.5rem 2rem 5rem;
        }

        /* ─── Page header ─── */
        .page-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 2rem;
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

        /* ─── Card grid ─── */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1px;
            background: var(--border);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
        }

        .card {
            background: var(--surface);
            padding: 1.75rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            transition: background 0.2s;
        }

        .card:hover { background: var(--surface2); }

        /* Card header */
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
        }

        .event-name {
            font-size: 10px;
            font-weight: 500;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--muted2);
            margin-bottom: 5px;
        }

        .item-name {
            font-family: 'Syne', sans-serif;
            font-size: 17px;
            font-weight: 800;
            letter-spacing: -0.3px;
            line-height: 1.2;
        }

        /* Status tags */
        .status-tag {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 100px;
            font-size: 11px;
            font-weight: 500;
            flex-shrink: 0;
        }

        .tag-live { background: rgba(34,197,94,0.1); color: #86efac; border: 1px solid rgba(34,197,94,0.2); }
        .tag-soon { background: rgba(255,140,66,0.1); color: var(--accent2); border: 1px solid rgba(255,140,66,0.2); }
        .tag-sold { background: rgba(239,68,68,0.08); color: #fca5a5; border: 1px solid rgba(239,68,68,0.15); }

        /* Divider */
        .card-divider { height: 1px; background: var(--border); }

        /* Price + stock row */
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
            font-size: 22px;
            font-weight: 800;
        }

        .stock-wrap { text-align: right; }

        .stock-num {
            font-family: 'Syne', sans-serif;
            font-size: 20px;
            font-weight: 800;
        }

        .stock-num.low { color: var(--accent); }
        .stock-num.ok  { color: #86efac; }
        .stock-num.none { color: var(--muted); }

        .stock-bar-track {
            height: 3px;
            background: rgba(255,255,255,0.06);
            border-radius: 100px;
            margin-top: 6px;
            width: 80px;
            margin-left: auto;
        }

        .stock-bar-fill {
            height: 100%;
            border-radius: 100px;
            transition: width 0.5s;
        }

        /* Countdown box */
        .countdown-box {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            background: rgba(255,140,66,0.05);
            border: 1px solid rgba(255,140,66,0.15);
            border-radius: 8px;
            font-size: 12px;
            color: var(--accent2);
        }

        .countdown-icon { font-size: 16px; }

        .countdown-sub {
            font-size: 10px;
            color: var(--muted2);
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .countdown-val {
            font-family: 'Syne', sans-serif;
            font-size: 15px;
            font-weight: 800;
        }

        /* Buttons */
        .btn-buy {
            width: 100%;
            padding: 0.75rem 1.25rem;
            background: var(--accent);
            border: none;
            border-radius: 10px;
            color: #fff;
            font-family: 'Syne', sans-serif;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.2px;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: auto;
        }

        .btn-buy:hover:not(:disabled) {
            background: #ff6635;
            box-shadow: 0 4px 20px rgba(255,77,28,0.35);
            transform: translateY(-1px);
        }

        .btn-buy:disabled {
            background: rgba(255,255,255,0.04);
            color: var(--muted);
            cursor: not-allowed;
            box-shadow: none;
            transform: none;
        }

        .btn-soldout {
            width: 100%;
            padding: 0.75rem 1.25rem;
            background: rgba(239,68,68,0.05);
            border: 1px solid rgba(239,68,68,0.15);
            border-radius: 10px;
            color: #fca5a5;
            font-family: 'Syne', sans-serif;
            font-size: 14px;
            font-weight: 700;
            cursor: not-allowed;
            margin-top: auto;
        }

        /* Empty state */
        .empty {
            grid-column: 1/-1;
            padding: 5rem 2rem;
            text-align: center;
            background: var(--surface);
        }

        .empty-icon { font-size: 40px; margin-bottom: 1rem; }
        .empty h3 { font-family: 'Syne', sans-serif; font-size: 18px; font-weight: 800; margin-bottom: 0.5rem; }
        .empty p { color: var(--muted2); font-size: 14px; font-weight: 300; }

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
        }

        .nav-right {
            position: relative;
        }

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

        .user-menu.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .user-menu a {
            display: block;
            padding: 12px 16px;
            text-decoration: none;
            color: var(--text);
            font-size: 14px;
            transition: background 0.2s;
        }

        .user-menu a:hover {
            background: rgba(255,255,255,0.05);
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

    <div class="nav-user" onclick="toggleUserMenu()">
        <div class="nav-avatar"><?= htmlspecialchars($initials) ?></div>
        <span class="nav-username">
            <?= htmlspecialchars($user['name'] ?? $user['username']) ?>
        </span>
    </div>

    <div class="user-menu" id="userMenu">
        <a href="change_password.php">Change Password</a>
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
                <?= count($items) ?> item<?= count($items) !== 1 ? 's' : '' ?> listed · Stock refreshes every 2s
            </p>
        </div>
        <div class="live-badge">
            <div class="live-dot"></div>
            Live now
        </div>
    </div>

    <div class="grid">

        <?php if (empty($items)): ?>
            <div class="empty">
                <div class="empty-icon">📦</div>
                <h3>No drops right now</h3>
                <p>Check back soon — new events drop regularly.</p>
            </div>
        <?php endif; ?>

        <?php foreach ($items as $item):
            $goLive = new DateTime($item['go_live_at']);
            $isLive = $goLive <= $now;
            $stock  = (int) $item['remaining_stock'];

            // Stock colour class
            if ($stock === 0)    $stockClass = 'none';
            elseif ($stock <= 10) $stockClass = 'low';
            else                  $stockClass = 'ok';

            // Stock bar width (assume 100 = full)
            $barPct  = min(100, max(0, $stock));
            $barColor = $stock === 0 ? 'var(--muted)' : ($stock <= 10 ? 'var(--accent)' : '#22c55e');
        ?>
        <div class="card">

            <!-- Header -->
            <div class="card-header">
                <div>
                    <div class="event-name"><?= htmlspecialchars($item['event_name']) ?></div>
                    <div class="item-name"><?= htmlspecialchars($item['item_name']) ?></div>
                </div>
                <?php if (!$isLive): ?>
                    <div class="status-tag tag-soon">Upcoming</div>
                <?php elseif ($stock > 0): ?>
                    <div class="status-tag tag-live"><div class="live-dot"></div> Live</div>
                <?php else: ?>
                    <div class="status-tag tag-sold">Sold Out</div>
                <?php endif; ?>
            </div>

            <div class="card-divider"></div>

            <!-- Countdown or Price+Stock -->
            <?php if (!$isLive): ?>
                <div class="countdown-box">
                    <span class="countdown-icon">⏱</span>
                    <div>
                        <div class="countdown-sub">Starts in</div>
                        <div class="countdown-val countdown"
                             data-time="<?= $item['go_live_at'] ?>">--h --m --s</div>
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
                             id="stock-<?= $item['item_id'] ?>">
                            <?= $stock ?>
                        </div>
                        <div class="stock-bar-track">
                            <div class="stock-bar-fill"
                                 style="width:<?= $barPct ?>%;background:<?= $barColor ?>"></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Action button -->
            <?php if (!$isLive): ?>
                <button class="btn-buy" disabled>Starts Soon</button>
            <?php elseif ($stock > 0): ?>
                <button class="btn-buy" onclick="buyItem(<?= $item['item_id'] ?>, this)">
                    Buy Now →
                </button>
            <?php else: ?>
                <button class="btn-soldout" disabled>Sold Out</button>
            <?php endif; ?>

        </div>
        <?php endforeach; ?>

    </div>
</main>

<!-- Toast notification -->
<div id="toast"></div>

<script>
/* ─── Buy ─── */
async function buyItem(itemId, button) {
    button.disabled = true;
    button.textContent = 'Processing…';

    const formData = new FormData();
    formData.append('item_id', itemId);

    try {
        const response = await fetch('purchase.php', { method: 'POST', body: formData });
        const data = await response.json();
        showToast(data.message, data.success ? 'success' : 'error');
    } catch (e) {
        showToast('Something went wrong. Try again.', 'error');
    }

    button.disabled = false;
    button.textContent = 'Buy Now →';
    loadStocks();
}

/* ─── Stock polling ─── */
async function loadStocks() {
    try {
        const response = await fetch('stock.php');
        const data = await response.json();
        data.forEach(item => {
            const el = document.getElementById('stock-' + item.id);
            if (el) el.textContent = item.remaining_stock;
        });
    } catch (e) { /* silent fail */ }
}

setInterval(loadStocks, 2000);

/* ─── Countdown ─── */
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

/* ─── Toast ─── */
let toastTimer;
function showToast(msg, type = 'success') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'show ' + type;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { t.className = ''; }, 3500);
}

/* ─── User menu ─── */
function toggleUserMenu() {
    document.getElementById('userMenu').classList.toggle('show');
}

/* Close when clicking outside */
document.addEventListener('click', function(e) {
    const menu = document.getElementById('userMenu');
    const user = document.querySelector('.nav-user');

    if (!user.contains(e.target) && !menu.contains(e.target)) {
        menu.classList.remove('show');
    }
});
</script>
</body>
</html>