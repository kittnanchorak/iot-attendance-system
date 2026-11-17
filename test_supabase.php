<?php
require_once __DIR__ . '/config/database.php';

try {
    echo "Connecting to Supabase (PostgreSQL)...\n";
    $db = Database::connect();
    echo "SUCCESS: Connected to Supabase successfully!\n\n";

    $tables = $db->query("SELECT tablename FROM pg_tables WHERE schemaname = 'public'")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables found in Supabase:\n";
    print_r($tables);

    $studentCount = $db->query("SELECT COUNT(*) FROM students")->fetchColumn();
    echo "\nTotal Students in Supabase: " . $studentCount . "\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
