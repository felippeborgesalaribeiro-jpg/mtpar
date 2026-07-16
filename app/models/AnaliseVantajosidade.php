<?php

class AnaliseVantajosidade
{
    const VANTAJOSA = 'VANTAJOSA';
    const NAO_VANTAJOSA = 'NÃO VANTAJOSA';

    private float $precoAta;
    private array $precosMercado;

    public function __construct(float $precoAta, array $precosMercado)
    {
        $this->precoAta = $precoAta;
        $this->precosMercado = $precosMercado;
    }

    public function calcular(): array
    {
        $valores = array_map(fn($p) => $p['valor'], $this->precosMercado);

        if (count($valores) === 0) {
            return [
                'media_mercado' => null,
                'diferenca_percentual' => null,
                'resultado' => null,
            ];
        }

        $mediaMercado = array_sum($valores) / count($valores);
        $diferencaPercentual = $this->precoAta != 0
            ? (($mediaMercado - $this->precoAta) / $this->precoAta) * 100
            : null;

        $resultado = $mediaMercado >= $this->precoAta ? self::VANTAJOSA : self::NAO_VANTAJOSA;

        return [
            'media_mercado' => $mediaMercado,
            'diferenca_percentual' => $diferencaPercentual,
            'resultado' => $resultado,
        ];
    }

    public function calcularDiferencaPorPreco(float $precoMercado): float
    {
        if ($this->precoAta == 0) {
            return 0;
        }

        return (($precoMercado - $this->precoAta) / $this->precoAta) * 100;
    }
}