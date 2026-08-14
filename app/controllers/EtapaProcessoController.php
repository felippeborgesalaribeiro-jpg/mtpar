<?php

require_once __DIR__ . '/../models/EtapaProcesso.php';
require_once __DIR__ . '/../helpers/auth.php';

class EtapaProcessoController
{
    public function listar(): void
    {
        exigirAdmin();

        $etapas = EtapaProcesso::buscarTodas();

        require __DIR__ . '/../views/admin/etapas_processo.php';
    }

    public function criar(): void
    {
        exigirAdmin();

        $nome = trim($_POST['nome'] ?? '');

        if ($nome === '') {
            $_SESSION['erro'] = 'Nome da etapa é obrigatório.';
            header('Location: index.php?action=etapas_processo');
            exit;
        }

        if (EtapaProcesso::buscarPorNome($nome) !== null) {
            $_SESSION['erro'] = 'Já existe uma etapa cadastrada com esse nome.';
            header('Location: index.php?action=etapas_processo');
            exit;
        }

        $etapa = new EtapaProcesso($nome);
        $etapa->salvar();

        $_SESSION['sucesso'] = 'Etapa do processo cadastrada.';
        header('Location: index.php?action=etapas_processo');
        exit;
    }

    public function editar(): void
    {
        exigirAdmin();

        $id = (int) ($_POST['etapa_id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');

        $etapa = EtapaProcesso::buscarPorId($id);

        if ($etapa === null) {
            $_SESSION['erro'] = 'Etapa não encontrada.';
            header('Location: index.php?action=etapas_processo');
            exit;
        }

        if ($nome === '') {
            $_SESSION['erro'] = 'Nome da etapa é obrigatório.';
            header('Location: index.php?action=etapas_processo');
            exit;
        }

        $duplicada = EtapaProcesso::buscarPorNome($nome);
        if ($duplicada !== null && $duplicada->id !== $etapa->id) {
            $_SESSION['erro'] = 'Já existe uma etapa cadastrada com esse nome.';
            header('Location: index.php?action=etapas_processo');
            exit;
        }

        $etapa->nome = $nome;
        $etapa->salvar();

        $_SESSION['sucesso'] = 'Etapa do processo atualizada.';
        header('Location: index.php?action=etapas_processo');
        exit;
    }

    public function excluir(): void
    {
        exigirAdmin();

        $id = (int) ($_GET['id'] ?? 0);
        $etapa = EtapaProcesso::buscarPorId($id);

        if ($etapa !== null) {
            $etapa->excluir();
        }

        $_SESSION['sucesso'] = 'Etapa do processo removida.';
        header('Location: index.php?action=etapas_processo');
        exit;
    }

    public function moverParaCima(): void
    {
        exigirAdmin();

        $etapa = EtapaProcesso::buscarPorId((int) ($_GET['id'] ?? 0));
        $etapa?->moverParaCima();

        header('Location: index.php?action=etapas_processo');
        exit;
    }

    public function moverParaBaixo(): void
    {
        exigirAdmin();

        $etapa = EtapaProcesso::buscarPorId((int) ($_GET['id'] ?? 0));
        $etapa?->moverParaBaixo();

        header('Location: index.php?action=etapas_processo');
        exit;
    }
}
