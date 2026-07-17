<?php

/**
 * Formata uma data (Y-m-d) por extenso, no padrão usado em documentos
 * oficiais: "14 de julho de 2026".
 */
function dataPorExtenso(string $dataYmd): string
{
    $meses = [
        1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril',
        5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto',
        9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro',
    ];

    $timestamp = strtotime($dataYmd);
    $dia = (int) date('j', $timestamp);
    $mes = (int) date('n', $timestamp);
    $ano = date('Y', $timestamp);

    return $dia . ' de ' . $meses[$mes] . ' de ' . $ano;
}

function numeroParaExtenso(float $valor): string
{
    $valor = round($valor, 2);
    $inteiro = (int) floor($valor);
    $centavos = (int) round(($valor - $inteiro) * 100);

    $textoInteiro = _extensoInteiro($inteiro);
    $textoReais = $inteiro === 1 ? 'real' : 'reais';

    if ($centavos === 0) {
        return $textoInteiro . ' ' . $textoReais;
    }

    $textoCentavos = _extensoInteiro($centavos);
    $textoCentavosPalavra = $centavos === 1 ? 'centavo' : 'centavos';

    return $textoInteiro . ' ' . $textoReais . ' e ' . $textoCentavos . ' ' . $textoCentavosPalavra;
}

function _extensoInteiro(int $numero): string
{
    if ($numero === 0) {
        return 'zero';
    }

    $unidades = ['', 'um', 'dois', 'três', 'quatro', 'cinco', 'seis', 'sete', 'oito', 'nove'];
    $dezADezenove = ['dez', 'onze', 'doze', 'treze', 'catorze', 'quinze', 'dezesseis', 'dezessete', 'dezoito', 'dezenove'];
    $dezenas = ['', '', 'vinte', 'trinta', 'quarenta', 'cinquenta', 'sessenta', 'setenta', 'oitenta', 'noventa'];
    $centenas = ['', 'cento', 'duzentos', 'trezentos', 'quatrocentos', 'quinhentos', 'seiscentos', 'setecentos', 'oitocentos', 'novecentos'];

    $extensoGrupo3 = function (int $n) use ($unidades, $dezADezenove, $dezenas, $centenas) {
        if ($n === 0) {
            return '';
        }
        if ($n === 100) {
            return 'cem';
        }

        $c = intdiv($n, 100);
        $resto = $n % 100;
        $partes = [];

        if ($c > 0) {
            $partes[] = $centenas[$c];
        }

        if ($resto >= 10 && $resto <= 19) {
            $partes[] = $dezADezenove[$resto - 10];
        } elseif ($resto >= 20) {
            $d = intdiv($resto, 10);
            $u = $resto % 10;
            $textoResto = $dezenas[$d];
            if ($u > 0) {
                $textoResto .= ' e ' . $unidades[$u];
            }
            $partes[] = $textoResto;
        } elseif ($resto > 0) {
            $partes[] = $unidades[$resto];
        }

        return implode(' e ', $partes);
    };

    $escalas = [
        ['valor' => 1000000000, 'singular' => 'bilhão', 'plural' => 'bilhões'],
        ['valor' => 1000000, 'singular' => 'milhão', 'plural' => 'milhões'],
        ['valor' => 1000, 'singular' => 'mil', 'plural' => 'mil'],
    ];

    $partesFinais = [];
    $restante = $numero;

    foreach ($escalas as $escala) {
        $quantidade = intdiv($restante, $escala['valor']);
        if ($quantidade > 0) {
            if ($escala['valor'] === 1000) {
                if ($quantidade === 1) {
                    $partesFinais[] = $escala['singular'];
                } else {
                    $partesFinais[] = $extensoGrupo3($quantidade) . ' ' . $escala['singular'];
                }
            } else {
                $nomeEscala = $quantidade === 1 ? $escala['singular'] : $escala['plural'];
                $partesFinais[] = $extensoGrupo3($quantidade) . ' ' . $nomeEscala;
            }
            $restante %= $escala['valor'];
        }
    }

    $restoFinal = $extensoGrupo3($restante);
    if ($restoFinal !== '') {
        $partesFinais[] = $restoFinal;
    }

    if (count($partesFinais) === 0) {
        return 'zero';
    }

    if (count($partesFinais) > 1) {
        $ultima = array_pop($partesFinais);
        return implode(' e ', $partesFinais) . ' e ' . $ultima;
    }

    return $partesFinais[0];
}