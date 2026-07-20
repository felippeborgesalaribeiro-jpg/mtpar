<?php

require_once __DIR__ . '/../models/Licitacao.php';
require_once __DIR__ . '/../models/Servidor.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/formatacao.php';

class LicitacaoController
{
    public function listar(): void
    {
        exigirLogin();

        $licitacoes = Licitacao::buscarTodas();
        $servidores = Servidor::buscarTodos();

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

        $servidorResponsavelId = trim($_POST['servidor_responsavel_id'] ?? '');
        $licitacao->servidorResponsavelId = $servidorResponsavelId !== '' ? (int) $servidorResponsavelId : null;
        $licitacao->editalLicitacao = trim($_POST['edital_licitacao'] ?? '');
        $licitacao->realizacaoSessaoPublica = trim($_POST['realizacao_sessao_publica'] ?? '') ?: null;
        $licitacao->valorAdjudicado = ($_POST['valor_adjudicado'] ?? '') !== ''
            ? converterMoedaBrParaFloat($_POST['valor_adjudicado'])
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

    /**
     * Marca o processo como adjudicado e homologado - o ato formal que
     * encerra a licitacao no setor. So grava a data se ainda nao estiver
     * finalizada, pra nao correr o risco de um clique acidental sobrescrever
     * a data ja confirmada.
     */
    public function finalizar(): void
    {
        exigirLogin();

        $id = (int) ($_POST['licitacao_id'] ?? 0);
        $licitacao = Licitacao::buscarPorId($id);

        if ($licitacao === null) {
            echo 'Licitação não encontrada.';
            return;
        }

        if (!$licitacao->estaFinalizada()) {
            $data = trim($_POST['data'] ?? '') ?: date('Y-m-d');
            $licitacao->dataAdjudicacaoHomologacao = $data;
            $licitacao->salvar();
        }

        header('Location: index.php?action=ver_demanda&id=' . $licitacao->demandaId);
        exit;
    }
}