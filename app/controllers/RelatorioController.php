<?php

require_once __DIR__ . '/../models/Cotacao.php';
require_once __DIR__ . '/../models/StatusCotacao.php';
require_once __DIR__ . '/../models/AnalisePrecos.php';
require_once __DIR__ . '/../models/Servidor.php';
require_once __DIR__ . '/../helpers/config.php';
require_once __DIR__ . '/../helpers/auth.php';

class RelatorioController
{
    public function formulario(): void
    {
        exigirLogin();

        $cotacaoId = (int) ($_GET['id'] ?? 0);
        $cotacao = Cotacao::buscarPorId($cotacaoId);

        if ($cotacao === null) {
            echo 'Cotação não encontrada.';
            return;
        }

        if ($cotacao->status !== StatusCotacao::Finalizada) {
            echo 'Esta cotação ainda não foi finalizada.';
            return;
        }

        if ($cotacao->criterioConsolidacao === AnalisePrecos::CRITERIO_PLANILHA_ORCAMENTARIA) {
            echo 'Esta cotação usa o critério de Planilha Orçamentária, que não tem comparação de preços '
                . '(excessivo/inexequível) para justificar em análise crítica.';
            return;
        }

        $servidorResponsavelId = $cotacao->servidorId;

        $servidores = array_filter(
            Servidor::buscarTodos(),
            fn($servidor) => $servidor->id !== $servidorResponsavelId
        );

        $validador = Servidor::buscarPorId(SERVIDOR_VALIDADOR_PADRAO_ID);

        require __DIR__ . '/../views/relatorio_formulario.php';
    }

    public function gerar(): void
    {
        exigirLogin();

        $cotacaoId             = (int) ($_POST['cotacao_id'] ?? 0);
        $elaboradoPorId        = (int) ($_POST['elaborado_por_id'] ?? 0);
        $numeroDfd             = trim($_POST['numero_dfd'] ?? '');
        $numeroTermoReferencia = trim($_POST['numero_termo_referencia'] ?? '');

        $cotacao      = Cotacao::buscarPorId($cotacaoId);
        $elaboradoPor = Servidor::buscarPorId($elaboradoPorId);
        $validador    = Servidor::buscarPorId(SERVIDOR_VALIDADOR_PADRAO_ID);

        if ($cotacao === null || $elaboradoPor === null || $validador === null) {
            echo 'Dados insuficientes para gerar o relatório.';
            return;
        }

        if ($cotacao->criterioConsolidacao === AnalisePrecos::CRITERIO_PLANILHA_ORCAMENTARIA) {
            echo 'Esta cotação usa o critério de Planilha Orçamentária, que não tem comparação de preços '
                . '(excessivo/inexequível) para justificar em análise crítica.';
            return;
        }

        require_once __DIR__ . '/../models/GeradorAnaliseCritica.php';

        $gerador        = new GeradorAnaliseCritica($cotacao, $elaboradoPor, $validador, $numeroDfd, $numeroTermoReferencia);
        $caminhoArquivo = $gerador->gerar();
        $nomeArquivo    = 'Analise_Critica_' . preg_replace('/[^A-Za-z0-9]/', '_', $cotacao->numeroProcesso) . '.docx';

        $this->enviarArquivo($caminhoArquivo, $nomeArquivo);
    }

    private function enviarArquivo(string $caminhoArquivo, string $nomeArquivo): void
    {
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