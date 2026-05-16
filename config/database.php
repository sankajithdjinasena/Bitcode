<?php

$host = "localhost";
$dbname = "flash_sale";
$user = "root";
$pass = "";

try {

    $conn = new PDO(
        "mysql:host=$host;dbname=$dbname",
        $user,
        $pass
    );

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {

    die($e->getMessage());
}