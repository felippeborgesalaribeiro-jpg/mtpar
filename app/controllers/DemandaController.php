<?php

require_once __DIR__ . '/../models/Demanda.php';
require_once __DIR__ . '/../models/Licitacao.php';
require_once __DIR__ . '/../models/Servidor.php';
require_once __DIR__ . '/../models/Cotacao.php';
require_once __DIR__ . '/../models/ProcessoVantajosidade.php';
require_once __DIR__ . '/../models/LotePropostaVencedora.php';
require_once __DIR__ . '/../models/SituacaoLote.php';
require_once __DIR__ . '/../models/SetorDemandante.php';
require_once __DIR__ . '/../helpers/auth.php';

class DemandaController
{
    public function listar(): void
    {
        exigirLogin();

        $demandas = Demanda::buscarTodas();

        require __DIR__ . '/../views/demandas.php';
    }

    public function mostrar(): void
    {
        exigirLogin();

        $id = (int) ($_GET['id'] ?? 0);
        $demanda = Demanda::buscarPorId($id);

        if ($demanda === null) {
            echo 'Demanda não encontrada.';
            return;
        }

        $modo = $_GET['modo'] ?? 'ver';
        $servidores = Servidor::buscarTodos();
        $servidorResponsavel = $demanda->buscarServidorResponsavel();
        $licitacao = Licitacao::buscarPorDemandaId($demanda->id);
        $cotacao = $demanda->buscarCotacaoVinculada();
        $vantajosidade = $demanda->buscarVantajosidadeVinculada();
        $resumoLotes = $this->montarResumoLotes($licitacao);
        [$linkVoltar, $labelVoltar] = $this->resolverVoltar();
        $setoresDemandantes = SetorDemandante::buscarTodos();

        require __DIR__ . '/../views/demanda_detalhe.php';
    }

    /**
     * A tela do Processo e aberta a partir de varios lugares (lista de
     * Demandas, lista de Licitacoes, tela da Cotacao). O botao "Voltar"
     * deve levar de volta pra onde a pessoa realmente veio, nao sempre
     * pra lista geral de Demandas.
     *
     * @return array{0: string, 1: string} [link, label]
     */
    private function resolverVoltar(): array
    {
        $origem = $_GET['origem'] ?? '';
        $origemId = (int) ($_GET['origem_id'] ?? 0);

        if ($origem === 'licitacoes') {
            return ['index.php?action=licitacoes', 'Voltar para Licitações'];
        }

        if ($origem === 'cotacao' && $origemId > 0) {
            return ['index.php?action=cotacao&id=' . $origemId, 'Voltar para a Cotação'];
        }

        return ['index.php?action=demandas', 'Voltar'];
    }

    /**
     * So retorna um resumo (pra mostrar em vez do badge simples de
     * "Processo finalizado") quando ha mais de um lote ou algum lote
     * fracassou/desertou - senao mantem o comportamento simples de antes.
     */
    private function montarResumoLotes(?Licitacao $licitacao): ?array
    {
        if ($licitacao === null) {
            return null;
        }

        $lotesAtivos = $licitacao->buscarLotesAtivos();
        $temFracasso = false;
        $comVencedor = 0;

        foreach ($lotesAtivos as $entrada) {
            $loteAtualId = $entrada['lote_atual']->id;

            if (SituacaoLote::buscarPorLicitacaoELote($licitacao->id, $loteAtualId) !== null) {
                $temFracasso = true;
            } elseif (LotePropostaVencedora::buscarPorLicitacaoELote($licitacao->id, $loteAtualId) !== null) {
                $comVencedor++;
            }
        }

        if (count($lotesAtivos) <= 1 && !$temFracasso) {
            return null;
        }

        return [
            'total' => count($lotesAtivos),
            'com_vencedor' => $comVencedor,
        ];
    }

    public function criar(): void
    {
        exigirLogin();

        $numeroProcesso = trim($_POST['numero_processo'] ?? '');
        $linkSigadoc = trim($_POST['link_sigadoc'] ?? '');
        $setorDemandante = trim($_POST['setor_demandante'] ?? '');
        $dataRecebimento = trim($_POST['data_recebimento'] ?? '');
        $objeto = trim($_POST['objeto'] ?? '');
        $servidorResponsavelId = (int) ($_POST['servidor_responsavel_id'] ?? 0) ?: null;
        $status = $_POST['status'] ?? Demanda::STATUS_EM_ANDAMENTO;

        if ($numeroProcesso === '' || $dataRecebimento === '') {
            echo 'Número do processo e data de recebimento são obrigatórios.';
            return;
        }

        $demanda = new Demanda($numeroProcesso, $dataRecebimento, $linkSigadoc, $setorDemandante, $objeto, $servidorResponsavelId, $status);
        $demanda->salvar();

        header('Location: index.php?action=ver_demanda&id=' . $demanda->id);
        exit;
    }

    public function editarInline(): void
    {
        exigirLogin();

        $id = (int) ($_POST['demanda_id'] ?? 0);
        $demanda = Demanda::buscarPorId($id);

        if ($demanda === null) {
            echo 'Demanda não encontrada.';
            return;
        }

        $statusAnterior = $demanda->status;

        $demanda->numeroProcesso = trim($_POST['numero_processo'] ?? '');
        $demanda->linkSigadoc = trim($_POST['link_sigadoc'] ?? '');
        $demanda->setorDemandante = trim($_POST['setor_demandante'] ?? '');
        $demanda->dataRecebimento = trim($_POST['data_recebimento'] ?? '');
        $demanda->objeto = trim($_POST['objeto'] ?? '');
        $demanda->servidorResponsavelId = (int) ($_POST['servidor_responsavel_id'] ?? 0) ?: null;
        $demanda->status = $_POST['status'] ?? Demanda::STATUS_EM_ANDAMENTO;

        $demanda->salvar();

        $mudouParaConcluido = $statusAnterior !== Demanda::STATUS_CONCLUIDO && $demanda->status === Demanda::STATUS_CONCLUIDO;

        if ($mudouParaConcluido) {
            $licitacaoExistente = Licitacao::buscarPorDemandaId($demanda->id);
            if ($licitacaoExistente === null) {
                Licitacao::criarApartirDeDemanda($demanda);
            }
        }

        $origem = trim($_POST['origem'] ?? '');
        $origemId = (int) ($_POST['origem_id'] ?? 0);
        $querystringOrigem = $origem !== '' ? '&origem=' . urlencode($origem) . ($origemId > 0 ? '&origem_id=' . $origemId : '') : '';

        header('Location: index.php?action=ver_demanda&id=' . $demanda->id . $querystringOrigem);
        exit;
    }

    public function excluir(): void
    {
        exigirLogin();

        $id = (int) ($_GET['id'] ?? 0);
        $demanda = Demanda::buscarPorId($id);

        if ($demanda !== null) {
            $demanda->excluir();
        }

        header('Location: index.php?action=demandas');
        exit;
    }
}