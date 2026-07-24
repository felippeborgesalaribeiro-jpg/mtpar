<?php

require_once __DIR__ . '/../app/models/Database.php';

$pdo = Database::getConnection();

try {
    $pdo->exec("ALTER TABLE cotacoes ADD COLUMN eh_republicacao_lote INTEGER NOT NULL DEFAULT 0");
    echo 'Coluna eh_republicacao_lote adicionada à tabela cotacoes.<br>';
} catch (PDOException $e) {
    echo 'Coluna eh_republicacao_lote já existe em cotacoes ou erro: ' . $e->getMessage() . '<br>';
}

try {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS situacoes_lote (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            licitacao_id INTEGER NOT NULL,
            lote_id INTEGER NOT NULL,
            situacao TEXT NOT NULL,
            motivo TEXT NOT NULL DEFAULT '',
            data_situacao TEXT NOT NULL,
            criado_em TEXT NOT NULL DEFAULT (datetime('now')),
            FOREIGN KEY (licitacao_id) REFERENCES licitacoes(id) ON DELETE CASCADE,
            FOREIGN KEY (lote_id) REFERENCES lotes(id) ON DELETE CASCADE,
            UNIQUE (licitacao_id, lote_id)
        )"
    );
    echo 'Tabela <b>situacoes_lote</b> criada (ou já existia).<br>';
} catch (PDOException $e) {
    echo 'Erro ao criar tabela situacoes_lote: ' . $e->getMessage() . '<br>';
}

try {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS republicacoes_lote (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            licitacao_id INTEGER NOT NULL,
            lote_anterior_id INTEGER NOT NULL,
            lote_novo_id INTEGER NOT NULL,
            cotacao_nova_id INTEGER NOT NULL,
            numero_rodada INTEGER NOT NULL,
            situacao_anterior TEXT NOT NULL,
            motivo TEXT NOT NULL DEFAULT '',
            criado_em TEXT NOT NULL DEFAULT (datetime('now')),
            FOREIGN KEY (licitacao_id) REFERENCES licitacoes(id) ON DELETE CASCADE,
            FOREIGN KEY (lote_anterior_id) REFERENCES lotes(id) ON DELETE CASCADE,
            FOREIGN KEY (lote_novo_id) REFERENCES lotes(id) ON DELETE CASCADE,
            FOREIGN KEY (cotacao_nova_id) REFERENCES cotacoes(id) ON DELETE CASCADE,
            UNIQUE (lote_anterior_id)
        )"
    );
    echo 'Tabela <b>republicacoes_lote</b> criada (ou já existia).<br>';
} catch (PDOException $e) {
    echo 'Erro ao criar tabela republicacoes_lote: ' . $e->getMessage() . '<br>';
}

echo '<br><b>Migração concluída.</b>';
