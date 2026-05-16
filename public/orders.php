<?php

require_once "../app/middleware/auth.php";
require_once "../config/database.php";

$userId = $_SESSION['user']['id'];

$stmt = $conn->prepare("
    SELECT orders.*, items.name as item_name
    FROM orders
    JOIN items ON items.id = orders.item_id
    WHERE orders.user_id = ?
");

$stmt->execute([$userId]);

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<h1>My Orders</h1>

<?php foreach($orders as $order): ?>

    <div>

        <p><?= $order['item_name'] ?></p>
        <p>Rs <?= $order['price'] ?></p>

    </div>

<?php endforeach; ?>