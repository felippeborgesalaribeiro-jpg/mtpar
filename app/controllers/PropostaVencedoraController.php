<?php

require_once __DIR__ . '/../models/Licitacao.php';
require_once __DIR__ . '/../models/Cotacao.php';
require_once __DIR__ . '/../models/Demanda.php';
require_once __DIR__ . '/../models/Empresa.php';
require_once __DIR__ . '/../models/Lote.php';
require_once __DIR__ . '/../models/Item.php';
require_once __DIR__ . '/../models/ItemPropostaVencedora.php';
require_once __DIR__ . '/../models/LotePropostaVencedora.php';
require_once __DIR__ . '/../models/SituacaoLote.php';
require_once __DIR__ . '/../models/RepublicacaoLote.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/formatacao.php';

class PropostaVencedoraController
{
    public function mostrar(): void
    {
        exigirLogin();

        $licitacaoId = (int) ($_GET['id'] ?? 0);
        $licitacao = Licitacao::buscarPorId($licitacaoId);

        if ($licitacao === null) {
            echo 'Licitação não encontrada.';
            return;
        }

        $demanda = Demanda::buscarPorId($licitacao->demandaId);
        $cotacao = Cotacao::buscarPorDemandaId($licitacao->demandaId);
        $lotesAtivos = $licitacao->buscarLotesAtivos();
        $valoresPropostos = ItemPropostaVencedora::buscarMapaPorLicitacao($licitacao->id);
        $lotesComEmpresa = LotePropostaVencedora::buscarMapaPorLicitacao($licitacao->id);
        $situacoesLote = SituacaoLote::buscarMapaPorLicitacao($licitacao->id);
        $historicoRepublicacoes = RepublicacaoLote::buscarHistoricoPorLicitacao($licitacao->id);

        require __DIR__ . '/../views/proposta_vencedora.php';
    }

    public function salvar(): void
    {
        exigirLogin();

        $licitacaoId = (int) ($_POST['licitacao_id'] ?? 0);
        $licitacao = Licitacao::buscarPorId($licitacaoId);

        if ($licitacao === null) {
            echo 'Licitação não encontrada.';
            return;
        }

        $licitacao->observacoesPropostaVencedora = trim($_POST['observacoes'] ?? '');
        $licitacao->salvar();

        foreach (($_POST['lote_empresa_vencedora_id'] ?? []) as $loteId => $empresaId) {
            $empresaId = (int) $empresaId;

            if ($empresaId === 0) {
                continue;
            }

            $lotePropostaVencedora = new LotePropostaVencedora($licitacao->id, (int) $loteId, $empresaId);
            $lotePropostaVencedora->salvar();
        }

        foreach (($_POST['valor_proposto'] ?? []) as $itemId => $valorTexto) {
            $valorTexto = trim((string) $valorTexto);

            if ($valorTexto === '') {
                continue;
            }

            $valor = converterMoedaBrParaFloat($valorTexto);
            $itemProposta = new ItemPropostaVencedora($licitacao->id, (int) $itemId, $valor);
            $itemProposta->salvar();
        }

        if (($_POST['operacao'] ?? '') === 'gerar_documento') {
            header('Location: index.php?action=gerar_documento_proposta_vencedora&id=' . $licitacao->id);
            exit;
        }

        $ultimoItemId = (int) ($_POST['ultimo_item_id'] ?? 0);
        $ancora = $ultimoItemId > 0 ? '#item-' . $ultimoItemId : '';

        header('Location: index.php?action=proposta_vencedora&id=' . $licitacao->id . $ancora);
        exit;
    }

    public function gerarDocumento(): void
    {
        exigirLogin();

        $licitacaoId = (int) ($_GET['id'] ?? 0);
        $licitacao = Licitacao::buscarPorId($licitacaoId);

        if ($licitacao === null) {
            echo 'Licitação não encontrada.';
            return;
        }

        $cotacao = Cotacao::buscarPorDemandaId($licitacao->demandaId);

        if ($cotacao === null) {
            echo 'Esta licitação não tem uma pesquisa de preço vinculada para comparar.';
            return;
        }

        $empresasPorLote = $this->resolverEmpresasPorLote($licitacao->id);
        $lotesAtuais = array_column($licitacao->buscarLotesAtivos(), 'lote_atual');

        require_once __DIR__ . '/../models/GeradorComparacaoProposta.php';

        $gerador = new GeradorComparacaoProposta($licitacao, $cotacao, $lotesAtuais, $empresasPorLote, usuarioLogado());
        $caminhoArquivo = $gerador->gerar();
        $nomeArquivo = 'Comparacao_Proposta_Vencedora_' . preg_replace('/[^A-Za-z0-9]/', '_', $licitacao->numeroProcesso) . '.docx';

        session_write_close();
        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
        header('Content-Length: ' . filesize($caminhoArquivo));
        readfile($caminhoArquivo);
        unlink($caminhoArquivo);
        exit;
    }

    public function gerarTermoAdjudicacao(): void
    {
        exigirLogin();

        $licitacaoId = (int) ($_GET['id'] ?? 0);
        $licitacao = Licitacao::buscarPorId($licitacaoId);

        if ($licitacao === null) {
            echo 'Licitação não encontrada.';
            return;
        }

        $cotacao = Cotacao::buscarPorDemandaId($licitacao->demandaId);

        if ($cotacao === null) {
            echo 'Esta licitação não tem uma pesquisa de preço vinculada.';
            return;
        }

        $empresasPorLote = $this->resolverEmpresasPorLote($licitacao->id);

        if (count($empresasPorLote) === 0) {
            echo 'Nenhum lote tem empresa vencedora definida ainda. Salve pelo menos um lote antes de gerar o termo.';
            return;
        }

        $categoriasPorLote = [];
        foreach (($_GET['categoria_lote'] ?? []) as $loteId => $categoria) {
            $categoriasPorLote[(int) $loteId] = trim((string) $categoria);
        }

        $data = trim($_GET['data'] ?? '') ?: date('Y-m-d');
        $lotesAtuais = array_column($licitacao->buscarLotesAtivos(), 'lote_atual');

        require_once __DIR__ . '/../models/GeradorTermoAdjudicacaoHomologacao.php';

        $gerador = new GeradorTermoAdjudicacaoHomologacao($licitacao, $cotacao, $lotesAtuais, $empresasPorLote, $categoriasPorLote, $data);
        $caminhoArquivo = $gerador->gerar();
        $nomeArquivo = 'Termo_Adjudicacao_Homologacao_' . preg_replace('/[^A-Za-z0-9]/', '_', $licitacao->numeroProcesso) . '.docx';

        session_write_close();
        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
        header('Content-Length: ' . filesize($caminhoArquivo));
        readfile($caminhoArquivo);
        unlink($caminhoArquivo);
        exit;
    }

    /**
     * Marca um lote (a rodada ATUAL dele) como fracassado ou deserto e,
     * opcionalmente, ja cria a cotacao de republicacao na hora.
     */
    public function marcarSituacaoLote(): void
    {
        exigirLogin();

        $licitacaoId = (int) ($_POST['licitacao_id'] ?? 0);
        $loteId = (int) ($_POST['lote_id'] ?? 0);
        $situacao = $_POST['situacao'] ?? '';
        $motivo = trim($_POST['motivo'] ?? '');
        $republicarAgora = isset($_POST['republicar_agora']);

        $licitacao = Licitacao::buscarPorId($licitacaoId);
        $lote = Lote::buscarPorId($loteId);

        if ($licitacao === null || $lote === null || !in_array($situacao, [SituacaoLote::FRACASSADO, SituacaoLote::DESERTO], true)) {
            echo 'Dados inválidos para marcar a situação do lote.';
            return;
        }

        (new SituacaoLote($licitacaoId, $loteId, $situacao, $motivo, date('Y-m-d')))->salvar();

        if ($republicarAgora) {
            $this->republicarLoteInterno($licitacao, $lote, $situacao, $motivo);
        }

        header('Location: index.php?action=proposta_vencedora&id=' . $licitacaoId);
        exit;
    }

    /**
     * Republica um lote que ja foi marcado como fracassado/deserto antes,
     * mas cuja cotacao de republicacao ainda nao tinha sido criada.
     */
    public function republicarLote(): void
    {
        exigirLogin();

        $licitacaoId = (int) ($_POST['licitacao_id'] ?? 0);
        $loteId = (int) ($_POST['lote_id'] ?? 0);

        $licitacao = Licitacao::buscarPorId($licitacaoId);
        $lote = Lote::buscarPorId($loteId);
        $situacaoLote = $lote !== null ? SituacaoLote::buscarPorLicitacaoELote($licitacaoId, $loteId) : null;

        if ($licitacao === null || $lote === null || $situacaoLote === null) {
            echo 'Só é possível republicar um lote marcado como fracassado ou deserto.';
            return;
        }

        if (RepublicacaoLote::buscarPorLoteAnterior($lote->id) !== null) {
            echo 'Este lote já tem uma republicação em andamento.';
            return;
        }

        $this->republicarLoteInterno($licitacao, $lote, $situacaoLote->situacao, $situacaoLote->motivo);

        header('Location: index.php?action=proposta_vencedora&id=' . $licitacaoId);
        exit;
    }

    /**
     * Cria a cotacao nova (pesquisa de preco do zero, so com os itens do
     * lote que fracassou/desertou), o lote novo dentro dela, e o registro
     * de republicacao que liga um ao outro. A cotacao nova nao fica visivel
     * na listagem geral de Cotacoes (Cotacao::ehRepublicacaoLote = true).
     */
    private function republicarLoteInterno(Licitacao $licitacao, Lote $loteAnterior, string $situacaoAnterior, string $motivo): void
    {
        $cotacaoOriginal = Cotacao::buscarPorDemandaId($licitacao->demandaId);

        if ($cotacaoOriginal === null) {
            return;
        }

        $origemAtual = RepublicacaoLote::buscarPorLoteNovo($loteAnterior->id);
        $numeroRodada = ($origemAtual !== null ? $origemAtual->numeroRodada : 1) + 1;

        $cotacaoNova = new Cotacao(
            $licitacao->numeroProcesso . '-R' . $numeroRodada,
            $cotacaoOriginal->orgaoSetor,
            $cotacaoOriginal->procedimento,
            $cotacaoOriginal->tipoJulgamento,
            'Republicação do Lote ' . $loteAnterior->numero . ' - ' . $licitacao->objeto,
            $cotacaoOriginal->servidorId,
            $cotacaoOriginal->criterioConsolidacao,
            ehRepublicacaoLote: true
        );
        $cotacaoNova->salvar();

        $loteNovo = new Lote($cotacaoNova->id, $loteAnterior->numero);
        $loteNovo->salvar();

        foreach ($loteAnterior->buscarItens() as $item) {
            $itemNovo = new Item($loteNovo->id, $item->numero, $item->descricao, $item->unidade, $item->quantidade);
            $itemNovo->salvar();
        }

        $republicacao = new RepublicacaoLote(
            $licitacao->id,
            $loteAnterior->id,
            $loteNovo->id,
            $cotacaoNova->id,
            $numeroRodada,
            $situacaoAnterior,
            $motivo
        );
        $republicacao->salvar();
    }

    /**
     * @return array<int, Empresa> empresa vencedora de cada lote, indexada
     * por lote_id (lotes ainda sem empresa definida ficam de fora).
     */
    private function resolverEmpresasPorLote(int $licitacaoId): array
    {
        $empresasPorLote = [];

        foreach (LotePropostaVencedora::buscarMapaPorLicitacao($licitacaoId) as $loteId => $lotePropostaVencedora) {
            $empresa = $lotePropostaVencedora->buscarEmpresa();

            if ($empresa !== null) {
                $empresasPorLote[$loteId] = $empresa;
            }
        }

        return $empresasPorLote;
    }
}
