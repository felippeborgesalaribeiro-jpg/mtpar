<?php

require_once __DIR__ . '/../app/models/Database.php';

$pdo = Database::getConnection();
$pdo->exec('DELETE FROM servidores');
$pdo->exec("DELETE FROM sqlite_sequence WHERE name = 'servidores'");

echo 'Tabela de servidores limpa. O próximo cadastro começará do ID 1.';