<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/PrecoVantajosidade.php';
require_once __DIR__ . '/AnaliseVantajosidade.php';

class ItemVantajosidade
{
    public ?int $id;
    public int $processoId;
    public string $lote;
    public string $item;
    public string $descricao;
    public string $unidade;
    public float $quantidade;
    public float $precoAta;

    public function __construct(
        int $processoId,
        string $lote,
        string $item,
        float $precoAta,
        string $descricao = '',
        string $unidade = 'UN',
        float $quantidade = 1,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->processoId = $processoId;
        $this->lote = $lote;
        $this->item = $item;
        $this->precoAta = $precoAta;
        $this->descricao = $descricao;
        $this->unidade = $unidade;
        $this->quantidade = $quantidade;
    }

    public function salvar(): int
    {
        $pdo = Database::getConnection();

        if ($this->id === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO itens_vantajosidade (processo_id, lote, item, descricao, unidade, quantidade, preco_ata)
                 VALUES (:processo_id, :lote, :item, :descricao, :unidade, :quantidade, :preco_ata)'
            );
            $stmt->execute($this->paramsParaSalvar());
            $this->id = (int) $pdo->lastInsertId();
        } else {
            $stmt = $pdo->prepare(
                'UPDATE itens_vantajosidade SET lote = :lote, item = :item, descricao = :descricao,
                 unidade = :unidade, quantidade = :quantidade, preco_ata = :preco_ata
                 WHERE id = :id'
            );
            $stmt->execute(array_merge($this->paramsParaSalvar(), ['id' => $this->id]));
        }

        return $this->id;
    }

    private function paramsParaSalvar(): array
    {
        return [
            'processo_id' => $this->processoId,
            'lote' => $this->lote,
            'item' => $this->item,
            'descricao' => $this->descricao,
            'unidade' => $this->unidade,
            'quantidade' => $this->quantidade,
            'preco_ata' => $this->precoAta,
        ];
    }

    public function excluir(): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM itens_vantajosidade WHERE id = :id');
        $stmt->execute(['id' => $this->id]);
    }

    public function buscarPrecos(): array
    {
        return PrecoVantajosidade::buscarPorItem($this->id);
    }

    public function analisar(): array
    {
        $precos = $this->buscarPrecos();
        $precosParaCalculo = array_map(fn($p) => ['valor' => $p->valor], $precos);

        $analise = new AnaliseVantajosidade($this->precoAta, $precosParaCalculo);

        return $analise->calcular();
    }

    public static function buscarPorId(int $id): ?ItemVantajosidade
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM itens_vantajosidade WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $linha = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$linha) {
            return null;
        }

        return self::fromArray($linha);
    }

    public static function buscarPorProcesso(int $processoId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM itens_vantajosidade WHERE processo_id = :processo_id ORDER BY lote ASC, item ASC');
        $stmt->execute(['processo_id' => $processoId]);

        $itens = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
            $itens[] = self::fromArray($linha);
        }

        return $itens;
    }

    private static function fromArray(array $linha): ItemVantajosidade
    {
        return new ItemVantajosidade(
            (int) $linha['processo_id'],
            $linha['lote'],
            $linha['item'],
            (float) $linha['preco_ata'],
            $linha['descricao'],
            $linha['unidade'] ?? 'UN',
            (float) ($linha['quantidade'] ?? 1),
            (int) $linha['id']
        );
    }
}