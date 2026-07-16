<?php

require_once __DIR__ . '/Database.php';

class Demanda
{
    const STATUS_EM_ANDAMENTO = 'EM ANDAMENTO';
    const STATUS_CONCLUIDO = 'CONCLUÍDO';
    const STATUS_CANCELADO = 'CANCELADO';

    const STATUS_OPCOES = [
        'EM ANDAMENTO',
        'CONCLUÍDO',
        'ELABORAÇÃO DE TR',
        'ELABORAÇÃO DE PESQUISA DE PREÇO',
        'AVISO DE LICITAÇÃO',
        'AVISO DE DISPENSA DE LICITAÇÃO',
        'EMISSÃO DE PED RESERVA',
        'FASE DE HABILITAÇÃO',
        'CANCELADO',
        'ENVIADO PARA CONDES',
        'ENVIADO PARA PARECER JURÍDICO',
        'ENVIADO PARA PGE',
        'SANEAMENTO DE PROCESSO',
        'PUBLICADO',
    ];

    public ?int $id;
    public string $numeroProcesso;
    public string $linkSigadoc;
    public string $setorDemandante;
    public string $dataRecebimento;
    public string $objeto;
    public ?int $servidorResponsavelId;
    public string $status;
    public ?string $deletedAt;

    public function __construct(
        string $numeroProcesso,
        string $dataRecebimento,
        string $linkSigadoc = '',
        string $setorDemandante = '',
        string $objeto = '',
        ?int $servidorResponsavelId = null,
        string $status = self::STATUS_EM_ANDAMENTO,
        ?int $id = null,
        ?string $deletedAt = null
    ) {
        $this->id = $id;
        $this->numeroProcesso = $numeroProcesso;
        $this->dataRecebimento = $dataRecebimento;
        $this->linkSigadoc = $linkSigadoc;
        $this->setorDemandante = $setorDemandante;
        $this->objeto = $objeto;
        $this->servidorResponsavelId = $servidorResponsavelId;
        $this->status = $status;
        $this->deletedAt = $deletedAt;
    }

    public function salvar(): int
    {
        $pdo = Database::getConnection();

        if ($this->id === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO demandas (numero_processo, link_sigadoc, setor_demandante, data_recebimento, objeto, servidor_responsavel_id, status)
                 VALUES (:numero_processo, :link_sigadoc, :setor_demandante, :data_recebimento, :objeto, :servidor_responsavel_id, :status)'
            );
            $stmt->execute($this->paramsParaSalvar());
            $this->id = (int) $pdo->lastInsertId();
        } else {
            $stmt = $pdo->prepare(
                'UPDATE demandas SET numero_processo = :numero_processo, link_sigadoc = :link_sigadoc,
                 setor_demandante = :setor_demandante, data_recebimento = :data_recebimento, objeto = :objeto,
                 servidor_responsavel_id = :servidor_responsavel_id, status = :status
                 WHERE id = :id'
            );
            $stmt->execute(array_merge($this->paramsParaSalvar(), ['id' => $this->id]));
        }

        return $this->id;
    }

    private function paramsParaSalvar(): array
    {
        return [
            'numero_processo' => $this->numeroProcesso,
            'link_sigadoc'    => $this->linkSigadoc,
            'setor_demandante' => $this->setorDemandante,
            'data_recebimento' => $this->dataRecebimento,
            'objeto'           => $this->objeto,
            'servidor_responsavel_id' => $this->servidorResponsavelId,
            'status'           => $this->status,
        ];
    }

    public function excluir(): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE demandas SET deleted_at = datetime('now') WHERE id = :id");
        $stmt->execute(['id' => $this->id]);
        $this->deletedAt = date('Y-m-d H:i:s');
    }

    public function restaurar(): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE demandas SET deleted_at = NULL WHERE id = :id');
        $stmt->execute(['id' => $this->id]);
        $this->deletedAt = null;
    }

    public function excluirDefinitivamente(): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM demandas WHERE id = :id');
        $stmt->execute(['id' => $this->id]);
    }

    public function buscarServidorResponsavel(): ?Servidor
    {
        if ($this->servidorResponsavelId === null) {
            return null;
        }

        require_once __DIR__ . '/Servidor.php';

        return Servidor::buscarPorId($this->servidorResponsavelId);
    }

    public function buscarCotacaoVinculada(): ?Cotacao
    {
        require_once __DIR__ . '/Cotacao.php';

        return Cotacao::buscarPorDemandaId($this->id);
    }

    public function buscarVantajosidadeVinculada(): ?ProcessoVantajosidade
    {
        require_once __DIR__ . '/ProcessoVantajosidade.php';

        return ProcessoVantajosidade::buscarPorDemandaId($this->id);
    }

    public static function buscarPorId(int $id): ?Demanda
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM demandas WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $id]);
        $linha = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$linha) return null;

        return self::fromArray($linha);
    }

    public static function buscarExcluidaPorId(int $id): ?Demanda
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM demandas WHERE id = :id AND deleted_at IS NOT NULL');
        $stmt->execute(['id' => $id]);
        $linha = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$linha) return null;

        return self::fromArray($linha);
    }

    public static function buscarTodas(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM demandas WHERE deleted_at IS NULL ORDER BY data_recebimento DESC');

        $demandas = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
            $demandas[] = self::fromArray($linha);
        }

        return $demandas;
    }

    public static function buscarExcluidas(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM demandas WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC');

        $demandas = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
            $demandas[] = self::fromArray($linha);
        }

        return $demandas;
    }

    public static function buscarEmAndamentoSemVinculo(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query(
            "SELECT d.* FROM demandas d
             WHERE d.deleted_at IS NULL
             AND d.status NOT IN ('" . self::STATUS_CONCLUIDO . "', '" . self::STATUS_CANCELADO . "')
             AND d.id NOT IN (SELECT demanda_id FROM cotacoes WHERE demanda_id IS NOT NULL)
             AND d.id NOT IN (SELECT demanda_id FROM processos_vantajosidade WHERE demanda_id IS NOT NULL)
             ORDER BY d.data_recebimento DESC"
        );

        $demandas = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
            $demandas[] = self::fromArray($linha);
        }

        return $demandas;
    }

    public static function buscarPendentesPorServidor(int $servidorId, int $limite = 5): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT * FROM demandas
             WHERE servidor_responsavel_id = :servidor_id
             AND deleted_at IS NULL
             AND status NOT IN ('" . self::STATUS_CONCLUIDO . "', '" . self::STATUS_CANCELADO . "')
             ORDER BY data_recebimento DESC
             LIMIT :limite"
        );
        $stmt->bindValue(':servidor_id', $servidorId, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        $demandas = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
            $demandas[] = self::fromArray($linha);
        }

        return $demandas;
    }

    public static function contarPendentesPorServidor(int $servidorId): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM demandas
             WHERE servidor_responsavel_id = :servidor_id
             AND deleted_at IS NULL
             AND status NOT IN ('" . self::STATUS_CONCLUIDO . "', '" . self::STATUS_CANCELADO . "')"
        );
        $stmt->execute(['servidor_id' => $servidorId]);

        return (int) $stmt->fetchColumn();
    }

    public static function contarEmAndamento(): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query(
            "SELECT COUNT(*) FROM demandas
             WHERE deleted_at IS NULL
             AND status NOT IN ('" . self::STATUS_CONCLUIDO . "', '" . self::STATUS_CANCELADO . "')"
        );

        return (int) $stmt->fetchColumn();
    }

    public static function contarExcluidas(): int
    {
        $pdo = Database::getConnection();
        return (int) $pdo->query('SELECT COUNT(*) FROM demandas WHERE deleted_at IS NOT NULL')->fetchColumn();
    }

    private static function fromArray(array $linha): Demanda
    {
        return new Demanda(
            $linha['numero_processo'],
            $linha['data_recebimento'],
            $linha['link_sigadoc'] ?? '',
            $linha['setor_demandante'] ?? '',
            $linha['objeto'] ?? '',
            $linha['servidor_responsavel_id'] !== null ? (int) $linha['servidor_responsavel_id'] : null,
            $linha['status'] ?? self::STATUS_EM_ANDAMENTO,
            (int) $linha['id'],
            $linha['deleted_at'] ?? null
        );
    }
}