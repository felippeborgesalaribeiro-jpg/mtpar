<?php

namespace Tests\Models;

use AnalisePrecos;
use Cotacao;
use Item;
use Lote;
use Preco;
use Servidor;
use Tests\DatabaseTestCase;

require_once __DIR__ . '/../../app/models/AnalisePrecos.php';
require_once __DIR__ . '/../../app/models/Cotacao.php';
require_once __DIR__ . '/../../app/models/Lote.php';
require_once __DIR__ . '/../../app/models/Item.php';
require_once __DIR__ . '/../../app/models/Preco.php';
require_once __DIR__ . '/../../app/models/Servidor.php';

final class ItemTest extends DatabaseTestCase
{
    public function testAnalisarComPrecosJaBuscadosDaOMesmoResultadoQueBuscandoInternamente(): void
    {
        $servidor = new Servidor('Servidor de Teste');
        $servidor->salvar();

        $cotacao = new Cotacao('MTPAR-PRO-2026/00099', '', '', '', '', $servidor->id);
        $cotacao->salvar();

        $lote = new Lote($cotacao->id, '01');
        $lote->salvar();

        $item = new Item($lote->id, 1, 'Item de teste', 'UN', 1);
        $item->salvar();

        foreach ([10, 11, 12] as $valor) {
            (new Preco($item->id, $valor))->salvar();
        }

        $resultadoBuscandoInternamente = $item->analisar(AnalisePrecos::CRITERIO_MEDIANA);

        $precosJaBuscados = $item->buscarPrecos();
        $resultadoComPrecosPassados = $item->analisar(AnalisePrecos::CRITERIO_MEDIANA, null, true, $precosJaBuscados);

        $this->assertSame($resultadoBuscandoInternamente['valor_referencia'], $resultadoComPrecosPassados['valor_referencia']);
        $this->assertSame($resultadoBuscandoInternamente['resultado_final'], $resultadoComPrecosPassados['resultado_final']);
    }
}
