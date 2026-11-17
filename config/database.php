<?php
/**
 * Database Configuration (Universal: Supports PostgreSQL / Supabase & MySQL)
 * IoT Attendance & Student Leave System
 */

// =====================================================
// SUPABASE / POSTGRESQL (IPv4 Pooler for Render)
// =====================================================

define('DB_DRIVER', getenv('DB_DRIVER') ?: 'pgsql');

// Supabase IPv4 Pooler Host
define('DB_HOST', getenv('DB_HOST') ?: 'aws-1-ap-southeast-2.pooler.supabase.com');
define('DB_PORT', getenv('DB_PORT') ?: '6543');
define('DB_NAME', getenv('DB_NAME') ?: 'postgres');
define('DB_USER', getenv('DB_USER') ?: 'postgres.snbqtuflqiqsgfdjvlxh');
define('DB_PASS', getenv('DB_PASS') ?: 'l;ylfu8iy[d[');

date_default_timezone_set('Asia/Bangkok');

class Database
{
    private static ?PDO $instance = null;

    public static function connect(): PDO
    {
        if (self::$instance === null) {
            try {
                if (DB_DRIVER === 'pgsql') {
                    // PostgreSQL / Supabase DSN
                    $dsn = sprintf(
                        'pgsql:host=%s;port=%s;dbname=%s;sslmode=require',
                        DB_HOST,
                        DB_PORT,
                        DB_NAME
                    );
                } else {
                    // MySQL DSN (XAMPP เดิม)
                    $dsn = sprintf(
                        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                        DB_HOST,
                        DB_PORT,
                        DB_NAME
                    );
                }

                self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);

                if (DB_DRIVER === 'pgsql') {
                    self::$instance->exec("SET TIME ZONE 'Asia/Bangkok'");
                } else {
                    self::$instance->exec("SET time_zone = '+07:00'");
                }

            } catch (PDOException $e) {
                error_log('[DB Error] ' . $e->getMessage());

                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
                die(json_encode([
                    'success' => false,
                    'message' => 'Database connection failed: ' . $e->getMessage()
                ], JSON_UNESCAPED_UNICODE));
            }
        }

        return self::$instance;
    }
}
