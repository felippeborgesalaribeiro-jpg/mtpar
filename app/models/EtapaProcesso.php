<?php

require_once __DIR__ . '/Database.php';

/**
 * Etapas intermediarias configuraveis do fluxo da Demanda (entre "EM
 * ANDAMENTO" e "CONCLUÍDO"/"CANCELADO", que continuam fixas no codigo -
 * ver Demanda::STATUS_EM_ANDAMENTO/STATUS_CONCLUIDO/STATUS_CANCELADO).
 * "ordem" controla a posicao no dropdown de status e no stepper visual da
 * tela do Processo; e mantida via moverParaCima()/moverParaBaixo(), nunca
 * digitada direto, pra nunca ter duas etapas com a mesma posicao.
 */
class EtapaProcesso
{
    public ?int $id;
    public string $nome;
    public int $ordem;
    public string $criadoEm;

    public function __construct(string $nome, int $ordem = 0, ?int $id = null, string $criadoEm = '')
    {
        $this->id = $id;
        $this->nome = $nome;
        $this->ordem = $ordem;
        $this->criadoEm = $criadoEm;
    }

    public function salvar(): int
    {
        $pdo = Database::getConnection();

        if ($this->id === null) {
            if ($this->ordem <= 0) {
                $proximaOrdem = (int) $pdo->query('SELECT COALESCE(MAX(ordem), 0) + 1 FROM etapas_processo')->fetchColumn();
                $this->ordem = $proximaOrdem;
            }

            $stmt = $pdo->prepare('INSERT INTO etapas_processo (nome, ordem) VALUES (:nome, :ordem)');
            $stmt->execute(['nome' => $this->nome, 'ordem' => $this->ordem]);
            $this->id = (int) $pdo->lastInsertId();
        } else {
            $stmt = $pdo->prepare('UPDATE etapas_processo SET nome = :nome, ordem = :ordem WHERE id = :id');
            $stmt->execute(['nome' => $this->nome, 'ordem' => $this->ordem, 'id' => $this->id]);
        }

        return $this->id;
    }

    public function excluir(): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM etapas_processo WHERE id = :id');
        $stmt->execute(['id' => $this->id]);
    }

    /**
     * Troca de posicao com a etapa imediatamente anterior na ordem. Sem
     * efeito se ja for a primeira.
     */
    public function moverParaCima(): void
    {
        $anterior = self::buscarVizinha($this->ordem, 'anterior');

        if ($anterior === null) {
            return;
        }

        self::trocarOrdem($this, $anterior);
    }

    /**
     * Troca de posicao com a proxima etapa na ordem. Sem efeito se ja for
     * a ultima.
     */
    public function moverParaBaixo(): void
    {
        $proxima = self::buscarVizinha($this->ordem, 'proxima');

        if ($proxima === null) {
            return;
        }

        self::trocarOrdem($this, $proxima);
    }

    private static function trocarOrdem(EtapaProcesso $a, EtapaProcesso $b): void
    {
        [$ordemA, $ordemB] = [$a->ordem, $b->ordem];
        $a->ordem = $ordemB;
        $b->ordem = $ordemA;
        $a->salvar();
        $b->salvar();
    }

    private static function buscarVizinha(int $ordem, string $direcao): ?EtapaProcesso
    {
        $pdo = Database::getConnection();
        $operador = $direcao === 'anterior' ? '<' : '>';
        $ordenacao = $direcao === 'anterior' ? 'DESC' : 'ASC';

        $stmt = $pdo->prepare("SELECT * FROM etapas_processo WHERE ordem {$operador} :ordem ORDER BY ordem {$ordenacao} LIMIT 1");
        $stmt->execute(['ordem' => $ordem]);
        $linha = $stmt->fetch(PDO::FETCH_ASSOC);

        return $linha ? self::fromArray($linha) : null;
    }

    public static function buscarPorId(int $id): ?EtapaProcesso
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM etapas_processo WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $linha = $stmt->fetch(PDO::FETCH_ASSOC);

        return $linha ? self::fromArray($linha) : null;
    }

    public static function buscarPorNome(string $nome): ?EtapaProcesso
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM etapas_processo WHERE nome = :nome');
        $stmt->execute(['nome' => trim($nome)]);
        $linha = $stmt->fetch(PDO::FETCH_ASSOC);

        return $linha ? self::fromArray($linha) : null;
    }

    public static function buscarTodas(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM etapas_processo ORDER BY ordem ASC');

        $etapas = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
            $etapas[] = self::fromArray($linha);
        }

        return $etapas;
    }

    private static function fromArray(array $linha): EtapaProcesso
    {
        return new EtapaProcesso(
            $linha['nome'],
            (int) $linha['ordem'],
            (int) $linha['id'],
            $linha['criado_em'] ?? ''
        );
    }
}
