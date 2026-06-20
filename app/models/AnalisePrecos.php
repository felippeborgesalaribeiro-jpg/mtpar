<?php

class AnalisePrecos
{
    const EXCESSIVO = 'EXCESSIVAMENTE ELEVADO';
    const INEXEQUIVEL = 'INEXEQUÍVEL';
    const EXCECAO_PRECO_PUBLICO = 'EXCEÇÃO - PREÇO PÚBLICO';
    const APROVADO = 'APROVADO';

    const CRITERIO_MEDIA = 'MEDIA';
    const CRITERIO_MEDIANA = 'MEDIANA';
    const CRITERIO_MENOR_PRECO = 'MENOR_PRECO';

    private array $precos;
    private string $criterio;
    private array $parametrosPrecoPublico;

    public function __construct(array $precos, string $criterio = self::CRITERIO_MEDIANA, array $parametrosPrecoPublico = [])
    {
        $this->precos = $precos;
        $this->criterio = $criterio;
        $this->parametrosPrecoPublico = $parametrosPrecoPublico;
    }

    public function calcular(): array
    {
        $resultadosEtapa1 = $this->calcularEtapa1();
        $resultadosEtapa2 = $this->calcularEtapa2($resultadosEtapa1);

        $resultadoFinal = [];
        foreach ($this->precos as $indice => $preco) {
            if ($resultadosEtapa1[$indice]['resultado'] === self::EXCESSIVO) {
                $resultadoFinal[$indice] = self::EXCESSIVO;
            } else {
                $resultadoFinal[$indice] = $resultadosEtapa2[$indice]['resultado'];
            }
        }

        return [
            'etapa1' => $resultadosEtapa1,
            'etapa2' => $resultadosEtapa2,
            'resultado_final' => $resultadoFinal,
            'valor_referencia' => $this->calcularValorReferencia($resultadoFinal),
        ];
    }

    private function calcularEtapa1(): array
    {
        $resultados = [];

        foreach ($this->precos as $indice => $preco) {
            $mediaDosDemais = $this->mediaDosDemais($this->precos, $indice, 'valor');

            if ($mediaDosDemais === null) {
                $resultados[$indice] = ['media_demais' => null, 'diferenca' => null, 'resultado' => self::APROVADO];
                continue;
            }

            $diferenca = ($preco['valor'] / $mediaDosDemais) - 1;
            $resultado = $diferenca > 0.30 ? self::EXCESSIVO : self::APROVADO;

            $resultados[$indice] = [
                'media_demais' => $mediaDosDemais,
                'diferenca' => $diferenca,
                'resultado' => $resultado,
            ];
        }

        return $resultados;
    }

    private function calcularEtapa2(array $resultadosEtapa1): array
    {
        $precosRestantes = [];
        foreach ($this->precos as $indice => $preco) {
            if ($resultadosEtapa1[$indice]['resultado'] !== self::EXCESSIVO) {
                $precosRestantes[$indice] = $preco;
            }
        }

        $resultados = [];

        foreach ($this->precos as $indice => $preco) {
            if ($resultadosEtapa1[$indice]['resultado'] === self::EXCESSIVO) {
                $resultados[$indice] = ['media_demais' => null, 'comparacao' => null, 'resultado' => null];
                continue;
            }

            $mediaDosDemais = $this->mediaDosDemais($precosRestantes, $indice, 'valor');
            $ehPrecoPublico = in_array($preco['parametro'], $this->parametrosPrecoPublico, true);

            if ($mediaDosDemais === null) {
                $comparacao = null;
                $resultado = $ehPrecoPublico ? self::EXCECAO_PRECO_PUBLICO : self::APROVADO;
            } else {
                $comparacao = $preco['valor'] / $mediaDosDemais;

                if ($ehPrecoPublico) {
                    $resultado = self::EXCECAO_PRECO_PUBLICO;
                } else {
                    $resultado = $comparacao < 0.70 ? self::INEXEQUIVEL : self::APROVADO;
                }
            }

            $resultados[$indice] = [
                'media_demais' => $mediaDosDemais,
                'comparacao' => $comparacao,
                'resultado' => $resultado,
            ];
        }

        return $resultados;
    }

    private function mediaDosDemais(array $precos, int $indiceExcluir, string $campo): ?float
    {
        $valores = [];
        foreach ($precos as $indice => $preco) {
            if ($indice !== $indiceExcluir) {
                $valores[] = $preco[$campo];
            }
        }

        if (count($valores) === 0) {
            return null;
        }

        return array_sum($valores) / count($valores);
    }

    private function calcularValorReferencia(array $resultadoFinal): ?float
    {
        $aprovados = [];
        foreach ($this->precos as $indice => $preco) {
            if ($resultadoFinal[$indice] === self::APROVADO || $resultadoFinal[$indice] === self::EXCECAO_PRECO_PUBLICO) {
                $aprovados[] = $preco['valor'];
            }
        }

        if (count($aprovados) === 0) {
            return null;
        }

        switch ($this->criterio) {
            case self::CRITERIO_MEDIA:
                return $this->calcularMedia($aprovados);
            case self::CRITERIO_MENOR_PRECO:
                return $this->calcularMenorPreco($aprovados);
            case self::CRITERIO_MEDIANA:
            default:
                return $this->calcularMediana($aprovados);
        }
    }

    public function calcularMedia(array $valores): float
    {
        return array_sum($valores) / count($valores);
    }

    public function calcularMediana(array $valores): float
    {
        sort($valores);
        $quantidade = count($valores);
        $meio = intdiv($quantidade, 2);

        if ($quantidade % 2 === 0) {
            return ($valores[$meio - 1] + $valores[$meio]) / 2;
        }

        return $valores[$meio];
    }

    public function calcularMenorPreco(array $valores): float
    {
        return min($valores);
    }
}