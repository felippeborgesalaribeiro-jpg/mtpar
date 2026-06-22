<?php

require_once __DIR__ . '/../models/Licitacao.php';
require_once __DIR__ . '/../helpers/auth.php';

class LicitacaoController
{
    public function listar(): void
    {
        exigirLogin();

        $licitacoes = Licitacao::buscarTodas();

        require __DIR__ . '/../views/licitacoes.php';
    }

    public function editar(): void
    {
        exigirLogin();

        $id = (int) ($_POST['licitacao_id'] ?? 0);
        $licitacao = Licitacao::buscarPorId($id);

        if ($licitacao === null) {
            echo 'Licitação não encontrada.';
            return;
        }

        $licitacao->editalLicitacao = trim($_POST['edital_licitacao'] ?? '');
        $licitacao->realizacaoSessaoPublica = trim($_POST['realizacao_sessao_publica'] ?? '') ?: null;
        $licitacao->valorEstimado = ($_POST['valor_estimado'] ?? '') !== ''
            ? (float) str_replace(',', '.', $_POST['valor_estimado'])
            : null;
        $licitacao->valorAdjudicado = ($_POST['valor_adjudicado'] ?? '') !== ''
            ? (float) str_replace(',', '.', $_POST['valor_adjudicado'])
            : null;
        $licitacao->encaminhadoPactuacaoContrato = trim($_POST['encaminhado_pactuacao_contrato'] ?? '') ?: null;

        $licitacao->salvar();

        header('Location: index.php?action=licitacoes');
        exit;
    }

    public function excluir(): void
    {
        exigirLogin();

        $id = (int) ($_GET['id'] ?? 0);
        $licitacao = Licitacao::buscarPorId($id);

        if ($licitacao !== null) {
            $licitacao->excluir();
        }

        header('Location: index.php?action=licitacoes');
        exit;
    }
}