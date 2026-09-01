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

                $valorReferencia = $resultado['valor_referencia'] ?? 0;
                $total = $valorReferencia * $item->quantidade;
                $valorTotalLote += $total;

                $mapaItens[] = [
                    'item' => $item,
                    'fornecedores' => $fornecedoresAprovados,
                    'valor_referencia' => $valorReferencia,
                    'total' => $total,
                ];
            }

            $mapaLotes[] = [
                'lote' => $lote,
                'itens' => $mapaItens,
                'valor_total' => $valorTotalLote,
            ];

            $valorGlobalCotacao += $valorTotalLote;
        }

        return [$mapaLotes, $valorGlobalCotacao];
    }
}
