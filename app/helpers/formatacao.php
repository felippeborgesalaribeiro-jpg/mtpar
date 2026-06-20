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