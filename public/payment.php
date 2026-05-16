<?php
/**
 * payment.php
 * -----------
 * Converts all cart items for the logged-in user into confirmed orders,
 * records a payment, and clears the cart.
 *
 * Called via AJAX POST from cart.php.
 * Returns JSON { success, message, reference }.
 */

require_once "../app/middleware/auth.php";
require_once "../config/database.php";

header('Content-Type: application/json');

$userId = (int) $_SESSION['user']['id'];
$method = $_POST['method'] ?? 'card';

$allowedMethods = ['card', 'bank_transfer', 'cash_on_delivery'];
if (!in_array($method, $allowedMethods)) {
    $method = 'card';
}

try {
    $conn->beginTransaction();

    // Fetch all cart items for this user (lock rows)
    $cartStmt = $conn->prepare("
        SELECT c.id AS cart_id, c.event_id, c.item_id, c.price
        FROM   cart c
        WHERE  c.user_id = ?
        FOR UPDATE
    ");
    $cartStmt->execute([$userId]);
    $cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($cartItems)) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Your cart is empty.']);
        exit;
    }

    $totalAmount = 0;
    $orderIds    = [];

    foreach ($cartItems as $ci) {
        // Double-check: user must not already have a confirmed order for this event
        $dupCheck = $conn->prepare("
            SELECT COUNT(*) FROM orders
            WHERE user_id = ? AND event_id = ? AND status = 'confirmed'
        ");
        $dupCheck->execute([$userId, $ci['event_id']]);
        if ((int)$dupCheck->fetchColumn() > 0) {
            // Already have an order — skip (shouldn't normally happen if purchase.php ran)
            continue;
        }

        // Insert order
        $insOrder = $conn->prepare("
            INSERT INTO orders (user_id, event_id, item_id, status, price)
            VALUES (?, ?, ?, 'confirmed', ?)
        ");
        $insOrder->execute([$userId, $ci['event_id'], $ci['item_id'], $ci['price']]);
        $orderId    = (int) $conn->lastInsertId();
        $orderIds[] = $orderId;
        $totalAmount += (float) $ci['price'];
    }

    if (empty($orderIds)) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'All cart items are already ordered.']);
        exit;
    }

    // Generate a human-readable transaction reference
    $txRef = 'SWD-' . strtoupper(substr(md5(uniqid($userId, true)), 0, 8));

    // Record payment for each order
    foreach ($orderIds as $oid) {
        $insPay = $conn->prepare("
            INSERT INTO payments (order_id, user_id, amount, method, status, transaction_ref, paid_at)
            VALUES (?, ?, ?, ?, 'paid', ?, NOW())
        ");
        // For bank transfer, mark as 'pending' until verified
        $payStatus = ($method === 'bank_transfer') ? 'pending' : 'paid';
        $insPay->execute([$oid, $userId, $totalAmount, $method, $txRef]);
    }

    // Clear the cart
    $conn->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$userId]);

    $conn->commit();

    echo json_encode([
        'success'   => true,
        'message'   => 'Payment confirmed!',
        'reference' => $txRef,
    ]);

} catch (PDOException $e) {
    $conn->rollBack();
    // Log: $e->getMessage()
    echo json_encode(['success' => false, 'message' => 'Payment processing failed. Please try again.']);
}
