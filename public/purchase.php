<?php

session_start();

require_once "../config/database.php";

header('Content-Type: application/json');

if(!isset($_SESSION['user']))
{
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit;
}

$userId = $_SESSION['user']['id'];
$itemId = $_POST['item_id'];

try {

    $conn->beginTransaction();

    // LOCK ROW
    $stmt = $conn->prepare("
        SELECT * FROM items
        WHERE id = ?
        FOR UPDATE
    ");

    $stmt->execute([$itemId]);

    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$item)
    {
        throw new Exception("Item not found");
    }

    // CHECK STOCK
    if($item['remaining_stock'] <= 0)
    {
        throw new Exception("Item Sold Out");
    }

    // CHECK DUPLICATE PURCHASE
    $check = $conn->prepare("
        SELECT * FROM orders
        WHERE user_id = ?
        AND item_id = ?
    ");

    $check->execute([$userId, $itemId]);

    if($check->fetch())
    {
        throw new Exception("Already Purchased");
    }

    // REDUCE STOCK
    $update = $conn->prepare("
        UPDATE items
        SET remaining_stock = remaining_stock - 1
        WHERE id = ?
    ");

    $update->execute([$itemId]);

    // CREATE ORDER
    $insert = $conn->prepare("
        INSERT INTO orders
        (user_id, event_id, item_id, price)
        VALUES (?, ?, ?, ?)
    ");

    $insert->execute([
        $userId,
        $item['event_id'],
        $itemId,
        $item['price']
    ]);

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Purchase Successful'
    ]);

} catch(Exception $e) {

    $conn->rollBack();

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}