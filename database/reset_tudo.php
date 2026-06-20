<?php

require_once __DIR__ . '/../app/models/Database.php';

$pdo = Database::getConnection();

$tabelas = ['precos', 'itens', 'lotes', 'cotacoes', 'servidores', 'parametros'];

foreach ($tabelas as $tabela) {
    $pdo->exec("DELETE FROM {$tabela}");
    $pdo->exec("DELETE FROM sqlite_sequence WHERE name = '{$tabela}'");
    echo "Tabela {$tabela} limpa.<br>";
}

echo '<br><b>Banco de dados completamente resetado.</b>';