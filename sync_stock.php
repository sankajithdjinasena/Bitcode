<?php

require_once "../config/database.php";
require 'redis.php';

$sql = "SELECT id, remaining_stock FROM items";

$stmt = $pdo->query($sql);

while ($item = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $redis->set(
        "item_stock:" . $item['id'],
        $item['remaining_stock']
    );
}

echo "Redis stock synced";