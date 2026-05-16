<?php
/**
 * payment.php (FIXED VERSION)
 * ---------------------------
 * Atomically converts cart items into orders,
 * reduces stock safely, records payments, and clears cart.
 */

require_once "../app/middleware/auth.php";
require_once "../config/database.php";

header('Content-Type: application/json');

$userId = (int) $_SESSION['user']['id'];
$method  = $_POST['method'] ?? 'card';

$allowedMethods = ['card', 'bank_transfer', 'cash_on_delivery'];
if (!in_array($method, $allowedMethods)) {
    $method = 'card';
}

try {
    $conn->beginTransaction();

    /**
     * 1. Lock and fetch cart items
     */
    $cartStmt = $conn->prepare("
        SELECT id AS cart_id, event_id, item_id, price
        FROM cart
        WHERE user_id = ?
        FOR UPDATE
    ");
    $cartStmt->execute([$userId]);
    $cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($cartItems)) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Your cart is empty.']);
        exit;
    }

    $orderIds    = [];
    $totalAmount  = 0;

    /**
     * 2. Process each cart item safely
     */
    foreach ($cartItems as $ci) {

        $itemId  = (int) $ci['item_id'];
        $eventId = (int) $ci['event_id'];
        $price   = (float) $ci['price'];

        /**
         * Lock item row (prevents overselling)
         */
        $itemStmt = $conn->prepare("
            SELECT remaining_stock
            FROM items
            WHERE id = ?
            FOR UPDATE
        ");
        $itemStmt->execute([$itemId]);
        $item = $itemStmt->fetch(PDO::FETCH_ASSOC);

        if (!$item || $item['remaining_stock'] <= 0) {
            $conn->rollBack();
            echo json_encode([
                'success' => false,
                'message' => "Item $itemId is out of stock."
            ]);
            exit;
        }

        /**
         * Reduce stock
         */
        $updateStock = $conn->prepare("
            UPDATE items
            SET remaining_stock = remaining_stock - 1
            WHERE id = ? AND remaining_stock > 0
        ");
        $updateStock->execute([$itemId]);

        /**
         * Prevent duplicate confirmed orders
         */
        $dupCheck = $conn->prepare("
            SELECT COUNT(*) 
            FROM orders
            WHERE user_id = ? AND event_id = ? AND status = 'confirmed'
        ");
        $dupCheck->execute([$userId, $eventId]);

        if ((int)$dupCheck->fetchColumn() > 0) {
            continue;
        }

        /**
         * Create order
         */
        $insOrder = $conn->prepare("
            INSERT INTO orders (user_id, event_id, item_id, status, price)
            VALUES (?, ?, ?, 'confirmed', ?)
        ");
        $insOrder->execute([$userId, $eventId, $itemId, $price]);

        $orderId = (int) $conn->lastInsertId();
        $orderIds[] = $orderId;

        $totalAmount += $price;
    }

    if (empty($orderIds)) {
        $conn->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'All cart items are already ordered.'
        ]);
        exit;
    }

    /**
     * 3. Generate transaction reference
     */
    $txRef = 'SWD-' . strtoupper(substr(md5(uniqid($userId, true)), 0, 8));

    /**
     * 4. Payment status
     */
    $payStatus = ($method === 'bank_transfer') ? 'pending' : 'paid';

    /**
     * 5. Insert payment per order (correct per-order amount)
     */
    foreach ($orderIds as $oid) {
        $orderPrice = 0;

        // get order price (safe, accurate)
        $priceStmt = $conn->prepare("SELECT price FROM orders WHERE id = ?");
        $priceStmt->execute([$oid]);
        $orderPrice = (float) $priceStmt->fetchColumn();

        $insPay = $conn->prepare("
            INSERT INTO payments 
            (order_id, user_id, amount, method, status, transaction_ref, paid_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");

        $insPay->execute([
            $oid,
            $userId,
            $orderPrice,
            $method,
            $payStatus,
            $txRef
        ]);
    }

    /**
     * 6. Clear cart
     */
    $clearCart = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $clearCart->execute([$userId]);

    /**
     * 7. Commit transaction
     */
    $conn->commit();

    echo json_encode([
        'success'   => true,
        'message'   => 'Payment confirmed!',
        'reference' => $txRef
    ]);

} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    // Log real error in production
    // error_log($e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Payment processing failed. Please try again.'
    ]);
}