<?php

require_once __DIR__ . '/../app/models/Database.php';

$pdo = Database::getConnection();

$tabelas = ['demandas', 'cotacoes', 'processos_vantajosidade'];

foreach ($tabelas as $tabela) {
    try {
        $pdo->exec("ALTER TABLE {$tabela} ADD COLUMN deleted_at TEXT DEFAULT NULL");
        echo "Coluna deleted_at adicionada à tabela <b>{$tabela}</b>.<br>";
    } catch (PDOException $e) {
        echo "Coluna deleted_at já existe em {$tabela} ou erro: " . $e->getMessage() . "<br>";
    }
}

echo '<br><b>Migração concluída.</b>';