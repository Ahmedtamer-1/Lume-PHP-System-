<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Live shop.php contents (first 20 lines):</h1>";
echo "<pre>";
$lines = file(__DIR__ . '/shop.php');
foreach (array_slice($lines, 0, 20) as $i => $line) {
    echo ($i + 1) . ": " . htmlspecialchars($line);
}
echo "</pre>";
