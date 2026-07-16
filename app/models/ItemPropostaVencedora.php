<?php

require_once __DIR__ . '/Database.php';

class ItemPropostaVencedora
{
    public ?int $id;
    public int $licitacaoId;
    public int $itemId;
    public float $valorProposto;

    public function __construct(int $licitacaoId, int $itemId, float $valorProposto, ?int $id = null)
    {
        $this->id = $id;
        $this->licitacaoId = $licitacaoId;
        $this->itemId = $itemId;
        $this->valorProposto = $valorProposto;
    }

    /**
     * Grava (ou atualiza, se ja existir um valor pra esse item nessa
     * licitacao) o valor proposto - um valor por item, sempre o mais
     * recente digitado.
     */
    public function salvar(): int
    {
        $pdo = Database::getConnection();

        $existente = self::buscarPorLicitacaoEItem($this->licitacaoId, $this->itemId);

        if ($existente !== null) {
            $this->id = $existente->id;
            $stmt = $pdo->prepare('UPDATE itens_proposta_vencedora SET valor_proposto = :valor WHERE id = :id');
            $stmt->execute(['valor' => $this->valorProposto, 'id' => $this->id]);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO itens_proposta_vencedora (licitacao_id, item_id, valor_proposto)
                 VALUES (:licitacao_id, :item_id, :valor)'
            );
            $stmt->execute([
                'licitacao_id' => $this->licitacaoId,
                'item_id' => $this->itemId,
                'valor' => $this->valorProposto,
            ]);
            $this->id = (int) $pdo->lastInsertId();
        }

        return $this->id;
    }

    public static function buscarPorLicitacaoEItem(int $licitacaoId, int $itemId): ?ItemPropostaVencedora
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT * FROM itens_proposta_vencedora WHERE licitacao_id = :licitacao_id AND item_id = :item_id'
        );
        $stmt->execute(['licitacao_id' => $licitacaoId, 'item_id' => $itemId]);
        $linha = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$linha) {
            return null;
        }

        return self::fromArray($linha);
    }

    /**
     * @return array<int, ItemPropostaVencedora> indexado por item_id, pra
     * facilitar o acesso na hora de montar a tela lote a lote.
     */
    public static function buscarMapaPorLicitacao(int $licitacaoId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM itens_proposta_vencedora WHERE licitacao_id = :licitacao_id');
        $stmt->execute(['licitacao_id' => $licitacaoId]);

        $mapa = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
            $entidade = self::fromArray($linha);
            $mapa[$entidade->itemId] = $entidade;
        }

        return $mapa;
    }

    private static function fromArray(array $linha): ItemPropostaVencedora
    {
        return new ItemPropostaVencedora(
            (int) $linha['licitacao_id'],
            (int) $linha['item_id'],
            (float) $linha['valor_proposto'],
            (int) $linha['id']
        );
    }
}
