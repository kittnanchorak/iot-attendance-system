<?php
require_once __DIR__ . '/config/database.php';
try {
    $db = Database::connect();
    echo "SUCCESS: Database connected!\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
