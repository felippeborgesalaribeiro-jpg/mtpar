<?php

require_once __DIR__ . '/Database.php';

class SetorDemandante
{
    public ?int $id;
    public string $nome;
    public string $criadoEm;

    public function __construct(string $nome, ?int $id = null, string $criadoEm = '')
    {
        $this->id = $id;
        $this->nome = $nome;
        $this->criadoEm = $criadoEm;
    }

    public function salvar(): int
    {
        $pdo = Database::getConnection();

        if ($this->id === null) {
            $stmt = $pdo->prepare('INSERT INTO setores_demandantes (nome) VALUES (:nome)');
            $stmt->execute(['nome' => $this->nome]);
            $this->id = (int) $pdo->lastInsertId();
        } else {
            $stmt = $pdo->prepare('UPDATE setores_demandantes SET nome = :nome WHERE id = :id');
            $stmt->execute(['nome' => $this->nome, 'id' => $this->id]);
        }

        return $this->id;
    }

    public function excluir(): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM setores_demandantes WHERE id = :id');
        $stmt->execute(['id' => $this->id]);
    }

    public static function buscarPorId(int $id): ?SetorDemandante
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM setores_demandantes WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $linha = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$linha) {
            return null;
        }

        return self::fromArray($linha);
    }

    public static function buscarPorNome(string $nome): ?SetorDemandante
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM setores_demandantes WHERE nome = :nome');
        $stmt->execute(['nome' => trim($nome)]);
        $linha = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$linha) {
            return null;
        }

        return self::fromArray($linha);
    }

    public static function buscarTodos(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM setores_demandantes ORDER BY nome ASC');

        $setores = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
            $setores[] = self::fromArray($linha);
        }

        return $setores;
    }

    private static function fromArray(array $linha): SetorDemandante
    {
        return new SetorDemandante(
            $linha['nome'],
            (int) $linha['id'],
            $linha['criado_em'] ?? ''
        );
    }
}
