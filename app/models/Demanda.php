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
    ];

    public ?int $id;
    public string $numeroProcesso;
    public string $linkSigadoc;
    public string $setorDemandante;
    public string $dataRecebimento;
    public string $objeto;
    public ?int $servidorResponsavelId;
    public string $status;

    public function __construct(
        string $numeroProcesso,
        string $dataRecebimento,
        string $linkSigadoc = '',
        string $setorDemandante = '',
        string $objeto = '',
        ?int $servidorResponsavelId = null,
        string $status = self::STATUS_EM_ANDAMENTO,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->numeroProcesso = $numeroProcesso;
        $this->dataRecebimento = $dataRecebimento;
        $this->linkSigadoc = $linkSigadoc;
        $this->setorDemandante = $setorDemandante;
        $this->objeto = $objeto;
        $this->servidorResponsavelId = $servidorResponsavelId;
        $this->status = $status;
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
            'link_sigadoc' => $this->linkSigadoc,
            'setor_demandante' => $this->setorDemandante,
            'data_recebimento' => $this->dataRecebimento,
            'objeto' => $this->objeto,
            'servidor_responsavel_id' => $this->servidorResponsavelId,
            'status' => $this->status,
        ];
    }

    public function excluir(): void
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

    public static function buscarPorId(int $id): ?Demanda
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM demandas WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $linha = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$linha) {
            return null;
        }

        return self::fromArray($linha);
    }

    public static function buscarTodas(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM demandas ORDER BY data_recebimento DESC');

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
             AND status NOT IN ('" . self::STATUS_CONCLUIDO . "', '" . self::STATUS_CANCELADO . "')"
        );
        $stmt->execute(['servidor_id' => $servidorId]);

        return (int) $stmt->fetchColumn();
    }

    public static function contarEmAndamento(): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query(
            "SELECT COUNT(*) FROM demandas WHERE status NOT IN ('" . self::STATUS_CONCLUIDO . "', '" . self::STATUS_CANCELADO . "')"
        );

        return (int) $stmt->fetchColumn();
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
            (int) $linha['id']
        );
    }
}