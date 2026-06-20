<?php

require_once __DIR__ . '/../app/models/Database.php';

$pdo = Database::getConnection();

$senhaHash = password_hash('123', PASSWORD_DEFAULT);

$stmt = $pdo->prepare("UPDATE servidores SET senha_hash = :senha_hash WHERE senha_hash = ''");
$stmt->execute(['senha_hash' => $senhaHash]);

echo $stmt->rowCount() . ' servidor(es) atualizados com senha padrão 123.';