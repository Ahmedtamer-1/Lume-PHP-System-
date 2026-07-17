<?php
require_once __DIR__ . '/includes/functions.php';

try {
    $pdo = db();
    $sql = "CREATE TABLE IF NOT EXISTS ad_spend (
        id INT AUTO_INCREMENT PRIMARY KEY,
        platform VARCHAR(50) NOT NULL,
        campaign_name VARCHAR(100) NULL,
        amount DECIMAL(10,2) NOT NULL,
        date_logged DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );";
    $pdo->exec($sql);
    echo "ad_spend table created successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
