<?php

require_once __DIR__ . '/../app/models/Database.php';

$pdo = Database::getConnection();

$id = 1; // seu ID de servidor, conforme confirmado anteriormente
$usuario = 'felippe';
$senha = '123';

$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

$stmt = $pdo->prepare(
    "UPDATE servidores SET usuario = :usuario, senha_hash = :senha_hash, nivel_acesso = 'ADMIN' WHERE id = :id"
);
$stmt->execute([
    'usuario' => $usuario,
    'senha_hash' => $senhaHash,
    'id' => $id,
]);

echo "Servidor ID {$id} definido como ADMIN. Usuário: {$usuario} / Senha: {$senha} (troque depois pelo Meu Perfil).";