<?php

require_once __DIR__ . '/Licitacao.php';
require_once __DIR__ . '/Cotacao.php';
require_once __DIR__ . '/Empresa.php';
require_once __DIR__ . '/Lote.php';
require_once __DIR__ . '/ItemPropostaVencedora.php';
require_once __DIR__ . '/../helpers/extenso.php';
require_once __DIR__ . '/../helpers/config.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Style\Cell;
use PhpOffice\PhpWord\IOFactory;

require_once __DIR__ . '/../../vendor/autoload.php';

class GeradorTermoAdjudicacaoHomologacao
{
    private Licitacao $licitacao;
    private Cotacao $cotacao;
    /** @var array<int, Empresa> empresa vencedora de cada lote, indexado por lote_id */
    private array $empresasPorLote;
    /** @var array<int, string> categoria opcional de cada lote (ex.: "AMPLA CONCORRÊNCIA"), indexado por lote_id */
    private array $categoriasPorLote;
    private string $data;
    private PhpWord $documento;
    private float $valorAdjudicadoGeral = 0.0;

    const FONTE_PADRAO = 'Calibri';
    const TAMANHO_PADRAO = 11;
    // Soma das larguras das 8 colunas da tabela do lote (500+2200+1500+1300+1000+700+1500+1400),
    // usada pras celulas de total que ocupam a largura inteira (gridSpan ou tabela sozinha).
    const LARGURA_TABELA_LOTE = 10100;

    /**
     * @param array<int, Empresa> $empresasPorLote
     * @param array<int, string> $categoriasPorLote
     */
    public function __construct(
        Licitacao $licitacao,
        Cotacao $cotacao,
        array $empresasPorLote,
        array $categoriasPorLote,
        string $data
    ) {
        $this->licitacao = $licitacao;
        $this->cotacao = $cotacao;
        $this->empresasPorLote = $empresasPorLote;
        $this->categoriasPorLote = $categoriasPorLote;
        $this->data = $data;

        $this->documento = new PhpWord();
        $this->documento->setDefaultFontName(self::FONTE_PADRAO);
        $this->documento->setDefaultFontSize(self::TAMANHO_PADRAO);
    }

    public function gerar(): string
    {
        $secao = $this->documento->addSection();

        $this->montarCabecalho($secao);
        $this->montarParagrafoResolutivo($secao);
        $this->montarTabelasPorLote($secao);
        $this->montarValorAdjudicadoGeral($secao);
        $this->montarAssinatura($secao);

        $caminhoTemp = sys_get_temp_dir() . '/termo_adjudicacao_' . uniqid() . '.docx';
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

    private function numeroLicitacaoFormatado(): string
    {
        $edital = trim($this->licitacao->editalLicitacao);

        if ($edital === '') {
            return 'MTPAR';
        }

        return stripos($edital, 'MTPAR') !== false ? $edital : $edital . '/MTPAR';
    }

    /**
     * "01, 02, 03, 04 e 05" - lista os numeros dos lotes que tem empresa
     * vencedora definida, na ordem em que aparecem na cotacao.
     */
    private function listaNumerosLotes(): string
    {
        $numeros = [];
        foreach ($this->cotacao->buscarLotes() as $lote) {
            if (isset($this->empresasPorLote[$lote->id])) {
                $numeros[] = $lote->numero;
            }
        }

        if (count($numeros) === 1) {
            return $numeros[0];
        }

        $ultimo = array_pop($numeros);

        return implode(', ', $numeros) . ' e ' . $ultimo;
    }

    private function montarCabecalho($secao): void
    {
        $secao->addText('TERMO DE ADJUDICAÇÃO E HOMOLOGAÇÃO', ['bold' => true, 'size' => 13], ['alignment' => Jc::CENTER]);
        $secao->addText(
            'LICITAÇÃO ELETRÔNICA N° ' . $this->numeroLicitacaoFormatado(),
            ['bold' => true],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 300]
        );
    }

    private function montarParagrafoResolutivo($secao): void
    {
        $lotesComEmpresa = array_filter(
            $this->cotacao->buscarLotes(),
            fn($lote) => isset($this->empresasPorLote[$lote->id])
        );
        $loteUnico = count($lotesComEmpresa) === 1 && count($this->cotacao->buscarLotes()) === 1;

        $paragrafo = $secao->addTextRun(['alignment' => Jc::BOTH, 'spaceAfter' => 300]);
        $paragrafo->addText(
            'O Diretor Presidente da MT Participações e Projetos S.A – MT-PAR, no uso de suas atribuições e '
            . 'considerando as informações constantes no relatório da sessão pública, resolve Adjudicar e '
            . 'Homologar '
        );

        if ($loteUnico) {
            $paragrafo->addText('o Lote único');
        } elseif (count($lotesComEmpresa) === 1) {
            $paragrafo->addText('o Lote ' . $this->listaNumerosLotes());
        } else {
            $paragrafo->addText('os Lotes ' . $this->listaNumerosLotes());
        }

        $paragrafo->addText(' da Licitação Eletrônica n° ' . $this->numeroLicitacaoFormatado());
        $paragrafo->addText(', oriundo do processo administrativo n° ');
        $paragrafo->addText($this->licitacao->numeroProcesso, ['bold' => true]);
        $paragrafo->addText(', o qual tem por escopo "' . $this->licitacao->objeto . '".');
    }

    private function montarTabelasPorLote($secao): void
    {
        foreach ($this->cotacao->buscarLotes() as $lote) {
            if (!isset($this->empresasPorLote[$lote->id])) {
                continue;
            }

            $this->montarTabelaLote($secao, $lote);
        }
    }

    private function montarTabelaLote($secao, Lote $lote): void
    {
        $estiloTabela = ['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80];
        $estiloCabecalho = ['bgColor' => 'D9D9D9'];
        $fonteCabecalho = ['bold' => true, 'size' => 8];
        $fonteCelula = ['size' => 8];

        $empresa = $this->empresasPorLote[$lote->id];
        $categoria = trim($this->categoriasPorLote[$lote->id] ?? '');
        $tituloLote = 'LOTE ' . $lote->numero . ($categoria !== '' ? ' - ' . mb_strtoupper($categoria) : '');

        $secao->addText($tituloLote, ['bold' => true, 'size' => 10], ['spaceBefore' => 300, 'spaceAfter' => 150]);

        $tabela = $secao->addTable($estiloTabela);
        $tabela->addRow();
        $tabela->addCell(500, $estiloCabecalho)->addText('ITEM', $fonteCabecalho, ['alignment' => Jc::CENTER]);
        $tabela->addCell(2200, $estiloCabecalho)->addText('EMPRESA', $fonteCabecalho, ['alignment' => Jc::CENTER]);
        $tabela->addCell(1500, $estiloCabecalho)->addText('CNPJ', $fonteCabecalho, ['alignment' => Jc::CENTER]);
        $tabela->addCell(1300, $estiloCabecalho)->addText('VALOR UNIT.', $fonteCabecalho, ['alignment' => Jc::CENTER]);
        $tabela->addCell(1000, $estiloCabecalho)->addText('UND. MED.', $fonteCabecalho, ['alignment' => Jc::CENTER]);
        $tabela->addCell(700, $estiloCabecalho)->addText('QNTD', $fonteCabecalho, ['alignment' => Jc::CENTER]);
        $tabela->addCell(1500, $estiloCabecalho)->addText('VALOR TOTAL ITEM', $fonteCabecalho, ['alignment' => Jc::CENTER]);
        $tabela->addCell(1400, $estiloCabecalho)->addText('SITUAÇÃO', $fonteCabecalho, ['alignment' => Jc::CENTER]);

        $valorGlobalLote = 0.0;
        $primeiraLinha = true;

        foreach ($lote->buscarItens() as $item) {
            $propostaItem = ItemPropostaVencedora::buscarPorLicitacaoEItem($this->licitacao->id, $item->id);

            if ($propostaItem === null) {
                continue;
            }

            $valorTotalItem = $propostaItem->valorProposto * $item->quantidade;
            $valorGlobalLote += $valorTotalItem;

            $tabela->addRow();
            $tabela->addCell(500)->addText((string) $item->numero, $fonteCelula, ['alignment' => Jc::CENTER]);

            $estiloEmpresa = $primeiraLinha ? ['vMerge' => Cell::VMERGE_RESTART] : ['vMerge' => Cell::VMERGE_CONTINUE];
            $tabela->addCell(2200, $estiloEmpresa)->addText(
                $primeiraLinha ? $empresa->nome : '',
                $fonteCelula
            );
            $tabela->addCell(1500, $estiloEmpresa)->addText(
                $primeiraLinha ? $this->formatarCnpj($empresa->cnpj) : '',
                $fonteCelula,
                ['alignment' => Jc::CENTER]
            );

            $tabela->addCell(1300)->addText(formatarMoeda($propostaItem->valorProposto), $fonteCelula, ['alignment' => Jc::CENTER]);
            $tabela->addCell(1000)->addText($item->unidade, $fonteCelula, ['alignment' => Jc::CENTER]);
            $tabela->addCell(700)->addText(formatarNumero($item->quantidade), $fonteCelula, ['alignment' => Jc::CENTER]);
            $tabela->addCell(1500)->addText(formatarMoeda($valorTotalItem), $fonteCelula, ['alignment' => Jc::CENTER]);

            $estiloSituacao = $primeiraLinha ? ['vMerge' => Cell::VMERGE_RESTART] : ['vMerge' => Cell::VMERGE_CONTINUE];
            $tabela->addCell(1400, $estiloSituacao)->addText(
                $primeiraLinha ? 'ADJUDICADO E HOMOLOGADO' : '',
                $fonteCelula,
                ['alignment' => Jc::CENTER]
            );

            $primeiraLinha = false;
        }

        $extenso = numeroParaExtenso($valorGlobalLote);
        $extensoComMaiuscula = mb_strtoupper(mb_substr($extenso, 0, 1)) . mb_substr($extenso, 1);

        // Fica dentro da propria tabela do lote (ultima linha, celula unica
        // ocupando as 8 colunas), em vez de um paragrafo solto depois da tabela.
        $tabela->addRow();
        $tabela->addCell(self::LARGURA_TABELA_LOTE, ['gridSpan' => 8, 'bgColor' => 'D9D9D9'])->addText(
            'VALOR GLOBAL DO LOTE: ' . formatarMoeda($valorGlobalLote) . ' (' . $extensoComMaiuscula . ').',
            ['bold' => true, 'size' => 9],
            ['alignment' => Jc::CENTER]
        );

        $secao->addTextBreak(1, ['size' => 4]);

        $this->valorAdjudicadoGeral += $valorGlobalLote;
    }

    private function montarValorAdjudicadoGeral($secao): void
    {
        $extenso = numeroParaExtenso($this->valorAdjudicadoGeral);
        $extensoComMaiuscula = mb_strtoupper(mb_substr($extenso, 0, 1)) . mb_substr($extenso, 1);

        // Uma unica tabela, com uma unica celula, so pra esse total geral -
        // nao e mais um paragrafo solto nem faz parte da tabela de nenhum lote.
        $tabela = $secao->addTable(['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80]);
        $tabela->addRow();
        $tabela->addCell(self::LARGURA_TABELA_LOTE, ['bgColor' => 'D9D9D9'])->addText(
            'VALOR ADJUDICADO E HOMOLOGADO: ' . formatarMoeda($this->valorAdjudicadoGeral) . ' (' . $extensoComMaiuscula . ').',
            ['bold' => true, 'size' => 10],
            ['alignment' => Jc::CENTER]
        );

        $secao->addTextBreak(1, ['size' => 4]);
    }

    private function montarAssinatura($secao): void
    {
        $secao->addText('Cuiabá-MT, ' . dataPorExtenso($this->data) . '.', [], ['spaceAfter' => 600]);
        $secao->addText(DIRETOR_PRESIDENTE_NOME, ['bold' => true], ['alignment' => Jc::CENTER]);
        $secao->addText(DIRETOR_PRESIDENTE_CARGO, [], ['alignment' => Jc::CENTER]);
        $secao->addText('MT Participações e Projetos S.A – MT-PAR', [], ['alignment' => Jc::CENTER, 'spaceAfter' => 300]);
        $secao->addText('(Original assinado eletronicamente)', ['italic' => true], ['alignment' => Jc::CENTER]);
    }
}
