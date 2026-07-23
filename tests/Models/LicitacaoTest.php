<?php

namespace Tests\Models;

use Cotacao;
use Demanda;
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

        // Ter so o valor_adjudicado digitado nao basta - "Homologada" exige
        // o ato formal de finalizar o processo (data_adjudicacao_homologacao).
        $licitacao->valorAdjudicado = 1000.0;
        $this->assertSame(StatusLicitacao::Publicada, $licitacao->status());

        $licitacao->dataAdjudicacaoHomologacao = '2026-01-20';
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

    public function testObservacoesPropostaVencedoraPersisteERecarrega(): void
    {
        $demanda = $this->criarDemanda();
        $licitacao = Licitacao::criarApartirDeDemanda($demanda);

        $licitacao->observacoesPropostaVencedora = 'Item 2 negociado com desconto adicional.';
        $licitacao->salvar();

        $recarregada = Licitacao::buscarPorId($licitacao->id);
        $this->assertSame('Item 2 negociado com desconto adicional.', $recarregada->observacoesPropostaVencedora);
    }

    public function testValorEstimadoEhRecalculadoAoCarregarMesmoSeEstiverErradoNoBanco(): void
    {
        // Regressao: o valor estimado nao pode mais ser editado a mao (o
        // campo do formulario foi removido). Se por algum motivo o valor
        // salvo no banco ficar desatualizado/errado em relacao ao mapa de
        // pesquisa da Cotacao vinculada, carregar a licitacao precisa
        // corrigir sozinho, sem exigir uma correcao manual.
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
        $item = new Item($lote->id, 1, 'Item de teste', 'UN', 1);
        $item->salvar();
        (new Preco($item->id, 223753.69))->salvar();

        $licitacao = Licitacao::criarApartirDeDemanda($demanda);

        \Database::getConnection()
            ->prepare('UPDATE licitacoes SET valor_estimado = :valor WHERE id = :id')
            ->execute(['valor' => 223.753, 'id' => $licitacao->id]);

        $recarregada = Licitacao::buscarPorId($licitacao->id);
        $this->assertEqualsWithDelta(223753.69, $recarregada->valorEstimado, 0.001);

        // A correcao tambem precisa ter sido persistida, nao so em memoria.
        $novaLeitura = \Database::getConnection()
            ->query('SELECT valor_estimado FROM licitacoes WHERE id = ' . $licitacao->id)
            ->fetchColumn();
        $this->assertEqualsWithDelta(223753.69, (float) $novaLeitura, 0.001);
    }

    public function testContarEsomarHomologadasIgnoramValorAdjudicadoSemFinalizar(): void
    {
        $demandaRascunho = $this->criarDemanda();
        $licitacaoRascunho = Licitacao::criarApartirDeDemanda($demandaRascunho);
        $licitacaoRascunho->valorAdjudicado = 700.0;
        $licitacaoRascunho->salvar();

        $this->assertSame(0, Licitacao::contarHomologadas());
        $this->assertEqualsWithDelta(0.0, Licitacao::somarValorAdjudicadoHomologadas(), 0.001);

        $licitacaoRascunho->dataAdjudicacaoHomologacao = '2026-03-10';
        $licitacaoRascunho->salvar();

        $this->assertSame(1, Licitacao::contarHomologadas());
        $this->assertEqualsWithDelta(700.0, Licitacao::somarValorAdjudicadoHomologadas(), 0.001);
    }

    public function testEstaFinalizadaSoEhVerdadeiroComDataAdjudicacaoHomologacaoDefinida(): void
    {
        $demanda = $this->criarDemanda();
        $licitacao = Licitacao::criarApartirDeDemanda($demanda);

        $this->assertFalse($licitacao->estaFinalizada());

        $licitacao->dataAdjudicacaoHomologacao = '2026-01-20';
        $licitacao->salvar();

        $recarregada = Licitacao::buscarPorId($licitacao->id);
        $this->assertTrue($recarregada->estaFinalizada());
        $this->assertSame('2026-01-20', $recarregada->dataAdjudicacaoHomologacao);
    }
}
