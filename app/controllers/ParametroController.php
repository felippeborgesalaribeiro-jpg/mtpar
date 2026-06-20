<?php

require_once __DIR__ . '/../models/Parametro.php';
require_once __DIR__ . '/../helpers/auth.php';

class ParametroController
{
    public function listar(): void
    {
        exigirLogin();

        $parametros = Parametro::buscarTodos();

        require __DIR__ . '/../views/parametros.php';
    }

    public function criar(): void
    {
        exigirLogin();

        $nome = trim($_POST['nome'] ?? '');
        $precoPublico = isset($_POST['preco_publico']);

        if ($nome === '') {
            echo 'Nome é obrigatório.';
            return;
        }

        $parametro = new Parametro($nome, $precoPublico);
        $parametro->salvar();

        header('Location: index.php?action=parametros');
        exit;
    }

    public function editar(): void
    {
        exigirLogin();

        $id = (int) ($_POST['parametro_id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $precoPublico = isset($_POST['preco_publico']);

        $parametro = Parametro::buscarPorId($id);

        if ($parametro === null) {
            echo 'Parâmetro não encontrado.';
            return;
        }

        $parametro->nome = $nome;
        $parametro->precoPublico = $precoPublico;
        $parametro->salvar();

        header('Location: index.php?action=parametros');
        exit;
    }

    public function excluir(): void
    {
        exigirLogin();

        $id = (int) ($_GET['id'] ?? 0);

        $parametro = Parametro::buscarPorId($id);

        if ($parametro !== null) {
            $parametro->excluir();
        }

        header('Location: index.php?action=parametros');
        exit;
    }
}