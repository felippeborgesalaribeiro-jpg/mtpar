<?php

require_once __DIR__ . '/../models/Licitacao.php';
require_once __DIR__ . '/../models/Cotacao.php';
require_once __DIR__ . '/../models/Demanda.php';
require_once __DIR__ . '/../models/Empresa.php';
require_once __DIR__ . '/../models/ItemPropostaVencedora.php';
require_once __DIR__ . '/../models/LotePropostaVencedora.php';
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
        $lotes = $cotacao !== null ? $cotacao->buscarLotes() : [];
        $valoresPropostos = ItemPropostaVencedora::buscarMapaPorLicitacao($licitacao->id);
        $lotesComEmpresa = LotePropostaVencedora::buscarMapaPorLicitacao($licitacao->id);

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

        header('Location: index.php?action=proposta_vencedora&id=' . $licitacao->id);
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

        require_once __DIR__ . '/../models/GeradorComparacaoProposta.php';

        $gerador = new GeradorComparacaoProposta($licitacao, $cotacao, $empresasPorLote, usuarioLogado());
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

        require_once __DIR__ . '/../models/GeradorTermoAdjudicacaoHomologacao.php';

        $gerador = new GeradorTermoAdjudicacaoHomologacao($licitacao, $cotacao, $empresasPorLote, $categoriasPorLote, $data);
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
