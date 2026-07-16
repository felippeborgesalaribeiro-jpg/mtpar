<?php

require_once __DIR__ . '/Licitacao.php';
require_once __DIR__ . '/Cotacao.php';
require_once __DIR__ . '/Empresa.php';
require_once __DIR__ . '/Servidor.php';
require_once __DIR__ . '/Lote.php';
require_once __DIR__ . '/ItemPropostaVencedora.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\IOFactory;

require_once __DIR__ . '/../../vendor/autoload.php';

class GeradorComparacaoProposta
{
    private Licitacao $licitacao;
    private Cotacao $cotacao;
    private Empresa $empresaVencedora;
    private ?Servidor $conferidoPor;
    private PhpWord $documento;
    private float $totalEstimadoGeral = 0.0;
    private float $totalPropostoGeral = 0.0;
    private bool $temItemPendente = false;

    const FONTE_PADRAO = 'Calibri';
    const TAMANHO_PADRAO = 11;

    public function __construct(
        Licitacao $licitacao,
        Cotacao $cotacao,
        Empresa $empresaVencedora,
        ?Servidor $conferidoPor = null
    ) {
        $this->licitacao = $licitacao;
        $this->cotacao = $cotacao;
        $this->empresaVencedora = $empresaVencedora;
        $this->conferidoPor = $conferidoPor;

        $this->documento = new PhpWord();
        $this->documento->setDefaultFontName(self::FONTE_PADRAO);
        $this->documento->setDefaultFontSize(self::TAMANHO_PADRAO);
    }

    public function gerar(): string
    {
        $this->montarCapa();
        $this->montarSecaoComparacaoPorLote();
        $this->montarSecaoResumo();
        $this->montarSecaoObservacoes();
        $this->montarSecaoConferencia();

        $caminhoTemp = sys_get_temp_dir() . '/comparacao_proposta_' . uniqid() . '.docx';
        $writer = IOFactory::createWriter($this->documento, 'Word2007');
        $writer->save($caminhoTemp);

        return $caminhoTemp;
    }

    private function formatarCnpj(string $cnpj): string
    {
        if (strlen($cnpj) !== 14) {
            return $cnpj;
        }

        return substr($cnpj, 0, 2) . '.' . substr($cnpj, 2, 3) . '.' . substr($cnpj, 5, 3)
            . '/' . substr($cnpj, 8, 4) . '-' . substr($cnpj, 12, 2);
    }

    private function montarCapa(): void
    {
        $secao = $this->documento->addSection();

        $secao->addText('MT PARTICIPAÇÕES E PROJETOS S.A', ['bold' => true, 'size' => 13], ['alignment' => Jc::CENTER]);
        $secao->addTextBreak();
        $secao->addText('CONFERÊNCIA DE PROPOSTA VENCEDORA', ['bold' => true, 'size' => 12], ['alignment' => Jc::CENTER]);
        $secao->addTextBreak(2);

        $linhaProcesso = $secao->addTextRun(['alignment' => Jc::CENTER]);
        $linhaProcesso->addText('Processo: ', ['bold' => true]);
        $linhaProcesso->addText(htmlspecialchars($this->licitacao->numeroProcesso));

        $linhaEmpresa = $secao->addTextRun(['alignment' => Jc::CENTER, 'spaceBefore' => 100]);
        $linhaEmpresa->addText('Empresa vencedora: ', ['bold' => true]);
        $linhaEmpresa->addText(htmlspecialchars($this->empresaVencedora->nome));

        $linhaCnpj = $secao->addTextRun(['alignment' => Jc::CENTER, 'spaceBefore' => 100]);
        $linhaCnpj->addText('CNPJ: ', ['bold' => true]);
        $linhaCnpj->addText($this->formatarCnpj($this->empresaVencedora->cnpj));

        $secao->addTextBreak(2);
        $secao->addText(
            'Conferência item a item e lote a lote da proposta apresentada pela empresa vencedora do certame, em '
            . 'comparação com o preço de referência apurado no mapa comparativo da pesquisa de preços vinculada a '
            . 'este processo (Processo ' . htmlspecialchars($this->cotacao->numeroProcesso) . ').',
            [],
            ['alignment' => Jc::BOTH, 'spaceBefore' => 200]
        );

        $secao->addPageBreak();
    }

    private function montarSecaoComparacaoPorLote(): void
    {
        $secao = $this->documento->addSection();
        $secao->addText('COMPARAÇÃO POR LOTE E ITEM', ['bold' => true, 'size' => 12], ['spaceAfter' => 300]);

        foreach ($this->cotacao->buscarLotes() as $lote) {
            $this->montarTabelaLote($secao, $lote);
        }
    }

    private function montarTabelaLote($secao, Lote $lote): void
    {
        $estiloTabela = ['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80];
        $estiloCabecalho = ['bgColor' => 'D9D9D9'];
        $fonteCabecalho = ['bold' => true, 'size' => 8];
        $fonteCelula = ['size' => 8];

        $secao->addText('LOTE ' . htmlspecialchars($lote->numero), ['bold' => true, 'size' => 10], ['spaceBefore' => 200, 'spaceAfter' => 150]);

        $tabela = $secao->addTable($estiloTabela);
        $tabela->addRow();
        $tabela->addCell(600, $estiloCabecalho)->addText('Item', $fonteCabecalho, ['alignment' => Jc::CENTER]);
        $tabela->addCell(3200, $estiloCabecalho)->addText('Descrição', $fonteCabecalho, ['alignment' => Jc::CENTER]);
        $tabela->addCell(900, $estiloCabecalho)->addText('Qtd.', $fonteCabecalho, ['alignment' => Jc::CENTER]);
        $tabela->addCell(1600, $estiloCabecalho)->addText('Ref. unitário (R$)', $fonteCabecalho, ['alignment' => Jc::CENTER]);
        $tabela->addCell(1600, $estiloCabecalho)->addText('Proposto unitário (R$)', $fonteCabecalho, ['alignment' => Jc::CENTER]);
        $tabela->addCell(1700, $estiloCabecalho)->addText('Situação', $fonteCabecalho, ['alignment' => Jc::CENTER]);

        $subtotalRef = 0.0;
        $subtotalProposto = 0.0;
        $subtotalTemPendente = false;

        foreach ($lote->buscarItens() as $item) {
            $resultado = $item->analisar($this->cotacao->criterioConsolidacao);
            $valorReferencia = $resultado['valor_referencia'] ?? 0;
            $propostaItem = ItemPropostaVencedora::buscarPorLicitacaoEItem($this->licitacao->id, $item->id);

            $subtotalRef += $valorReferencia * $item->quantidade;

            $situacao = 'Aguardando proposta';
            $textoProposto = '—';

            if ($propostaItem !== null) {
                $subtotalProposto += $propostaItem->valorProposto * $item->quantidade;
                $textoProposto = formatarMoeda($propostaItem->valorProposto);
                $situacao = $propostaItem->valorProposto > $valorReferencia ? 'Acima da referência' : 'Dentro do valor';
            } else {
                $subtotalTemPendente = true;
            }

            $tabela->addRow();
            $tabela->addCell(600)->addText((string) $item->numero, $fonteCelula, ['alignment' => Jc::CENTER]);
            $tabela->addCell(3200)->addText(htmlspecialchars($item->descricao), $fonteCelula);
            $tabela->addCell(900)->addText(formatarNumero($item->quantidade) . ' ' . htmlspecialchars($item->unidade), $fonteCelula, ['alignment' => Jc::CENTER]);
            $tabela->addCell(1600)->addText(formatarMoeda($valorReferencia), $fonteCelula, ['alignment' => Jc::CENTER]);
            $tabela->addCell(1600)->addText($textoProposto, $fonteCelula, ['alignment' => Jc::CENTER]);
            $tabela->addCell(1700)->addText($situacao, $fonteCelula, ['alignment' => Jc::CENTER]);
        }

        $textoSubtotalProposto = formatarMoeda($subtotalProposto) . ($subtotalTemPendente ? ' *' : '');
        $situacaoSubtotal = $subtotalProposto > $subtotalRef ? 'Lote acima do estimado' : 'Lote dentro do estimado';

        $tabela->addRow();
        $celulaLabel = $tabela->addCell(4700, $estiloCabecalho);
        $celulaLabel->addText('Subtotal do lote', $fonteCabecalho);
        $tabela->addCell(900, $estiloCabecalho)->addText('', $fonteCabecalho);
        $tabela->addCell(1600, $estiloCabecalho)->addText(formatarMoeda($subtotalRef), $fonteCabecalho, ['alignment' => Jc::CENTER]);
        $tabela->addCell(1600, $estiloCabecalho)->addText($textoSubtotalProposto, $fonteCabecalho, ['alignment' => Jc::CENTER]);
        $tabela->addCell(1700, $estiloCabecalho)->addText($situacaoSubtotal, $fonteCabecalho, ['alignment' => Jc::CENTER]);

        $secao->addTextBreak();

        $this->totalEstimadoGeral += $subtotalRef;
        $this->totalPropostoGeral += $subtotalProposto;
        $this->temItemPendente = $this->temItemPendente || $subtotalTemPendente;
    }

    private function montarSecaoResumo(): void
    {
        $secao = $this->documento->addSection();
        $secao->addText('RESUMO GERAL', ['bold' => true, 'size' => 12], ['spaceAfter' => 300]);

        $economia = $this->totalEstimadoGeral - $this->totalPropostoGeral;
        $economiaPercentual = $this->totalEstimadoGeral > 0 ? ($economia / $this->totalEstimadoGeral) * 100 : 0;

        $linhaEstimado = $secao->addTextRun(['spaceAfter' => 150]);
        $linhaEstimado->addText('Valor total estimado (mapa comparativo): ');
        $linhaEstimado->addText(formatarMoeda($this->totalEstimadoGeral), ['bold' => true]);

        $linhaProposto = $secao->addTextRun(['spaceAfter' => 150]);
        $linhaProposto->addText('Valor total da proposta vencedora: ');
        $linhaProposto->addText(formatarMoeda($this->totalPropostoGeral), ['bold' => true]);
        if ($this->temItemPendente) {
            $linhaProposto->addText(' *');
        }

        $linhaEconomia = $secao->addTextRun(['spaceAfter' => 200]);
        $linhaEconomia->addText('Economicidade da proposta em relação ao estimado: ');
        $linhaEconomia->addText(
            formatarMoeda(abs($economia)) . ' (' . formatarNumero(abs($economiaPercentual), 1) . '%) '
            . ($economia >= 0 ? 'ABAIXO do estimado' : 'ACIMA do estimado'),
            ['bold' => true]
        );

        if ($this->temItemPendente) {
            $secao->addText(
                '* Um ou mais itens ainda não têm valor de proposta registrado; os totais acima consideram apenas os itens já preenchidos.',
                ['italic' => true, 'size' => 9],
                ['spaceAfter' => 200]
            );
        }
    }

    private function montarSecaoObservacoes(): void
    {
        if (trim($this->licitacao->observacoesPropostaVencedora) === '') {
            return;
        }

        $secao = $this->documento->addSection();
        $secao->addText('OBSERVAÇÕES', ['bold' => true, 'size' => 12], ['spaceAfter' => 300]);
        $secao->addText(
            htmlspecialchars($this->licitacao->observacoesPropostaVencedora),
            [],
            ['alignment' => Jc::BOTH]
        );
    }

    private function montarSecaoConferencia(): void
    {
        if ($this->conferidoPor === null) {
            return;
        }

        $secao = $this->documento->addSection();
        $secao->addText('CONFERIDO POR:', [], ['spaceBefore' => 400, 'spaceAfter' => 400]);
        $secao->addText(mb_strtoupper($this->conferidoPor->nome), ['bold' => true], ['alignment' => Jc::CENTER]);
        $secao->addText($this->conferidoPor->cargo, [], ['alignment' => Jc::CENTER]);
    }
}
