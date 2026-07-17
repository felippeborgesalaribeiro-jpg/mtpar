<?php

require_once __DIR__ . '/../app/models/Database.php';

$pdo = Database::getConnection();

try {
    $pdo->exec("ALTER TABLE licitacoes ADD COLUMN data_adjudicacao_homologacao TEXT");
    echo 'Coluna data_adjudicacao_homologacao adicionada à tabela licitacoes.<br>';
} catch (PDOException $e) {
    echo 'Coluna data_adjudicacao_homologacao já existe em licitacoes ou erro: ' . $e->getMessage() . '<br>';
}

try {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS lotes_proposta_vencedora (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            licitacao_id INTEGER NOT NULL,
            lote_id INTEGER NOT NULL,
            empresa_vencedora_id INTEGER NOT NULL,
            criado_em TEXT NOT NULL DEFAULT (datetime('now')),
            FOREIGN KEY (licitacao_id) REFERENCES licitacoes(id) ON DELETE CASCADE,
            FOREIGN KEY (lote_id) REFERENCES lotes(id) ON DELETE CASCADE,
            FOREIGN KEY (empresa_vencedora_id) REFERENCES empresas(id),
            UNIQUE (licitacao_id, lote_id)
        )"
    );
    echo 'Tabela <b>lotes_proposta_vencedora</b> criada (ou já existia).<br>';
} catch (PDOException $e) {
    echo 'Erro ao criar tabela lotes_proposta_vencedora: ' . $e->getMessage() . '<br>';
}

// A partir desta migração, a empresa vencedora passa a ser definida por
// lote (tabela lotes_proposta_vencedora) em vez de uma só para a
// licitação inteira. A coluna licitacoes.empresa_vencedora_id continua
// existindo no banco (para não arriscar um DROP COLUMN em produção),
// mas o sistema não lê nem grava mais nela a partir de agora.

echo '<br><b>Migração concluída.</b>';
