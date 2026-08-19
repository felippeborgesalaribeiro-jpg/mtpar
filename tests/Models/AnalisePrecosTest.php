<?php

namespace Tests\Models;

use AnalisePrecos;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/AnalisePrecos.php';

final class AnalisePrecosTest extends TestCase
{
    public function testValorReferenciaPorMediaEArredondadoParaCentavos(): void
    {
        // Media bruta de (13.40, 12.50, 14.90, 14.66) = 13.865, que em ponto
        // flutuante vira 13.864999999999998 - sem o arredondamento na origem,
        // isso vazava pro total do lote (valor bruto x quantidade), dando um
        // total que nao batia com o valor unitario de duas casas mostrado
        // na tela. Caso real que motivou o fix: processo MTPAR-PRO-2026/01337.
        $precos = [
            ['parametro' => 'X', 'valor' => 13.40, 'fonte' => ''],
            ['parametro' => 'X', 'valor' => 12.50, 'fonte' => ''],
            ['parametro' => 'X', 'valor' => 14.90, 'fonte' => ''],
            ['parametro' => 'X', 'valor' => 14.66, 'fonte' => ''],
        ];

        $analise = new AnalisePrecos($precos, AnalisePrecos::CRITERIO_MEDIA);
        $resultado = $analise->calcular();

        $this->assertSame(13.86, $resultado['valor_referencia']);
    }

    public function testValorUnitarioArredondadoVezesQuantidadeBateComOTotal(): void
    {
        $precos = [
            ['parametro' => 'X', 'valor' => 13.40, 'fonte' => ''],
            ['parametro' => 'X', 'valor' => 12.50, 'fonte' => ''],
            ['parametro' => 'X', 'valor' => 14.90, 'fonte' => ''],
            ['parametro' => 'X', 'valor' => 14.66, 'fonte' => ''],
        ];

        $analise = new AnalisePrecos($precos, AnalisePrecos::CRITERIO_MEDIA);
        $valorReferencia = $analise->calcular()['valor_referencia'];

        $quantidade = 100;
        $totalCalculado = round($valorReferencia * $quantidade, 2);
        $totalConferidoNaMao = round($valorReferencia, 2) * $quantidade;

        $this->assertEqualsWithDelta($totalConferidoNaMao, $totalCalculado, 0.001);
    }

    public function testValorReferenciaPorMedianaComQuantidadeParDeAprovadosEArredondado(): void
    {
        // Mediana de (13.86, 13.87) = 13.865 -> arredonda pra 13.87 (ou
        // 13.86, dependendo do modo de arredondamento) mas nunca fica com
        // mais de duas casas decimais.
        $precos = [
            ['parametro' => 'X', 'valor' => 13.86, 'fonte' => ''],
            ['parametro' => 'X', 'valor' => 13.87, 'fonte' => ''],
        ];

        $analise = new AnalisePrecos($precos, AnalisePrecos::CRITERIO_MEDIANA);
        $resultado = $analise->calcular();

        $this->assertEqualsWithDelta(round($resultado['valor_referencia'], 2), $resultado['valor_referencia'], 0.0001);
    }

    public function testComArredondamentoDesativadoMantemOValorBrutoDeAntesDaCorrecao(): void
    {
        // Cotacoes de antes da correcao nao podem ter os numeros mudados
        // retroativamente (ja viraram Mapa/Relatorio formal) - por isso quem
        // chama pode desligar o arredondamento e manter o comportamento antigo.
        $precos = [
            ['parametro' => 'X', 'valor' => 13.40, 'fonte' => ''],
            ['parametro' => 'X', 'valor' => 12.50, 'fonte' => ''],
            ['parametro' => 'X', 'valor' => 14.90, 'fonte' => ''],
            ['parametro' => 'X', 'valor' => 14.66, 'fonte' => ''],
        ];

        $analise = new AnalisePrecos($precos, AnalisePrecos::CRITERIO_MEDIA, [], arredondarValorReferencia: false);
        $resultado = $analise->calcular();

        $valorBrutoEsperado = (13.40 + 12.50 + 14.90 + 14.66) / 4;
        $this->assertSame($valorBrutoEsperado, $resultado['valor_referencia']);
        $this->assertNotSame(13.86, $resultado['valor_referencia']);
    }
}
