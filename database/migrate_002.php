<?php

require_once __DIR__ . '/../app/models/Database.php';

$pdo = Database::getConnection();

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS parametros (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nome TEXT NOT NULL,
        preco_publico INTEGER NOT NULL DEFAULT 0,
        criado_em TEXT NOT NULL DEFAULT (datetime('now'))
    )"
);

echo 'Tabela parametros criada.<br>';

$existentes = $pdo->query('SELECT COUNT(*) FROM parametros')->fetchColumn();

if ($existentes == 0) {
    $padroes = [
        ['Art. 46, I', 1],
        ['Art. 46, II', 1],
        ['Art. 46, III', 0],
        ['Art. 46, IV', 0],
        ['Art. 46, V', 0],
        ['Art. 59', 1],
    ];

    $stmt = $pdo->prepare('INSERT INTO parametros (nome, preco_publico) VALUES (:nome, :preco_publico)');
    foreach ($padroes as [$nome, $precoPublico]) {
        $stmt->execute(['nome' => $nome, 'preco_publico' => $precoPublico]);
    }

    echo 'Parâmetros padrão inseridos (Art. 46 I-V e Art. 59).<br>';
}

echo '<br>Migração concluída.';