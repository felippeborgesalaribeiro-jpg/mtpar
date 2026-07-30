<?php

require_once __DIR__ . '/ProcessoVantajosidade.php';
require_once __DIR__ . '/ItemVantajosidade.php';
require_once __DIR__ . '/Servidor.php';
require_once __DIR__ . '/AnaliseVantajosidade.php';
require_once __DIR__ . '/../helpers/extenso.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\IOFactory;

require_once __DIR__ . '/../../vendor/autoload.php';

class GeradorAnaliseCriticaVantajosidade
{
    private ProcessoVantajosidade $processo;
    private Servidor $elaboradoPor;
    private Servidor $validadoPor;
    private string $numeroDfd;
    private PhpWord $documento;

    const FONTE_PADRAO   = 'Calibri';
    const TAMANHO_PADRAO = 11;

    public function __construct(
        ProcessoVantajosidade $processo,
        Servidor $elaboradoPor,
        Servidor $validadoPor,
        string $numeroDfd
    ) {
        $this->processo     = $processo;
        $this->elaboradoPor = $elaboradoPor;
        $this->validadoPor  = $validadoPor;
        $this->numeroDfd    = $numeroDfd;

        $this->documento = new PhpWord();
        $this->documento->setDefaultFontName(self::FONTE_PADRAO);
        $this->documento->setDefaultFontSize(self::TAMANHO_PADRAO);
    }

    public function gerar(): string
    {
        $this->montarCapa();
        $this->montarSumario();
        $this->montarSecaoLegislacao();
        $this->montarSecaoMetodologia();
        $this->montarSecaoAnaliseDeVantajosidade();
        $this->montarSecaoConclusao();
        $this->montarSecaoElaboracao();

        $caminhoTemp = sys_get_temp_dir() . '/analise_critica_vantajosidade_' . uniqid() . '.docx';
        $writer = IOFactory::createWriter($this->documento, 'Word2007');
        $writer->save($caminhoTemp);

        return $caminhoTemp;
    }

    private function identificadorProcesso(): string
    {
        return $this->processo->ehContratoAditivo()
            ? 'Aditivo do Contrato ' . $this->processo->numeroContrato
            : 'Ata ' . $this->processo->numeroAta;
    }

    private function montarCapa(): void
    {
        $secao = $this->documento->addSection();

        for ($i = 0; $i < 6; $i++) {
            $secao->addTextBreak();
        }

        $secao->addText('MT PARTICIPAÇÕES E PROJETOS S.A', ['bold' => true, 'size' => 13], ['alignment' => Jc::CENTER]);

        for ($i = 0; $i < 8; $i++) {
            $secao->addTextBreak();
        }

        $secao->addText('MANIFESTAÇÃO TÉCNICA', ['bold' => true, 'size' => 12], ['alignment' => Jc::CENTER]);
        $secao->addTextBreak();

        $textoObjeto = $secao->addTextRun(['alignment' => Jc::CENTER]);
        $textoObjeto->addText('OBJETO: ', ['bold' => true]);
        $textoObjeto->addText('"Análise Crítica de Comprovação de Vantajosidade - ', ['italic' => true]);
        $textoObjeto->addText($this->identificadorProcesso(), ['italic' => true]);
        $textoObjeto->addText('"', ['italic' => true]);

        $secao->addPageBreak();
    }

    private function montarSumario(): void
    {
        $secao = $this->documento->addSection();

        $secao->addText('SUMÁRIO', ['bold' => true, 'size' => 13], ['alignment' => Jc::CENTER, 'spaceAfter' => 400]);

        $itens = [
            '1. DA LEGISLAÇÃO APLICÁVEL',
            '2. DA METODOLOGIA DE ANÁLISE',
            '3. ANÁLISE DE VANTAJOSIDADE',
            '4. DA CONCLUSÃO',
            '5. DA ELABORAÇÃO',
        ];

        foreach ($itens as $titulo) {
            $secao->addText($titulo, ['bold' => true], ['spaceAfter' => 150]);
        }

        $secao->addPageBreak();
    }

    private function montarSecaoLegislacao(): void
    {
        $secao     = $this->documento->addSection();
        $paragrafo = ['alignment' => Jc::BOTH, 'spaceAfter' => 200];
        $citacao   = ['alignment' => Jc::BOTH, 'spaceAfter' => 200, 'indentation' => ['left' => 700]];

        $secao->addText('1. DA LEGISLAÇÃO APLICÁVEL', ['bold' => true, 'size' => 12], ['spaceAfter' => 300]);

        $secao->addText(
            'A Lei nº 13.303/2016, conhecida também como Lei das Estatais, dispõe sobre o estatuto jurídico das empresas públicas, da sociedade de economia mista e de suas subsidiárias, e estabelece, como diretriz das contratações, a seleção da proposta mais vantajosa para a companhia.',
            [], $paragrafo
        );

        $secao->addText('Art. 32. As contratações destinam-se a assegurar a seleção da proposta mais vantajosa, inclusive no que se refere ao ciclo de vida do objeto, e a evitar operações em que se caracterize sobrepreço ou superfaturamento.', ['italic' => true, 'size' => 10], $citacao);

        $secao->addText(
            'Diante disso, com o advento da Lei 13.303/2016, a MTPAR editou seu Regulamento Interno de Licitações e Contratos (RILC/MTPAR), aprovado pelo Conselho de Administração, e instituído por meio da Resolução Nº 004/CONSELHO DE ADM/2020 do Conselho de Administração da empresa, com atualizações posteriores conforme aprovado pela Resolução Nº004/2023/CAD.',
            [], $paragrafo
        );

        if ($this->processo->ehContratoAditivo()) {
            $secao->addText(
                'Especificamente quanto ao aditivo de contrato objeto desta análise, aplica-se o limite legal de acréscimo previsto no ordenamento jurídico pátrio para contratações públicas, segundo o qual os acréscimos ao objeto contratado não podem ultrapassar 25% (vinte e cinco por cento) do valor inicial atualizado do contrato, cabendo à Administração comprovar a vantajosidade econômica de tal acréscimo frente às condições de mercado vigentes.',
                [], $paragrafo
            );
        } else {
            $secao->addText(
                'Especificamente quanto à adesão a Ata de Registro de Preços gerenciada por outro órgão ou entidade ("carona"), cabe à MT-PAR comprovar, antes da contratação, que os preços registrados permanecem vantajosos em relação às condições atualmente praticadas no mercado, sob pena de não se justificar a adesão.',
                [], $paragrafo
            );
        }

        $secao->addPageBreak();
    }

    private function montarSecaoMetodologia(): void
    {
        $secao     = $this->documento->addSection();
        $paragrafo = ['alignment' => Jc::BOTH, 'spaceAfter' => 200];

        $secao->addText('2. DA METODOLOGIA DE ANÁLISE', ['bold' => true, 'size' => 12], ['spaceAfter' => 300]);

        $secao->addText(
            'Para cada item analisado, confronta-se o valor de referência (' . ($this->processo->ehContratoAditivo() ? 'valor unitário praticado no contrato' : 'preço unitário registrado na Ata') . ') com a média dos preços de mercado coletados na pesquisa de preços realizada. Considera-se VANTAJOSA a ' . ($this->processo->ehContratoAditivo() ? 'contratação do aditivo' : 'adesão') . ' quando a média de mercado apurada for igual ou superior ao valor de referência, indicando que este permanece igual ou mais econômico que as condições atuais de mercado; caso contrário, o item é considerado NÃO VANTAJOSO.',
            [], $paragrafo
        );

        if ($this->processo->ehContratoAditivo()) {
            $secao->addText(
                'Adicionalmente, verifica-se o índice do aditivo, calculado pela razão entre o valor total apurado para os itens do aditivo e o valor total do objeto do contrato original, a fim de aferir sua conformidade com o limite legal de 25% (vinte e cinco por cento).',
                [], $paragrafo
            );
        }

        $secao->addPageBreak();
    }

    private function montarSecaoAnaliseDeVantajosidade(): void
    {
        $secao = $this->documento->addSection();

        $secao->addText('3. ANÁLISE DE VANTAJOSIDADE', ['bold' => true, 'size' => 12], ['spaceAfter' => 300]);

        $objetoTexto = $this->processo->objeto !== '' ? $this->processo->objeto : '[OBJETO NÃO INFORMADO]';
        $paragrafo   = ['alignment' => Jc::BOTH, 'spaceAfter' => 200];

        $introducao = $secao->addTextRun($paragrafo);
        $introducao->addText('Tendo em vista o DFD - ');
        $introducao->addText($this->numeroDfd !== '' ? $this->numeroDfd : '[A PREENCHER]', ['bold' => true]);
        $introducao->addText(' o qual tem por objeto "');
        $introducao->addText($objetoTexto, ['italic' => true]);
        $introducao->addText('", referente à ');
        $introducao->addText($this->identificadorProcesso());
        $introducao->addText('.');

        if ($this->processo->ehContratoAditivo()) {
            $this->montarQuadroIndiceAditivo($secao);
        }

        $itens = $this->processo->buscarItens();

        foreach ($itens as $item) {
            $this->montarTabelaItem($secao, $item);
        }

        $secao->addPageBreak();
    }

    private function montarQuadroIndiceAditivo($secao): void
    {
        $estiloTabela          = ['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80];
        $estiloCelulaCabecalho = ['bgColor' => 'D9D9D9'];
        $fonteCabecalho        = ['bold' => true, 'size' => 9];
        $fonteCelula           = ['size' => 9];

        $indice        = $this->processo->calcularIndiceAditivo();
        $dentroLimite  = $this->processo->indiceAditivoDentroDoLimiteLegal();

        $tabela = $secao->addTable($estiloTabela);
        $tabela->addRow();
        $tabela->addCell(2800, $estiloCelulaCabecalho)->addText('Valor total do objeto', $fonteCabecalho, ['alignment' => Jc::CENTER]);
        $tabela->addCell(2800, $estiloCelulaCabecalho)->addText('Valor apurado do aditivo', $fonteCabecalho, ['alignment' => Jc::CENTER]);
        $tabela->addCell(2000, $estiloCelulaCabecalho)->addText('Índice do aditivo', $fonteCabecalho, ['alignment' => Jc::CENTER]);

        $tabela->addRow();
        $tabela->addCell(2800)->addText(
            $this->processo->valorTotalObjeto !== null ? formatarMoeda($this->processo->valorTotalObjeto) : '—',
            $fonteCelula, ['alignment' => Jc::CENTER]
        );
        $tabela->addCell(2800)->addText(formatarMoeda($this->processo->calcularValorTotalItens()), $fonteCelula, ['alignment' => Jc::CENTER]);
        $tabela->addCell(2000)->addText(
            $indice !== null ? formatarNumero($indice, 1) . '%' : '—',
            array_merge($fonteCelula, ['bold' => true, 'color' => $dentroLimite === false ? 'C00000' : '000000']),
            ['alignment' => Jc::CENTER]
        );

        $secao->addTextBreak();

        if ($indice !== null && $dentroLimite === false) {
            $secao->addText(
                'ATENÇÃO: o índice apurado ultrapassa o limite legal de 25% (vinte e cinco por cento) do valor total do objeto do contrato, devendo o processo ser objeto de análise jurídica específica antes de prosseguir.',
                ['bold' => true, 'color' => 'C00000'],
                ['alignment' => Jc::BOTH, 'spaceAfter' => 200]
            );
        }
    }

    private function montarTabelaItem($secao, ItemVantajosidade $item): void
    {
        $estiloTabela          = ['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80];
        $estiloCelulaCabecalho = ['bgColor' => 'D9D9D9'];
        $estiloCelulaTitulo    = ['bgColor' => '1F3864'];
        $fonteCabecalho        = ['bold' => true, 'size' => 9];
        $fonteCelula           = ['size' => 9];
        $fonteTitulo           = ['bold' => true, 'size' => 10, 'color' => 'FFFFFF'];

        $col1         = 1500;
        $col2         = 5400;
        $col3         = 1800;
        $col4         = 1500;
        $larguraTotal = $col1 + $col2 + $col3 + $col4;

        $resultado = $item->analisar();
        $precos    = $item->buscarPrecos();

        $tabela = $secao->addTable($estiloTabela);

        $tabela->addRow();
        $tabela->addCell($larguraTotal, array_merge($estiloCelulaTitulo, ['gridSpan' => 4]))
            ->addText('LOTE ' . $item->lote . ' — ITEM ' . $item->item, $fonteTitulo);

        $tabela->addRow();
        $tabela->addCell($col1, $estiloCelulaCabecalho)->addText('ITEM',        $fonteCabecalho, ['alignment' => Jc::CENTER]);
        $tabela->addCell($col2, $estiloCelulaCabecalho)->addText('DESCRIÇÃO',   $fonteCabecalho, ['alignment' => Jc::CENTER]);
        $tabela->addCell($col3, $estiloCelulaCabecalho)->addText('UND. MEDIDA', $fonteCabecalho, ['alignment' => Jc::CENTER]);
        $tabela->addCell($col4, $estiloCelulaCabecalho)->addText('QTDE',        $fonteCabecalho, ['alignment' => Jc::CENTER]);

        $tabela->addRow();
        $tabela->addCell($col1)->addText($item->item,              $fonteCelula, ['alignment' => Jc::CENTER]);
        $tabela->addCell($col2)->addText($item->descricao,         $fonteCelula);
        $tabela->addCell($col3)->addText($item->unidade,           $fonteCelula, ['alignment' => Jc::CENTER]);
        $tabela->addCell($col4)->addText(formatarNumero($item->quantidade), $fonteCelula, ['alignment' => Jc::CENTER]);

        $tabela->addRow();
        $celulaRef = $tabela->addCell($larguraTotal, ['gridSpan' => 4]);
        $linhaRef  = $celulaRef->addTextRun();
        $linhaRef->addText(($this->processo->ehContratoAditivo() ? 'Valor de referência do contrato: ' : 'Preço registrado na Ata: '), ['size' => 9]);
        $linhaRef->addText(formatarMoeda($item->precoAta), ['bold' => true, 'bgColor' => 'FFFF00', 'size' => 9]);

        $secao->addTextBreak();

        if (count($precos) > 0) {
            $this->montarTabelaPrecosMercado($secao, $precos, $item->precoAta);
            $secao->addTextBreak();

            foreach ($precos as $preco) {
                $secao->addText(
                    $this->montarJustificativaPreco($preco, $item->precoAta),
                    [], ['alignment' => Jc::BOTH, 'spaceAfter' => 150]
                );
            }
        } else {
            $secao->addText('Nenhum preço de mercado coletado para este item até o momento.', ['italic' => true, 'size' => 9], ['spaceAfter' => 150]);
        }

        if ($resultado['resultado'] !== null) {
            $secao->addText(
                'Resultado do item: ' . ($resultado['resultado'] === AnaliseVantajosidade::VANTAJOSA ? 'VANTAJOSO ADERIR' : 'MERCADO MAIS BARATO — NÃO VANTAJOSO'),
                ['bold' => true, 'bgColor' => $resultado['resultado'] === AnaliseVantajosidade::VANTAJOSA ? '92D050' : 'FF9999'],
                ['spaceAfter' => 300]
            );
        }
    }

    private function montarTabelaPrecosMercado($secao, array $precos, float $precoReferencia): void
    {
        $estiloTabela          = ['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80];
        $estiloCelulaCabecalho = ['bgColor' => 'D9D9D9'];
        $fonteCabecalho        = ['bold' => true, 'size' => 8];
        $fonteCelula           = ['size' => 8];

        $analiseAux = new AnaliseVantajosidade($precoReferencia, []);

        $tabela = $secao->addTable($estiloTabela);
        $tabela->addRow();
        $tabela->addCell(500,  $estiloCelulaCabecalho)->addText('Nº',                 $fonteCabecalho, ['alignment' => Jc::CENTER]);
        $tabela->addCell(2600, $estiloCelulaCabecalho)->addText('Fonte / Fornecedor', $fonteCabecalho, ['alignment' => Jc::CENTER]);
        $tabela->addCell(1600, $estiloCelulaCabecalho)->addText('Parâmetro',          $fonteCabecalho, ['alignment' => Jc::CENTER]);
        $tabela->addCell(1500, $estiloCelulaCabecalho)->addText('Preço mercado (R$)', $fonteCabecalho, ['alignment' => Jc::CENTER]);
        $tabela->addCell(1400, $estiloCelulaCabecalho)->addText('% em relação à ref.', $fonteCabecalho, ['alignment' => Jc::CENTER]);

        foreach ($precos as $indice => $preco) {
            $diferenca = $analiseAux->calcularDiferencaPorPreco($preco->valor);

            $tabela->addRow();
            $tabela->addCell(500)->addText((string) ($indice + 1), $fonteCelula, ['alignment' => Jc::CENTER]);
            $tabela->addCell(2600)->addText($preco->fonte ?: '—', $fonteCelula);
            $tabela->addCell(1600)->addText($preco->parametro ?: '—', $fonteCelula);
            $tabela->addCell(1500)->addText(formatarMoeda($preco->valor), $fonteCelula, ['alignment' => Jc::CENTER]);
            $tabela->addCell(1400)->addText(($diferenca >= 0 ? '+' : '') . formatarNumero($diferenca, 1) . '%', $fonteCelula, ['alignment' => Jc::CENTER]);
        }
    }

    private function montarJustificativaPreco($preco, float $precoReferencia): string
    {
        $fonte          = $preco->fonte !== '' ? $preco->fonte : 'fonte não identificada';
        $valorFormatado = formatarMoeda($preco->valor);
        $analiseAux     = new AnaliseVantajosidade($precoReferencia, []);
        $diferenca      = $analiseAux->calcularDiferencaPorPreco($preco->valor);
        $percentual     = formatarNumero(abs($diferenca), 1);

        if ($diferenca >= 0) {
            return "O preço de {$valorFormatado}, coletado junto a {$fonte}, está {$percentual}% acima do valor de referência, "
                . "reforçando a vantajosidade da manutenção do valor atualmente praticado.";
        }

        return "O preço de {$valorFormatado}, coletado junto a {$fonte}, está {$percentual}% abaixo do valor de referência, "
            . "indicando que o mercado pratica condição mais econômica que a atualmente vigente para este item.";
    }

    private function montarSecaoConclusao(): void
    {
        $secao = $this->documento->addSection();

        $secao->addText('4. DA CONCLUSÃO', ['bold' => true, 'size' => 12], ['spaceAfter' => 300]);

        $itens             = $this->processo->buscarItens();
        $totalItens        = 0;
        $totalVantajosos   = 0;

        foreach ($itens as $item) {
            $resultado = $item->analisar();
            if ($resultado['resultado'] !== null) {
                $totalItens++;
                if ($resultado['resultado'] === AnaliseVantajosidade::VANTAJOSA) {
                    $totalVantajosos++;
                }
            }
        }

        $todosVantajosos = $totalItens > 0 && $totalVantajosos === $totalItens;

        $linhaResultado = $secao->addTextRun(['alignment' => Jc::BOTH, 'spaceAfter' => 200]);
        $linhaResultado->addText('Da análise realizada, ');
        $linhaResultado->addText($totalVantajosos, ['bold' => true]);
        $linhaResultado->addText(' de ');
        $linhaResultado->addText($totalItens, ['bold' => true]);
        $linhaResultado->addText(' itens analisados apresentaram-se VANTAJOSOS em relação às condições atuais de mercado.');

        if ($this->processo->ehContratoAditivo()) {
            $indice = $this->processo->calcularIndiceAditivo();
            if ($indice !== null) {
                $linhaIndice = $secao->addTextRun(['alignment' => Jc::BOTH, 'spaceAfter' => 200]);
                $linhaIndice->addText('O índice do aditivo apurado foi de ');
                $linhaIndice->addText(formatarNumero($indice, 1) . '%', ['bold' => true]);
                $linhaIndice->addText($this->processo->indiceAditivoDentroDoLimiteLegal()
                    ? ', permanecendo dentro do limite legal de 25% (vinte e cinco por cento).'
                    : ', ULTRAPASSANDO o limite legal de 25% (vinte e cinco por cento), o que exige análise jurídica prévia antes do prosseguimento do processo.');
            }
        }

        $secao->addText(
            $todosVantajosos
                ? 'Diante do exposto, conclui-se que ' . ($this->processo->ehContratoAditivo() ? 'a contratação do aditivo' : 'a adesão') . ' se mostra vantajosa para a MT Participações e Projetos S.A., estando em conformidade com os princípios da economicidade e da eficiência.'
                : 'Diante do exposto, recomenda-se a reavaliação dos itens apontados como não vantajosos, podendo ser necessária nova pesquisa de preços antes de prosseguir com o processo.',
            [],
            ['alignment' => Jc::BOTH, 'spaceAfter' => 200]
        );

        $secao->addPageBreak();
    }

    private function montarSecaoElaboracao(): void
    {
        $secao = $this->documento->addSection();

        $secao->addText('5. DA ELABORAÇÃO', ['bold' => true, 'size' => 12], ['spaceAfter' => 400]);

        $secao->addText('ELABORADO POR:', [], ['spaceAfter' => 400]);
        $secao->addText(mb_strtoupper($this->elaboradoPor->nome), ['bold' => true], ['alignment' => Jc::CENTER]);
        $secao->addText($this->elaboradoPor->cargo, [], ['alignment' => Jc::CENTER, 'spaceAfter' => 400]);

        $secao->addText('VALIDADO:', [], ['spaceAfter' => 400]);
        $secao->addText(mb_strtoupper($this->validadoPor->nome), ['bold' => true], ['alignment' => Jc::CENTER]);
        $secao->addText($this->validadoPor->cargo, [], ['alignment' => Jc::CENTER]);
    }
}
