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

/**
 * Mostra valor monetário sem "arredondar por dentro": preserva até
 * $maxDecimais casas quando existirem, mas nunca menos que $minDecimais.
 * Usado no Mapa (preço unitário, média/mediana) onde cortar em 2 casas
 * distorce o valor de referência que depois vira o total do lote.
 */
function formatarValorPreciso(?float $valor, int $minDecimais = 2, int $maxDecimais = 4): string
{
    if ($valor === null) {
        $valor = 0.0;
    }

    $formatado = number_format($valor, $maxDecimais, ',', '.');

    if ($minDecimais < $maxDecimais && str_contains($formatado, ',')) {
        // Tira zeros finais depois da vírgula, mas mantém ao menos $minDecimais.
        [$inteiro, $decimal] = explode(',', $formatado);
        $decimal = rtrim($decimal, '0');
        if (strlen($decimal) < $minDecimais) {
            $decimal = str_pad($decimal, $minDecimais, '0');
        }
        $formatado = $decimal === '' ? $inteiro : $inteiro . ',' . $decimal;
    }

    return 'R$ ' . $formatado;
}

/**
 * Interpreta um valor monetário digitado em formato brasileiro
 * ("1.234,56", "1234,56", "1234.56", "1234") pra float sem perda.
 *
 * O truque antigo — str_replace(',', '.', $_POST) e (float) — truncava
 * qualquer entrada com separador de milhar: "1.234,56" virava "1.234.56"
 * e o cast pra float parava no segundo ponto, resultando em 1.234
 * (um erro de três ordens de grandeza). Como o formulário do Mapa
 * pre-preenche o campo com formatarNumero() (que inclui o ponto de
 * milhar), esse bug acionava toda vez que alguém editava um preço
 * antigo.
 */
function converterMoedaBrParaFloat(?string $valor): float
{
    if ($valor === null) {
        return 0.0;
    }

    $valor = trim($valor);
    if ($valor === '') {
        return 0.0;
    }

    // Remove tudo que nao seja digito, virgula, ponto ou sinal (ex.: "R$", espacos).
    $valor = preg_replace('/[^\d,\.\-]/', '', $valor) ?? '';

    if ($valor === '' || $valor === '-') {
        return 0.0;
    }

    $temVirgula = str_contains($valor, ',');
    $temPonto   = str_contains($valor, '.');

    if ($temVirgula && $temPonto) {
        // Formato br: "1.234,56" -> ponto e milhar, virgula e decimal.
        $valor = str_replace('.', '', $valor);
        $valor = str_replace(',', '.', $valor);
    } elseif ($temVirgula) {
        // "1234,56" -> virgula e decimal.
        $valor = str_replace(',', '.', $valor);
    } elseif ($temPonto) {
        // Sem virgula: um unico ponto e decimal ("1234.56"); varios
        // pontos ("1.234.567") sao separador de milhar, entao tira todos.
        if (substr_count($valor, '.') > 1) {
            $valor = str_replace('.', '', $valor);
        }
    }

    return (float) $valor;
}
