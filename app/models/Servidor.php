<?php

require_once __DIR__ . '/Database.php';

class Servidor
{
    public ?int $id;
    public string $nome;
    public string $matricula;
    public string $cargo;

    public function __construct(string $nome, string $matricula = '', string $cargo = '', ?int $id = null)
    {
        $this->id = $id;
        $this->nome = $nome;
        $this->matricula = $matricula;
        $this->cargo = $cargo;
    }

    public function salvar(): int
    {
        $pdo = Database::getConnection();

        if ($this->id === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO servidores (nome, matricula, cargo) VALUES (:nome, :matricula, :cargo)'
            );
            $stmt->execute([
                'nome' => $this->nome,
                'matricula' => $this->matricula,
                'cargo' => $this->cargo,
            ]);
            $this->id = (int) $pdo->lastInsertId();
        } else {
            $stmt = $pdo->prepare(
                'UPDATE servidores SET nome = :nome, matricula = :matricula, cargo = :cargo WHERE id = :id'
            );
            $stmt->execute([
                'nome' => $this->nome,
                'matricula' => $this->matricula,
                'cargo' => $this->cargo,
                'id' => $this->id,
            ]);
        }

        return $this->id;
    }

    public function excluir(): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM servidores WHERE id = :id');
        $stmt->execute(['id' => $this->id]);
    }

    public static function buscarPorId(int $id): ?Servidor
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM servidores WHERE id = :id');
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
        $stmt = $pdo->query('SELECT * FROM servidores ORDER BY nome ASC');

        $servidores = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
            $servidores[] = self::fromArray($linha);
        }

        return $servidores;
    }

    private static function fromArray(array $linha): Servidor
    {
        return new Servidor($linha['nome'], $linha['matricula'], $linha['cargo'] ?? '', (int) $linha['id']);
    }
}