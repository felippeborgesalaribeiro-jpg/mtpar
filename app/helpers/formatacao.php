<?php

function formatarMoeda(?float $valor): string
{
    if ($valor === null) {
        $valor = 0.0;
    }

    return 'R$ ' . number_format($valor, 2, ',', '.');
}

function formatarNumero(?float $valor, int $decimais = 2): string
{
    if ($valor === null) {
        $valor = 0.0;
    }

    return number_format($valor, $decimais, ',', '.');
}

function converterMoedaBrParaFloat(string $valor): float
{
    // Remove separador de milhar (.) antes de trocar a virgula decimal por
    // ponto - "223.753,69" precisa virar "223753.69", nunca "223.753.69".
    return (float) str_replace(',', '.', str_replace('.', '', trim($valor)));
}