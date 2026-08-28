<?php

namespace Tests\Models;

use AnalisePrecos;
use Cotacao;
use Demanda;
use Item;
use Lote;
use Preco;
use Servidor;
use StatusCotacao;
use Tests\DatabaseTestCase;

require_once __DIR__ . '/../../app/models/AnalisePrecos.php';
require_once __DIR__ . '/../../app/models/Cotacao.php';
require_once __DIR__ . '/../../app/models/StatusCotacao.php';
require_once __DIR__ . '/../../app/models/Demanda.php';
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

    public function testDeveArredondarValorReferenciaComparaComADataDeCorte(): void
    {
        $servidor = $this->criarServidor();

        $cotacaoNova = new Cotacao('MTPAR-PRO-2026/00008', '', '', '', '', $servidor->id);
        $cotacaoNova->salvar();
        // criado_em so vem preenchido do banco (datetime('now')) - o objeto
        // recem-salvo em memoria ainda tem a string vazia do construtor.
        $cotacaoNova = Cotacao::buscarPorId($cotacaoNova->id);
        $this->assertTrue($cotacaoNova->deveArredondarValorReferencia());

        // Simula uma cotacao criada antes da correcao - criado_em nao e
        // setavel pelo construtor (sempre vem do datetime('now') do SQLite),
        // entao "envelhecemos" ela direto no banco, como o teste de status
        // antigo acima ja faz.
        \Database::getConnection()
            ->prepare('UPDATE cotacoes SET criado_em = :valor WHERE id = :id')
            ->execute(['valor' => '2020-01-01 00:00:00', 'id' => $cotacaoNova->id]);

        $cotacaoAntiga = Cotacao::buscarPorId($cotacaoNova->id);
        $this->assertFalse($cotacaoAntiga->deveArredondarValorReferencia());
    }

    public function testCalcularValorTotalNaoArredondaParaCotacaoDeAntesDaCorrecao(): void
    {
        $servidor = $this->criarServidor();
        $cotacao = new Cotacao('MTPAR-PRO-2026/00010', '', '', '', '', $servidor->id, AnalisePrecos::CRITERIO_MEDIA);
        $cotacao->salvar();

        \Database::getConnection()
            ->prepare('UPDATE cotacoes SET criado_em = :valor WHERE id = :id')
            ->execute(['valor' => '2020-01-01 00:00:00', 'id' => $cotacao->id]);
        $cotacao = Cotacao::buscarPorId($cotacao->id);

        $lote = new Lote($cotacao->id, '01');
        $lote->salvar();
        $item = new Item($lote->id, 1, 'Item de teste', 'UN', 100);
        $item->salvar();
        foreach ([13.40, 12.50, 14.90, 14.66] as $valor) {
            (new Preco($item->id, $valor))->salvar();
        }

        // Sem a correcao, o total bate com o bruto (13.865 x 100 = 1386.50),
        // nao com o arredondado (13.86 x 100 = 1386.00) - preserva o numero
        // que ja saiu num Mapa/Relatorio antigo.
        $this->assertEqualsWithDelta(1386.50, $cotacao->calcularValorTotal(), 0.001);
    }

    public function testCotacaoDeRepublicacaoDeLoteNaoAparecemEmBuscarTodas(): void
    {
        $servidor = $this->criarServidor();

        $cotacaoNormal = new Cotacao('MTPAR-PRO-2026/00009', '', '', '', '', $servidor->id);
        $cotacaoNormal->salvar();

        $cotacaoRepublicacao = new Cotacao('MTPAR-PRO-2026/00009-R2', '', '', '', '', $servidor->id, ehRepublicacaoLote: true);
        $cotacaoRepublicacao->salvar();

        $todas = Cotacao::buscarTodas();
        $ids = array_map(fn($c) => $c->id, $todas);

        $this->assertContains($cotacaoNormal->id, $ids);
        $this->assertNotContains($cotacaoRepublicacao->id, $ids);

        // Mas continua acessivel por id normalmente (so nao aparece na lista).
        $recarregada = Cotacao::buscarPorId($cotacaoRepublicacao->id);
        $this->assertNotNull($recarregada);
        $this->assertTrue($recarregada->ehRepublicacaoLote);
    }

    public function testCotacaoCriadaSemDemandaPodeSerVinculadaDepois(): void
    {
        $servidor = $this->criarServidor();
        $cotacao = new Cotacao('MTPAR-PRO-2026/00008', '', '', '', '', $servidor->id);
        $cotacao->salvar();

        $this->assertNull($cotacao->demandaId);
        $this->assertNull(Cotacao::buscarPorId($cotacao->id)->demandaId);

        $demanda = new Demanda('MTPAR-PRO-2026/00008', '2026-01-10');
        $demanda->salvar();

        // Regressao: uma cotacao criada sem vinculo precisa poder ser
        // vinculada a uma demanda depois, editando o campo normalmente.
        $cotacao->demandaId = $demanda->id;
        $cotacao->salvar();

        $recarregada = Cotacao::buscarPorId($cotacao->id);
        $this->assertSame($demanda->id, $recarregada->demandaId);
        $this->assertSame($demanda->id, $recarregada->buscarDemandaVinculada()->id);
    }

    public function testItensComPrecosInsuficientesListaItemComMenosDeTresAprovados(): void
    {
        $servidor = $this->criarServidor();
        $cotacao = new Cotacao('MTPAR-PRO-2026/00011', '', '', '', '', $servidor->id);
        $cotacao->salvar();

        $lote = new Lote($cotacao->id, '01');
        $lote->salvar();

        // Item 1: so 2 precos aprovados (proximos, nenhum excessivo/inexequivel) - abaixo do minimo de 3.
        $item1 = new Item($lote->id, 1, 'Item com poucos preços', 'UN', 1);
        $item1->salvar();
        foreach ([10, 11] as $valor) {
            (new Preco($item1->id, $valor))->salvar();
        }

        // Item 2: 3 precos aprovados - atinge o minimo.
        $item2 = new Item($lote->id, 2, 'Item com preços suficientes', 'UN', 1);
        $item2->salvar();
        foreach ([10, 11, 12] as $valor) {
            (new Preco($item2->id, $valor))->salvar();
        }

        $pendentes = $cotacao->itensComPrecosInsuficientes();

        $this->assertCount(1, $pendentes);
        $this->assertSame($item1->id, $pendentes[0]['item']->id);
        $this->assertSame(2, $pendentes[0]['aprovados']);
    }

    public function testItensComPrecosInsuficientesConsideraItemSemNenhumPrecoComoPendente(): void
    {
        $servidor = $this->criarServidor();
        $cotacao = new Cotacao('MTPAR-PRO-2026/00012', '', '', '', '', $servidor->id);
        $cotacao->salvar();

        $lote = new Lote($cotacao->id, '01');
        $lote->salvar();

        $item = new Item($lote->id, 1, 'Item sem preço', 'UN', 1);
        $item->salvar();

        $pendentes = $cotacao->itensComPrecosInsuficientes();

        $this->assertCount(1, $pendentes);
        $this->assertSame(0, $pendentes[0]['aprovados']);
    }

    public function testItensComPrecosInsuficientesNaoSeAplicaAPlanilhaOrcamentaria(): void
    {
        $servidor = $this->criarServidor();
        $cotacao = new Cotacao(
            'MTPAR-PRO-2026/00013', '', '', '', '', $servidor->id,
            AnalisePrecos::CRITERIO_PLANILHA_ORCAMENTARIA
        );
        $cotacao->salvar();

        $lote = new Lote($cotacao->id, '01');
        $lote->salvar();

        // Planilha Orcamentaria: um unico valor, sem comparacao de precos.
        $item = new Item($lote->id, 1, 'Item de planilha orçamentária', 'UN', 1);
        $item->salvar();
        (new Preco($item->id, 1000))->salvar();

        $this->assertSame([], $cotacao->itensComPrecosInsuficientes());
    }
}
