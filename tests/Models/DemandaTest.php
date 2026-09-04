<?php

namespace Tests\Models;

use Cotacao;
use Demanda;
use PDOException;
use Servidor;
use Tests\DatabaseTestCase;

require_once __DIR__ . '/../../app/models/Demanda.php';
require_once __DIR__ . '/../../app/models/Cotacao.php';
require_once __DIR__ . '/../../app/models/StatusCotacao.php';
require_once __DIR__ . '/../../app/models/Servidor.php';

final class DemandaTest extends DatabaseTestCase
{
    public function testExcluirDefinitivamenteFalhaComPDOExceptionSeAindaHouverCotacaoVinculada(): void
    {
        // cotacoes.demanda_id nao tem ON DELETE CASCADE (diferente de
        // licitacoes.demanda_id) - excluir a Demanda com uma Cotacao ainda
        // ativa vinculada bate na FOREIGN KEY constraint. O admin trata isso
        // com uma mensagem amigavel (AdminController::excluirDefinitivamenteDemanda);
        // este teste documenta a exceção real que precisa ser capturada lá.
        $servidor = new Servidor('Servidor de Teste');
        $servidor->salvar();

        $demanda = new Demanda('MTPAR-PRO-2026/00901', '2026-01-10');
        $demanda->salvar();

        $cotacao = new Cotacao(
            $demanda->numeroProcesso,
            '',
            '',
            '',
            '',
            $servidor->id,
            demandaId: $demanda->id
        );
        $cotacao->salvar();

        $this->expectException(PDOException::class);
        $demanda->excluirDefinitivamente();
    }

    public function testCalcularDiasEmAbertoContaDiasCorridosDesdeORecebimento(): void
    {
        $dataRecebimento = (new \DateTime('today'))->modify('-15 days')->format('Y-m-d');
        $demanda = new Demanda('MTPAR-PRO-2026/00900', $dataRecebimento);
        $demanda->salvar();

        $this->assertSame(15, $demanda->calcularDiasEmAberto());
    }

    public function testCalcularDiasEmAbertoEhZeroNoDiaDoRecebimento(): void
    {
        $demanda = new Demanda('MTPAR-PRO-2026/00901', (new \DateTime('today'))->format('Y-m-d'));
        $demanda->salvar();

        $this->assertSame(0, $demanda->calcularDiasEmAberto());
    }

    public function testExisteOutraComNumeroDetectaDuplicidadeEmDemandaAtiva(): void
    {
        $demanda = new Demanda('MTPAR-PRO-2026/00940', '2026-02-01');
        $demanda->salvar();

        $this->assertTrue(Demanda::existeOutraComNumero('MTPAR-PRO-2026/00940'));
        $this->assertFalse(Demanda::existeOutraComNumero('MTPAR-PRO-2026/09999'));
    }

    public function testExisteOutraComNumeroIgnoraDemandaNaLixeira(): void
    {
        // Se o setor excluiu um numero errado, precisa poder recadastrar com
        // o mesmo numero sem ter que restaurar/limpar a lixeira antes.
        $demanda = new Demanda('MTPAR-PRO-2026/00941', '2026-02-01');
        $demanda->salvar();
        $demanda->excluir();

        $this->assertFalse(Demanda::existeOutraComNumero('MTPAR-PRO-2026/00941'));
    }

    public function testExisteOutraComNumeroIgnoraProprioIdNaEdicao(): void
    {
        // Ao editar mantendo o proprio numero, nao pode acusar conflito
        // com ela mesma - dai o parametro $ignorarId.
        $demanda = new Demanda('MTPAR-PRO-2026/00942', '2026-02-01');
        $demanda->salvar();

        $this->assertFalse(Demanda::existeOutraComNumero('MTPAR-PRO-2026/00942', $demanda->id));
    }

    public function testExisteOutraComNumeroRetornaFalseParaNumeroVazio(): void
    {
        $this->assertFalse(Demanda::existeOutraComNumero(''));
        $this->assertFalse(Demanda::existeOutraComNumero('   '));
    }
}
