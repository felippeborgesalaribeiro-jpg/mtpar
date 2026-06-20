<?php

require_once __DIR__ . '/../app/models/Database.php';

$schemaPath = __DIR__ . '/schema.sql';
$schemaSql = file_get_contents($schemaPath);

$pdo = Database::getConnection();
$pdo->exec($schemaSql);

echo "Banco de dados inicializado com sucesso!";