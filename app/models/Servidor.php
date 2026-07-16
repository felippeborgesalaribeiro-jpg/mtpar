<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/NivelAcesso.php';

class Servidor
{
    public ?int $id;
    public string $nome;
    public string $matricula;
    public string $cargo;
    public string $usuario;
    public string $senhaHash;
    public NivelAcesso $nivelAcesso;

    public function __construct(
        string $nome,
        string $matricula = '',
        string $cargo = '',
        string $usuario = '',
        string $senhaHash = '',
        NivelAcesso $nivelAcesso = NivelAcesso::Comum,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->nome = $nome;
        $this->matricula = $matricula;
        $this->cargo = $cargo;
        $this->usuario = $usuario;
        $this->senhaHash = $senhaHash;
        $this->nivelAcesso = $nivelAcesso;
    }

    public function salvar(): int
    {
        $pdo = Database::getConnection();

        if ($this->id === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO servidores (nome, matricula, cargo, usuario, senha_hash, nivel_acesso)
                 VALUES (:nome, :matricula, :cargo, :usuario, :senha_hash, :nivel_acesso)'
            );
            $stmt->execute([
                'nome' => $this->nome,
                'matricula' => $this->matricula,
                'cargo' => $this->cargo,
                'usuario' => $this->usuario,
                'senha_hash' => $this->senhaHash,
                'nivel_acesso' => $this->nivelAcesso->value,
            ]);
            $this->id = (int) $pdo->lastInsertId();
        } else {
            $stmt = $pdo->prepare(
                'UPDATE servidores SET nome = :nome, matricula = :matricula, cargo = :cargo,
                 usuario = :usuario, senha_hash = :senha_hash, nivel_acesso = :nivel_acesso
                 WHERE id = :id'
            );
            $stmt->execute([
                'nome' => $this->nome,
                'matricula' => $this->matricula,
                'cargo' => $this->cargo,
                'usuario' => $this->usuario,
                'senha_hash' => $this->senhaHash,
                'nivel_acesso' => $this->nivelAcesso->value,
                'id' => $this->id,
            ]);
        }

        return $this->id;
    }

    public function definirSenha(string $senhaTextoPuro): void
    {
        $this->senhaHash = password_hash($senhaTextoPuro, PASSWORD_DEFAULT);
    }

    public function verificarSenha(string $senhaTextoPuro): bool
    {
        return password_verify($senhaTextoPuro, $this->senhaHash);
    }

    public function resetarSenhaPadrao(): void
    {
        $this->definirSenha('123');
        $this->salvar();
    }

    public function ehAdmin(): bool
    {
        return $this->nivelAcesso === NivelAcesso::Admin;
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

    public static function buscarPorUsuario(string $usuario): ?Servidor
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM servidores WHERE usuario = :usuario');
        $stmt->execute(['usuario' => $usuario]);
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
        return new Servidor(
            $linha['nome'],
            $linha['matricula'],
            $linha['cargo'] ?? '',
            $linha['usuario'] ?? '',
            $linha['senha_hash'] ?? '',
            NivelAcesso::tryFrom($linha['nivel_acesso'] ?? '') ?? NivelAcesso::Comum,
            (int) $linha['id']
        );
    }
}