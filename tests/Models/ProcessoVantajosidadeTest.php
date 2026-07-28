<?php

namespace Tests\Models;

use ItemVantajosidade;
use ProcessoVantajosidade;
use Servidor;
use Tests\DatabaseTestCase;

require_once __DIR__ . '/../../app/models/ProcessoVantajosidade.php';
require_once __DIR__ . '/../../app/models/ItemVantajosidade.php';
require_once __DIR__ . '/../../app/models/Servidor.php';

final class ProcessoVantajosidadeTest extends DatabaseTestCase
{
    private function criarServidor(): Servidor
    {
        $servidor = new Servidor('Servidor de Teste');
        $servidor->salvar();

        return $servidor;
    }

    public function testProcessoTipoAtaNaoCalculaIndiceDeAditivo(): void
    {
        $servidor = $this->criarServidor();

        $processo = new ProcessoVantajosidade('009/2024', 'SEPLAG', 'Objeto', $servidor->id);
        $processo->salvar();

        $this->assertFalse($processo->ehContratoAditivo());
        $this->assertNull($processo->calcularIndiceAditivo());
        $this->assertNull($processo->indiceAditivoDentroDoLimiteLegal());
    }

    public function testCalculaIndiceDoAditivoAPartirDosItens(): void
    {
        $servidor = $this->criarServidor();

        $processo = new ProcessoVantajosidade(
            '',
            '',
            'Aditivo de contrato de limpeza',
            $servidor->id,
            demandaId: null,
            tipo: ProcessoVantajosidade::TIPO_CONTRATO_ADITIVO,
            numeroContrato: '012/2026',
            valorTotalObjeto: 100000.0
        );
        $processo->salvar();

        (new ItemVantajosidade($processo->id, '01', '1', 500.0, 'Item 1', 'UN', 20))->salvar(); // 10.000
        (new ItemVantajosidade($processo->id, '01', '2', 1000.0, 'Item 2', 'UN', 10))->salvar(); // 10.000

        $this->assertTrue($processo->ehContratoAditivo());
        $this->assertSame(20000.0, $processo->calcularValorTotalItens());
        $this->assertSame(20.0, $processo->calcularIndiceAditivo());
        $this->assertTrue($processo->indiceAditivoDentroDoLimiteLegal());
    }

    public function testIndiceAcimaDoLimiteLegalNaoFicaDentroDoLimite(): void
    {
        $servidor = $this->criarServidor();

        $processo = new ProcessoVantajosidade(
            '',
            '',
            'Aditivo acima do limite',
            $servidor->id,
            demandaId: null,
            tipo: ProcessoVantajosidade::TIPO_CONTRATO_ADITIVO,
            numeroContrato: '013/2026',
            valorTotalObjeto: 10000.0
        );
        $processo->salvar();

        (new ItemVantajosidade($processo->id, '01', '1', 3000.0, 'Item 1', 'UN', 1))->salvar(); // 30% do total

        $this->assertSame(30.0, $processo->calcularIndiceAditivo());
        $this->assertFalse($processo->indiceAditivoDentroDoLimiteLegal());
    }

    public function testSalvarEBuscarPorIdPreservaCamposDeContrato(): void
    {
        $servidor = $this->criarServidor();

        $processo = new ProcessoVantajosidade(
            '',
            '',
            'Objeto',
            $servidor->id,
            demandaId: null,
            tipo: ProcessoVantajosidade::TIPO_CONTRATO_ADITIVO,
            numeroContrato: '020/2026',
            valorTotalObjeto: 5000.0
        );
        $processo->salvar();

        $encontrado = ProcessoVantajosidade::buscarPorId($processo->id);

        $this->assertNotNull($encontrado);
        $this->assertTrue($encontrado->ehContratoAditivo());
        $this->assertSame('020/2026', $encontrado->numeroContrato);
        $this->assertSame(5000.0, $encontrado->valorTotalObjeto);
    }
}
