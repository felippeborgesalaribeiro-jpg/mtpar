<?php

namespace Tests\Models;

use Cotacao;
use Item;
use Lote;
use Preco;
use Servidor;
use StatusCotacao;
use Tests\DatabaseTestCase;

require_once __DIR__ . '/../../app/models/AnalisePrecos.php';
require_once __DIR__ . '/../../app/models/Cotacao.php';
require_once __DIR__ . '/../../app/models/StatusCotacao.php';
require_once __DIR__ . '/../../app/models/Lote.php';
require_once __DIR__ . '/../../app/models/Item.php';
require_once __DIR__ . '/../../app/models/Preco.php';
require_once __DIR__ . '/../../app/models/Parametro.php';
require_once __DIR__ . '/../../app/models/Servidor.php';

final class CotacaoTest extends DatabaseTestCase
{
    private function criarServidor(): Servidor
    {
        $servidor = new Servidor('Servidor de Teste');
        $servidor->salvar();

        return $servidor;
    }

    public function testSalvarECarregarUmaCotacao(): void
    {
        $servidor = $this->criarServidor();

        $cotacao = new Cotacao(
            'MTPAR-PRO-2026/00001',
            'Setor de Compras',
            'Dispensa',
            'Menor preço',
            'Aquisição de material de expediente',
            $servidor->id
        );
        $cotacao->salvar();

        $encontrada = Cotacao::buscarPorId($cotacao->id);

        $this->assertNotNull($encontrada);
        $this->assertSame('MTPAR-PRO-2026/00001', $encontrada->numeroProcesso);
        $this->assertSame(StatusCotacao::EmAndamento, $encontrada->status);
    }

    public function testExcluirEhSoftDeleteNaoRemoveORegistro(): void
    {
        $servidor = $this->criarServidor();
        $cotacao = new Cotacao('MTPAR-PRO-2026/00002', '', '', '', '', $servidor->id);
        $cotacao->salvar();

        $cotacao->excluir();

        // Some depois da lista/consulta ativa...
        $this->assertNull(Cotacao::buscarPorId($cotacao->id));
        $this->assertEmpty(Cotacao::buscarTodas());

        // ...mas continua recuperavel na lixeira, nao foi de fato apagada.
        $naLixeira = Cotacao::buscarExcluidaPorId($cotacao->id);
        $this->assertNotNull($naLixeira);
        $this->assertSame(1, Cotacao::contarExcluidas());
    }

    public function testRestaurarDevolveACotacaoParaAListaAtiva(): void
    {
        $servidor = $this->criarServidor();
        $cotacao = new Cotacao('MTPAR-PRO-2026/00003', '', '', '', '', $servidor->id);
        $cotacao->salvar();
        $cotacao->excluir();

        $cotacao->restaurar();

        $this->assertNotNull(Cotacao::buscarPorId($cotacao->id));
        $this->assertSame(0, Cotacao::contarExcluidas());
    }

    public function testExcluirDefinitivamenteRemoveDeVezEArrastaLotesPorCascata(): void
    {
        $servidor = $this->criarServidor();
        $cotacao = new Cotacao('MTPAR-PRO-2026/00004', '', '', '', '', $servidor->id);
        $cotacao->salvar();

        $lote = new Lote($cotacao->id, '1');
        $lote->salvar();

        $cotacao->excluirDefinitivamente();

        $this->assertNull(Cotacao::buscarExcluidaPorId($cotacao->id));
        $this->assertNull(Lote::buscarPorId($lote->id));
    }

    public function testFinalizarMudaOStatusEPersiste(): void
    {
        $servidor = $this->criarServidor();
        $cotacao = new Cotacao('MTPAR-PRO-2026/00005', '', '', '', '', $servidor->id);
        $cotacao->salvar();

        $cotacao->status = StatusCotacao::Finalizada;
        $cotacao->salvar();

        $recarregada = Cotacao::buscarPorId($cotacao->id);
        $this->assertSame(StatusCotacao::Finalizada, $recarregada->status);
    }

    public function testFromArrayIgnoraValorInvalidoDeStatus(): void
    {
        $servidor = $this->criarServidor();
        $cotacao = new Cotacao('MTPAR-PRO-2026/00006', '', '', '', '', $servidor->id);
        $cotacao->salvar();

        \Database::getConnection()
            ->prepare('UPDATE cotacoes SET status = :valor WHERE id = :id')
            ->execute(['valor' => 'STATUS_ANTIGO_REMOVIDO', 'id' => $cotacao->id]);

        $recarregada = Cotacao::buscarPorId($cotacao->id);

        $this->assertSame(StatusCotacao::EmAndamento, $recarregada->status);
    }

    public function testCalcularValorTotalSomaOValorDeReferenciaDeCadaItemPelaQuantidadeEmTodosOsLotes(): void
    {
        $servidor = $this->criarServidor();
        $cotacao = new Cotacao('MTPAR-PRO-2026/00007', '', '', '', '', $servidor->id);
        $cotacao->salvar();

        // Lote 1, item 1: tres precos proximos (10, 11, 12) - nenhum e outlier,
        // valor de referencia pela mediana = 11; quantidade 2 => 22.
        $lote1 = new Lote($cotacao->id, '01');
        $lote1->salvar();
        $item1 = new Item($lote1->id, 1, 'Item de teste 1', 'UN', 2);
        $item1->salvar();
        foreach ([10, 12, 11] as $valor) {
            (new Preco($item1->id, $valor))->salvar();
        }

        // Lote 1, item 2: um unico preco (50), sem comparacao possivel => aprovado
        // direto; quantidade 1 => 50.
        $item2 = new Item($lote1->id, 2, 'Item de teste 2', 'UN', 1);
        $item2->salvar();
        (new Preco($item2->id, 50))->salvar();

        // Lote 2, item 1: um unico preco (100); quantidade 3 => 300.
        $lote2 = new Lote($cotacao->id, '02');
        $lote2->salvar();
        $item3 = new Item($lote2->id, 1, 'Item de teste 3', 'UN', 3);
        $item3->salvar();
        (new Preco($item3->id, 100))->salvar();

        // Total esperado: 22 + 50 + 300 = 372.
        $this->assertEqualsWithDelta(372.0, $cotacao->calcularValorTotal(), 0.001);
    }
}
