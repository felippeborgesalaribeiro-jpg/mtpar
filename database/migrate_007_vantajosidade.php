<?php

require_once __DIR__ . '/../app/models/Database.php';

$pdo = Database::getConnection();

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS processos_vantajosidade (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        numero_ata TEXT NOT NULL,
        orgao_gerenciador TEXT NOT NULL DEFAULT '',
        objeto TEXT NOT NULL DEFAULT '',
        servidor_id INTEGER NOT NULL,
        status TEXT NOT NULL DEFAULT 'EM_ANDAMENTO',
        criado_em TEXT NOT NULL DEFAULT (datetime('now')),
        FOREIGN KEY (servidor_id) REFERENCES servidores(id)
    )"
);
echo 'Tabela processos_vantajosidade criada.<br>';

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS itens_vantajosidade (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        processo_id INTEGER NOT NULL,
        lote TEXT NOT NULL,
        item TEXT NOT NULL,
        descricao TEXT NOT NULL DEFAULT '',
        unidade TEXT NOT NULL DEFAULT 'UN',
        quantidade REAL NOT NULL DEFAULT 1,
        preco_ata REAL NOT NULL,
        criado_em TEXT NOT NULL DEFAULT (datetime('now')),
        FOREIGN KEY (processo_id) REFERENCES processos_vantajosidade(id) ON DELETE CASCADE
    )"
);
echo 'Tabela itens_vantajosidade criada.<br>';

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS precos_vantajosidade (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        item_id INTEGER NOT NULL,
        parametro TEXT NOT NULL DEFAULT '',
        valor REAL NOT NULL,
        fonte TEXT NOT NULL DEFAULT '',
        criado_em TEXT NOT NULL DEFAULT (datetime('now')),
        FOREIGN KEY (item_id) REFERENCES itens_vantajosidade(id) ON DELETE CASCADE
    )"
);
echo 'Tabela precos_vantajosidade criada.<br>';

echo '<br>Migração concluída.';