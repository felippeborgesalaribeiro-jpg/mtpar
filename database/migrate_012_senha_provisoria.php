<?php

require_once __DIR__ . '/../app/models/Database.php';

$pdo = Database::getConnection();

try {
    $pdo->exec("ALTER TABLE servidores ADD COLUMN senha_provisoria INTEGER NOT NULL DEFAULT 0");
    echo 'Coluna senha_provisoria adicionada à tabela servidores.<br>';
} catch (PDOException $e) {
    echo 'Coluna senha_provisoria já existe em servidores ou erro: ' . $e->getMessage() . '<br>';
}

// Servidores ja existentes ficam com senha_provisoria = 0 (nao ha como saber
// retroativamente quem ainda esta com a senha padrao "123"). A partir de
// agora, todo servidor novo ou com senha resetada pelo admin nasce marcado
// como provisorio, ate a propria pessoa definir uma senha no Perfil.

echo '<br><b>Migração concluída.</b>';
