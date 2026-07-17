<?php

namespace Tests\Models;

use Cotacao;
use Empresa;
use Licitacao;
use LotePropostaVencedora;
use Lote;
use Demanda;
use Servidor;
use Tests\DatabaseTestCase;

require_once __DIR__ . '/../../app/models/Empresa.php';
require_once __DIR__ . '/../../app/models/Demanda.php';
require_once __DIR__ . '/../../app/models/StatusLicitacao.php';
require_once __DIR__ . '/../../app/models/Licitacao.php';
require_once __DIR__ . '/../../app/models/AnalisePrecos.php';
require_once __DIR__ . '/../../app/models/Cotacao.php';
require_once __DIR__ . '/../../app/models/StatusCotacao.php';
require_once __DIR__ . '/../../app/models/Lote.php';
require_once __DIR__ . '/../../app/models/Servidor.php';
require_once __DIR__ . '/../../app/models/LotePropostaVencedora.php';

final class EmpresaTest extends DatabaseTestCase
{
    public function testSalvarNormalizaCnpjRemovendoPontuacao(): void
    {
        $empresa = new Empresa('Climatiza Engenharia e Instalações Ltda.', '12.345.678/0001-90', 'Climatiza Engenharia');
        $empresa->salvar();

        $recarregada = Empresa::buscarPorId($empresa->id);
        $this->assertSame('12345678000190', $recarregada->cnpj);
    }

    public function testBuscarPorCnpjAceitaCnpjFormatadoOuNao(): void
    {
        $empresa = new Empresa('Refrigeração Cuiabá Ltda.', '08765432000111');
        $empresa->salvar();

        $this->assertNotNull(Empresa::buscarPorCnpj('08765432000111'));
        $this->assertNotNull(Empresa::buscarPorCnpj('08.765.432/0001-11'));
        $this->assertNull(Empresa::buscarPorCnpj('00000000000000'));
    }

    public function testBuscarEncontraPorNomeNomeFantasiaOuDigitosDoCnpj(): void
    {
        $empresa = new Empresa('Climatiza Engenharia e Instalações Ltda.', '12345678000190', 'Climatiza Engenharia');
        $empresa->salvar();

        $this->assertCount(1, Empresa::buscar('climatiza'));
        $this->assertCount(1, Empresa::buscar('Engenharia'));
        $this->assertCount(1, Empresa::buscar('12345678000190'));
        $this->assertCount(1, Empresa::buscar('12.345.678/0001-90'));
        $this->assertCount(0, Empresa::buscar('inexistente'));
    }

    public function testBuscarComTextoCurtoNaoQuebraEIgnoraStringVazia(): void
    {
        $this->assertSame([], Empresa::buscar(''));
        $this->assertSame([], Empresa::buscar('   '));
    }

    /**
     * Cria uma Licitacao com um lote (via Cotacao vinculada) pra poder
     * registrar uma empresa vencedora naquele lote.
     */
    private function criarLicitacaoComLote(string $numeroProcesso): array
    {
        $servidor = new Servidor('Servidor de Teste');
        $servidor->salvar();

        $demanda = new Demanda($numeroProcesso, '2026-01-01');
        $demanda->salvar();

        $cotacao = new Cotacao($numeroProcesso, '', '', '', '', $servidor->id, demandaId: $demanda->id);
        $cotacao->salvar();

        $lote = new Lote($cotacao->id, '01');
        $lote->salvar();

        $licitacao = Licitacao::criarApartirDeDemanda($demanda);

        return [$licitacao, $lote];
    }

    public function testContarLicitacoesHomologadasSoContaLicitacoesFinalizadas(): void
    {
        $empresa = new Empresa('Instaladora Pantanal Ltda.', '33222111000177');
        $empresa->salvar();

        [$licitacao1, $lote1] = $this->criarLicitacaoComLote('MTPAR-PRO-2026/00001');
        (new LotePropostaVencedora($licitacao1->id, $lote1->id, $empresa->id))->salvar();
        $licitacao1->dataAdjudicacaoHomologacao = '2026-01-20';
        $licitacao1->salvar();

        [$licitacao2, $lote2] = $this->criarLicitacaoComLote('MTPAR-PRO-2026/00002');
        (new LotePropostaVencedora($licitacao2->id, $lote2->id, $empresa->id))->salvar();
        // Ainda nao finalizada (sem data_adjudicacao_homologacao) - nao deve contar.

        $this->assertSame(1, $empresa->contarLicitacoesHomologadas());
    }
}
