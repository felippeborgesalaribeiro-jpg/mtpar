<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Item.php';

class Lote
{
    public ?int $id;
    public int $cotacaoId;
    public string $numero;

    public function __construct(int $cotacaoId, string $numero, ?int $id = null)
    {
        $this->id = $id;
        $this->cotacaoId = $cotacaoId;
        $this->numero = $numero;
    }

    public function salvar(): int
    {
        $pdo = Database::getConnection();

        if ($this->id === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO lotes (cotacao_id, numero) VALUES (:cotacao_id, :numero)'
            );
            $stmt->execute([
                'cotacao_id' => $this->cotacaoId,
                'numero' => $this->numero,
            ]);
            $this->id = (int) $pdo->lastInsertId();
        } else {
            $stmt = $pdo->prepare(
                'UPDATE lotes SET numero = :numero WHERE id = :id'
            );
            $stmt->execute([
                'numero' => $this->numero,
                'id' => $this->id,
            ]);
        }

        return $this->id;
    }

    public function excluir(): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM lotes WHERE id = :id');
        $stmt->execute(['id' => $this->id]);
    }

    public function buscarItens(): array
    {
        return Item::buscarPorLote($this->id);
    }

    public function proximoNumeroItem(): int
    {
        $itens = $this->buscarItens();

        if (count($itens) === 0) {
            return 1;
        }

        $numeros = array_map(fn($item) => $item->numero, $itens);

        return max($numeros) + 1;
    }

    public static function buscarPorId(int $id): ?Lote
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM lotes WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $linha = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$linha) {
            return null;
        }

        return new Lote((int) $linha['cotacao_id'], $linha['numero'], (int) $linha['id']);
    }

    public static function buscarPorCotacao(int $cotacaoId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM lotes WHERE cotacao_id = :cotacao_id ORDER BY numero ASC');
        $stmt->execute(['cotacao_id' => $cotacaoId]);

        $lotes = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
            $lotes[] = new Lote((int) $linha['cotacao_id'], $linha['numero'], (int) $linha['id']);
        }

        return $lotes;
    }

    public static function proximoNumeroLote(int $cotacaoId): string
    {
        $lotes = self::buscarPorCotacao($cotacaoId);

        if (count($lotes) === 0) {
            return '01';
        }

        $numeros = array_map(fn($lote) => (int) $lote->numero, $lotes);
        $proximo = max($numeros) + 1;

        return str_pad((string) $proximo, 2, '0', STR_PAD_LEFT);
    }
}