<?php

require_once __DIR__ . '/../app/models/Database.php';

$pdo = Database::getConnection();

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS tentativas_login (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        identificador TEXT NOT NULL,
        tentativa_em TEXT NOT NULL DEFAULT (datetime('now'))
    )"
);
echo 'Tabela tentativas_login criada.<br>';

$pdo->exec(
    'CREATE INDEX IF NOT EXISTS idx_tentativas_login_ident_em
     ON tentativas_login (identificador, tentativa_em)'
);
echo 'Índice em (identificador, tentativa_em) criado.<br>';

echo '<br><b>Migração 019 concluída.</b>';
