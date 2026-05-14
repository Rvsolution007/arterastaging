<?php
$pdo = new PDO("mysql:host=127.0.0.1;dbname=brandkit", "root", "");
$stmt = $pdo->query("SHOW COLUMNS FROM business");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($columns as $col) {
    if (strpos($col["Field"], "extra") !== false) {
        echo $col["Field"] . "\n";
    }
}

