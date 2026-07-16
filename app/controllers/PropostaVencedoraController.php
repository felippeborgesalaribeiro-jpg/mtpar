<?php

require_once __DIR__ . '/../models/Licitacao.php';
require_once __DIR__ . '/../models/Cotacao.php';
require_once __DIR__ . '/../models/Demanda.php';
require_once __DIR__ . '/../models/Empresa.php';
require_once __DIR__ . '/../models/ItemPropostaVencedora.php';
require_once __DIR__ . '/../helpers/auth.php';

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
        $empresaVencedora = $licitacao->buscarEmpresaVencedora();

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

        $empresaId = (int) ($_POST['empresa_vencedora_id'] ?? 0);
        $licitacao->empresaVencedoraId = $empresaId ?: null;
        $licitacao->observacoesPropostaVencedora = trim($_POST['observacoes'] ?? '');
        $licitacao->salvar();

        foreach (($_POST['valor_proposto'] ?? []) as $itemId => $valorTexto) {
            $valorTexto = trim((string) $valorTexto);

            if ($valorTexto === '') {
                continue;
            }

            $valor = (float) str_replace(',', '.', $valorTexto);
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

        $empresaVencedora = $licitacao->buscarEmpresaVencedora();

        if ($empresaVencedora === null) {
            echo 'Selecione a empresa vencedora antes de gerar o documento.';
            return;
        }

        require_once __DIR__ . '/../models/GeradorComparacaoProposta.php';

        $gerador = new GeradorComparacaoProposta($licitacao, $cotacao, $empresaVencedora, usuarioLogado());
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
}
