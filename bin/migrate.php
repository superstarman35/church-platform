<?php

declare(strict_types=1);

use App\Core\Database;
use PDO;

$pdo = require dirname(__DIR__) . '/bootstrap/app.php';

$pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (migration VARCHAR(190) PRIMARY KEY, applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
$applied = $pdo->query('SELECT migration FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
$files = glob(dirname(__DIR__) . '/database/migrations/*.sql') ?: [];
sort($files, SORT_STRING);

foreach ($files as $file) {
    $name = basename($file);
    if (in_array($name, $applied, true)) {
        echo "SKIP {$name}\n";
        continue;
    }

    $sql = file_get_contents($file);
    if ($sql === false) {
        throw new RuntimeException("Unable to read migration: {$name}");
    }

    // MariaDB DDL can implicitly commit. Record the migration only after all statements succeed.
    $pdo->exec($sql);
    $statement = $pdo->prepare('INSERT INTO schema_migrations (migration) VALUES (:migration)');
    $statement->execute(['migration' => $name]);
    echo "APPLIED {$name}\n";
}
