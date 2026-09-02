<?php

namespace Tests\Models;

use Cotacao;
use Demanda;
use Item;
use Licitacao;
use Lote;
use Preco;
use ProcessoVantajosidade;
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
require_once __DIR__ . '/../../app/models/ProcessoVantajosidade.php';
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

    public function testBuscarTodasNaoResincronizaValorEstimadoMasBuscarPorIdSim(): void
    {
        // buscarTodas() e usada em listagens (Licitacoes, Aplic, Relatorios) -
        // recalcular o mapa de precos inteiro da cotacao vinculada pra cada
        // linha ali seria caro e desnecessario. So buscarPorId/
        // buscarPorDemandaId (telas de detalhe/edicao) precisam do valor
        // sempre em dia.
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
        (new Preco($item->id, 100))->salvar();

        $licitacao = Licitacao::criarApartirDeDemanda($demanda);
        $this->assertEqualsWithDelta(100.0, $licitacao->valorEstimado, 0.001);

        // Preco novo adicionado depois que a Licitacao ja foi criada (perto
        // o bastante do primeiro pra nao ser descartado como excessivo).
        (new Preco($item->id, 110))->salvar();

        $daListagem = array_values(array_filter(
            Licitacao::buscarTodas(),
            fn(Licitacao $l) => $l->id === $licitacao->id
        ))[0];
        $this->assertEqualsWithDelta(100.0, $daListagem->valorEstimado, 0.001, 'buscarTodas() não deve resincronizar');

        $doDetalhe = Licitacao::buscarPorId($licitacao->id);
        $this->assertEqualsWithDelta(105.0, $doDetalhe->valorEstimado, 0.001, 'buscarPorId() deve resincronizar (mediana de 100/110)');
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

    public function testEditarADemandaRefleteNaLicitacaoSemDivergir(): void
    {
        // Coracao do "Caminho A": os campos de identidade do processo (numero,
        // setor, objeto, data, link, responsavel) moram so na Demanda. Editar
        // a Demanda depois que a Licitacao ja existe precisa refletir na hora,
        // sem que os dois fiquem com valores diferentes.
        $servidor = $this->criarServidor();
        $demanda = $this->criarDemanda();
        $demanda->servidorResponsavelId = $servidor->id;
        $demanda->salvar();

        $licitacao = Licitacao::criarApartirDeDemanda($demanda);
        $this->assertSame('MTPAR-PRO-2026/00100', $licitacao->numeroProcesso);
        $this->assertSame('Setor de TI', $licitacao->setorDemandante);

        // Depois de a Licitacao ja existir, a Demanda e corrigida/editada.
        $outroServidor = new Servidor('Outro Servidor');
        $outroServidor->salvar();

        $demanda->numeroProcesso = 'MTPAR-PRO-2026/00100-CORRIGIDO';
        $demanda->setorDemandante = 'Setor de Compras';
        $demanda->objeto = 'Objeto corrigido';
        $demanda->linkSigadoc = 'https://sigadoc/novo';
        $demanda->dataRecebimento = '2026-02-02';
        $demanda->servidorResponsavelId = $outroServidor->id;
        $demanda->salvar();

        $recarregada = Licitacao::buscarPorId($licitacao->id);

        $this->assertSame('MTPAR-PRO-2026/00100-CORRIGIDO', $recarregada->numeroProcesso);
        $this->assertSame('Setor de Compras', $recarregada->setorDemandante);
        $this->assertSame('Objeto corrigido', $recarregada->objeto);
        $this->assertSame('https://sigadoc/novo', $recarregada->linkSigadoc);
        $this->assertSame('2026-02-02', $recarregada->dataRecebimento);
        $this->assertSame($outroServidor->id, $recarregada->servidorResponsavelId);
        $this->assertSame($outroServidor->id, $recarregada->buscarServidorResponsavel()->id);
    }

    public function testBuscarTodasTambemRefleteAIdentidadeAtualDaDemanda(): void
    {
        $demanda = $this->criarDemanda();
        $licitacao = Licitacao::criarApartirDeDemanda($demanda);

        $demanda->objeto = 'Objeto atualizado na listagem';
        $demanda->salvar();

        $daListagem = array_values(array_filter(
            Licitacao::buscarTodas(),
            fn(Licitacao $l) => $l->id === $licitacao->id
        ))[0];

        $this->assertSame('Objeto atualizado na listagem', $daListagem->objeto);
    }

    public function testGerarAoConcluirDemandaCriaLicitacaoQuandoNaoEhVantajosidade(): void
    {
        $demanda = $this->criarDemanda();

        $licitacao = Licitacao::gerarAoConcluirDemanda($demanda);

        $this->assertNotNull($licitacao);
        $this->assertSame($demanda->id, $licitacao->demandaId);
        $this->assertNotNull(Licitacao::buscarPorDemandaId($demanda->id));
    }

    public function testGerarAoConcluirDemandaNaoCriaLicitacaoParaProcessoDeVantajosidade(): void
    {
        // Regressao: concluir uma demanda que e de Vantajosidade (adesao a
        // ata) nao pode gerar uma licitacao "fantasma" - vantajosidade segue
        // outra trilha, sem fase de licitacao.
        $servidor = $this->criarServidor();
        $demanda = $this->criarDemanda();

        $vantajosidade = new ProcessoVantajosidade(
            'MTPAR-ATA-2026/00100', 'Órgão Gerenciador', 'Adesão a ata',
            $servidor->id, ProcessoVantajosidade::STATUS_EM_ANDAMENTO,
            demandaId: $demanda->id
        );
        $vantajosidade->salvar();

        $licitacao = Licitacao::gerarAoConcluirDemanda($demanda);

        $this->assertNull($licitacao);
        $this->assertNull(Licitacao::buscarPorDemandaId($demanda->id));
    }

    public function testGerarAoConcluirDemandaNaoDuplicaSeJaExiste(): void
    {
        $demanda = $this->criarDemanda();

        $primeira = Licitacao::gerarAoConcluirDemanda($demanda);
        $segunda = Licitacao::gerarAoConcluirDemanda($demanda);

        $this->assertNotNull($primeira);
        $this->assertNotNull($segunda);
        $this->assertSame($primeira->id, $segunda->id);
    }
}
