<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Debug Mode Active</h1>";
echo "<p>If there is a fatal error, it will be printed below:</p>";
echo "<hr>";

try {
    require_once __DIR__ . '/index.php';
} catch (Throwable $e) {
    echo "<h2>Fatal Error Caught:</h2>";
    echo "<pre style='background:#f8d7da;color:#721c24;padding:15px;border-radius:5px;'>";
    echo "<strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "\n";
    echo "<strong>File:</strong> " . $e->getFile() . "\n";
    echo "<strong>Line:</strong> " . $e->getLine() . "\n\n";
    echo "<strong>Trace:</strong>\n" . htmlspecialchars($e->getTraceAsString());
    echo "</pre>";
}
