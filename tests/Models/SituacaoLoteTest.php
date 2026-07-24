<?php

namespace Tests\Models;

use Cotacao;
use Demanda;
use Licitacao;
use Lote;
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
require_once __DIR__ . '/../../app/models/Servidor.php';

final class SituacaoLoteTest extends DatabaseTestCase
{
    private function criarLicitacaoComLote(): array
    {
        $servidor = new Servidor('Servidor de Teste');
        $servidor->salvar();

        $demanda = new Demanda('MTPAR-PRO-2026/00800', '2026-01-10');
        $demanda->salvar();

        $cotacao = new Cotacao('MTPAR-PRO-2026/00800', '', '', '', '', $servidor->id, demandaId: $demanda->id);
        $cotacao->salvar();

        $lote = new Lote($cotacao->id, '01');
        $lote->salvar();

        $licitacao = Licitacao::criarApartirDeDemanda($demanda);

        return [$licitacao, $lote];
    }

    public function testMarcarLoteComoFracassadoEBuscarDeVolta(): void
    {
        [$licitacao, $lote] = $this->criarLicitacaoComLote();

        (new SituacaoLote($licitacao->id, $lote->id, SituacaoLote::FRACASSADO, 'Nenhuma proposta habilitada', '2026-07-22'))->salvar();

        $encontrada = SituacaoLote::buscarPorLicitacaoELote($licitacao->id, $lote->id);
        $this->assertNotNull($encontrada);
        $this->assertSame(SituacaoLote::FRACASSADO, $encontrada->situacao);
        $this->assertSame('Nenhuma proposta habilitada', $encontrada->motivo);
        $this->assertSame('2026-07-22', $encontrada->dataSituacao);
    }

    public function testSalvarDeNovoAtualizaEmVezDeDuplicar(): void
    {
        [$licitacao, $lote] = $this->criarLicitacaoComLote();

        (new SituacaoLote($licitacao->id, $lote->id, SituacaoLote::FRACASSADO))->salvar();
        (new SituacaoLote($licitacao->id, $lote->id, SituacaoLote::DESERTO, 'Ninguém apareceu'))->salvar();

        $encontrada = SituacaoLote::buscarPorLicitacaoELote($licitacao->id, $lote->id);
        $this->assertSame(SituacaoLote::DESERTO, $encontrada->situacao);

        $mapa = SituacaoLote::buscarMapaPorLicitacao($licitacao->id);
        $this->assertCount(1, $mapa);
    }

    public function testBuscarPorLicitacaoELoteRetornaNullQuandoNuncaFoiMarcado(): void
    {
        [$licitacao, $lote] = $this->criarLicitacaoComLote();

        $this->assertNull(SituacaoLote::buscarPorLicitacaoELote($licitacao->id, $lote->id));
    }
}
