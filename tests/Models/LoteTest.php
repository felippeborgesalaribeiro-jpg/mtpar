<?php

namespace Tests\Models;

use Cotacao;
use Item;
use Lote;
use Servidor;
use Tests\DatabaseTestCase;

require_once __DIR__ . '/../../app/models/AnalisePrecos.php';
require_once __DIR__ . '/../../app/models/Cotacao.php';
require_once __DIR__ . '/../../app/models/StatusCotacao.php';
require_once __DIR__ . '/../../app/models/Lote.php';
require_once __DIR__ . '/../../app/models/Item.php';
require_once __DIR__ . '/../../app/models/Servidor.php';

final class LoteTest extends DatabaseTestCase
{
    private function criarCotacaoComLote(): array
    {
        $servidor = new Servidor('Servidor de Teste');
        $servidor->salvar();

        $cotacao = new Cotacao('PROC-001', '', '', '', '', $servidor->id);
        $cotacao->salvar();

        $lote = new Lote($cotacao->id, '01');
        $lote->salvar();

        return [$cotacao, $lote];
    }

    public function testRenumerarItensFechaBuracosMantendoOrdem(): void
    {
        [, $lote] = $this->criarCotacaoComLote();

        $itemA = new Item($lote->id, 7, 'Item que veio de outro lote');
        $itemA->salvar();
        $itemB = new Item($lote->id, 21, 'Outro item que veio de outro lote');
        $itemB->salvar();

        $lote->renumerarItens();

        $itens = $lote->buscarItens();
        $this->assertSame(1, $itens[0]->numero);
        $this->assertSame('Item que veio de outro lote', $itens[0]->descricao);
        $this->assertSame(2, $itens[1]->numero);
        $this->assertSame('Outro item que veio de outro lote', $itens[1]->descricao);
    }

    public function testRenumerarItensLoteVazioNaoQuebra(): void
    {
        [, $lote] = $this->criarCotacaoComLote();

        $lote->renumerarItens();

        $this->assertSame([], $lote->buscarItens());
    }

    public function testMoverItemRenumeraOrigemEDestinoAutomaticamente(): void
    {
        [$cotacao, $loteOrigem] = $this->criarCotacaoComLote();

        $loteDestino = new Lote($cotacao->id, '02');
        $loteDestino->salvar();

        // Simula um lote de destino que ja tinha ficado com numeracao torta
        // de uma reorganizacao anterior (item com numero 21, por exemplo).
        $itemJaNoDestino = new Item($loteDestino->id, 21, 'Item que ja estava no destino');
        $itemJaNoDestino->salvar();

        $itemMovido = new Item($loteOrigem->id, 1, 'Item a ser movido');
        $itemMovido->salvar();
        $itemQueFica = new Item($loteOrigem->id, 2, 'Item que fica na origem');
        $itemQueFica->salvar();

        $itemMovido->moverParaLote($loteDestino->id, $loteDestino->proximoNumeroItem());
        $loteOrigem->renumerarItens();
        $loteDestino->renumerarItens();

        $itensOrigem = $loteOrigem->buscarItens();
        $this->assertCount(1, $itensOrigem);
        $this->assertSame(1, $itensOrigem[0]->numero);
        $this->assertSame('Item que fica na origem', $itensOrigem[0]->descricao);

        $itensDestino = $loteDestino->buscarItens();
        $this->assertCount(2, $itensDestino);
        $this->assertSame(1, $itensDestino[0]->numero);
        $this->assertSame('Item que ja estava no destino', $itensDestino[0]->descricao);
        $this->assertSame(2, $itensDestino[1]->numero);
        $this->assertSame('Item a ser movido', $itensDestino[1]->descricao);
    }
}
