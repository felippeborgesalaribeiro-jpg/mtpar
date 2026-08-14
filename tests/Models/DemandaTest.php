<?php

namespace Tests\Models;

use Demanda;
use EtapaProcesso;
use Tests\DatabaseTestCase;

require_once __DIR__ . '/../../app/models/Demanda.php';
require_once __DIR__ . '/../../app/models/EtapaProcesso.php';

final class DemandaTest extends DatabaseTestCase
{
    public function testOpcoesStatusColocaEmAndamentoNoInicioEConcluidoECanceladoNoFim(): void
    {
        (new EtapaProcesso('ELABORAÇÃO DE TR'))->salvar();
        (new EtapaProcesso('PUBLICADO'))->salvar();

        $this->assertSame(
            ['EM ANDAMENTO', 'ELABORAÇÃO DE TR', 'PUBLICADO', 'CONCLUÍDO', 'CANCELADO'],
            Demanda::opcoesStatus()
        );
    }

    public function testCalcularDiasEmAbertoContaDiasCorridosDesdeORecebimento(): void
    {
        $dataRecebimento = (new \DateTime('today'))->modify('-15 days')->format('Y-m-d');
        $demanda = new Demanda('MTPAR-PRO-2026/00900', $dataRecebimento);
        $demanda->salvar();

        $this->assertSame(15, $demanda->calcularDiasEmAberto());
    }

    public function testCalcularDiasEmAbertoEhZeroNoDiaDoRecebimento(): void
    {
        $demanda = new Demanda('MTPAR-PRO-2026/00901', (new \DateTime('today'))->format('Y-m-d'));
        $demanda->salvar();

        $this->assertSame(0, $demanda->calcularDiasEmAberto());
    }
}
