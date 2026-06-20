<?php

require_once __DIR__ . '/../app/models/Database.php';

$pdo = Database::getConnection();

try {
    $pdo->exec("ALTER TABLE itens ADD COLUMN unidade TEXT NOT NULL DEFAULT 'UN'");
    echo 'Coluna unidade adicionada.<br>';
} catch (PDOException $e) {
    echo 'Coluna unidade já existe ou erro: ' . $e->getMessage() . '<br>';
}

try {
    $pdo->exec("ALTER TABLE itens ADD COLUMN quantidade REAL NOT NULL DEFAULT 1");
    echo 'Coluna quantidade adicionada.<br>';
} catch (PDOException $e) {
    echo 'Coluna quantidade já existe ou erro: ' . $e->getMessage() . '<br>';
}

echo '<br>Migração concluída.';