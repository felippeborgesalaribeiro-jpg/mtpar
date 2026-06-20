<?php

require_once __DIR__ . '/Database.php';

class Parametro
{
    public ?int $id;
    public string $nome;
    public bool $precoPublico;

    public function __construct(string $nome, bool $precoPublico = false, ?int $id = null)
    {
        $this->id = $id;
        $this->nome = $nome;
        $this->precoPublico = $precoPublico;
    }

    public function salvar(): int
    {
        $pdo = Database::getConnection();

        if ($this->id === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO parametros (nome, preco_publico) VALUES (:nome, :preco_publico)'
            );
            $stmt->execute([
                'nome' => $this->nome,
                'preco_publico' => $this->precoPublico ? 1 : 0,
            ]);
            $this->id = (int) $pdo->lastInsertId();
        } else {
            $stmt = $pdo->prepare(
                'UPDATE parametros SET nome = :nome, preco_publico = :preco_publico WHERE id = :id'
            );
            $stmt->execute([
                'nome' => $this->nome,
                'preco_publico' => $this->precoPublico ? 1 : 0,
                'id' => $this->id,
            ]);
        }

        return $this->id;
    }

    public function excluir(): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM parametros WHERE id = :id');
        $stmt->execute(['id' => $this->id]);
    }

    public static function buscarPorId(int $id): ?Parametro
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM parametros WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $linha = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$linha) {
            return null;
        }

        return self::fromArray($linha);
    }

    public static function buscarTodos(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM parametros ORDER BY nome ASC');

        $parametros = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
            $parametros[] = self::fromArray($linha);
        }

        return $parametros;
    }

    public static function buscarNomesPrecoPublico(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT nome FROM parametros WHERE preco_publico = 1');

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private static function fromArray(array $linha): Parametro
    {
        return new Parametro($linha['nome'], (bool) $linha['preco_publico'], (int) $linha['id']);
    }
}