<?php

namespace Tests\Helpers;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/helpers/formatacao.php';

final class FormatacaoTest extends TestCase
{
    public function testConverterMoedaBrParaFloatTrataSeparadorDeMilharCorretamente(): void
    {
        // Regressao: str_replace(',', '.') direto no texto, sem remover o
        // ponto de milhar antes, transformava "1.200,00" em "1.200.00", e o
        // PHP truncava o float em "1.200" (exibido como R$ 1,20). Aconteceu
        // de verdade tanto no valor estimado da Licitacao quanto no valor
        // da proposta vencedora.
        $this->assertEqualsWithDelta(1200.0, \converterMoedaBrParaFloat('1.200,00'), 0.001);
        $this->assertEqualsWithDelta(223753.69, \converterMoedaBrParaFloat('223.753,69'), 0.001);
        $this->assertEqualsWithDelta(1234567.89, \converterMoedaBrParaFloat('1.234.567,89'), 0.001);
    }

    public function testConverterMoedaBrParaFloatSemSeparadorDeMilhar(): void
    {
        $this->assertEqualsWithDelta(500.0, \converterMoedaBrParaFloat('500,00'), 0.001);
        $this->assertEqualsWithDelta(0.5, \converterMoedaBrParaFloat('0,50'), 0.001);
    }
}
