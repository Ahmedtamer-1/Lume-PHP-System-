<?php
define('ENV', 'development');
require_once __DIR__ . '/config/database.php';
$sql = file_get_contents(__DIR__ . '/database/migration-cogs.sql');
try {
    db()->exec($sql);
    echo "Migration successful\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
