<?php

require_once "../app/middleware/auth.php";
require_once "../config/database.php";

$stmt = $conn->query("
    SELECT events.*, items.id as item_id,
    items.name as item_name,
    items.price,
    items.remaining_stock
    FROM events
    JOIN items ON items.event_id = events.id
");

$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<h1>Marketplace</h1>

<?php foreach($items as $item): ?>

    <div style="border:1px solid black; padding:20px; margin-bottom:20px;">

        <h2><?= $item['name'] ?></h2>

        <p>Item: <?= $item['item_name'] ?></p>

        <p>Price: Rs <?= $item['price'] ?></p>

        <p>
            Remaining Stock:
            <span id="stock-<?= $item['item_id'] ?>">
                <?= $item['remaining_stock'] ?>
            </span>
        </p>

        <?php

        $isLive = strtotime($item['go_live_at']) <= time();

        ?>

        <?php if($isLive && $item['remaining_stock'] > 0): ?>

            <button onclick="buyItem(<?= $item['item_id'] ?>, this)">
                Buy Now
            </button>

        <?php elseif($item['remaining_stock'] <= 0): ?>

            <button disabled>SOLD OUT</button>

        <?php else: ?>

            <p>
                Event starts at:
                <?= $item['go_live_at'] ?>
            </p>

        <?php endif; ?>

    </div>

<?php endforeach; ?>

<script>
async function buyItem(itemId, button)
{
    button.disabled = true;

    const formData = new FormData();
    formData.append('item_id', itemId);

    const response = await fetch('purchase.php', {
        method: 'POST',
        body: formData
    });

    const data = await response.json();

    alert(data.message);

    button.disabled = false;

    loadStocks();
}

async function loadStocks()
{
    const response = await fetch('stock.php');

    const data = await response.json();

    data.forEach(item => {

        const stockEl = document.getElementById('stock-' + item.id);

        if(stockEl)
        {
            stockEl.innerText = item.remaining_stock;
        }

    });
}

setInterval(loadStocks, 2000);
</script>