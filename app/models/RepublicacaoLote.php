<?php

require_once __DIR__ . '/Database.php';

class RepublicacaoLote
{
    public ?int $id;
    public int $licitacaoId;
    public int $loteAnteriorId;
    public int $loteNovoId;
    public int $cotacaoNovaId;
    public int $numeroRodada;
    public string $situacaoAnterior;
    public string $motivo;

    public function __construct(
        int $licitacaoId,
        int $loteAnteriorId,
        int $loteNovoId,
        int $cotacaoNovaId,
        int $numeroRodada,
        string $situacaoAnterior,
        string $motivo = '',
        ?int $id = null
    ) {
        $this->id = $id;
        $this->licitacaoId = $licitacaoId;
        $this->loteAnteriorId = $loteAnteriorId;
        $this->loteNovoId = $loteNovoId;
        $this->cotacaoNovaId = $cotacaoNovaId;
        $this->numeroRodada = $numeroRodada;
        $this->situacaoAnterior = $situacaoAnterior;
        $this->motivo = $motivo;
    }

    public function salvar(): int
    {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare(
            'INSERT INTO republicacoes_lote
             (licitacao_id, lote_anterior_id, lote_novo_id, cotacao_nova_id, numero_rodada, situacao_anterior, motivo)
             VALUES (:licitacao_id, :lote_anterior_id, :lote_novo_id, :cotacao_nova_id, :numero_rodada, :situacao_anterior, :motivo)'
        );
        $stmt->execute([
            'licitacao_id' => $this->licitacaoId,
            'lote_anterior_id' => $this->loteAnteriorId,
            'lote_novo_id' => $this->loteNovoId,
            'cotacao_nova_id' => $this->cotacaoNovaId,
            'numero_rodada' => $this->numeroRodada,
            'situacao_anterior' => $this->situacaoAnterior,
            'motivo' => $this->motivo,
        ]);
        $this->id = (int) $pdo->lastInsertId();

        return $this->id;
    }

    /**
     * A republicacao (se existir) que sucedeu esse lote especifico -
     * usada pra seguir a cadeia de rodadas ate achar o lote atual.
     */
    public static function buscarPorLoteAnterior(int $loteAnteriorId): ?RepublicacaoLote
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM republicacoes_lote WHERE lote_anterior_id = :lote_anterior_id');
        $stmt->execute(['lote_anterior_id' => $loteAnteriorId]);
        $linha = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$linha) {
            return null;
        }

        return self::fromArray($linha);
    }

    /**
     * A republicacao que deu origem a esse lote (se ele proprio ja for
     * fruto de uma rodada anterior) - usada pra descobrir em que numero de
     * rodada um lote esta antes de republica-lo de novo.
     */
    public static function buscarPorLoteNovo(int $loteNovoId): ?RepublicacaoLote
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM republicacoes_lote WHERE lote_novo_id = :lote_novo_id');
        $stmt->execute(['lote_novo_id' => $loteNovoId]);
        $linha = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$linha) {
            return null;
        }

        return self::fromArray($linha);
    }

    /**
     * @return array<int, RepublicacaoLote> historico completo de rodadas de
     * uma licitacao, na ordem em que aconteceram - usado pra exibir o
     * historico de tentativas de um lote.
     */
    public static function buscarHistoricoPorLicitacao(int $licitacaoId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT * FROM republicacoes_lote WHERE licitacao_id = :licitacao_id ORDER BY numero_rodada ASC'
        );
        $stmt->execute(['licitacao_id' => $licitacaoId]);

        $historico = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
            $historico[] = self::fromArray($linha);
        }

        return $historico;
    }

    private static function fromArray(array $linha): RepublicacaoLote
    {
        return new RepublicacaoLote(
            (int) $linha['licitacao_id'],
            (int) $linha['lote_anterior_id'],
            (int) $linha['lote_novo_id'],
            (int) $linha['cotacao_nova_id'],
            (int) $linha['numero_rodada'],
            $linha['situacao_anterior'],
            $linha['motivo'] ?? '',
            (int) $linha['id']
        );
    }
}
