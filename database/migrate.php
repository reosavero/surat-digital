<?php

/**
 * Database Migration Script
 */

require_once __DIR__ . '/../config/database.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "Connecting to MySQL server...\n";

    // Drop database or recreate tables safely
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `surat_digital` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $pdo->exec("USE `surat_digital`;");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->exec("DROP TABLE IF EXISTS `surat_logs`;");
    $pdo->exec("DROP TABLE IF EXISTS `disposisi`;");
    $pdo->exec("DROP TABLE IF EXISTS `surat`;");
    $pdo->exec("DROP TABLE IF EXISTS `settings`;");
    $pdo->exec("DROP TABLE IF EXISTS `users`;");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    $sql = file_get_contents(__DIR__ . '/schema.sql');
    if (!$sql) {
        throw new Exception("schema.sql is empty or not found.");
    }

    $pdo->exec($sql);

    echo "Database schema migrated successfully!\n";

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
