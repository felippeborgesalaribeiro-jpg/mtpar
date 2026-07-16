<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/ItemVantajosidade.php';
require_once __DIR__ . '/Servidor.php';

class ProcessoVantajosidade
{
    const STATUS_EM_ANDAMENTO = 'EM_ANDAMENTO';
    const STATUS_FINALIZADO   = 'FINALIZADO';

    public ?int $id;
    public string $numeroAta;
    public string $orgaoGerenciador;
    public string $objeto;
    public int $servidorId;
    public string $status;
    public ?int $demandaId;
    public ?string $deletedAt;

    public function __construct(
        string $numeroAta,
        string $orgaoGerenciador,
        string $objeto,
        int $servidorId,
        string $status = self::STATUS_EM_ANDAMENTO,
        ?int $id = null,
        ?int $demandaId = null,
        ?string $deletedAt = null
    ) {
        $this->id               = $id;
        $this->numeroAta        = $numeroAta;
        $this->orgaoGerenciador = $orgaoGerenciador;
        $this->objeto           = $objeto;
        $this->servidorId       = $servidorId;
        $this->status           = $status;
        $this->demandaId        = $demandaId;
        $this->deletedAt        = $deletedAt;
    }

    public function salvar(): int
    {
        $pdo = Database::getConnection();

        if ($this->id === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO processos_vantajosidade (numero_ata, orgao_gerenciador, objeto, servidor_id, status, demanda_id)
                 VALUES (:numero_ata, :orgao_gerenciador, :objeto, :servidor_id, :status, :demanda_id)'
            );
            $stmt->execute($this->paramsParaSalvar());
            $this->id = (int) $pdo->lastInsertId();
        } else {
            $stmt = $pdo->prepare(
                'UPDATE processos_vantajosidade SET numero_ata = :numero_ata, orgao_gerenciador = :orgao_gerenciador,
                 objeto = :objeto, servidor_id = :servidor_id, status = :status, demanda_id = :demanda_id WHERE id = :id'
            );
            $stmt->execute(array_merge($this->paramsParaSalvar(), ['id' => $this->id]));
        }

        return $this->id;
    }

    private function paramsParaSalvar(): array
    {
        return [
            'numero_ata'        => $this->numeroAta,
            'orgao_gerenciador' => $this->orgaoGerenciador,
            'objeto'            => $this->objeto,
            'servidor_id'       => $this->servidorId,
            'status'            => $this->status,
            'demanda_id'        => $this->demandaId,
        ];
    }

    public function excluir(): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE processos_vantajosidade SET deleted_at = datetime('now') WHERE id = :id");
        $stmt->execute(['id' => $this->id]);
        $this->deletedAt = date('Y-m-d H:i:s');
    }

    public function restaurar(): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE processos_vantajosidade SET deleted_at = NULL WHERE id = :id');
        $stmt->execute(['id' => $this->id]);
        $this->deletedAt = null;
    }

    public function excluirDefinitivamente(): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM processos_vantajosidade WHERE id = :id');
        $stmt->execute(['id' => $this->id]);
    }

    public function buscarItens(): array
    {
        return ItemVantajosidade::buscarPorProcesso($this->id);
    }

    public function buscarServidor(): ?Servidor
    {
        return Servidor::buscarPorId($this->servidorId);
    }

    public function buscarDemandaVinculada(): ?Demanda
    {
        if ($this->demandaId === null) return null;

        require_once __DIR__ . '/Demanda.php';

        return Demanda::buscarPorId($this->demandaId);
    }

    public static function buscarPorId(int $id): ?ProcessoVantajosidade
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM processos_vantajosidade WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $id]);
        $linha = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$linha) return null;

        return self::fromArray($linha);
    }

    public static function buscarExcluidaPorId(int $id): ?ProcessoVantajosidade
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM processos_vantajosidade WHERE id = :id AND deleted_at IS NOT NULL');
        $stmt->execute(['id' => $id]);
        $linha = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$linha) return null;

        return self::fromArray($linha);
    }

    public static function buscarPorDemandaId(int $demandaId): ?ProcessoVantajosidade
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM processos_vantajosidade WHERE demanda_id = :demanda_id AND deleted_at IS NULL LIMIT 1');
        $stmt->execute(['demanda_id' => $demandaId]);
        $linha = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$linha) return null;

        return self::fromArray($linha);
    }

    public static function buscarTodos(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM processos_vantajosidade WHERE deleted_at IS NULL ORDER BY id DESC');

        $processos = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
            $processos[] = self::fromArray($linha);
        }

        return $processos;
    }

    public static function buscarExcluidos(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM processos_vantajosidade WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC');

        $processos = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
            $processos[] = self::fromArray($linha);
        }

        return $processos;
    }

    public static function contarExcluidos(): int
    {
        $pdo = Database::getConnection();
        return (int) $pdo->query('SELECT COUNT(*) FROM processos_vantajosidade WHERE deleted_at IS NOT NULL')->fetchColumn();
    }

    private static function fromArray(array $linha): ProcessoVantajosidade
    {
        return new ProcessoVantajosidade(
            $linha['numero_ata'],
            $linha['orgao_gerenciador'],
            $linha['objeto'],
            (int) $linha['servidor_id'],
            $linha['status'],
            (int) $linha['id'],
            $linha['demanda_id'] !== null ? (int) $linha['demanda_id'] : null,
            $linha['deleted_at'] ?? null
        );
    }
}