<?php

require_once __DIR__ . '/../models/Parametro.php';

class ParametroController
{
    public function listar(): void
    {
        $parametros = Parametro::buscarTodos();

        require __DIR__ . '/../views/parametros.php';
    }

    public function criar(): void
    {
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
        $id = (int) ($_GET['id'] ?? 0);

        $parametro = Parametro::buscarPorId($id);

        if ($parametro !== null) {
            $parametro->excluir();
        }

        header('Location: index.php?action=parametros');
        exit;
    }
}