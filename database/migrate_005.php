<?php

require_once __DIR__ . '/../app/models/Database.php';

$pdo = Database::getConnection();

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS demandas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        numero_processo TEXT NOT NULL,
        link_sigadoc TEXT NOT NULL DEFAULT '',
        setor_demandante TEXT NOT NULL DEFAULT '',
        data_recebimento TEXT NOT NULL,
        objeto TEXT NOT NULL DEFAULT '',
        servidor_responsavel_id INTEGER,
        status TEXT NOT NULL DEFAULT 'EM ANDAMENTO',
        criado_em TEXT NOT NULL DEFAULT (datetime('now')),
        FOREIGN KEY (servidor_responsavel_id) REFERENCES servidores(id)
    )"
);
echo 'Tabela demandas criada.<br>';

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS licitacoes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        demanda_id INTEGER NOT NULL,
        numero_processo TEXT NOT NULL,
        link_sigadoc TEXT NOT NULL DEFAULT '',
        setor_demandante TEXT NOT NULL DEFAULT '',
        data_recebimento TEXT NOT NULL,
        objeto TEXT NOT NULL DEFAULT '',
        servidor_responsavel_id INTEGER,
        edital_licitacao TEXT NOT NULL DEFAULT '',
        realizacao_sessao_publica TEXT,
        valor_estimado REAL,
        valor_adjudicado REAL,
        encaminhado_pactuacao_contrato TEXT,
        criado_em TEXT NOT NULL DEFAULT (datetime('now')),
        FOREIGN KEY (demanda_id) REFERENCES demandas(id) ON DELETE CASCADE,
        FOREIGN KEY (servidor_responsavel_id) REFERENCES servidores(id)
    )"
);
echo 'Tabela licitacoes criada.<br>';

echo '<br>Migração concluída.';