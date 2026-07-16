<?php

namespace Tests\Models;

use Cotacao;
use Demanda;
use Item;
use ItemPropostaVencedora;
use Licitacao;
use Lote;
use Servidor;
use Tests\DatabaseTestCase;

require_once __DIR__ . '/../../app/models/AnalisePrecos.php';
require_once __DIR__ . '/../../app/models/Cotacao.php';
require_once __DIR__ . '/../../app/models/StatusCotacao.php';
require_once __DIR__ . '/../../app/models/Demanda.php';
require_once __DIR__ . '/../../app/models/StatusLicitacao.php';
require_once __DIR__ . '/../../app/models/Licitacao.php';
require_once __DIR__ . '/../../app/models/Lote.php';
require_once __DIR__ . '/../../app/models/Item.php';
require_once __DIR__ . '/../../app/models/ItemPropostaVencedora.php';
require_once __DIR__ . '/../../app/models/Servidor.php';

final class ItemPropostaVencedoraTest extends DatabaseTestCase
{
    private function criarLicitacaoComItem(): array
    {
        $servidor = new Servidor('Servidor de Teste');
        $servidor->salvar();

        $demanda = new Demanda('MTPAR-PRO-2026/00500', '2026-01-10');
        $demanda->salvar();

        $cotacao = new Cotacao('MTPAR-PRO-2026/00500', '', '', '', '', $servidor->id, demandaId: $demanda->id);
        $cotacao->salvar();

        $lote = new Lote($cotacao->id, '01');
        $lote->salvar();
        $item = new Item($lote->id, 1, 'Item de teste', 'UN', 1);
        $item->salvar();

        $licitacao = Licitacao::criarApartirDeDemanda($demanda);

        return [$licitacao, $item];
    }

    public function testSalvarCriaUmRegistroPorItem(): void
    {
        [$licitacao, $item] = $this->criarLicitacaoComItem();

        $proposta = new ItemPropostaVencedora($licitacao->id, $item->id, 100.0);
        $proposta->salvar();

        $encontrada = ItemPropostaVencedora::buscarPorLicitacaoEItem($licitacao->id, $item->id);
        $this->assertNotNull($encontrada);
        $this->assertEqualsWithDelta(100.0, $encontrada->valorProposto, 0.001);
    }

    public function testSalvarDeNovoParaOMesmoItemAtualizaEmVezDeDuplicar(): void
    {
        [$licitacao, $item] = $this->criarLicitacaoComItem();

        (new ItemPropostaVencedora($licitacao->id, $item->id, 100.0))->salvar();
        (new ItemPropostaVencedora($licitacao->id, $item->id, 150.0))->salvar();

        $encontrada = ItemPropostaVencedora::buscarPorLicitacaoEItem($licitacao->id, $item->id);
        $this->assertEqualsWithDelta(150.0, $encontrada->valorProposto, 0.001);

        $mapa = ItemPropostaVencedora::buscarMapaPorLicitacao($licitacao->id);
        $this->assertCount(1, $mapa);
    }

    public function testBuscarMapaPorLicitacaoIndexaPorItemId(): void
    {
        [$licitacao, $item] = $this->criarLicitacaoComItem();

        (new ItemPropostaVencedora($licitacao->id, $item->id, 200.0))->salvar();

        $mapa = ItemPropostaVencedora::buscarMapaPorLicitacao($licitacao->id);
        $this->assertArrayHasKey($item->id, $mapa);
        $this->assertEqualsWithDelta(200.0, $mapa[$item->id]->valorProposto, 0.001);
    }

    public function testBuscarPorLicitacaoEItemRetornaNullQuandoNaoExiste(): void
    {
        [$licitacao, $item] = $this->criarLicitacaoComItem();

        $this->assertNull(ItemPropostaVencedora::buscarPorLicitacaoEItem($licitacao->id, $item->id));
    }
}
