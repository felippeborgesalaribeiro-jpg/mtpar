<?php

require_once __DIR__ . '/../models/SetorDemandante.php';
require_once __DIR__ . '/../helpers/auth.php';

class SetorDemandanteController
{
    public function listar(): void
    {
        exigirAdmin();

        $setores = SetorDemandante::buscarTodos();

        require __DIR__ . '/../views/admin/setores_demandantes.php';
    }

    public function criar(): void
    {
        exigirAdmin();

        $nome = trim($_POST['nome'] ?? '');

        if ($nome === '') {
            $_SESSION['erro'] = 'Nome do setor é obrigatório.';
            header('Location: index.php?action=setores_demandantes');
            exit;
        }

        if (SetorDemandante::buscarPorNome($nome) !== null) {
            $_SESSION['erro'] = 'Já existe um setor demandante cadastrado com esse nome.';
            header('Location: index.php?action=setores_demandantes');
            exit;
        }

        $setor = new SetorDemandante($nome);
        $setor->salvar();

        $_SESSION['sucesso'] = 'Setor demandante cadastrado.';
        header('Location: index.php?action=setores_demandantes');
        exit;
    }

    public function editar(): void
    {
        exigirAdmin();

        $id = (int) ($_POST['setor_id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');

        $setor = SetorDemandante::buscarPorId($id);

        if ($setor === null) {
            $_SESSION['erro'] = 'Setor demandante não encontrado.';
            header('Location: index.php?action=setores_demandantes');
            exit;
        }

        if ($nome === '') {
            $_SESSION['erro'] = 'Nome do setor é obrigatório.';
            header('Location: index.php?action=setores_demandantes');
            exit;
        }

        $duplicado = SetorDemandante::buscarPorNome($nome);
        if ($duplicado !== null && $duplicado->id !== $setor->id) {
            $_SESSION['erro'] = 'Já existe um setor demandante cadastrado com esse nome.';
            header('Location: index.php?action=setores_demandantes');
            exit;
        }

        $setor->nome = $nome;
        $setor->salvar();

        $_SESSION['sucesso'] = 'Setor demandante atualizado.';
        header('Location: index.php?action=setores_demandantes');
        exit;
    }

    public function excluir(): void
    {
        exigir_csrf();
        exigirAdmin();

        $id = (int) ($_POST['id'] ?? 0);
        $setor = SetorDemandante::buscarPorId($id);

        if ($setor !== null) {
            $setor->excluir();
        }

        $_SESSION['sucesso'] = 'Setor demandante removido.';
        header('Location: index.php?action=setores_demandantes');
        exit;
    }
}
