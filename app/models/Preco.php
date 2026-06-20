<?php

require_once __DIR__ . '/Database.php';

class Preco
{
    public ?int $id;
    public int $itemId;
    public string $parametro;
    public float $valor;
    public string $fonte;

    public function __construct(int $itemId, float $valor, string $parametro = '', string $fonte = '', ?int $id = null)
    {
        $this->id = $id;
        $this->itemId = $itemId;
        $this->valor = $valor;
        $this->parametro = $parametro;
        $this->fonte = $fonte;
    }

    public function salvar(): int
    {
        $pdo = Database::getConnection();

        if ($this->id === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO precos (item_id, parametro, valor, fonte) VALUES (:item_id, :parametro, :valor, :fonte)'
            );
            $stmt->execute([
                'item_id' => $this->itemId,
                'parametro' => $this->parametro,
                'valor' => $this->valor,
                'fonte' => $this->fonte,
            ]);
            $this->id = (int) $pdo->lastInsertId();
        } else {
            $stmt = $pdo->prepare(
                'UPDATE precos SET parametro = :parametro, valor = :valor, fonte = :fonte WHERE id = :id'
            );
            $stmt->execute([
                'parametro' => $this->parametro,
                'valor' => $this->valor,
                'fonte' => $this->fonte,
                'id' => $this->id,
            ]);
        }

        return $this->id;
    }

    public function excluir(): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM precos WHERE id = :id');
        $stmt->execute(['id' => $this->id]);
    }

    public static function buscarPorId(int $id): ?Preco
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM precos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $linha = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$linha) {
            return null;
        }

        return new Preco(
            (int) $linha['item_id'],
            (float) $linha['valor'],
            $linha['parametro'],
            $linha['fonte'],
            (int) $linha['id']
        );
    }

    public static function buscarPorItem(int $itemId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM precos WHERE item_id = :item_id ORDER BY id ASC');
        $stmt->execute(['item_id' => $itemId]);

        $precos = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
            $precos[] = new Preco(
                (int) $linha['item_id'],
                (float) $linha['valor'],
                $linha['parametro'],
                $linha['fonte'],
                (int) $linha['id']
            );
        }

        return $precos;
    }
}