<?php

require_once __DIR__ . '/../app/models/Database.php';

$pdo = Database::getConnection();

try {
    $pdo->exec('ALTER TABLE cotacoes ADD COLUMN demanda_id INTEGER REFERENCES demandas(id)');
    echo 'Coluna demanda_id adicionada à tabela cotacoes.<br>';
} catch (PDOException $e) {
    echo 'Erro ao adicionar em cotacoes: ' . $e->getMessage() . '<br>';
}

try {
    $pdo->exec('ALTER TABLE processos_vantajosidade ADD COLUMN demanda_id INTEGER REFERENCES demandas(id)');
    echo 'Coluna demanda_id adicionada à tabela processos_vantajosidade.<br>';
} catch (PDOException $e) {
    echo 'Erro ao adicionar em processos_vantajosidade: ' . $e->getMessage() . '<br>';
}

echo '<br><b>Migração concluída.</b>';