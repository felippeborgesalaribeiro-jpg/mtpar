<?php

/**
 * Primeiro acesso de uma instalacao nova (maquina nova, banco recem-criado
 * pelo database/init.php). Um banco vindo so do schema.sql nao tem nenhum
 * servidor cadastrado - e como o cadastro de servidores exige estar logado
 * como ADMIN, sem isso nao da pra entrar no sistema de jeito nenhum.
 *
 * So cria alguma coisa se a tabela estiver realmente vazia, entao rodar de
 * novo por engano numa instalacao ja em uso nao faz nada.
 */

require_once __DIR__ . '/../app/models/Database.php';
require_once __DIR__ . '/../app/models/Servidor.php';
require_once __DIR__ . '/../app/models/NivelAcesso.php';

$nome     = 'Administrador';
$usuario  = 'felippe';
$senha    = '123';

$pdo = Database::getConnection();
$totalServidores = (int) $pdo->query('SELECT COUNT(*) FROM servidores')->fetchColumn();

if ($totalServidores > 0) {
    echo "Já existe servidor cadastrado neste banco ({$totalServidores}). Nada foi alterado.<br>";
    echo 'Para transformar um servidor existente em ADMIN, use o database/definir_admin.php.';
    return;
}

$servidor = new Servidor($nome, '', '', $usuario, '', NivelAcesso::Admin);
$servidor->definirSenha($senha);
$servidor->senhaProvisoria = true;
$servidor->salvar();

echo "Administrador criado com sucesso.<br>";
echo "Usuário: <b>{$usuario}</b><br>";
echo "Senha: <b>{$senha}</b><br><br>";
echo 'O sistema vai pedir a troca da senha no primeiro login.';
