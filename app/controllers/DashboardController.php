<?php

require_once __DIR__ . '/../models/Cotacao.php';
require_once __DIR__ . '/../models/Servidor.php';
require_once __DIR__ . '/../models/Demanda.php';
require_once __DIR__ . '/../models/Licitacao.php';
require_once __DIR__ . '/../helpers/auth.php';

class DashboardController
{
    public function mostrar(): void
    {
        $servidorLogado = exigirLogin();

        $servidores = Servidor::buscarTodos();

        $minhasPendencias = $this->montarPendenciasCombinadas($servidorLogado->id);
        $totalMinhasPendencias = count($minhasPendencias);
        $minhasPendenciasExibidas = array_slice($minhasPendencias, 0, 5);

        $cotacoes = Cotacao::buscarTodas();
        $cotacoesEmAndamento = count(array_filter($cotacoes, fn($c) => $c->status === Cotacao::STATUS_EM_ANDAMENTO));
        $processosEmAndamento = Demanda::contarEmAndamento();
        $licitacoesPublicadas = Licitacao::contarPublicadas();
        $licitacoesHomologadas = Licitacao::contarHomologadas();
        $valorHomologadas = Licitacao::somarValorAdjudicadoHomologadas();

        require __DIR__ . '/../views/dashboard.php';
    }

    private function montarPendenciasCombinadas(int $servidorId): array
    {
        $pendencias = [];

        $demandas = Demanda::buscarPendentesPorServidor($servidorId, 100);
        foreach ($demandas as $demanda) {
            $pendencias[] = [
                'tipo' => 'demanda',
                'status' => $demanda->status,
                'numero_processo' => $demanda->numeroProcesso,
                'data' => $demanda->dataRecebimento,
                'link' => 'index.php?action=ver_demanda&id=' . $demanda->id,
            ];
        }

        $cotacoesEmAndamento = Cotacao::buscarEmAndamentoPorServidor($servidorId);
        foreach ($cotacoesEmAndamento as $cotacao) {
            $pendencias[] = [
                'tipo' => 'cotacao',
                'status' => 'Cotação em andamento',
                'numero_processo' => $cotacao->numeroProcesso,
                'data' => $cotacao->criadoEm !== '' ? $cotacao->criadoEm : date('Y-m-d'),
                'link' => 'index.php?action=cotacao&id=' . $cotacao->id,
            ];
        }

        usort($pendencias, function ($a, $b) {
            return strcmp($b['data'], $a['data']);
        });

        return $pendencias;
    }
}