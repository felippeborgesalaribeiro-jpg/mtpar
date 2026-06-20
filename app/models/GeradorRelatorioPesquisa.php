<?php

require_once __DIR__ . '/Cotacao.php';
require_once __DIR__ . '/Servidor.php';
require_once __DIR__ . '/AnalisePrecos.php';
require_once __DIR__ . '/../helpers/extenso.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\IOFactory;

require_once __DIR__ . '/../../vendor/autoload.php';

class GeradorRelatorioPesquisa
{
    private Cotacao $cotacao;
    private Servidor $elaboradoPor;
    private PhpWord $documento;
    private float $valorGlobalEstimado = 0.0;

    const FONTE_PADRAO = 'Calibri';
    const TAMANHO_PADRAO = 11;

    const CRITERIO_LABEL = [
        AnalisePrecos::CRITERIO_MEDIA => 'média',
        AnalisePrecos::CRITERIO_MEDIANA => 'mediana',
        AnalisePrecos::CRITERIO_MENOR_PRECO => 'menor preço',
    ];

    public function __construct(Cotacao $cotacao, Servidor $elaboradoPor)
    {
        $this->cotacao = $cotacao;
        $this->elaboradoPor = $elaboradoPor;

        $this->documento = new PhpWord();
        $this->documento->setDefaultFontName(self::FONTE_PADRAO);
        $this->documento->setDefaultFontSize(self::TAMANHO_PADRAO);
    }

    public function gerar(): string
    {
        $this->montarCapa();
        $this->montarSecaoLegislacao();
        $this->montarSecaoMetodologia();
        $this->montarSecaoPesquisaPorItem();
        $this->montarSecaoConclusao();
        $this->montarSecaoElaboracao();

        $caminhoTemp = sys_get_temp_dir() . '/relatorio_pesquisa_' . uniqid() . '.docx';
        $writer = IOFactory::createWriter($this->documento, 'Word2007');
        $writer->save($caminhoTemp);

        return $caminhoTemp;
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

        $secao->addText('RELATÓRIO DE PESQUISA DE PREÇOS', ['bold' => true, 'size' => 12], ['alignment' => Jc::CENTER]);
        $secao->addTextBreak();

        $textoObjeto = $secao->addTextRun(['alignment' => Jc::CENTER]);
        $textoObjeto->addText('OBJETO: ', ['bold' => true]);
        $textoObjeto->addText('"Pesquisa de Preços - Processo ', ['italic' => true]);
        $textoObjeto->addText(htmlspecialchars($this->cotacao->numeroProcesso), ['italic' => true]);
        $textoObjeto->addText('"', ['italic' => true]);

        $secao->addPageBreak();
    }

    private function montarSecaoLegislacao(): void
    {
        $secao = $this->documento->addSection();
        $paragrafo = ['alignment' => Jc::BOTH, 'spaceAfter' => 200];
        $citacao = ['alignment' => Jc::BOTH, 'spaceAfter' => 200, 'indentation' => ['left' => 700]];

        $secao->addText('1. DA LEGISLAÇÃO APLICÁVEL', ['bold' => true, 'size' => 12], ['spaceAfter' => 300]);

        $secao->addText(
            'A Lei nº 13.303/2016, conhecida também como Lei das Estatais, dispõe sobre o estatuto jurídico das empresas públicas, da sociedade de economia mista e de suas subsidiárias, no âmbito da União, dos Estados, do Distrito Federal e dos Municípios.',
            [],
            $paragrafo
        );

        $secao->addText(
            'Especificamente quanto à obrigatoriedade de regulamentação interna dos procedimentos de licitação e contratação direta, a Lei nº 13.303/2016 estabelece:',
            [],
            $paragrafo
        );

        $secao->addText('Art. 40. As empresas públicas e as sociedades de economia mista deverão publicar e manter atualizado regulamento interno de licitações e contratos, compatível com o disposto nesta Lei, especialmente quanto a:', ['italic' => true, 'size' => 10], $citacao);
        $secao->addText('IV - procedimentos de licitação e contratação direta;', ['italic' => true, 'size' => 10], $citacao);

        $secao->addText(
            'Diante disso, com o advento da Lei 13.303/2016, a MTPAR editou seu Regulamento Interno de Licitações e Contratos (RILC/MTPAR), aprovado pelo Conselho de Administração, e instituído por meio da Resolução Nº 004/CONSELHO DE ADM/2020 do Conselho de Administração da empresa, com atualizações posteriores conforme aprovado pela Resolução Nº004/2023/CAD.',
            [],
            $paragrafo
        );

        $secao->addText(
            'A estimativa do valor do objeto e a presente pesquisa de preços observam, especificamente, os parâmetros estabelecidos no art. 9º do RILC/MTPAR, que dispõe:',
            [],
            $paragrafo
        );

        $secao->addText('Art. 9º. A estimativa do valor do objeto do procedimento licitatório e a justificativa de preço da contratação direta serão realizadas a partir dos seguintes parâmetros:', ['italic' => true, 'size' => 10], $citacao);
        $secao->addText('I – pesquisa no banco de preços disponibilizado pelo Estado de Mato Grosso, no Sistema Radar de Controle Público do Tribunal de Contas do Estado de Mato Grosso e no Painel de Preços do Governo Federal mantido pelo Ministério do Planejamento ou em outro instrumento congênere;', ['italic' => true, 'size' => 10], $citacao);
        $secao->addText('II - pesquisa em mídia e sítios especializados ou de domínio amplo;', ['italic' => true, 'size' => 10], $citacao);
        $secao->addText('III - contratações similares realizadas pela própria MT-PAR ou por outros entes públicos ou privados;', ['italic' => true, 'size' => 10], $citacao);
        $secao->addText('IV - por meio da elaboração de planilha de custos e formação de preços pela própria MT-PAR; ou', ['italic' => true, 'size' => 10], $citacao);
        $secao->addText('V - pesquisa junto a fornecedores de bens ou prestadores de serviços.', ['italic' => true, 'size' => 10], $citacao);
        $secao->addText('§1º Os parâmetros previstos nos incisos deste artigo poderão ser utilizados de forma combinada ou não, demonstrada no processo administrativo a metodologia utilizada para obtenção do preço de referência.', ['italic' => true, 'size' => 10], $citacao);
        $secao->addText('§2º Serão utilizadas, como metodologia para obtenção do preço de referência para a contratação, a média, a mediana ou o menor dos valores obtidos na pesquisa de preços, desde que o cálculo incida sobre um conjunto de três ou mais preços, oriundos de um ou mais dos parâmetros adotados neste artigo, desconsiderados os valores inexequíveis e os excessivamente elevados.', ['italic' => true, 'size' => 10], $citacao);
        $secao->addText('§3º Poderão ser utilizados outros critérios ou metodologias, desde que devidamente justificados.', ['italic' => true, 'size' => 10], $citacao);
        $secao->addText('§4º Os preços coletados devem ser analisados de forma crítica, em especial, quando houver grande variação entre os valores apresentados.', ['italic' => true, 'size' => 10], $citacao);
        $secao->addText('§5º Para desconsideração dos preços inexequíveis ou excessivamente elevados, deverão ser adotados critérios fundamentados e descritos no processo administrativo.', ['italic' => true, 'size' => 10], $citacao);
        $secao->addText('§6º Excepcionalmente, mediante justificativa será admitida a pesquisa com menos de três preços ou fornecedores.', ['italic' => true, 'size' => 10], $citacao);
        $secao->addText('§7º (Dispositivo revogado pela Resolução nº 004/2023/CAD).', ['italic' => true, 'size' => 10, 'strikethrough' => true], $citacao);

        $secao->addText(
            'Por sua vez, no que se refere ao critério numérico para identificação de preços excessivos e inexequíveis, aplica-se subsidiariamente o disposto no art. 47 do Decreto Estadual nº 1.525, de 23 de novembro de 2022, que estabelece:',
            [],
            $paragrafo
        );

        $secao->addText('Art. 47. Serão utilizados como métodos para obtenção do preço estimado a média, a mediana ou o menor dos valores obtidos na pesquisa de preços, desde que o cálculo incida sobre um conjunto de no mínimo 03 (três) preços oriundos dos parâmetros de que trata o art. 46 deste Decreto, desconsiderados os valores inexequíveis e os excessivamente elevados.', ['italic' => true, 'size' => 10], $citacao);
        $secao->addText('§3º Salvo quando estabelecido de forma diversa e justificada nos autos, serão considerados: I - preços excessivos, aqueles que sejam superiores a 30% (trinta por cento) da média dos demais preços; II - preços inexequíveis, aqueles que sejam inferiores a 70% (setenta por cento) da média dos demais preços.', ['italic' => true, 'size' => 10], $citacao);
        $secao->addText('§4º A não consideração de propostas inexequíveis ou excessivamente elevadas deve ser declarada expressamente pela área técnica competente, sendo possível a ressalva de situações excepcionais devidamente justificadas de acordo com a natureza ou especificidade do bem ou serviço em cotação.', ['italic' => true, 'size' => 10], $citacao);
        $secao->addText('§5º Excetuam-se da regra de inexequibilidade prevista no parágrafo anterior os valores registrados em atas e previstos em contratos firmados pela Administração Pública, em execução ou executados no período de 1 (um) ano anterior à data da pesquisa de preços.', ['italic' => true, 'size' => 10], $citacao);

        $secao->addPageBreak();
    }

    private function montarSecaoMetodologia(): void
    {
        $secao = $this->documento->addSection();
        $paragrafo = ['alignment' => Jc::BOTH, 'spaceAfter' => 200];

        $secao->addText('2. DA METODOLOGIA DE ANÁLISE', ['bold' => true, 'size' => 12], ['spaceAfter' => 300]);

        $secao->addText(
            'A presente pesquisa adota metodologia de análise em duas etapas sucessivas, na forma do art. 9º, §§4º e 5º, do RILC/MTPAR, em conjunto com os critérios numéricos estabelecidos no art. 47, §3º, do Decreto Estadual nº 1.525/2022.',
            [],
            $paragrafo
        );

        $secao->addText(
            'Na primeira etapa, calcula-se, para cada preço coletado, a média dos demais preços do mesmo item, sendo considerado EXCESSIVAMENTE ELEVADO aquele que superar em mais de 30% (trinta por cento) essa média, na forma do art. 47, §3º, inciso I, do Decreto Estadual nº 1.525/2022.',
            [],
            $paragrafo
        );

        $secao->addText(
            'Na segunda etapa, utilizando-se apenas os preços remanescentes da primeira etapa, recalcula-se a média dos demais preços, sendo considerado INEXEQUÍVEL aquele que for inferior a 70% (setenta por cento) dessa nova média, na forma do art. 47, §3º, inciso II, do Decreto Estadual nº 1.525/2022 — ressalvados os valores registrados em ata ou previstos em contrato firmado pela Administração Pública, em execução ou executado no período de 1 (um) ano anterior à data da pesquisa, na forma do §5º do mesmo artigo.',
            [],
            $paragrafo
        );

        $criterioLabel = self::CRITERIO_LABEL[$this->cotacao->criterioConsolidacao] ?? 'mediana';

        $linhaCriterio = $secao->addTextRun($paragrafo);
        $linhaCriterio->addText('Os preços aprovados em ambas as etapas compõem o preço de referência do item, sendo adotado, para a presente pesquisa, o critério da ');
        $linhaCriterio->addText($criterioLabel, ['bold' => true]);
        $linhaCriterio->addText(', nos termos do art. 9º, §2º, do RILC/MTPAR.');

        $secao->addText(
            'As tabelas a seguir demonstram, para cada preço coletado em cada item de cada lote, o cálculo da média dos demais preços, o percentual de variação correspondente e o resultado da análise, repetindo-se a mesma estrutura para todos os itens do objeto licitado.',
            [],
            $paragrafo
        );

        $secao->addPageBreak();
    }

    private function montarSecaoPesquisaPorItem(): void
    {
        $secao = $this->documento->addSection();

        $secao->addText('3. DA PESQUISA DE PREÇOS POR LOTE E ITEM', ['bold' => true, 'size' => 12], ['spaceAfter' => 300]);

        $lotes = $this->cotacao->buscarLotes();
        $valorGlobalEstimado = 0.0;

        foreach ($lotes as $lote) {
            $itens = $lote->buscarItens();
            $valorTotalLote = 0.0;

            foreach ($itens as $item) {
                $this->montarBlocoItem($secao, $lote, $item);
                $resultado = $item->analisar($this->cotacao->criterioConsolidacao);
                $valorReferencia = $resultado['valor_referencia'] ?? 0;
                $valorTotalLote += $valorReferencia * $item->quantidade;
            }

            if (count($lotes) > 1) {
                $secao->addText(
                    'Preço de referência consolidado do Lote ' . htmlspecialchars($lote->numero) . ': ' . formatarMoeda($valorTotalLote),
                    ['bold' => true],
                    ['spaceAfter' => 300, 'spaceBefore' => 150]
                );
            }

            $valorGlobalEstimado += $valorTotalLote;
        }

        $this->valorGlobalEstimado = $valorGlobalEstimado;
    }

    private function montarBlocoItem($secao, $lote, $item): void
    {
        $estiloTabela = ['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80];
        $estiloCelulaCabecalho = ['bgColor' => 'D9D9D9'];
        $fonteCabecalho = ['bold' => true, 'size' => 8];
        $fonteCelula = ['size' => 8];

        $secao->addText(
            'LOTE ' . htmlspecialchars($lote->numero) . ' — ITEM ' . $item->numero . ' — ' . htmlspecialchars($item->descricao),
            ['bold' => true, 'size' => 10],
            ['spaceBefore' => 300, 'spaceAfter' => 150]
        );

        $precos = $item->buscarPrecos();
        $resultado = $item->analisar($this->cotacao->criterioConsolidacao);

        $tabela = $secao->addTable($estiloTabela);
        $tabela->addRow();
        $tabela->addCell(500, $estiloCelulaCabecalho)->addText('Nº', $fonteCabecalho, ['alignment' => Jc::CENTER]);
        $tabela->addCell(2200, $estiloCelulaCabecalho)->addText('Fonte / Fornecedor', $fonteCabecalho, ['alignment' => Jc::CENTER]);
        $tabela->addCell(1400, $estiloCelulaCabecalho)->addText('Parâmetro', $fonteCabecalho, ['alignment' => Jc::CENTER]);
        $tabela->addCell(1300, $estiloCelulaCabecalho)->addText('Preço (R$)', $fonteCabecalho, ['alignment' => Jc::CENTER]);
        $tabela->addCell(1500, $estiloCelulaCabecalho)->addText('Média dos demais (R$)', $fonteCabecalho, ['alignment' => Jc::CENTER]);
        $tabela->addCell(1100, $estiloCelulaCabecalho)->addText('% em relação à média', $fonteCabecalho, ['alignment' => Jc::CENTER]);
        $tabela->addCell(1600, $estiloCelulaCabecalho)->addText('Resultado', $fonteCabecalho, ['alignment' => Jc::CENTER]);

        foreach ($precos as $indice => $preco) {
            $resultadoFinal = $resultado['resultado_final'][$indice];
            $resultadoEtapa1 = $resultado['etapa1'][$indice];
            $resultadoEtapa2 = $resultado['etapa2'][$indice];

            if ($resultadoFinal === AnalisePrecos::EXCESSIVO) {
                $mediaDosDemais = $resultadoEtapa1['media_demais'];
                $percentual = $resultadoEtapa1['diferenca'] !== null
                    ? '+' . formatarNumero($resultadoEtapa1['diferenca'] * 100, 1) . '%'
                    : '—';
            } elseif ($resultadoEtapa2['comparacao'] !== null) {
                $mediaDosDemais = $resultadoEtapa2['media_demais'];
                $percentual = formatarNumero($resultadoEtapa2['comparacao'] * 100, 1) . '%';
            } else {
                $mediaDosDemais = $resultadoEtapa1['media_demais'];
                $percentual = '—';
            }

            $textoMedia = $mediaDosDemais !== null ? formatarMoeda($mediaDosDemais) : '—';

            $tabela->addRow();
            $tabela->addCell(500)->addText((string) ($indice + 1), $fonteCelula, ['alignment' => Jc::CENTER]);
            $tabela->addCell(2200)->addText(htmlspecialchars($preco->fonte ?: '—'), $fonteCelula);
            $tabela->addCell(1400)->addText(htmlspecialchars($preco->parametro ?: '—'), $fonteCelula);
            $tabela->addCell(1300)->addText(formatarMoeda($preco->valor), $fonteCelula, ['alignment' => Jc::CENTER]);
            $tabela->addCell(1500)->addText($textoMedia, $fonteCelula, ['alignment' => Jc::CENTER]);
            $tabela->addCell(1100)->addText($percentual, $fonteCelula, ['alignment' => Jc::CENTER]);
            $tabela->addCell(1600)->addText($resultadoFinal, $fonteCelula, ['alignment' => Jc::CENTER]);
        }

        $secao->addTextBreak();

        foreach ($precos as $indice => $preco) {
            $textoJustificativa = $this->montarJustificativaPreco($preco, $resultado, $indice);
            $secao->addText($textoJustificativa, [], ['alignment' => Jc::BOTH, 'spaceAfter' => 150]);
        }

        $valorReferencia = $resultado['valor_referencia'] ?? 0;
        $valorTotal = $valorReferencia * $item->quantidade;
        $criterioLabel = self::CRITERIO_LABEL[$this->cotacao->criterioConsolidacao] ?? 'mediana';

        $linhaUnitario = $secao->addTextRun(['spaceBefore' => 150]);
        $linhaUnitario->addText('Preço de referência unitário do item (' . $criterioLabel . ' dos preços aprovados): ');
        $linhaUnitario->addText(formatarMoeda($valorReferencia), ['bold' => true]);

        $linhaTotal = $secao->addTextRun(['spaceAfter' => 300]);
        $linhaTotal->addText('Preço de referência total do item (x ' . formatarNumero($item->quantidade) . ' ' . htmlspecialchars($item->unidade) . '): ');
        $linhaTotal->addText(formatarMoeda($valorTotal), ['bold' => true]);
    }

    private function montarJustificativaPreco($preco, array $resultado, int $indice): string
    {
        $fonte = $preco->fonte !== '' ? $preco->fonte : 'fonte não identificada';
        $valorFormatado = formatarMoeda($preco->valor);
        $resultadoFinal = $resultado['resultado_final'][$indice];

        if ($resultadoFinal === AnalisePrecos::EXCESSIVO) {
            $diferenca = $resultado['etapa1'][$indice]['diferenca'] ?? 0;
            $percentual = formatarNumero($diferenca * 100, 1);

            return "O preço de {$valorFormatado}, ofertado por {$fonte}, foi considerado EXCESSIVAMENTE ELEVADO, "
                . "tendo em vista que superou em {$percentual}% a média dos demais preços coletados para o item, "
                . "ultrapassando o limite de 30% (trinta por cento) previsto no art. 47, §3º, inciso I, do Decreto "
                . "Estadual nº 1.525/2022, razão pela qual sua desconsideração é aqui expressamente fundamentada e "
                . "descrita, na forma do art. 9º, §5º, do RILC/MTPAR, para fins de composição do preço de referência.";
        }

        if ($resultadoFinal === AnalisePrecos::INEXEQUIVEL) {
            $comparacao = $resultado['etapa2'][$indice]['comparacao'] ?? 0;
            $percentual = formatarNumero($comparacao * 100, 1);

            return "O preço de {$valorFormatado}, ofertado por {$fonte}, foi considerado INEXEQUÍVEL, "
                . "por corresponder a apenas {$percentual}% da média dos demais preços remanescentes, estando "
                . "abaixo do limite de 70% (setenta por cento) previsto no art. 47, §3º, inciso II, do Decreto "
                . "Estadual nº 1.525/2022, razão pela qual sua desconsideração é aqui expressamente fundamentada e "
                . "descrita, na forma do art. 9º, §5º, do RILC/MTPAR, para fins de composição do preço de referência.";
        }

        if ($resultadoFinal === AnalisePrecos::EXCECAO_PRECO_PUBLICO) {
            return "O preço de {$valorFormatado}, ofertado por {$fonte}, muito embora situado abaixo de 70% "
                . "(setenta por cento) da média dos demais preços, foi mantido para fins de composição do preço de "
                . "referência, por se tratar de valor registrado em ata ou previsto em contrato firmado pela "
                . "Administração Pública, em execução ou executado no período de 1 (um) ano anterior à data da "
                . "pesquisa de preços, na forma do art. 47, §5º, do Decreto Estadual nº 1.525/2022, não se aplicando, "
                . "portanto, o critério de inexequibilidade.";
        }

        $parametroTexto = $preco->parametro !== '' ? ", referente ao parâmetro {$preco->parametro}" : '';
        $diferenca = $resultado['etapa1'][$indice]['diferenca'] ?? null;

        $textoVariacao = '';
        if ($diferenca !== null) {
            $percentualDiferenca = formatarNumero($diferenca * 100, 1);
            $textoVariacao = " (variação de {$percentualDiferenca}% em relação à média dos demais preços)";
        }

        return "O preço de {$valorFormatado}, ofertado por {$fonte}{$parametroTexto}, foi considerado APROVADO{$textoVariacao}, "
            . "por não se enquadrar nas hipóteses de preço excessivo ou inexequível previstas no art. 47, §3º, do "
            . "Decreto Estadual nº 1.525/2022, mantendo-se dentro da faixa de preços praticados no mercado, na "
            . "forma do art. 9º, §4º, do RILC/MTPAR.";
    }

    private function montarSecaoConclusao(): void
    {
        $secao = $this->documento->addSection();

        $secao->addText('4. DA CONCLUSÃO', ['bold' => true, 'size' => 12], ['spaceAfter' => 300]);

        $extenso = numeroParaExtenso($this->valorGlobalEstimado);
        $extensoComMaiuscula = mb_strtoupper(mb_substr($extenso, 0, 1)) . mb_substr($extenso, 1);
        $criterioLabel = self::CRITERIO_LABEL[$this->cotacao->criterioConsolidacao] ?? 'mediana';

        $linhaPrecoGlobal = $secao->addTextRun(['alignment' => Jc::BOTH, 'spaceAfter' => 200]);
        $linhaPrecoGlobal->addText('Concluída a pesquisa de preços, com a desconsideração fundamentada dos valores excessivos e inexequíveis identificados, e adotado o critério da ');
        $linhaPrecoGlobal->addText($criterioLabel, ['bold' => true]);
        $linhaPrecoGlobal->addText(' para composição dos preços de referência, nos termos do art. 9º, §2º, do RILC/MTPAR, apresenta-se o preço de referência global estimado para a contratação, no valor de ');
        $linhaPrecoGlobal->addText(
            formatarMoeda($this->valorGlobalEstimado) . ' (' . $extensoComMaiuscula . ')',
            ['bold' => true]
        );
        $linhaPrecoGlobal->addText('.');

        $secao->addText(
            'A presente pesquisa observou os parâmetros estabelecidos no art. 9º do RILC/MTPAR e os critérios de desconsideração de preços excessivos e inexequíveis previstos no art. 47 do Decreto Estadual nº 1.525/2022, estando, portanto, em conformidade com os princípios da legalidade, razoabilidade, economicidade e eficiência.',
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
        $secao->addText($this->elaboradoPor->cargo, [], ['alignment' => Jc::CENTER]);
    }
}