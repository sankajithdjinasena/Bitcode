<?php

require_once "../config/database.php";

header('Content-Type: application/json');

$stmt = $conn->query("
    SELECT id, remaining_stock
    FROM items
");

echo json_encode(
    $stmt->fetchAll(PDO::FETCH_ASSOC)
);