<?php

require_once __DIR__ . '/Database.php';

class SituacaoLote
{
    const FRACASSADO = 'FRACASSADO';
    const DESERTO = 'DESERTO';

    public ?int $id;
    public int $licitacaoId;
    public int $loteId;
    public string $situacao;
    public string $motivo;
    public string $dataSituacao;

    public function __construct(
        int $licitacaoId,
        int $loteId,
        string $situacao,
        string $motivo = '',
        string $dataSituacao = '',
        ?int $id = null
    ) {
        $this->id = $id;
        $this->licitacaoId = $licitacaoId;
        $this->loteId = $loteId;
        $this->situacao = $situacao;
        $this->motivo = $motivo;
        $this->dataSituacao = $dataSituacao !== '' ? $dataSituacao : date('Y-m-d');
    }

    /**
     * Grava (ou atualiza, se aquele lote dessa licitacao ja tinha uma
     * situacao registrada) - uma situacao por lote, sempre a mais recente.
     */
    public function salvar(): int
    {
        $pdo = Database::getConnection();

        $existente = self::buscarPorLicitacaoELote($this->licitacaoId, $this->loteId);

        if ($existente !== null) {
            $this->id = $existente->id;
            $stmt = $pdo->prepare(
                'UPDATE situacoes_lote SET situacao = :situacao, motivo = :motivo, data_situacao = :data
                 WHERE id = :id'
            );
            $stmt->execute([
                'situacao' => $this->situacao,
                'motivo' => $this->motivo,
                'data' => $this->dataSituacao,
                'id' => $this->id,
            ]);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO situacoes_lote (licitacao_id, lote_id, situacao, motivo, data_situacao)
                 VALUES (:licitacao_id, :lote_id, :situacao, :motivo, :data)'
            );
            $stmt->execute([
                'licitacao_id' => $this->licitacaoId,
                'lote_id' => $this->loteId,
                'situacao' => $this->situacao,
                'motivo' => $this->motivo,
                'data' => $this->dataSituacao,
            ]);
            $this->id = (int) $pdo->lastInsertId();
        }

        return $this->id;
    }

    public static function buscarPorLicitacaoELote(int $licitacaoId, int $loteId): ?SituacaoLote
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT * FROM situacoes_lote WHERE licitacao_id = :licitacao_id AND lote_id = :lote_id'
        );
        $stmt->execute(['licitacao_id' => $licitacaoId, 'lote_id' => $loteId]);
        $linha = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$linha) {
            return null;
        }

        return self::fromArray($linha);
    }

    /**
     * @return array<int, SituacaoLote> indexado por lote_id.
     */
    public static function buscarMapaPorLicitacao(int $licitacaoId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM situacoes_lote WHERE licitacao_id = :licitacao_id');
        $stmt->execute(['licitacao_id' => $licitacaoId]);

        $mapa = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
            $entidade = self::fromArray($linha);
            $mapa[$entidade->loteId] = $entidade;
        }

        return $mapa;
    }

    private static function fromArray(array $linha): SituacaoLote
    {
        return new SituacaoLote(
            (int) $linha['licitacao_id'],
            (int) $linha['lote_id'],
            $linha['situacao'],
            $linha['motivo'] ?? '',
            $linha['data_situacao'],
            (int) $linha['id']
        );
    }
}
