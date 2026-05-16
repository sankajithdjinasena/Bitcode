<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$stmt = $conn->query("SELECT * FROM events ORDER BY id DESC");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Events</h2>

<a href="create_event.php">+ Create Event</a>

<br><br>

<table border="1" cellpadding="10">
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Go Live</th>
        <th>Status</th>
        <th>Actions</th>
    </tr>

    <?php foreach ($events as $event): ?>
        <tr>
            <td><?= $event['id'] ?></td>
            <td><?= $event['name'] ?></td>
            <td><?= $event['go_live_at'] ?></td>
            <td><?= $event['status'] ?></td>
            <td>
                <a href="items.php?event_id=<?= $event['id'] ?>">
                    Add Items
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>