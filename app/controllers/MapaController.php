<?php

require_once __DIR__ . '/../models/Cotacao.php';
require_once __DIR__ . '/../models/AnalisePrecos.php';
require_once __DIR__ . '/../models/Parametro.php';
require_once __DIR__ . '/../helpers/auth.php';

class MapaController
{
    public function mostrar(int $cotacaoId): void
    {
        exigirLogin();

        $cotacao = $this->buscarCotacaoOuEcoar($cotacaoId);
        if ($cotacao === null) {
            return;
        }

        $servidor = $cotacao->buscarServidor();
        [$mapaLotes, $valorGlobalCotacao] = $this->montarMapaLotes($cotacao);

        require __DIR__ . '/../views/mapa.php';
    }

    /**
     * Validacao do Preco de Referencia: mesma tabela do mapa comparativo,
     * mas sem as colunas de fonte/fornecedor - so item, especificacao,
     * media/criterio, und, qtd, total e o valor total do lote.
     */
    public function mostrarValidacao(int $cotacaoId): void
    {
        exigirLogin();

        $cotacao = $this->buscarCotacaoOuEcoar($cotacaoId);
        if ($cotacao === null) {
            return;
        }

        $servidor = $cotacao->buscarServidor();
        [$mapaLotes, $valorGlobalCotacao] = $this->montarMapaLotes($cotacao);

        require __DIR__ . '/../views/validacao_preco_referencia.php';
    }

    private function buscarCotacaoOuEcoar(int $cotacaoId): ?Cotacao
    {
        $cotacao = Cotacao::buscarPorId($cotacaoId);

        if ($cotacao === null) {
            echo 'Cotação não encontrada.';
            return null;
        }

        return $cotacao;
    }

    /**
     * @return array{0: array<int, array{lote: Lote, itens: array, valor_total: float}>, 1: float}
     */
    private function montarMapaLotes(Cotacao $cotacao): array
    {
        $lotes = $cotacao->buscarLotes();

        $mapaLotes = [];
        $valorGlobalCotacao = 0.0;
        $parametrosPrecoPublico = Parametro::buscarNomesPrecoPublico();
        $arredondar = $cotacao->deveArredondarValorReferencia();

        foreach ($lotes as $lote) {
            $itens = $lote->buscarItens();
            $mapaItens = [];
            $valorTotalLote = 0.0;

            foreach ($itens as $item) {
                $precos = $item->buscarPrecos();
                $resultado = $item->analisar($cotacao->criterioConsolidacao, $parametrosPrecoPublico, $arredondar, $precos);

                $fornecedoresAprovados = [];
                foreach ($precos as $indice => $preco) {
                    $resultadoFinal = $resultado['resultado_final'][$indice];
                    if ($resultadoFinal === AnalisePrecos::APROVADO || $resultadoFinal === AnalisePrecos::EXCECAO_PRECO_PUBLICO) {
                        $fornecedoresAprovados[] = [
                            'fonte' => $preco->fonte !== '' ? $preco->fonte : 'Fonte não informada',
                            'valor' => $preco->valor,
                        ];
                    }
                }

                // Arredonda o valor de referencia PARA A EXIBICAO antes de
                // multiplicar pela quantidade. Cotacoes antigas (antes de
                // DATA_CORTE_VALOR_REFERENCIA_ARREDONDADO) mantem por dentro
                // a media/mediana com casas alem de 2 - se o total fosse
                // calculado do valor bruto (ex.: 1031,6666... x 4 = 4126,6666
                // => R$ 4.126,67), o usuario conferindo "1031,67 x 4 = 4126,68"
                // veria diferenca de 1 centavo. Multiplicando pelo mesmo
                // numero que aparece na tela, a conta sempre fecha, sem
                // alterar o valor historico armazenado.
                $valorReferencia = round($resultado['valor_referencia'] ?? 0, 2);
                $total = round($valorReferencia * $item->quantidade, 2);
                $valorTotalLote += $total;

                $mapaItens[] = [
                    'item' => $item,
                    'fornecedores' => $fornecedoresAprovados,
                    'valor_referencia' => $valorReferencia,
                    'total' => $total,
                ];
            }

            // Blindagem final contra ruido de ponto flutuante acumulado ao
            // somar varios totais 2dp - o total do lote e o valor global
            // sao os "orcamentos" que aparecem em Termos e Relatorios.
            $valorTotalLote = round($valorTotalLote, 2);

            $mapaLotes[] = [
                'lote' => $lote,
                'itens' => $mapaItens,
                'valor_total' => $valorTotalLote,
            ];

            $valorGlobalCotacao += $valorTotalLote;
        }

        $valorGlobalCotacao = round($valorGlobalCotacao, 2);

        return [$mapaLotes, $valorGlobalCotacao];
    }
}
