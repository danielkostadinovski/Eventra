<?php

require __DIR__ . '/../config/db.php';

$existing = db()->query('SELECT COUNT(*) FROM sqlite_master')->fetchColumn();

if ($existing > 0) {
    echo 'The database already exists - to rebuild it, first run: rm -f database/eventra.db*' . PHP_EOL;
    exit(1);
}

try {
    db()->beginTransaction();
    db()->exec(file_get_contents(__DIR__ . '/schema.sql'));
    db()->commit();
} catch (PDOException $e) {
    db()->rollBack();
    echo 'The database could not be created: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}

$tables = db()->query("SELECT COUNT(*) FROM sqlite_master WHERE type = 'table'")->fetchColumn();
$views = db()->query("SELECT COUNT(*) FROM sqlite_master WHERE type = 'view'")->fetchColumn();
$triggers = db()->query("SELECT COUNT(*) FROM sqlite_master WHERE type = 'trigger'")->fetchColumn();

echo "Database created: $tables tables, $views views, $triggers triggers." . PHP_EOL;