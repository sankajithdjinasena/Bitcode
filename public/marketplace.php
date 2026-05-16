<?php

require_once "../app/middleware/auth.php";
require_once "../config/database.php";

$stmt = $conn->query("
   SELECT 
    events.id as event_id,
    events.name as event_name,
    events.cover_image,
    events.go_live_at,
    events.status,
    events.created_at,

    items.id as item_id,
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

     <h2><?= $item['event_name'] ?></h2>

      <p>Item: <?= $item['item_name'] ?></p>

        <p>Price: Rs <?= $item['price'] ?></p>

        <p>
            Remaining Stock:
            <span id="stock-<?= $item['item_id'] ?>">
                <?= $item['remaining_stock'] ?>
            </span>
        </p>

        <?php

        date_default_timezone_set('Asia/Colombo');

        $now = new DateTime('now', new DateTimeZone('Asia/Colombo'));
        $goLive = new DateTime($item['go_live_at']);

        $isLive = $goLive <= $now;
        ?>

       <?php if(!$isLive): ?>

<p>
    Event starts in:
    <span class="countdown" data-time="<?= $item['go_live_at'] ?>"></span>
</p>

<?php elseif($item['remaining_stock'] > 0): ?>

<button onclick="buyItem(<?= $item['item_id'] ?>, this)">
    Buy Now
</button>

<?php else: ?>

<button disabled>SOLD OUT</button>

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

<script>
function startCountdown() {
    const elements = document.querySelectorAll('.countdown');

    elements.forEach(el => {

        const targetTime = new Date(el.dataset.time).getTime();

        function update() {
            const now = new Date().getTime();
            const diff = targetTime - now;

            if (diff <= 0) {
                el.innerHTML = "LIVE NOW 🔥";
                location.reload(); // auto refresh when sale starts
                return;
            }

            const hours = Math.floor((diff / (1000 * 60 * 60)));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);

            el.innerHTML =
                String(hours).padStart(2, '0') + "h " +
                String(minutes).padStart(2, '0') + "m " +
                String(seconds).padStart(2, '0') + "s";
        }

        update();
        setInterval(update, 1000);
    });
}

startCountdown();
</script>