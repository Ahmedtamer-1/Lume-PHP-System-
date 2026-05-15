<?php
require_once __DIR__ . '/config.php';

/**
 * Returns a singleton PDO connection.
 * Usage: $pdo = db();
 */
function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        DB_HOST, DB_NAME, DB_CHARSET
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        if (ENV === 'development') {
            die('<pre style="color:red">DB Error: ' . $e->getMessage() . '</pre>');
        }
        die('A database error occurred. Please try again later.');
    }

    return $pdo;
}
