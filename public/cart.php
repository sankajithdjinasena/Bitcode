<?php
require_once "../app/middleware/auth.php";
require_once "../config/database.php";

$userId = (int) $_SESSION['user']['id'];

// Fetch this user's cart with item + event info
$stmt = $conn->prepare("
    SELECT
        c.id           AS cart_id,
        c.price        AS cart_price,
        c.added_at,
        i.id           AS item_id,
        i.name         AS item_name,
        e.id           AS event_id,
        e.name         AS event_name,
        e.cover_image,
        e.go_live_at,
        e.status       AS event_status
    FROM   cart c
    JOIN   items  i ON i.id  = c.item_id
    JOIN   events e ON e.id  = c.event_id
    WHERE  c.user_id = ?
    ORDER  BY c.added_at DESC
");
$stmt->execute([$userId]);
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = array_sum(array_column($cartItems, 'cart_price'));

// Current user display
$user     = $_SESSION['user'];
$initials = strtoupper(substr($user['name'] ?? $user['username'], 0, 1));
if (strpos($user['name'] ?? '', ' ') !== false) {
    $parts    = explode(' ', $user['name']);
    $initials = strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart — SwiftDrop</title>
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

        .nav-back {
            padding: 7px 16px;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            border-radius: 9px;
            color: var(--text);
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
        }

        .nav-back:hover { background: rgba(255,255,255,0.08); }

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
            color: #ff6035;
        }

        /* ─── Main ─── */
        main {
            position: relative;
            z-index: 1;
            max-width: 1000px;
            margin: 0 auto;
            padding: 2.5rem 2rem 6rem;
        }

        /* ─── Page header ─── */
        .page-header {
            margin-bottom: 2.5rem;
        }

        .page-title {
            font-family: 'Syne', sans-serif;
            font-size: clamp(1.75rem, 4vw, 2.4rem);
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

        /* ─── Layout: cart list + order summary side by side ─── */
        .cart-layout {
            display: grid;
            grid-template-columns: 1fr 360px;
            gap: 1.5rem;
            align-items: start;
        }

        /* ─── Cart item card ─── */
        .cart-item {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 1.4rem 1.6rem;
            display: flex;
            align-items: center;
            gap: 1.2rem;
            margin-bottom: 1rem;
            transition: border-color 0.2s;
        }

        .cart-item:hover { border-color: rgba(255,77,28,0.2); }

        .cart-item-thumb {
            width: 72px;
            height: 72px;
            border-radius: 10px;
            object-fit: cover;
            background: var(--surface2);
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: var(--muted);
            overflow: hidden;
        }

        .cart-item-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .cart-item-info { flex: 1; min-width: 0; }

        .cart-item-event {
            font-size: 10px;
            font-weight: 500;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--accent2);
            margin-bottom: 3px;
        }

        .cart-item-name {
            font-family: 'Syne', sans-serif;
            font-size: 17px;
            font-weight: 800;
            letter-spacing: -0.2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .cart-item-date {
            font-size: 12px;
            color: var(--muted2);
            font-weight: 300;
            margin-top: 3px;
        }

        .cart-item-price {
            font-family: 'Syne', sans-serif;
            font-size: 22px;
            font-weight: 800;
            flex-shrink: 0;
        }

        .cart-item-remove {
            background: none;
            border: none;
            color: var(--muted);
            cursor: pointer;
            padding: 6px;
            border-radius: 6px;
            font-size: 16px;
            transition: all 0.2s;
            flex-shrink: 0;
        }

        .cart-item-remove:hover { color: #fca5a5; background: rgba(239,68,68,0.08); }

        /* ─── Empty cart ─── */
        .empty-cart {
            padding: 5rem 2rem;
            text-align: center;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
        }

        .empty-cart .empty-icon { font-size: 44px; margin-bottom: 1rem; }

        .empty-cart h3 {
            font-family: 'Syne', sans-serif;
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .empty-cart p { color: var(--muted2); font-size: 14px; margin-bottom: 1.5rem; }

        .btn-browse {
            display: inline-block;
            padding: 0.75rem 2rem;
            background: var(--accent);
            border-radius: 10px;
            color: #fff;
            font-family: 'Syne', sans-serif;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-browse:hover {
            background: #ff6635;
            box-shadow: 0 4px 18px rgba(255,77,28,0.35);
            transform: translateY(-1px);
        }

        /* ─── Order summary / payment panel ─── */
        .order-panel {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.6rem;
            position: sticky;
            top: 84px;
        }

        .panel-title {
            font-family: 'Syne', sans-serif;
            font-size: 16px;
            font-weight: 800;
            letter-spacing: -0.2px;
            margin-bottom: 1.2rem;
        }

        /* Summary lines */
        .summary-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            color: var(--muted2);
            padding: 5px 0;
        }

        .summary-line.total {
            font-size: 16px;
            color: var(--text);
            font-weight: 500;
            border-top: 1px solid var(--border);
            margin-top: 8px;
            padding-top: 14px;
        }

        .summary-line.total .sum-val {
            font-family: 'Syne', sans-serif;
            font-size: 22px;
            font-weight: 800;
            color: var(--accent);
        }

        .panel-divider {
            height: 1px;
            background: var(--border);
            margin: 1.3rem 0;
        }

        /* ─── Payment method selector ─── */
        .pm-label {
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--muted2);
            margin-bottom: 10px;
        }

        .pm-options { display: flex; flex-direction: column; gap: 8px; }

        .pm-option {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 11px 14px;
            border: 1px solid var(--border);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
            user-select: none;
        }

        .pm-option:hover { border-color: rgba(255,77,28,0.3); background: rgba(255,77,28,0.03); }

        .pm-option.selected {
            border-color: var(--accent);
            background: rgba(255,77,28,0.07);
        }

        .pm-radio {
            width: 16px; height: 16px;
            border-radius: 50%;
            border: 2px solid var(--muted);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            transition: border-color 0.2s;
        }

        .pm-option.selected .pm-radio { border-color: var(--accent); }

        .pm-radio-dot {
            width: 7px; height: 7px;
            border-radius: 50%;
            background: var(--accent);
            display: none;
        }

        .pm-option.selected .pm-radio-dot { display: block; }

        .pm-icon { font-size: 20px; }

        .pm-text { flex: 1; }
        .pm-name { font-size: 13px; font-weight: 500; }
        .pm-desc { font-size: 11px; color: var(--muted2); }

        /* Card details form */
        .card-form {
            display: none;
            flex-direction: column;
            gap: 10px;
            margin-top: 12px;
            padding: 14px;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 10px;
        }

        .card-form.visible { display: flex; }

        .form-row { display: flex; gap: 10px; }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
            flex: 1;
        }

        .form-group label {
            font-size: 11px;
            color: var(--muted2);
            font-weight: 500;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .form-group input {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 9px 12px;
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            width: 100%;
            transition: border-color 0.2s;
            outline: none;
        }

        .form-group input::placeholder { color: var(--muted); }

        .form-group input:focus { border-color: rgba(255,77,28,0.4); }

        .bank-details {
            display: none;
            padding: 14px;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 10px;
            margin-top: 12px;
            font-size: 13px;
            line-height: 1.8;
            color: var(--muted2);
        }

        .bank-details.visible { display: block; }
        .bank-details strong { color: var(--text); }

        /* Pay button */
        .btn-pay {
            width: 100%;
            padding: 0.85rem 1rem;
            background: var(--accent);
            border: none;
            border-radius: 11px;
            color: #fff;
            font-family: 'Syne', sans-serif;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.2px;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 1.2rem;
        }

        .btn-pay:hover:not(:disabled) {
            background: #ff6635;
            box-shadow: 0 6px 24px rgba(255,77,28,0.38);
            transform: translateY(-1px);
        }

        .btn-pay:disabled {
            background: rgba(255,255,255,0.05);
            color: var(--muted);
            cursor: not-allowed;
        }

        .secure-note {
            margin-top: 10px;
            text-align: center;
            font-size: 11px;
            color: var(--muted);
        }

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

        /* Confirmation overlay */
        .confirm-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(7,7,13,0.85);
            backdrop-filter: blur(8px);
            z-index: 300;
            align-items: center;
            justify-content: center;
        }

        .confirm-overlay.show { display: flex; }

        .confirm-modal {
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 2.5rem;
            max-width: 420px;
            width: 90%;
            text-align: center;
        }

        .confirm-icon { font-size: 52px; margin-bottom: 1rem; }

        .confirm-title {
            font-family: 'Syne', sans-serif;
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 0.5rem;
        }

        .confirm-sub {
            color: var(--muted2);
            font-size: 14px;
            margin-bottom: 1.5rem;
        }

        .confirm-ref {
            display: inline-block;
            padding: 8px 20px;
            background: rgba(255,77,28,0.08);
            border: 1px solid rgba(255,77,28,0.2);
            border-radius: 8px;
            font-family: 'Syne', sans-serif;
            font-size: 16px;
            font-weight: 700;
            color: var(--accent2);
            margin-bottom: 1.5rem;
            letter-spacing: 1px;
        }

        .btn-done {
            display: inline-block;
            padding: 0.75rem 2.5rem;
            background: var(--accent);
            border-radius: 10px;
            color: #fff;
            font-family: 'Syne', sans-serif;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }

        .btn-done:hover { background: #ff6635; }

        @media (max-width: 720px) {
            .cart-layout { grid-template-columns: 1fr; }
            .order-panel { position: static; }
            nav { padding: 1rem 1.25rem; }
            main { padding: 1.5rem 1rem 4rem; }
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
        <a href="index.php" class="nav-back">← Marketplace</a>
        <a href="logout.php" class="nav-logout">Sign Out</a>
    </div>
</nav>

<!-- ─── MAIN ─── -->
<main>
    <div class="page-header">
        <h1 class="page-title">Your <span>Cart</span></h1>
        <p class="page-sub">
            <?= count($cartItems) ?> item<?= count($cartItems) !== 1 ? 's' : '' ?> ·
            Review and complete your purchase
        </p>
    </div>

    <?php if (empty($cartItems)): ?>
        <div class="empty-cart">
            <div class="empty-icon">🛒</div>
            <h3>Your cart is empty</h3>
            <p>Head back to the marketplace and grab something before it sells out.</p>
            <a href="index.php" class="btn-browse">Browse Drops →</a>
        </div>

    <?php else: ?>
    <div class="cart-layout">

        <!-- Left: cart items -->
        <div class="cart-list">
            <?php foreach ($cartItems as $ci):
                $cover = !empty($ci['cover_image'])
                    ? '../uploads/events/' . htmlspecialchars($ci['cover_image'])
                    : null;
            ?>
            <div class="cart-item" id="cart-row-<?= $ci['cart_id'] ?>">
                <div class="cart-item-thumb">
                    <?php if ($cover): ?>
                        <img src="<?= $cover ?>" alt="<?= htmlspecialchars($ci['event_name']) ?>">
                    <?php else: ?>
                        ⚡
                    <?php endif; ?>
                </div>
                <div class="cart-item-info">
                    <div class="cart-item-event"><?= htmlspecialchars($ci['event_name']) ?></div>
                    <div class="cart-item-name"><?= htmlspecialchars($ci['item_name']) ?></div>
                    <div class="cart-item-date">
                        Added <?= (new DateTime($ci['added_at']))->format('M j, g:i A') ?>
                    </div>
                </div>
                <div class="cart-item-price">Rs <?= number_format($ci['cart_price']) ?></div>
                <button class="cart-item-remove"
                        onclick="removeItem(<?= $ci['cart_id'] ?>, <?= $ci['item_id'] ?>)"
                        title="Remove">✕</button>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Right: order summary + payment -->
        <div class="order-panel">
            <div class="panel-title">Order Summary</div>

            <?php foreach ($cartItems as $ci): ?>
            <div class="summary-line">
                <span><?= htmlspecialchars($ci['item_name']) ?></span>
                <span>Rs <?= number_format($ci['cart_price']) ?></span>
            </div>
            <?php endforeach; ?>

            <div class="summary-line total">
                <span>Total</span>
                <span class="sum-val" id="totalDisplay">Rs <?= number_format($total) ?></span>
            </div>

            <div class="panel-divider"></div>

            <!-- Payment method -->
            <div class="pm-label">Payment Method</div>
            <div class="pm-options">

                <div class="pm-option selected" data-method="card" onclick="selectMethod(this)">
                    <div class="pm-radio"><div class="pm-radio-dot"></div></div>
                    <div class="pm-icon">💳</div>
                    <div class="pm-text">
                        <div class="pm-name">Credit / Debit Card</div>
                        <div class="pm-desc">Visa, Mastercard, Amex</div>
                    </div>
                </div>

                <div class="pm-option" data-method="bank_transfer" onclick="selectMethod(this)">
                    <div class="pm-radio"><div class="pm-radio-dot"></div></div>
                    <div class="pm-icon">🏦</div>
                    <div class="pm-text">
                        <div class="pm-name">Bank Transfer</div>
                        <div class="pm-desc">Direct bank deposit</div>
                    </div>
                </div>

                <div class="pm-option" data-method="cash_on_delivery" onclick="selectMethod(this)">
                    <div class="pm-radio"><div class="pm-radio-dot"></div></div>
                    <div class="pm-icon">💵</div>
                    <div class="pm-text">
                        <div class="pm-name">Cash on Delivery</div>
                        <div class="pm-desc">Pay when received</div>
                    </div>
                </div>

            </div>

            <!-- Card form -->
            <div class="card-form visible" id="cardForm">
                <div class="form-group">
                    <label>Card Number</label>
                    <input type="text" id="cardNumber" placeholder="1234 5678 9012 3456"
                           maxlength="19" oninput="formatCard(this)">
                </div>
                <div class="form-group">
                    <label>Cardholder Name</label>
                    <input type="text" id="cardName" placeholder="Full name on card">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Expiry</label>
                        <input type="text" id="cardExpiry" placeholder="MM / YY"
                               maxlength="7" oninput="formatExpiry(this)">
                    </div>
                    <div class="form-group">
                        <label>CVV</label>
                        <input type="text" id="cardCvv" placeholder="•••" maxlength="4">
                    </div>
                </div>
            </div>

            <!-- Bank transfer details -->
            <div class="bank-details" id="bankDetails">
                Transfer to:<br>
                <strong>Bank:</strong> Bank of Ceylon<br>
                <strong>Account:</strong> 0123456789<br>
                <strong>Branch:</strong> Colombo Main<br>
                <strong>Reference:</strong> Your username / email<br><br>
                Upload proof after checkout — we'll confirm within 24 hrs.
            </div>

            <button class="btn-pay" id="payBtn" onclick="submitPayment()">
                Pay Rs <?= number_format($total) ?> →
            </button>

            <div class="secure-note">🔒 Your payment details are encrypted and secure</div>
        </div>

    </div>
    <?php endif; ?>
</main>

<!-- ─── Confirmation modal ─── -->
<div class="confirm-overlay" id="confirmOverlay">
    <div class="confirm-modal">
        <div class="confirm-icon">🎉</div>
        <div class="confirm-title">Order Confirmed!</div>
        <div class="confirm-sub">Your purchase was successful. Here's your reference number:</div>
        <div class="confirm-ref" id="confirmRef">—</div>
        <div class="confirm-sub">We'll send details to your email shortly.</div>
        <button class="btn-done" onclick="window.location.href='index.php'">Back to Marketplace</button>
    </div>
</div>

<div id="toast"></div>

<script>
let selectedMethod = 'card';

function selectMethod(el) {
    document.querySelectorAll('.pm-option').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');
    selectedMethod = el.dataset.method;

    document.getElementById('cardForm').classList.toggle('visible',    selectedMethod === 'card');
    document.getElementById('bankDetails').classList.toggle('visible', selectedMethod === 'bank_transfer');
}

function formatCard(el) {
    let v = el.value.replace(/\D/g,'').slice(0,16);
    el.value = v.replace(/(.{4})/g,'$1 ').trim();
}

function formatExpiry(el) {
    let v = el.value.replace(/\D/g,'').slice(0,4);
    if (v.length > 2) v = v.slice(0,2) + ' / ' + v.slice(2);
    el.value = v;
}

async function removeItem(cartId, itemId) {
    if (!confirm('Remove this item from your cart?')) return;

    const fd = new FormData();
    fd.append('cart_id', cartId);
    fd.append('item_id', itemId);

    try {
        const res  = await fetch('remove_cart.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            const row = document.getElementById('cart-row-' + cartId);
            if (row) row.remove();
            showToast('Item removed.', 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            showToast(data.message || 'Could not remove item.', 'error');
        }
    } catch(e) {
        showToast('Something went wrong.', 'error');
    }
}

async function submitPayment() {
    const btn = document.getElementById('payBtn');

    // Basic validation for card
    if (selectedMethod === 'card') {
        const num  = document.getElementById('cardNumber').value.replace(/\s/g,'');
        const name = document.getElementById('cardName').value.trim();
        const exp  = document.getElementById('cardExpiry').value.trim();
        const cvv  = document.getElementById('cardCvv').value.trim();

        if (num.length < 16)        { showToast('Enter a valid 16-digit card number.', 'error'); return; }
        if (!name)                   { showToast('Enter the cardholder name.', 'error'); return; }
        if (exp.length < 7)          { showToast('Enter a valid expiry date.', 'error'); return; }
        if (cvv.length < 3)          { showToast('Enter a valid CVV.', 'error'); return; }
    }

    btn.disabled = true;
    btn.textContent = 'Processing…';

    const fd = new FormData();
    fd.append('method', selectedMethod);

    try {
        const res  = await fetch('payment.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.success) {
            document.getElementById('confirmRef').textContent = data.reference;
            document.getElementById('confirmOverlay').classList.add('show');
        } else {
            showToast(data.message || 'Payment failed.', 'error');
            btn.disabled = false;
            btn.textContent = 'Retry Payment →';
        }
    } catch(e) {
        showToast('Something went wrong. Please try again.', 'error');
        btn.disabled = false;
        btn.textContent = 'Retry Payment →';
    }
}

let toastTimer;
function showToast(msg, type = 'success') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'show ' + type;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { t.className = ''; }, 3500);
}
</script>
</body>
</html>
