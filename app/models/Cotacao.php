<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Lote.php';
require_once __DIR__ . '/Servidor.php';

class Cotacao
{
    const STATUS_EM_ANDAMENTO = 'EM_ANDAMENTO';
    const STATUS_FINALIZADA = 'FINALIZADA';

    public ?int $id;
    public string $numeroProcesso;
    public string $orgaoSetor;
    public string $procedimento;
    public string $tipoJulgamento;
    public string $objeto;
    public int $servidorId;
    public string $criterioConsolidacao;
    public string $status;
    public string $criadoEm;

    public function __construct(
        string $numeroProcesso,
        string $orgaoSetor,
        string $procedimento,
        string $tipoJulgamento,
        string $objeto,
        int $servidorId,
        string $criterioConsolidacao = AnalisePrecos::CRITERIO_MEDIANA,
        string $status = self::STATUS_EM_ANDAMENTO,
        ?int $id = null,
        string $criadoEm = ''
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
    }

    public function salvar(): int
    {
        $pdo = Database::getConnection();

        if ($this->id === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO cotacoes (numero_processo, orgao_setor, procedimento, tipo_julgamento, objeto, servidor_id, criterio_consolidacao, status)
                 VALUES (:numero_processo, :orgao_setor, :procedimento, :tipo_julgamento, :objeto, :servidor_id, :criterio_consolidacao, :status)'
            );
            $stmt->execute([
                'numero_processo' => $this->numeroProcesso,
                'orgao_setor' => $this->orgaoSetor,
                'procedimento' => $this->procedimento,
                'tipo_julgamento' => $this->tipoJulgamento,
                'objeto' => $this->objeto,
                'servidor_id' => $this->servidorId,
                'criterio_consolidacao' => $this->criterioConsolidacao,
                'status' => $this->status,
            ]);
            $this->id = (int) $pdo->lastInsertId();
        } else {
            $stmt = $pdo->prepare(
                'UPDATE cotacoes SET numero_processo = :numero_processo, orgao_setor = :orgao_setor,
                 procedimento = :procedimento, tipo_julgamento = :tipo_julgamento, objeto = :objeto,
                 servidor_id = :servidor_id, criterio_consolidacao = :criterio_consolidacao, status = :status
                 WHERE id = :id'
            );
            $stmt->execute([
                'numero_processo' => $this->numeroProcesso,
                'orgao_setor' => $this->orgaoSetor,
                'procedimento' => $this->procedimento,
                'tipo_julgamento' => $this->tipoJulgamento,
                'objeto' => $this->objeto,
                'servidor_id' => $this->servidorId,
                'criterio_consolidacao' => $this->criterioConsolidacao,
                'status' => $this->status,
                'id' => $this->id,
            ]);
        }

        return $this->id;
    }

    public function excluir(): void
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

    public function contarItens(): int
    {
        $total = 0;
        foreach ($this->buscarLotes() as $lote) {
            $total += count($lote->buscarItens());
        }

        return $total;
    }

    public static function buscarPorId(int $id): ?Cotacao
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM cotacoes WHERE id = :id');
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
        $stmt = $pdo->query('SELECT * FROM cotacoes ORDER BY id DESC');

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
            'SELECT * FROM cotacoes WHERE servidor_id = :servidor_id AND status = :status ORDER BY id DESC'
        );
        $stmt->execute(['servidor_id' => $servidorId, 'status' => self::STATUS_EM_ANDAMENTO]);

        $cotacoes = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
            $cotacoes[] = self::fromArray($linha);
        }

        return $cotacoes;
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
            $linha['status'],
            (int) $linha['id'],
            $linha['criado_em'] ?? ''
        );
    }
}