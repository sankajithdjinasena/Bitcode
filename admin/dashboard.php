<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

require_once "../config/database.php";
?>

<h1>Admin Dashboard</h1>

<ul>
    <li><a href="events.php">Manage Events</a></li>
    <li><a href="items.php">Manage Items</a></li>
</ul>