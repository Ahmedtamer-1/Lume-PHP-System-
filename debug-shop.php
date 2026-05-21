<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Now just include shop.php directly with errors visible
try {
    require __DIR__ . '/shop.php';
} catch (Throwable $e) {
    echo "<h2>Fatal Error Caught:</h2>";
    echo "<pre style='background:#f8d7da;color:#721c24;padding:15px;border-radius:5px;'>";
    echo "<strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "\n";
    echo "<strong>File:</strong> " . $e->getFile() . "\n";
    echo "<strong>Line:</strong> " . $e->getLine() . "\n\n";
    echo "<strong>Trace:</strong>\n" . htmlspecialchars($e->getTraceAsString());
    echo "</pre>";
}
