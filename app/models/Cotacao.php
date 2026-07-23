<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Lote.php';
require_once __DIR__ . '/Servidor.php';
require_once __DIR__ . '/StatusCotacao.php';
require_once __DIR__ . '/Parametro.php';

class Cotacao
{
    public ?int $id;
    public string $numeroProcesso;
    public string $orgaoSetor;
    public string $procedimento;
    public string $tipoJulgamento;
    public string $objeto;
    public int $servidorId;
    public string $criterioConsolidacao;
    public StatusCotacao $status;
    public string $criadoEm;
    public ?int $demandaId;
    public ?string $deletedAt;

    public function __construct(
        string $numeroProcesso,
        string $orgaoSetor,
        string $procedimento,
        string $tipoJulgamento,
        string $objeto,
        int $servidorId,
        string $criterioConsolidacao = AnalisePrecos::CRITERIO_MEDIANA,
        StatusCotacao $status = StatusCotacao::EmAndamento,
        ?int $id = null,
        string $criadoEm = '',
        ?int $demandaId = null,
        ?string $deletedAt = null
    ) {
        $this->id = $id;
        $this->numeroProcesso = $numeroProcesso;
        $this->orgaoSetor = $orgaoSetor;
        $this->procedimento = $procedimento;
        $this->tipoJulgamento = $tipoJulgamento;
        $this->objeto = $objeto;
        $this->servidorId = $servidorId;
        $this->criterioConsolidacao = $criterioConsolidacao;
        $this->status = $status;
        $this->criadoEm = $criadoEm;
        $this->demandaId = $demandaId;
        $this->deletedAt = $deletedAt;
    }

    public function salvar(): int
    {
        $pdo = Database::getConnection();

        if ($this->id === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO cotacoes (numero_processo, orgao_setor, procedimento, tipo_julgamento, objeto, servidor_id, criterio_consolidacao, status, demanda_id)
                 VALUES (:numero_processo, :orgao_setor, :procedimento, :tipo_julgamento, :objeto, :servidor_id, :criterio_consolidacao, :status, :demanda_id)'
            );
            $stmt->execute([
                'numero_processo'      => $this->numeroProcesso,
                'orgao_setor'          => $this->orgaoSetor,
                'procedimento'         => $this->procedimento,
                'tipo_julgamento'      => $this->tipoJulgamento,
                'objeto'               => $this->objeto,
                'servidor_id'          => $this->servidorId,
                'criterio_consolidacao' => $this->criterioConsolidacao,
                'status'               => $this->status->value,
                'demanda_id'           => $this->demandaId,
            ]);
            $this->id = (int) $pdo->lastInsertId();
        } else {
            $stmt = $pdo->prepare(
                'UPDATE cotacoes SET numero_processo = :numero_processo, orgao_setor = :orgao_setor,
                 procedimento = :procedimento, tipo_julgamento = :tipo_julgamento, objeto = :objeto,
                 servidor_id = :servidor_id, criterio_consolidacao = :criterio_consolidacao, status = :status,
                 demanda_id = :demanda_id
                 WHERE id = :id'
            );
            $stmt->execute([
                'numero_processo'      => $this->numeroProcesso,
                'orgao_setor'          => $this->orgaoSetor,
                'procedimento'         => $this->procedimento,
                'tipo_julgamento'      => $this->tipoJulgamento,
                'objeto'               => $this->objeto,
                'servidor_id'          => $this->servidorId,
                'criterio_consolidacao' => $this->criterioConsolidacao,
                'status'               => $this->status->value,
                'demanda_id'           => $this->demandaId,
                'id'                   => $this->id,
            ]);
        }

        return $this->id;
    }

    public function excluir(): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE cotacoes SET deleted_at = datetime('now') WHERE id = :id");
        $stmt->execute(['id' => $this->id]);
        $this->deletedAt = date('Y-m-d H:i:s');
    }

    public function restaurar(): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE cotacoes SET deleted_at = NULL WHERE id = :id');
        $stmt->execute(['id' => $this->id]);
        $this->deletedAt = null;
    }

    public function excluirDefinitivamente(): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM cotacoes WHERE id = :id');
        $stmt->execute(['id' => $this->id]);
    }

    public function buscarLotes(): array
    {
        return Lote::buscarPorCotacao($this->id);
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

    public function contarItens(): int
    {
        $total = 0;
        foreach ($this->buscarLotes() as $lote) {
            $total += count($lote->buscarItens());
        }

        return $total;
    }

    /**
     * Mesmo calculo usado no mapa comparativo: soma, por lote, o valor de
     * referencia de cada item (segundo o criterio de consolidacao da cotacao)
     * multiplicado pela quantidade.
     */
    public function calcularValorTotal(): float
    {
        $valorTotal = 0.0;
        $parametrosPrecoPublico = Parametro::buscarNomesPrecoPublico();

        foreach ($this->buscarLotes() as $lote) {
            foreach ($lote->buscarItens() as $item) {
                $resultado = $item->analisar($this->criterioConsolidacao, $parametrosPrecoPublico);
                $valorReferencia = $resultado['valor_referencia'] ?? 0;
                $valorTotal += $valorReferencia * $item->quantidade;
            }
        }

        return $valorTotal;
    }

    public static function buscarPorId(int $id): ?Cotacao
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM cotacoes WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $id]);
        $linha = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$linha) return null;

        return self::fromArray($linha);
    }

    public static function buscarExcluidaPorId(int $id): ?Cotacao
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM cotacoes WHERE id = :id AND deleted_at IS NOT NULL');
        $stmt->execute(['id' => $id]);
        $linha = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$linha) return null;

        return self::fromArray($linha);
    }

    public static function buscarPorDemandaId(int $demandaId): ?Cotacao
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM cotacoes WHERE demanda_id = :demanda_id AND deleted_at IS NULL LIMIT 1');
        $stmt->execute(['demanda_id' => $demandaId]);
        $linha = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$linha) return null;

        return self::fromArray($linha);
    }

    public static function buscarTodas(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM cotacoes WHERE deleted_at IS NULL ORDER BY id DESC');

        $cotacoes = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
            $cotacoes[] = self::fromArray($linha);
        }

        return $cotacoes;
    }

    public static function buscarExcluidas(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM cotacoes WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC');

        $cotacoes = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
            $cotacoes[] = self::fromArray($linha);
        }

        return $cotacoes;
    }

    public static function buscarEmAndamentoPorServidor(int $servidorId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT * FROM cotacoes WHERE servidor_id = :servidor_id AND status = :status AND deleted_at IS NULL ORDER BY id DESC'
        );
        $stmt->execute(['servidor_id' => $servidorId, 'status' => StatusCotacao::EmAndamento->value]);

        $cotacoes = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
            $cotacoes[] = self::fromArray($linha);
        }

        return $cotacoes;
    }

    public static function contarExcluidas(): int
    {
        $pdo = Database::getConnection();
        return (int) $pdo->query('SELECT COUNT(*) FROM cotacoes WHERE deleted_at IS NOT NULL')->fetchColumn();
    }

    private static function fromArray(array $linha): Cotacao
    {
        return new Cotacao(
            $linha['numero_processo'],
            $linha['orgao_setor'],
            $linha['procedimento'],
            $linha['tipo_julgamento'],
            $linha['objeto'],
            (int) $linha['servidor_id'],
            $linha['criterio_consolidacao'],
            StatusCotacao::tryFrom($linha['status']) ?? StatusCotacao::EmAndamento,
            (int) $linha['id'],
            $linha['criado_em'] ?? '',
            $linha['demanda_id'] !== null ? (int) $linha['demanda_id'] : null,
            $linha['deleted_at'] ?? null
        );
    }
}