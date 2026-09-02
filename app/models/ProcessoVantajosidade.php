<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/ItemVantajosidade.php';
require_once __DIR__ . '/Servidor.php';

class ProcessoVantajosidade
{
    const STATUS_EM_ANDAMENTO = 'EM_ANDAMENTO';
    const STATUS_FINALIZADO   = 'FINALIZADO';

    const TIPO_ATA               = 'ATA';
    const TIPO_CONTRATO_ADITIVO  = 'CONTRATO_ADITIVO';
    const LIMITE_LEGAL_ADITIVO_PERCENTUAL = 25.0;

    public ?int $id;
    public string $numeroAta;
    public string $orgaoGerenciador;
    public string $objeto;
    public int $servidorId;
    public string $status;
    public ?int $demandaId;
    public ?string $deletedAt;
    public string $tipo;
    public string $numeroContrato;
    public ?float $valorTotalObjeto;

    public function __construct(
        string $numeroAta,
        string $orgaoGerenciador,
        string $objeto,
        int $servidorId,
        string $status = self::STATUS_EM_ANDAMENTO,
        ?int $id = null,
        ?int $demandaId = null,
        ?string $deletedAt = null,
        string $tipo = self::TIPO_ATA,
        string $numeroContrato = '',
        ?float $valorTotalObjeto = null
    ) {
        $this->id               = $id;
        $this->numeroAta        = $numeroAta;
        $this->orgaoGerenciador = $orgaoGerenciador;
        $this->objeto           = $objeto;
        $this->servidorId       = $servidorId;
        $this->status           = $status;
        $this->demandaId        = $demandaId;
        $this->deletedAt        = $deletedAt;
        $this->tipo             = $tipo;
        $this->numeroContrato   = $numeroContrato;
        $this->valorTotalObjeto = $valorTotalObjeto;
    }

    public function ehContratoAditivo(): bool
    {
        return $this->tipo === self::TIPO_CONTRATO_ADITIVO;
    }

    /**
     * Soma do valor de referência (preco_ata * quantidade) de todos os
     * itens do processo - no caso de aditivo de contrato, representa o
     * valor total que está sendo pleiteado no aditivo.
     */
    public function calcularValorTotalItens(): float
    {
        $total = 0.0;
        foreach ($this->buscarItens() as $item) {
            $total += $item->precoAta * $item->quantidade;
        }

        return $total;
    }

    /**
     * Percentual que o aditivo representa em relação ao valor total do
     * objeto do contrato. Retorna null quando não se aplica (tipo ATA ou
     * sem valor total do objeto informado).
     */
    public function calcularIndiceAditivo(): ?float
    {
        if (!$this->ehContratoAditivo() || $this->valorTotalObjeto === null || $this->valorTotalObjeto == 0.0) {
            return null;
        }

        return ($this->calcularValorTotalItens() / $this->valorTotalObjeto) * 100;
    }

    public function indiceAditivoDentroDoLimiteLegal(): ?bool
    {
        $indice = $this->calcularIndiceAditivo();

        return $indice === null ? null : $indice <= self::LIMITE_LEGAL_ADITIVO_PERCENTUAL;
    }

    public function salvar(): int
    {
        $pdo = Database::getConnection();

        if ($this->id === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO processos_vantajosidade (numero_ata, orgao_gerenciador, objeto, servidor_id, status, demanda_id, tipo, numero_contrato, valor_total_objeto)
                 VALUES (:numero_ata, :orgao_gerenciador, :objeto, :servidor_id, :status, :demanda_id, :tipo, :numero_contrato, :valor_total_objeto)'
            );
            $stmt->execute($this->paramsParaSalvar());
            $this->id = (int) $pdo->lastInsertId();
        } else {
            $stmt = $pdo->prepare(
                'UPDATE processos_vantajosidade SET numero_ata = :numero_ata, orgao_gerenciador = :orgao_gerenciador,
                 objeto = :objeto, servidor_id = :servidor_id, status = :status, demanda_id = :demanda_id,
                 tipo = :tipo, numero_contrato = :numero_contrato, valor_total_objeto = :valor_total_objeto WHERE id = :id'
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
            'tipo'              => $this->tipo,
            'numero_contrato'   => $this->numeroContrato,
            'valor_total_objeto' => $this->valorTotalObjeto,
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

    /**
     * Devolve um mapa {demanda_id => ProcessoVantajosidade} das vantajosidades
     * vinculadas a cada uma das demandas pedidas. Usado pelas listagens pra
     * evitar N+1. Demandas sem vantajosidade ficam ausentes do mapa.
     *
     * @param array<int, int> $demandaIds
     * @return array<int, ProcessoVantajosidade>
     */
    public static function mapaPorDemandaIds(array $demandaIds): array
    {
        $demandaIds = array_values(array_unique(array_filter($demandaIds, fn($v) => $v !== null)));
        if (count($demandaIds) === 0) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($demandaIds), '?'));
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT * FROM processos_vantajosidade
             WHERE demanda_id IN ($placeholders) AND deleted_at IS NULL"
        );
        $stmt->execute($demandaIds);

        $mapa = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
            $processo = self::fromArray($linha);
            $mapa[$processo->demandaId] ??= $processo;
        }

        return $mapa;
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
            $linha['deleted_at'] ?? null,
            $linha['tipo'] ?? self::TIPO_ATA,
            $linha['numero_contrato'] ?? '',
            isset($linha['valor_total_objeto']) && $linha['valor_total_objeto'] !== null ? (float) $linha['valor_total_objeto'] : null
        );
    }
}