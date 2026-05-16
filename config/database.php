<?php

$conn = new PDO(
    "mysql:host=localhost;dbname=flash_sale",
    "root",
    ""
);

$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);