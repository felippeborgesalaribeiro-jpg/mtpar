<?php

namespace Tests\Models;

use Cotacao;
use Demanda;
use Empresa;
use Licitacao;
use Lote;
use LotePropostaVencedora;
use Servidor;
use Tests\DatabaseTestCase;

require_once __DIR__ . '/../../app/models/AnalisePrecos.php';
require_once __DIR__ . '/../../app/models/Cotacao.php';
require_once __DIR__ . '/../../app/models/StatusCotacao.php';
require_once __DIR__ . '/../../app/models/Demanda.php';
require_once __DIR__ . '/../../app/models/StatusLicitacao.php';
require_once __DIR__ . '/../../app/models/Licitacao.php';
require_once __DIR__ . '/../../app/models/Lote.php';
require_once __DIR__ . '/../../app/models/Empresa.php';
require_once __DIR__ . '/../../app/models/LotePropostaVencedora.php';
require_once __DIR__ . '/../../app/models/Servidor.php';

final class LotePropostaVencedoraTest extends DatabaseTestCase
{
    private function criarLicitacaoComDoisLotes(): array
    {
        $servidor = new Servidor('Servidor de Teste');
        $servidor->salvar();

        $demanda = new Demanda('MTPAR-PRO-2026/00700', '2026-01-10');
        $demanda->salvar();

        $cotacao = new Cotacao('MTPAR-PRO-2026/00700', '', '', '', '', $servidor->id, demandaId: $demanda->id);
        $cotacao->salvar();

        $lote1 = new Lote($cotacao->id, '01');
        $lote1->salvar();
        $lote2 = new Lote($cotacao->id, '02');
        $lote2->salvar();

        $licitacao = Licitacao::criarApartirDeDemanda($demanda);

        return [$licitacao, $lote1, $lote2];
    }

    private function criarEmpresa(string $nome, string $cnpj): Empresa
    {
        $empresa = new Empresa($nome, $cnpj);
        $empresa->salvar();

        return $empresa;
    }

    public function testCadaLoteRegistraSuaProrpriaEmpresaVencedora(): void
    {
        [$licitacao, $lote1, $lote2] = $this->criarLicitacaoComDoisLotes();
        $empresaA = $this->criarEmpresa('Empresa A', '11111111000191');
        $empresaB = $this->criarEmpresa('Empresa B', '22222222000192');

        (new LotePropostaVencedora($licitacao->id, $lote1->id, $empresaA->id))->salvar();
        (new LotePropostaVencedora($licitacao->id, $lote2->id, $empresaB->id))->salvar();

        $mapa = LotePropostaVencedora::buscarMapaPorLicitacao($licitacao->id);

        $this->assertSame($empresaA->id, $mapa[$lote1->id]->empresaVencedoraId);
        $this->assertSame($empresaB->id, $mapa[$lote2->id]->empresaVencedoraId);
        $this->assertSame('Empresa A', $mapa[$lote1->id]->buscarEmpresa()->nome);
    }

    public function testSalvarDeNovoParaOMesmoLoteAtualizaEmVezDeDuplicar(): void
    {
        [$licitacao, $lote1] = $this->criarLicitacaoComDoisLotes();
        $empresaA = $this->criarEmpresa('Empresa A', '11111111000191');
        $empresaB = $this->criarEmpresa('Empresa B', '22222222000192');

        // Empresa A foi desclassificada, Empresa B assume o lote 1.
        (new LotePropostaVencedora($licitacao->id, $lote1->id, $empresaA->id))->salvar();
        (new LotePropostaVencedora($licitacao->id, $lote1->id, $empresaB->id))->salvar();

        $encontrada = LotePropostaVencedora::buscarPorLicitacaoELote($licitacao->id, $lote1->id);
        $this->assertSame($empresaB->id, $encontrada->empresaVencedoraId);

        $mapa = LotePropostaVencedora::buscarMapaPorLicitacao($licitacao->id);
        $this->assertCount(1, $mapa);
    }

    public function testBuscarPorLicitacaoELoteRetornaNullQuandoNaoDefinida(): void
    {
        [$licitacao, $lote1] = $this->criarLicitacaoComDoisLotes();

        $this->assertNull(LotePropostaVencedora::buscarPorLicitacaoELote($licitacao->id, $lote1->id));
    }
}
