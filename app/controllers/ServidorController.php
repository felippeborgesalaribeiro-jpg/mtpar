<?php

require_once __DIR__ . '/../models/Servidor.php';

class ServidorController
{
    public function listar(): void
    {
        $servidores = Servidor::buscarTodos();

        require __DIR__ . '/../views/servidores.php';
    }

    public function criar(): void
    {
        $nome = trim($_POST['nome'] ?? '');
        $matricula = trim($_POST['matricula'] ?? '');
        $cargo = trim($_POST['cargo'] ?? '');

        if ($nome === '') {
            echo 'Nome é obrigatório.';
            return;
        }

        $servidor = new Servidor($nome, $matricula, $cargo);
        $servidor->salvar();

        header('Location: index.php?action=servidores');
        exit;
    }

    public function editar(): void
    {
        $id = (int) ($_POST['servidor_id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $matricula = trim($_POST['matricula'] ?? '');
        $cargo = trim($_POST['cargo'] ?? '');

        $servidor = Servidor::buscarPorId($id);

        if ($servidor === null) {
            echo 'Servidor não encontrado.';
            return;
        }

        $servidor->nome = $nome;
        $servidor->matricula = $matricula;
        $servidor->cargo = $cargo;
        $servidor->salvar();

        header('Location: index.php?action=servidores');
        exit;
    }

    public function excluir(): void
    {
        $id = (int) ($_GET['id'] ?? 0);

        $servidor = Servidor::buscarPorId($id);

        if ($servidor !== null) {
            $servidor->excluir();
        }

        header('Location: index.php?action=servidores');
        exit;
    }
}