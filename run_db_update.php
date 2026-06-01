<?php
require_once __DIR__ . '/config/database.php';

$sqlFile = __DIR__ . '/database_update.sql';
if (!file_exists($sqlFile)) {
    die("Error: database_update.sql not found.");
}

$sql = file_get_contents($sqlFile);

try {
    // Execute the SQL queries
    db()->exec($sql);
    echo "<h1>Database Updated Successfully!</h1>";
    echo "<p>All missing tables and columns have been safely added.</p>";
    echo "<p>You can now delete this file (run_db_update.php).</p>";
} catch (PDOException $e) {
    echo "<h1>Database Error</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
