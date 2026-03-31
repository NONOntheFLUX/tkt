<?php
/**
 * Database Configuration
 * Update these values to match your WampServer / phpMyAdmin setup.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'simple_crm');
define('DB_USER', 'root');
define('DB_PASS', '');        // Default WampServer password is empty
define('DB_CHARSET', 'utf8mb4');

/**
 * Get PDO database connection (singleton pattern).
 * Uses error mode exceptions so errors are thrown, not silently ignored.
 *
 * @return PDO
 */
function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // In production, log this instead of displaying
            die('Database connection failed: ' . $e->getMessage());
        }
    }

    return $pdo;
}
