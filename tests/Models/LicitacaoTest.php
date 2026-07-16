<?php

namespace Tests\Models;

use Cotacao;
use Demanda;
use Empresa;
use Item;
use Licitacao;
use Lote;
use Preco;
use Servidor;
use StatusLicitacao;
use Tests\DatabaseTestCase;

require_once __DIR__ . '/../../app/models/AnalisePrecos.php';
require_once __DIR__ . '/../../app/models/Cotacao.php';
require_once __DIR__ . '/../../app/models/StatusCotacao.php';
require_once __DIR__ . '/../../app/models/Demanda.php';
require_once __DIR__ . '/../../app/models/Licitacao.php';
require_once __DIR__ . '/../../app/models/StatusLicitacao.php';
require_once __DIR__ . '/../../app/models/Empresa.php';
require_once __DIR__ . '/../../app/models/Lote.php';
require_once __DIR__ . '/../../app/models/Item.php';
require_once __DIR__ . '/../../app/models/Preco.php';
require_once __DIR__ . '/../../app/models/Parametro.php';
require_once __DIR__ . '/../../app/models/Servidor.php';

final class LicitacaoTest extends DatabaseTestCase
{
    private function criarServidor(): Servidor
    {
        $servidor = new Servidor('Servidor de Teste');
        $servidor->salvar();

        return $servidor;
    }

    private function criarDemanda(): Demanda
    {
        $demanda = new Demanda('MTPAR-PRO-2026/00100', '2026-01-10', '', 'Setor de TI', 'Objeto de teste');
        $demanda->salvar();

        return $demanda;
    }

    public function testCriarApartirDeDemandaPreencheValorEstimadoComOTotalDaCotacaoVinculada(): void
    {
        $servidor = $this->criarServidor();
        $demanda = $this->criarDemanda();

        $cotacao = new Cotacao(
            $demanda->numeroProcesso,
            $demanda->setorDemandante,
            'Dispensa',
            'Menor preço',
            $demanda->objeto,
            $servidor->id,
            demandaId: $demanda->id
        );
        $cotacao->salvar();

        $lote = new Lote($cotacao->id, '01');
        $lote->salvar();
        $item = new Item($lote->id, 1, 'Item de teste', 'UN', 2);
        $item->salvar();
        (new Preco($item->id, 100))->salvar();

        $licitacao = Licitacao::criarApartirDeDemanda($demanda);

        // valor de referencia (unico preco) = 100, quantidade 2 => 200.
        $this->assertEqualsWithDelta(200.0, $licitacao->valorEstimado, 0.001);

        $recarregada = Licitacao::buscarPorId($licitacao->id);
        $this->assertEqualsWithDelta(200.0, $recarregada->valorEstimado, 0.001);
    }

    public function testCriarApartirDeDemandaSemCotacaoVinculadaDeixaValorEstimadoNulo(): void
    {
        $demanda = $this->criarDemanda();

        $licitacao = Licitacao::criarApartirDeDemanda($demanda);

        $this->assertNull($licitacao->valorEstimado);
    }

    public function testStatusEhInferidoAPartirDoAvancoDoProcesso(): void
    {
        $demanda = $this->criarDemanda();
        $licitacao = Licitacao::criarApartirDeDemanda($demanda);

        $this->assertSame(StatusLicitacao::AguardandoPublicacao, $licitacao->status());

        $licitacao->editalLicitacao = 'Edital 001/2026';
        $this->assertSame(StatusLicitacao::Publicada, $licitacao->status());

        $licitacao->valorAdjudicado = 1000.0;
        $this->assertSame(StatusLicitacao::Homologada, $licitacao->status());

        $licitacao->encaminhadoPactuacaoContrato = '2026-02-01';
        $this->assertSame(StatusLicitacao::EncaminhadaParaContratacao, $licitacao->status());
    }

    public function testSalvarUmaLicitacaoJaExistenteAtualizaSemErro(): void
    {
        // Regressao: paramsParaSalvar() inclui demanda_id, que precisa estar
        // presente tambem no UPDATE - senao o driver sqlite rejeita o
        // parametro nomeado extra com "column index out of range".
        $demanda = $this->criarDemanda();
        $licitacao = Licitacao::criarApartirDeDemanda($demanda);

        $licitacao->editalLicitacao = 'Edital 002/2026';
        $licitacao->valorAdjudicado = 500.0;
        $licitacao->salvar();

        $recarregada = Licitacao::buscarPorId($licitacao->id);
        $this->assertSame('Edital 002/2026', $recarregada->editalLicitacao);
        $this->assertEqualsWithDelta(500.0, $recarregada->valorAdjudicado, 0.001);
        $this->assertSame($demanda->id, $recarregada->demandaId);
    }

    public function testCriadoEmEhPreenchidoAoRecarregarDoBanco(): void
    {
        $demanda = $this->criarDemanda();
        $licitacao = Licitacao::criarApartirDeDemanda($demanda);

        $recarregada = Licitacao::buscarPorId($licitacao->id);

        $this->assertNotSame('', $recarregada->criadoEm);
    }

    public function testEmpresaVencedoraEObservacoesPersistemERecarregam(): void
    {
        $demanda = $this->criarDemanda();
        $licitacao = Licitacao::criarApartirDeDemanda($demanda);

        $empresa = new Empresa('Climatiza Engenharia e Instalações Ltda.', '12345678000190');
        $empresa->salvar();

        $licitacao->empresaVencedoraId = $empresa->id;
        $licitacao->observacoesPropostaVencedora = 'Item 2 negociado com desconto adicional.';
        $licitacao->salvar();

        $recarregada = Licitacao::buscarPorId($licitacao->id);
        $this->assertSame($empresa->id, $recarregada->empresaVencedoraId);
        $this->assertSame('Item 2 negociado com desconto adicional.', $recarregada->observacoesPropostaVencedora);

        $empresaVencedora = $recarregada->buscarEmpresaVencedora();
        $this->assertNotNull($empresaVencedora);
        $this->assertSame('Climatiza Engenharia e Instalações Ltda.', $empresaVencedora->nome);
    }

    public function testBuscarEmpresaVencedoraRetornaNullQuandoNaoDefinida(): void
    {
        $demanda = $this->criarDemanda();
        $licitacao = Licitacao::criarApartirDeDemanda($demanda);

        $this->assertNull($licitacao->buscarEmpresaVencedora());
    }
}
