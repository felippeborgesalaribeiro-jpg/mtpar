<?php

namespace Tests\Models;

use Demanda;
use Licitacao;
use RelatorioLicitacao;
use Servidor;
use Tests\DatabaseTestCase;

require_once __DIR__ . '/../../app/models/Demanda.php';
require_once __DIR__ . '/../../app/models/Licitacao.php';
require_once __DIR__ . '/../../app/models/StatusLicitacao.php';
require_once __DIR__ . '/../../app/models/RelatorioLicitacao.php';
require_once __DIR__ . '/../../app/models/Servidor.php';

final class RelatorioLicitacaoTest extends DatabaseTestCase
{
    private function criarServidor(string $nome): Servidor
    {
        $servidor = new Servidor($nome);
        $servidor->salvar();

        return $servidor;
    }

    private function criarDemanda(string $setor): Demanda
    {
        $demanda = new Demanda('MTPAR-PRO-2026/' . uniqid(), '2026-01-10', '', $setor);
        $demanda->salvar();

        return $demanda;
    }

    public function testPorSetorDemandanteAgrupaEcalculaTotais(): void
    {
        $servidor = $this->criarServidor('Ana');

        $demanda1 = $this->criarDemanda('Setor A');
        $licitacao1 = Licitacao::criarApartirDeDemanda($demanda1);
        $licitacao1->servidorResponsavelId = $servidor->id;
        $licitacao1->valorEstimado = 1000.0;
        $licitacao1->valorAdjudicado = 900.0;
        $licitacao1->salvar();

        $demanda2 = $this->criarDemanda('Setor A');
        $licitacao2 = Licitacao::criarApartirDeDemanda($demanda2);
        $licitacao2->valorEstimado = 500.0;
        $licitacao2->salvar();

        $demanda3 = $this->criarDemanda('Setor B');
        Licitacao::criarApartirDeDemanda($demanda3);

        $linhas = RelatorioLicitacao::porSetorDemandante(Licitacao::buscarTodas());

        $this->assertSame(2, $linhas['Setor A']['quantidade']);
        $this->assertEqualsWithDelta(1500.0, $linhas['Setor A']['valor_estimado'], 0.001);
        $this->assertSame(1, $linhas['Setor A']['homologadas']);
        $this->assertEqualsWithDelta(900.0, $linhas['Setor A']['valor_adjudicado'], 0.001);
        $this->assertEqualsWithDelta(100.0, $linhas['Setor A']['economicidade'], 0.001);

        $this->assertSame(1, $linhas['Setor B']['quantidade']);
        $this->assertNull($linhas['Setor B']['economicidade']);
    }

    public function testPorServidorResponsavelUsaNaoInformadoQuandoAusente(): void
    {
        $demanda = $this->criarDemanda('Setor X');
        Licitacao::criarApartirDeDemanda($demanda);

        $linhas = RelatorioLicitacao::porServidorResponsavel(Licitacao::buscarTodas());

        $this->assertSame(1, $linhas['Não informado']['quantidade']);
    }

    public function testPorAnoUsaRealizacaoSessaoPublicaQuandoDisponivel(): void
    {
        $demanda = $this->criarDemanda('Setor Y');
        $licitacao = Licitacao::criarApartirDeDemanda($demanda);
        $licitacao->realizacaoSessaoPublica = '2025-06-15';
        $licitacao->salvar();

        $linhas = RelatorioLicitacao::porAno(Licitacao::buscarTodas());

        $this->assertArrayHasKey('2025', $linhas);
        $this->assertSame(1, $linhas['2025']['quantidade']);
    }
}
