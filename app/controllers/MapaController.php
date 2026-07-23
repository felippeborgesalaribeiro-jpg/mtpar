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

        $cotacao = Cotacao::buscarPorId($cotacaoId);

        if ($cotacao === null) {
            echo 'Cotação não encontrada.';
            return;
        }

        $servidor = $cotacao->buscarServidor();
        $lotes = $cotacao->buscarLotes();

        $mapaLotes = [];
        $valorGlobalCotacao = 0.0;
        $parametrosPrecoPublico = Parametro::buscarNomesPrecoPublico();

        foreach ($lotes as $lote) {
            $itens = $lote->buscarItens();
            $mapaItens = [];
            $valorTotalLote = 0.0;

            foreach ($itens as $item) {
                $resultado = $item->analisar($cotacao->criterioConsolidacao, $parametrosPrecoPublico);
                $precos = $item->buscarPrecos();

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

        require __DIR__ . '/../views/mapa.php';
    }
}