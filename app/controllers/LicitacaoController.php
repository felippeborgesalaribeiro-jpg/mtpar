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

        // Evita N+1: em vez de cada linha da tabela chamar buscarServidorResponsavel,
        // busca os servidores necessarios em uma unica consulta e passa como mapa.
        $mapaServidores = Servidor::mapaPorIds(array_map(
            fn(Licitacao $l) => $l->servidorResponsavelId, $licitacoes
        ));

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

        // O servidor responsavel NAO e editado aqui: ele mora na Demanda
        // (fonte unica de verdade), e a Licitacao so o le de la. Editar o
        // responsavel e feito pelo formulario da Demanda, no topo da tela.
        $licitacao->editalLicitacao = trim($_POST['edital_licitacao'] ?? '');
        $licitacao->realizacaoSessaoPublica = trim($_POST['realizacao_sessao_publica'] ?? '') ?: null;
        $licitacao->valorAdjudicado = ($_POST['valor_adjudicado'] ?? '') !== ''
            ? converterMoedaBrParaFloat($_POST['valor_adjudicado'])
            : null;
        $licitacao->encaminhadoPactuacaoContrato = trim($_POST['encaminhado_pactuacao_contrato'] ?? '') ?: null;

        $licitacao->salvar();

        header('Location: index.php?action=ver_demanda&id=' . $licitacao->demandaId);
        exit;
    }

    public function excluir(): void
    {
        exigirLogin();

        $id = (int) ($_POST['id'] ?? 0);
        $licitacao = Licitacao::buscarPorId($id);

        if ($licitacao !== null) {
            $licitacao->excluir();
        }

        header('Location: index.php?action=licitacoes');
        exit;
    }

}