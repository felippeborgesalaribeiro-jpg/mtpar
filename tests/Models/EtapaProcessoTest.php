<?php

namespace Tests\Models;

use EtapaProcesso;
use Tests\DatabaseTestCase;

require_once __DIR__ . '/../../app/models/EtapaProcesso.php';

final class EtapaProcessoTest extends DatabaseTestCase
{
    public function testSalvarSemOrdemEncaixaNoFinalAutomaticamente(): void
    {
        $primeira = new EtapaProcesso('ELABORAÇÃO DE TR');
        $primeira->salvar();
        $segunda = new EtapaProcesso('AVISO DE LICITAÇÃO');
        $segunda->salvar();

        $this->assertSame(1, $primeira->ordem);
        $this->assertSame(2, $segunda->ordem);

        $nomes = array_map(fn(EtapaProcesso $e) => $e->nome, EtapaProcesso::buscarTodas());
        $this->assertSame(['ELABORAÇÃO DE TR', 'AVISO DE LICITAÇÃO'], $nomes);
    }

    public function testMoverParaCimaETrocaDeOrdemComAAnterior(): void
    {
        $a = new EtapaProcesso('A');
        $a->salvar();
        $b = new EtapaProcesso('B');
        $b->salvar();
        $c = new EtapaProcesso('C');
        $c->salvar();

        $c->moverParaCima();

        $nomes = array_map(fn(EtapaProcesso $e) => $e->nome, EtapaProcesso::buscarTodas());
        $this->assertSame(['A', 'C', 'B'], $nomes);
    }

    public function testMoverParaCimaNaPrimeiraNaoFazNada(): void
    {
        $a = new EtapaProcesso('A');
        $a->salvar();
        $b = new EtapaProcesso('B');
        $b->salvar();

        $a->moverParaCima();

        $nomes = array_map(fn(EtapaProcesso $e) => $e->nome, EtapaProcesso::buscarTodas());
        $this->assertSame(['A', 'B'], $nomes);
    }

    public function testMoverParaBaixoTrocaDeOrdemComAProxima(): void
    {
        $a = new EtapaProcesso('A');
        $a->salvar();
        $b = new EtapaProcesso('B');
        $b->salvar();

        $a->moverParaBaixo();

        $nomes = array_map(fn(EtapaProcesso $e) => $e->nome, EtapaProcesso::buscarTodas());
        $this->assertSame(['B', 'A'], $nomes);
    }

    public function testExcluirRemoveDaLista(): void
    {
        $a = new EtapaProcesso('A');
        $a->salvar();

        $a->excluir();

        $this->assertNull(EtapaProcesso::buscarPorId($a->id));
        $this->assertCount(0, EtapaProcesso::buscarTodas());
    }
}
