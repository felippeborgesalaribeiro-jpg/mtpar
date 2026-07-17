<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Empresa.php';

class LotePropostaVencedora
{
    public ?int $id;
    public int $licitacaoId;
    public int $loteId;
    public int $empresaVencedoraId;

    public function __construct(int $licitacaoId, int $loteId, int $empresaVencedoraId, ?int $id = null)
    {
        $this->id = $id;
        $this->licitacaoId = $licitacaoId;
        $this->loteId = $loteId;
        $this->empresaVencedoraId = $empresaVencedoraId;
    }

    /**
     * Grava (ou atualiza, se aquele lote dessa licitacao ja tinha uma
     * empresa vencedora definida) - uma empresa por lote, sempre a mais
     * recente escolhida.
     */
    public function salvar(): int
    {
        $pdo = Database::getConnection();

        $existente = self::buscarPorLicitacaoELote($this->licitacaoId, $this->loteId);

        if ($existente !== null) {
            $this->id = $existente->id;
            $stmt = $pdo->prepare('UPDATE lotes_proposta_vencedora SET empresa_vencedora_id = :empresa_id WHERE id = :id');
            $stmt->execute(['empresa_id' => $this->empresaVencedoraId, 'id' => $this->id]);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO lotes_proposta_vencedora (licitacao_id, lote_id, empresa_vencedora_id)
                 VALUES (:licitacao_id, :lote_id, :empresa_id)'
            );
            $stmt->execute([
                'licitacao_id' => $this->licitacaoId,
                'lote_id' => $this->loteId,
                'empresa_id' => $this->empresaVencedoraId,
            ]);
            $this->id = (int) $pdo->lastInsertId();
        }

        return $this->id;
    }

    public function buscarEmpresa(): ?Empresa
    {
        return Empresa::buscarPorId($this->empresaVencedoraId);
    }

    public static function buscarPorLicitacaoELote(int $licitacaoId, int $loteId): ?LotePropostaVencedora
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT * FROM lotes_proposta_vencedora WHERE licitacao_id = :licitacao_id AND lote_id = :lote_id'
        );
        $stmt->execute(['licitacao_id' => $licitacaoId, 'lote_id' => $loteId]);
        $linha = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$linha) {
            return null;
        }

        return self::fromArray($linha);
    }

    /**
     * @return array<int, LotePropostaVencedora> indexado por lote_id.
     */
    public static function buscarMapaPorLicitacao(int $licitacaoId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM lotes_proposta_vencedora WHERE licitacao_id = :licitacao_id');
        $stmt->execute(['licitacao_id' => $licitacaoId]);

        $mapa = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
            $entidade = self::fromArray($linha);
            $mapa[$entidade->loteId] = $entidade;
        }

        return $mapa;
    }

    private static function fromArray(array $linha): LotePropostaVencedora
    {
        return new LotePropostaVencedora(
            (int) $linha['licitacao_id'],
            (int) $linha['lote_id'],
            (int) $linha['empresa_vencedora_id'],
            (int) $linha['id']
        );
    }
}
