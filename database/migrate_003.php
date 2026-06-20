<?php

require_once __DIR__ . '/../app/models/Database.php';

$pdo = Database::getConnection();

try {
    $pdo->exec("ALTER TABLE servidores ADD COLUMN cargo TEXT NOT NULL DEFAULT ''");
    echo 'Coluna cargo adicionada à tabela servidores.<br>';
} catch (PDOException $e) {
    echo 'Coluna cargo já existe ou erro: ' . $e->getMessage() . '<br>';
}

echo '<br>Migração concluída.';