<?php
/**
 * remove_cart.php
 * ---------------
 * Removes one item from the user's cart and restores the stock count.
 * Uses a transaction so stock is never lost if the DELETE fails.
 */

require_once "../app/middleware/auth.php";
require_once "../config/database.php";

header('Content-Type: application/json');

$userId = (int) $_SESSION['user']['id'];
$cartId = filter_input(INPUT_POST, 'cart_id', FILTER_VALIDATE_INT);
$itemId = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);

if (!$cartId || !$itemId) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

try {
    $conn->beginTransaction();

    // Verify ownership
    $check = $conn->prepare("
        SELECT id FROM cart WHERE id = ? AND user_id = ?
    ");
    $check->execute([$cartId, $userId]);
    if (!$check->fetch()) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Cart item not found.']);
        exit;
    }

    // Restore stock
    $conn->prepare("
        UPDATE items SET remaining_stock = remaining_stock + 1 WHERE id = ?
    ")->execute([$itemId]);

    // Remove from cart
    $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?")->execute([$cartId, $userId]);

    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Item removed.']);

} catch (PDOException $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => 'Could not remove item.']);
}
