<?php

namespace Tests\Models;

use Cotacao;
use Lote;
use Servidor;
use Tests\DatabaseTestCase;

require_once __DIR__ . '/../../app/models/AnalisePrecos.php';
require_once __DIR__ . '/../../app/models/Cotacao.php';
require_once __DIR__ . '/../../app/models/Lote.php';
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
        $this->assertSame(Cotacao::STATUS_EM_ANDAMENTO, $encontrada->status);
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
}
