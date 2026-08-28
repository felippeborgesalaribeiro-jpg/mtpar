<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Preco.php';
require_once __DIR__ . '/AnalisePrecos.php';
require_once __DIR__ . '/Parametro.php';

class Item
{
    public ?int $id;
    public int $loteId;
    public int $numero;
    public string $descricao;
    public string $unidade;
    public float $quantidade;

    public function __construct(
        int $loteId,
        int $numero,
        string $descricao = '',
        string $unidade = 'UN',
        float $quantidade = 1,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->loteId = $loteId;
        $this->numero = $numero;
        $this->descricao = $descricao;
        $this->unidade = $unidade;
        $this->quantidade = $quantidade;
    }

    public function salvar(): int
    {
        $pdo = Database::getConnection();

        if ($this->id === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO itens (lote_id, numero, descricao, unidade, quantidade)
                 VALUES (:lote_id, :numero, :descricao, :unidade, :quantidade)'
            );
            $stmt->execute([
                'lote_id' => $this->loteId,
                'numero' => $this->numero,
                'descricao' => $this->descricao,
                'unidade' => $this->unidade,
                'quantidade' => $this->quantidade,
            ]);
            $this->id = (int) $pdo->lastInsertId();
        } else {
            $stmt = $pdo->prepare(
                'UPDATE itens SET descricao = :descricao, unidade = :unidade, quantidade = :quantidade WHERE id = :id'
            );
            $stmt->execute([
                'descricao' => $this->descricao,
                'unidade' => $this->unidade,
                'quantidade' => $this->quantidade,
                'id' => $this->id,
            ]);
        }

        return $this->id;
    }

    public function excluir(): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM itens WHERE id = :id');
        $stmt->execute(['id' => $this->id]);
    }

    /**
     * Move o item para outro lote (reorganizacao de lotes ja preenchidos).
     * So muda o vinculo do item - os precos ja cadastrados (tabela precos,
     * ligada por item_id) continuam intactos, nao precisa recadastrar nada.
     */
    public function moverParaLote(int $novoLoteId, int $novoNumero): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE itens SET lote_id = :lote_id, numero = :numero WHERE id = :id');
        $stmt->execute([
            'lote_id' => $novoLoteId,
            'numero' => $novoNumero,
            'id' => $this->id,
        ]);
        $this->loteId = $novoLoteId;
        $this->numero = $novoNumero;
    }

    public function buscarPrecos(): array
    {
        return Preco::buscarPorItem($this->id);
    }

    /**
     * @param array<int, string>|null $parametrosPrecoPublico ja calculado por quem chama
     * (ex.: Cotacao::calcularValorTotal() percorrendo varios itens) - evita
     * repetir a mesma consulta a cada item. Se null, busca aqui mesmo.
     * @param bool $arredondarValorReferencia ver Cotacao::deveArredondarValorReferencia()
     * - quem chama e que sabe se o item pertence a uma cotacao de antes ou
     * depois da correcao de arredondamento.
     * @param array<int, Preco>|null $precos ja buscados por quem chama (ex.:
     * um gerador de documento que tambem precisa listar os precos um a um) -
     * evita repetir a consulta a cada chamada. Se null, busca aqui mesmo.
     */
    public function analisar(
        string $criterio = AnalisePrecos::CRITERIO_MEDIANA,
        ?array $parametrosPrecoPublico = null,
        bool $arredondarValorReferencia = true,
        ?array $precos = null
    ): array {
        $precos ??= $this->buscarPrecos();

        $precosParaCalculo = [];
        foreach ($precos as $preco) {
            $precosParaCalculo[] = [
                'valor' => $preco->valor,
                'parametro' => $preco->parametro,
            ];
        }

        $parametrosPrecoPublico ??= Parametro::buscarNomesPrecoPublico();

        $analise = new AnalisePrecos($precosParaCalculo, $criterio, $parametrosPrecoPublico, $arredondarValorReferencia);

        return $analise->calcular();
    }

    public static function buscarPorId(int $id): ?Item
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM itens WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $linha = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$linha) {
            return null;
        }

        return self::fromArray($linha);
    }

    public static function buscarPorLote(int $loteId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM itens WHERE lote_id = :lote_id ORDER BY numero ASC');
        $stmt->execute(['lote_id' => $loteId]);

        $itens = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
            $itens[] = self::fromArray($linha);
        }

        return $itens;
    }

    private static function fromArray(array $linha): Item
    {
        return new Item(
            (int) $linha['lote_id'],
            (int) $linha['numero'],
            $linha['descricao'],
            $linha['unidade'] ?? 'UN',
            (float) ($linha['quantidade'] ?? 1),
            (int) $linha['id']
        );
    }
}