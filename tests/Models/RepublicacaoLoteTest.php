<?php

namespace Tests\Models;

use Cotacao;
use Demanda;
use Licitacao;
use Lote;
use RepublicacaoLote;
use Servidor;
use SituacaoLote;
use Tests\DatabaseTestCase;

require_once __DIR__ . '/../../app/models/AnalisePrecos.php';
require_once __DIR__ . '/../../app/models/Cotacao.php';
require_once __DIR__ . '/../../app/models/StatusCotacao.php';
require_once __DIR__ . '/../../app/models/Demanda.php';
require_once __DIR__ . '/../../app/models/StatusLicitacao.php';
require_once __DIR__ . '/../../app/models/Licitacao.php';
require_once __DIR__ . '/../../app/models/Lote.php';
require_once __DIR__ . '/../../app/models/SituacaoLote.php';
require_once __DIR__ . '/../../app/models/RepublicacaoLote.php';
require_once __DIR__ . '/../../app/models/Servidor.php';

final class RepublicacaoLoteTest extends DatabaseTestCase
{
    private function criarLicitacaoComDoisLotes(): array
    {
        $servidor = new Servidor('Servidor de Teste');
        $servidor->salvar();

        $demanda = new Demanda('MTPAR-PRO-2026/00900', '2026-01-10');
        $demanda->salvar();

        $cotacao = new Cotacao('MTPAR-PRO-2026/00900', '', '', '', '', $servidor->id, demandaId: $demanda->id);
        $cotacao->salvar();

        $lote1 = new Lote($cotacao->id, '01');
        $lote1->salvar();
        $lote2 = new Lote($cotacao->id, '02');
        $lote2->salvar();

        $licitacao = Licitacao::criarApartirDeDemanda($demanda);

        return [$licitacao, $cotacao, $lote1, $lote2];
    }

    public function testRepublicarUmLoteCriaEncadeamentoAteOLoteNovo(): void
    {
        [$licitacao, $cotacaoOriginal, , $loteFracassado] = $this->criarLicitacaoComDoisLotes();

        (new SituacaoLote($licitacao->id, $loteFracassado->id, SituacaoLote::FRACASSADO, 'Nenhuma proposta habilitada'))->salvar();

        $cotacaoNova = new Cotacao($licitacao->numeroProcesso . '-R2', '', '', '', '', $cotacaoOriginal->servidorId);
        $cotacaoNova->salvar();
        $loteNovo = new Lote($cotacaoNova->id, $loteFracassado->numero);
        $loteNovo->salvar();

        $republicacao = new RepublicacaoLote(
            $licitacao->id,
            $loteFracassado->id,
            $loteNovo->id,
            $cotacaoNova->id,
            2,
            SituacaoLote::FRACASSADO,
            'Nenhuma proposta habilitada'
        );
        $republicacao->salvar();

        $encontrada = RepublicacaoLote::buscarPorLoteAnterior($loteFracassado->id);
        $this->assertNotNull($encontrada);
        $this->assertSame($loteNovo->id, $encontrada->loteNovoId);
        $this->assertSame(2, $encontrada->numeroRodada);

        $this->assertNull(RepublicacaoLote::buscarPorLoteAnterior($loteNovo->id));

        // O lote novo "lembra" de que rodada ele e (pra numerar a proxima
        // republicacao, se esse lote tambem vier a fracassar).
        $origemDoLoteNovo = RepublicacaoLote::buscarPorLoteNovo($loteNovo->id);
        $this->assertNotNull($origemDoLoteNovo);
        $this->assertSame(2, $origemDoLoteNovo->numeroRodada);
        $this->assertNull(RepublicacaoLote::buscarPorLoteNovo($loteFracassado->id));
    }

    public function testBuscarHistoricoPorLicitacaoOrdenaPorRodada(): void
    {
        [$licitacao, , , $lote] = $this->criarLicitacaoComDoisLotes();

        $cotacaoR2 = new Cotacao($licitacao->numeroProcesso . '-R2', '', '', '', '', 1);
        $cotacaoR2->salvar();
        $loteR2 = new Lote($cotacaoR2->id, $lote->numero);
        $loteR2->salvar();

        $cotacaoR3 = new Cotacao($licitacao->numeroProcesso . '-R3', '', '', '', '', 1);
        $cotacaoR3->salvar();
        $loteR3 = new Lote($cotacaoR3->id, $lote->numero);
        $loteR3->salvar();

        (new RepublicacaoLote($licitacao->id, $lote->id, $loteR2->id, $cotacaoR2->id, 2, SituacaoLote::DESERTO))->salvar();
        (new RepublicacaoLote($licitacao->id, $loteR2->id, $loteR3->id, $cotacaoR3->id, 3, SituacaoLote::FRACASSADO))->salvar();

        $historico = RepublicacaoLote::buscarHistoricoPorLicitacao($licitacao->id);

        $this->assertCount(2, $historico);
        $this->assertSame(2, $historico[0]->numeroRodada);
        $this->assertSame(3, $historico[1]->numeroRodada);
    }
}
