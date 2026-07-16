<?php

require_once __DIR__ . '/../app/models/Database.php';

$pdo = Database::getConnection();

try {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS empresas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            nome_fantasia TEXT NOT NULL DEFAULT '',
            cnpj TEXT NOT NULL UNIQUE,
            criado_em TEXT NOT NULL DEFAULT (datetime('now'))
        )"
    );
    echo 'Tabela <b>empresas</b> criada (ou já existia).<br>';
} catch (PDOException $e) {
    echo 'Erro ao criar tabela empresas: ' . $e->getMessage() . '<br>';
}

try {
    $pdo->exec('ALTER TABLE licitacoes ADD COLUMN empresa_vencedora_id INTEGER REFERENCES empresas(id)');
    echo 'Coluna empresa_vencedora_id adicionada à tabela licitacoes.<br>';
} catch (PDOException $e) {
    echo 'Coluna empresa_vencedora_id já existe em licitacoes ou erro: ' . $e->getMessage() . '<br>';
}

try {
    $pdo->exec("ALTER TABLE licitacoes ADD COLUMN observacoes_proposta_vencedora TEXT NOT NULL DEFAULT ''");
    echo 'Coluna observacoes_proposta_vencedora adicionada à tabela licitacoes.<br>';
} catch (PDOException $e) {
    echo 'Coluna observacoes_proposta_vencedora já existe em licitacoes ou erro: ' . $e->getMessage() . '<br>';
}

try {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS itens_proposta_vencedora (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            licitacao_id INTEGER NOT NULL,
            item_id INTEGER NOT NULL,
            valor_proposto REAL NOT NULL,
            criado_em TEXT NOT NULL DEFAULT (datetime('now')),
            FOREIGN KEY (licitacao_id) REFERENCES licitacoes(id) ON DELETE CASCADE,
            FOREIGN KEY (item_id) REFERENCES itens(id) ON DELETE CASCADE,
            UNIQUE (licitacao_id, item_id)
        )"
    );
    echo 'Tabela <b>itens_proposta_vencedora</b> criada (ou já existia).<br>';
} catch (PDOException $e) {
    echo 'Erro ao criar tabela itens_proposta_vencedora: ' . $e->getMessage() . '<br>';
}

echo '<br><b>Migração concluída.</b>';
