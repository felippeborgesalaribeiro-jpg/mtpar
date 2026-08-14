<?php

require_once __DIR__ . '/../app/models/Database.php';

$pdo = Database::getConnection();

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS setores_demandantes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nome TEXT NOT NULL UNIQUE,
        criado_em TEXT NOT NULL DEFAULT (datetime('now'))
    )"
);
echo 'Tabela setores_demandantes criada.<br>';

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS etapas_processo (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nome TEXT NOT NULL UNIQUE,
        ordem INTEGER NOT NULL,
        criado_em TEXT NOT NULL DEFAULT (datetime('now'))
    )"
);
echo 'Tabela etapas_processo criada.<br>';

try {
    $pdo->exec('ALTER TABLE licitacoes ADD COLUMN enviado_aplic_em TEXT');
    echo 'Coluna enviado_aplic_em adicionada à tabela licitacoes.<br>';
} catch (PDOException $e) {
    echo 'Coluna enviado_aplic_em já existe em licitacoes ou erro: ' . $e->getMessage() . '<br>';
}

// Povoa setores_demandantes com os nomes ja usados nas demandas existentes,
// pra ninguem comecar com a lista vazia. Etapas do processo ficam de fora
// de proposito - o admin cadastra do zero, na ordem que fizer sentido pro
// setor (nao da pra inferir ordem a partir do texto livre que ja existe).
$nomesExistentes = $pdo->query(
    "SELECT DISTINCT TRIM(setor_demandante) AS nome FROM demandas
     WHERE TRIM(setor_demandante) != '' ORDER BY nome"
)->fetchAll(PDO::FETCH_COLUMN);

$stmtInserirSetor = $pdo->prepare('INSERT OR IGNORE INTO setores_demandantes (nome) VALUES (:nome)');
foreach ($nomesExistentes as $nome) {
    $stmtInserirSetor->execute(['nome' => $nome]);
}
echo count($nomesExistentes) . ' setor(es) demandante(s) importado(s) a partir das demandas existentes.<br>';

echo '<br><b>Migração concluída.</b>';
