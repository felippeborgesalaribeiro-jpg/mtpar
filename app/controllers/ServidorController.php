<?php

require_once __DIR__ . '/../models/Servidor.php';
require_once __DIR__ . '/../helpers/auth.php';

class ServidorController
{
    public function listar(): void
    {
        exigirAdmin();

        $servidores = Servidor::buscarTodos();

        require __DIR__ . '/../views/servidores.php';
    }

    public function criar(): void
    {
        exigirAdmin();

        $nome = trim($_POST['nome'] ?? '');
        $matricula = trim($_POST['matricula'] ?? '');
        $cargo = trim($_POST['cargo'] ?? '');
        $usuario = trim($_POST['usuario'] ?? '');
        $nivelAcesso = $_POST['nivel_acesso'] ?? Servidor::NIVEL_COMUM;

        if ($nome === '' || $usuario === '') {
            echo 'Nome e usuário são obrigatórios.';
            return;
        }

        $servidor = new Servidor($nome, $matricula, $cargo, $usuario, '', $nivelAcesso);
        $servidor->definirSenha('123');
        $servidor->salvar();

        header('Location: index.php?action=servidores');
        exit;
    }

    public function editar(): void
    {
        exigirAdmin();

        $id = (int) ($_POST['servidor_id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $matricula = trim($_POST['matricula'] ?? '');
        $cargo = trim($_POST['cargo'] ?? '');
        $usuario = trim($_POST['usuario'] ?? '');
        $nivelAcesso = $_POST['nivel_acesso'] ?? Servidor::NIVEL_COMUM;

        $servidor = Servidor::buscarPorId($id);

        if ($servidor === null) {
            echo 'Servidor não encontrado.';
            return;
        }

        $servidor->nome = $nome;
        $servidor->matricula = $matricula;
        $servidor->cargo = $cargo;
        $servidor->usuario = $usuario;
        $servidor->nivelAcesso = $nivelAcesso;
        $servidor->salvar();

        header('Location: index.php?action=servidores');
        exit;
    }

    public function resetarSenha(): void
    {
        exigirAdmin();

        $id = (int) ($_GET['id'] ?? 0);
        $servidor = Servidor::buscarPorId($id);

        if ($servidor !== null) {
            $servidor->resetarSenhaPadrao();
        }

        header('Location: index.php?action=servidores&senha_resetada=1');
        exit;
    }

    public function excluir(): void
    {
        exigirAdmin();

        $id = (int) ($_GET['id'] ?? 0);

        $servidor = Servidor::buscarPorId($id);

        if ($servidor !== null) {
            $servidor->excluir();
        }

        header('Location: index.php?action=servidores');
        exit;
    }
}