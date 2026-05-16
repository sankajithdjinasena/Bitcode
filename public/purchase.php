<?php

require_once "../app/middleware/auth.php";
require_once "../config/database.php";

header('Content-Type: application/json');

/*
|--------------------------------------------------------------------------
| RATE LIMITING
|--------------------------------------------------------------------------
| Prevents spam / bot hammering
*/

$ip = $_SERVER['REMOTE_ADDR'];

$rateFile = sys_get_temp_dir() . "/rate_" . md5($ip);

$requests = [];

if (file_exists($rateFile)) {
    $requests = json_decode(file_get_contents($rateFile), true);
}

$now = time();

$requests = array_filter($requests, function ($t) use ($now) {
    return ($now - $t) < 60;
});

if (count($requests) >= 20) {

    echo json_encode([
        'success' => false,
        'message' => 'Too many requests'
    ]);

    exit;
}

$requests[] = $now;

file_put_contents($rateFile, json_encode($requests));

/*
|--------------------------------------------------------------------------
| VALIDATE INPUT
|--------------------------------------------------------------------------
*/

$itemId = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);

if (!$itemId) {

    echo json_encode([
        'success' => false,
        'message' => 'Invalid item'
    ]);

    exit;
}

$userId = (int) $_SESSION['user']['id'];

try {

    /*
    |--------------------------------------------------------------------------
    | CHECK EVENT STATUS
    |--------------------------------------------------------------------------
    */

    $eventStmt = $conn->prepare("
        SELECT
            e.status,
            e.go_live_at
        FROM events e
        JOIN items i ON i.event_id = e.id
        WHERE i.id = ?
    ");

    $eventStmt->execute([$itemId]);

    $event = $eventStmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {

        echo json_encode([
            'success' => false,
            'message' => 'Event not found'
        ]);

        exit;
    }

    date_default_timezone_set('Asia/Colombo');

    if (
        strtotime($event['go_live_at']) > time()
        || $event['status'] !== 'live'
    ) {

        echo json_encode([
            'success' => false,
            'message' => 'Event not live'
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | CHECK DUPLICATE CART
    |--------------------------------------------------------------------------
    */

    $dupStmt = $conn->prepare("
        SELECT COUNT(*)
        FROM cart
        WHERE user_id = ?
        AND event_id = (
            SELECT event_id
            FROM items
            WHERE id = ?
        )
    ");

    $dupStmt->execute([$userId, $itemId]);

    if ($dupStmt->fetchColumn() > 0) {

        echo json_encode([
            'success' => false,
            'message' => 'Already in cart'
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | CHECK ALREADY PURCHASED
    |--------------------------------------------------------------------------
    */

    $orderStmt = $conn->prepare("
        SELECT COUNT(*)
        FROM orders
        WHERE user_id = ?
        AND event_id = (
            SELECT event_id
            FROM items
            WHERE id = ?
        )
        AND status = 'confirmed'
    ");

    $orderStmt->execute([$userId, $itemId]);

    if ($orderStmt->fetchColumn() > 0) {

        echo json_encode([
            'success' => false,
            'message' => 'Already purchased'
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | BEGIN TRANSACTION
    |--------------------------------------------------------------------------
    */

    $conn->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | LOCK ITEM ROW
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        SELECT
            id,
            remaining_stock,
            event_id,
            price
        FROM items
        WHERE id = ?
        FOR UPDATE
    ");

    $stmt->execute([$itemId]);

    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item || (int)$item['remaining_stock'] <= 0) {

        $conn->rollBack();

        echo json_encode([
            'success' => false,
            'message' => 'Sold out'
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | ATOMIC STOCK DEDUCTION
    |--------------------------------------------------------------------------
    */
/*
    $updateStmt = $conn->prepare("
        UPDATE items
        SET remaining_stock = remaining_stock - 1
        WHERE id = ?
        AND remaining_stock > 0
    ");

    $updateStmt->execute([$itemId]);

    if ($updateStmt->rowCount() === 0) {

        $conn->rollBack();

        echo json_encode([
            'success' => false,
            'message' => 'Sold out'
        ]);

        exit;
    }
*/
    /*
    |--------------------------------------------------------------------------
    | INSERT INTO CART
    |--------------------------------------------------------------------------
    */

    $cartStmt = $conn->prepare("
        INSERT INTO cart
        (
            user_id,
            event_id,
            item_id,
            price
        )
        VALUES (?, ?, ?, ?)
    ");

    $cartStmt->execute([
        $userId,
        $item['event_id'],
        $itemId,
        $item['price']
    ]);

    /*
    |--------------------------------------------------------------------------
    | COMMIT TRANSACTION
    |--------------------------------------------------------------------------
    */

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Added to cart successfully',
        'redirect' => 'cart.php'
    ]);

} catch (PDOException $e) {

    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    /*
    |--------------------------------------------------------------------------
    | DUPLICATE KEY ERROR
    |--------------------------------------------------------------------------
    */

    if ($e->getCode() === '23000') {

        echo json_encode([
            'success' => false,
            'message' => 'Already in cart'
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | SERVER ERROR
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        'success' => false,
        'message' => 'Server busy. Please try again.'
    ]);
}