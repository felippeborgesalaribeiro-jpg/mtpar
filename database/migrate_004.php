<?php

require_once __DIR__ . '/../app/models/Database.php';

$pdo = Database::getConnection();

try {
    $pdo->exec("ALTER TABLE servidores ADD COLUMN usuario TEXT NOT NULL DEFAULT ''");
    echo 'Coluna usuario adicionada.<br>';
} catch (PDOException $e) {
    echo 'Coluna usuario já existe ou erro: ' . $e->getMessage() . '<br>';
}

try {
    $pdo->exec("ALTER TABLE servidores ADD COLUMN senha_hash TEXT NOT NULL DEFAULT ''");
    echo 'Coluna senha_hash adicionada.<br>';
} catch (PDOException $e) {
    echo 'Coluna senha_hash já existe ou erro: ' . $e->getMessage() . '<br>';
}

try {
    $pdo->exec("ALTER TABLE servidores ADD COLUMN nivel_acesso TEXT NOT NULL DEFAULT 'COMUM'");
    echo 'Coluna nivel_acesso adicionada.<br>';
} catch (PDOException $e) {
    echo 'Coluna nivel_acesso já existe ou erro: ' . $e->getMessage() . '<br>';
}

echo '<br>Migração concluída. Agora defina seu cadastro como ADMIN manualmente (próximo passo).';