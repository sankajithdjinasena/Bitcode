<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$event_id = $_GET['event_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $event_id = $_POST['event_id'];
    $name = $_POST['name'];
    $price = $_POST['price'];
    $stock_qty = $_POST['stock'];

  $stmt = $conn->prepare("
    INSERT INTO items (event_id, name, price, stock_qty, remaining_stock)
    VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $event_id,
        $name,
        $price,
        $stock_qty,
        $stock_qty
    ]);

    header("Location: items.php?event_id=" . $event_id);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM items WHERE event_id = ?");
$stmt->execute([$event_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Manage Items</h2>

<form method="POST">

    <input type="hidden" name="event_id" value="<?= $event_id ?>">

    <input type="text" name="name" placeholder="Item Name" required>
    <br><br>

    <input type="number" name="price" placeholder="Price" required>
    <br><br>

    <input type="number" name="stock_qty" min="100" max="500" required>
    <br><br>

    <button type="submit">Add Item</button>

</form>

<hr>

<h3>Existing Items</h3>

<?php foreach ($items as $item): ?>
    <div>
        <?= $item['name'] ?> - Rs <?= $item['price'] ?> (Stock: <?= $item['remaining_stock'] ?>)
    </div>
<?php endforeach; ?>