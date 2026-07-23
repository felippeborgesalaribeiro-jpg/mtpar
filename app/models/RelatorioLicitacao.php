<?php

require_once __DIR__ . '/Licitacao.php';

/**
 * Agrega uma lista de Licitacao em relatorios por unidade demandante,
 * por servidor responsavel e por ano - sem tocar o banco diretamente,
 * para poder ser testado com fixtures em memoria.
 */
class RelatorioLicitacao
{
    public static function porSetorDemandante(array $licitacoes): array
    {
        return self::agrupar(
            $licitacoes,
            fn(Licitacao $l) => $l->setorDemandante !== '' ? $l->setorDemandante : 'Não informado'
        );
    }

    public static function porServidorResponsavel(array $licitacoes): array
    {
        return self::agrupar(
            $licitacoes,
            function (Licitacao $l) {
                $servidor = $l->buscarServidorResponsavel();
                return $servidor !== null ? $servidor->nome : 'Não informado';
            }
        );
    }

    public static function porAno(array $licitacoes): array
    {
        $linhas = self::agrupar($licitacoes, [self::class, 'anoDeReferencia']);
        krsort($linhas);

        return $linhas;
    }

    private static function anoDeReferencia(Licitacao $licitacao): string
    {
        $dataReferencia = $licitacao->realizacaoSessaoPublica ?? $licitacao->criadoEm;

        if ($dataReferencia === '' || $dataReferencia === null) {
            return 'Não informado';
        }

        return date('Y', strtotime($dataReferencia));
    }

    private static function agrupar(array $licitacoes, callable $chaveDe): array
    {
        $grupos = [];

        foreach ($licitacoes as $licitacao) {
            $chave = $chaveDe($licitacao);

            if (!isset($grupos[$chave])) {
                $grupos[$chave] = [
                    'chave' => $chave,
                    'quantidade' => 0,
                    'valor_estimado' => 0.0,
                    'valor_adjudicado' => 0.0,
                    'valor_estimado_homologadas' => 0.0,
                    'homologadas' => 0,
                ];
            }

            $grupos[$chave]['quantidade']++;
            $grupos[$chave]['valor_estimado'] += $licitacao->valorEstimado ?? 0;

            // "Homologada" exige o ato formal de finalizar o processo -
            // so ter um valor_adjudicado digitado nao basta (pode ser rascunho).
            if ($licitacao->estaFinalizada()) {
                $grupos[$chave]['valor_adjudicado'] += $licitacao->valorAdjudicado ?? 0;
                $grupos[$chave]['valor_estimado_homologadas'] += $licitacao->valorEstimado ?? 0;
                $grupos[$chave]['homologadas']++;
            }
        }

        foreach ($grupos as &$grupo) {
            $grupo['economicidade'] = $grupo['homologadas'] > 0
                ? $grupo['valor_estimado_homologadas'] - $grupo['valor_adjudicado']
                : null;
        }
        unset($grupo);

        return $grupos;
    }
}
