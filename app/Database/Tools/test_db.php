<?php
require 'config/database.php';
$db = Database::connect();
echo "--- PLANTAS ---\n";
$res = $db->query("SELECT * FROM plantas");
print_r($res->fetch_all(MYSQLI_ASSOC));
echo "\n--- LECTURAS ---\n";
$res = $db->query("SELECT * FROM lecturas_sensores");
print_r($res->fetch_all(MYSQLI_ASSOC));
echo "\n--- USUARIOS ---\n";
$res = $db->query("SELECT * FROM usuarios");
print_r($res->fetch_all(MYSQLI_ASSOC));
