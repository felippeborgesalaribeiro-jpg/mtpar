<?php

require_once __DIR__ . '/Database.php';

class Empresa
{
    public ?int $id;
    public string $nome;
    public string $nomeFantasia;
    public string $cnpj;
    public string $criadoEm;

    public function __construct(
        string $nome,
        string $cnpj,
        string $nomeFantasia = '',
        ?int $id = null,
        string $criadoEm = ''
    ) {
        $this->id = $id;
        $this->nome = $nome;
        $this->cnpj = self::normalizarCnpj($cnpj);
        $this->nomeFantasia = $nomeFantasia;
        $this->criadoEm = $criadoEm;
    }

    public function salvar(): int
    {
        $pdo = Database::getConnection();

        if ($this->id === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO empresas (nome, nome_fantasia, cnpj) VALUES (:nome, :nome_fantasia, :cnpj)'
            );
            $stmt->execute([
                'nome' => $this->nome,
                'nome_fantasia' => $this->nomeFantasia,
                'cnpj' => $this->cnpj,
            ]);
            $this->id = (int) $pdo->lastInsertId();
        } else {
            $stmt = $pdo->prepare(
                'UPDATE empresas SET nome = :nome, nome_fantasia = :nome_fantasia, cnpj = :cnpj WHERE id = :id'
            );
            $stmt->execute([
                'nome' => $this->nome,
                'nome_fantasia' => $this->nomeFantasia,
                'cnpj' => $this->cnpj,
                'id' => $this->id,
            ]);
        }

        return $this->id;
    }

    /**
     * Quantas licitacoes essa empresa venceu E que ja foram homologadas
     * (valor_adjudicado preenchido) - usado no cadastro/relatorios.
     */
    public function contarLicitacoesHomologadas(): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM licitacoes WHERE empresa_vencedora_id = :id AND valor_adjudicado IS NOT NULL'
        );
        $stmt->execute(['id' => $this->id]);

        return (int) $stmt->fetchColumn();
    }

    public static function buscarPorId(int $id): ?Empresa
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM empresas WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $linha = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$linha) {
            return null;
        }

        return self::fromArray($linha);
    }

    /**
     * O CNPJ e sempre gravado so com digitos (sem pontos/barra/traco),
     * pra garantir que a UNIQUE constraint pegue duplicatas mesmo que o
     * usuario digite formatado ou nao.
     */
    public static function normalizarCnpj(string $cnpj): string
    {
        return preg_replace('/\D/', '', $cnpj);
    }

    public static function buscarPorCnpj(string $cnpj): ?Empresa
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM empresas WHERE cnpj = :cnpj');
        $stmt->execute(['cnpj' => self::normalizarCnpj($cnpj)]);
        $linha = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$linha) {
            return null;
        }

        return self::fromArray($linha);
    }

    public static function buscarTodas(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM empresas ORDER BY nome ASC');

        $empresas = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
            $empresas[] = self::fromArray($linha);
        }

        return $empresas;
    }

    /**
     * Busca dinamica por nome, nome fantasia ou CNPJ (comparando so os
     * digitos do CNPJ, ignorando pontuacao) - usada no autocomplete da
     * conferencia de proposta vencedora.
     */
    public static function buscar(string $query): array
    {
        $query = trim($query);

        if ($query === '') {
            return [];
        }

        // O CNPJ e sempre gravado so com digitos (ver Empresa::normalizarCnpj);
        // aqui so precisamos extrair os digitos do que foi buscado pra comparar.
        $digitosQuery = preg_replace('/\D/', '', $query);

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT * FROM empresas
             WHERE nome LIKE :termo
             OR nome_fantasia LIKE :termo
             OR (:digitos != '' AND cnpj LIKE :termoDigitos)
             ORDER BY nome ASC
             LIMIT 20"
        );
        $stmt->execute([
            'termo' => '%' . $query . '%',
            'digitos' => $digitosQuery,
            'termoDigitos' => '%' . $digitosQuery . '%',
        ]);

        $empresas = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
            $empresas[] = self::fromArray($linha);
        }

        return $empresas;
    }

    private static function fromArray(array $linha): Empresa
    {
        return new Empresa(
            $linha['nome'],
            $linha['cnpj'],
            $linha['nome_fantasia'] ?? '',
            (int) $linha['id'],
            $linha['criado_em'] ?? ''
        );
    }
}
