<?php
/**
 * purchase.php
 * ------------
 * Atomically reserves one item for the logged-in user and adds it to their cart.
 *
 * Concurrency strategy (handles 1 000+ simultaneous requests):
 *   1. Begin an InnoDB transaction.
 *   2. SELECT … FOR UPDATE on the item row — this row-level lock means only
 *      ONE PHP process can proceed past this point per item at a time; the rest
 *      queue behind the lock (no lost-update / oversell).
 *   3. Re-check remaining_stock inside the lock.
 *   4. Check the cart+orders tables to enforce "one item per event per user".
 *   5. Decrement stock and insert cart row atomically then COMMIT.
 *
 * Because the unique key `uq_cart_user_event` is on the cart table, even if
 * two requests race past step 4 simultaneously, only one INSERT will succeed —
 * the second gets a duplicate-key error that we catch and return gracefully.
 *
 * NGINX note: point multiple PHP-FPM workers at this same file; the InnoDB
 * row lock ensures correctness regardless of how many workers run in parallel.
 */

require_once "../app/middleware/auth.php";
require_once "../config/database.php";

header('Content-Type: application/json');

// ── Input validation ──────────────────────────────────────────────────────────
$itemId = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
if (!$itemId) {
    echo json_encode(['success' => false, 'message' => 'Invalid item.']);
    exit;
}

$userId = (int) $_SESSION['user']['id'];

try {
    // ── Begin transaction ─────────────────────────────────────────────────────
    $conn->beginTransaction();

    // ── 1. Lock the item row & fetch current state ────────────────────────────
    $stmt = $conn->prepare("
        SELECT i.id, i.name, i.price, i.remaining_stock, i.event_id
        FROM   items i
        WHERE  i.id = ?
        FOR UPDATE
    ");
    $stmt->execute([$itemId]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Item not found.']);
        exit;
    }

    // ── 2. Check event is live ────────────────────────────────────────────────
    $evStmt = $conn->prepare("
        SELECT id, name, status, go_live_at
        FROM   events
        WHERE  id = ?
    ");
    $evStmt->execute([$item['event_id']]);
    $event = $evStmt->fetch(PDO::FETCH_ASSOC);

    date_default_timezone_set('Asia/Colombo');
    $goLive = new DateTime($event['go_live_at'], new DateTimeZone('Asia/Colombo'));
    $now    = new DateTime('now',               new DateTimeZone('Asia/Colombo'));

    if ($goLive > $now || $event['status'] === 'ended') {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'This event is not live yet.']);
        exit;
    }

    // ── 3. Check stock (inside lock) ──────────────────────────────────────────
    if ((int)$item['remaining_stock'] <= 0) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Sorry, this item just sold out!']);
        exit;
    }

    // ── 4. Enforce one-item-per-event rule ────────────────────────────────────
    //    Check both cart and confirmed orders
    $dupStmt = $conn->prepare("
        SELECT COUNT(*) FROM cart
        WHERE user_id = ? AND event_id = ?
    ");
    $dupStmt->execute([$userId, $item['event_id']]);
    if ((int)$dupStmt->fetchColumn() > 0) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'You already have an item from this event in your cart.']);
        exit;
    }

    $ordStmt = $conn->prepare("
        SELECT COUNT(*) FROM orders
        WHERE user_id = ? AND event_id = ? AND status = 'confirmed'
    ");
    $ordStmt->execute([$userId, $item['event_id']]);
    if ((int)$ordStmt->fetchColumn() > 0) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'You have already purchased an item from this event.']);
        exit;
    }

    // ── 5. Decrement stock ────────────────────────────────────────────────────
    $decStmt = $conn->prepare("
        UPDATE items
        SET    remaining_stock = remaining_stock - 1
        WHERE  id = ? AND remaining_stock > 0
    ");
    $decStmt->execute([$itemId]);

    if ($decStmt->rowCount() === 0) {
        // Stock hit 0 between our check and update (edge-case safety net)
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Sorry, this item just sold out!']);
        exit;
    }

    // ── 6. Insert into cart ───────────────────────────────────────────────────
    //    The UNIQUE KEY `uq_cart_user_event` acts as a last-line-of-defence
    //    against race conditions: a duplicate INSERT will throw an exception.
    $cartStmt = $conn->prepare("
        INSERT INTO cart (user_id, event_id, item_id, price)
        VALUES (?, ?, ?, ?)
    ");
    $cartStmt->execute([$userId, $item['event_id'], $itemId, $item['price']]);

    // ── 7. Commit ─────────────────────────────────────────────────────────────
    $conn->commit();

    echo json_encode([
        'success'  => true,
        'message'  => 'Added to cart! Redirecting…',
        'redirect' => 'cart.php',
    ]);

} catch (PDOException $e) {
    $conn->rollBack();

    // Duplicate-key = another process beat this user to the same event slot
    if ($e->getCode() === '23000') {
        echo json_encode(['success' => false, 'message' => 'You already have an item from this event in your cart.']);
    } else {
        // Log $e->getMessage() to server log in production
        echo json_encode(['success' => false, 'message' => 'A server error occurred. Please try again.']);
    }
}
