<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Demanda.php';
require_once __DIR__ . '/Servidor.php';
require_once __DIR__ . '/StatusLicitacao.php';

class Licitacao
{
    public ?int $id;
    public int $demandaId;
    public string $numeroProcesso;
    public string $linkSigadoc;
    public string $setorDemandante;
    public string $dataRecebimento;
    public string $objeto;
    public ?int $servidorResponsavelId;
    public string $editalLicitacao;
    public ?string $realizacaoSessaoPublica;
    public ?float $valorEstimado;
    public ?float $valorAdjudicado;
    public ?string $encaminhadoPactuacaoContrato;
    public string $criadoEm;
    public string $observacoesPropostaVencedora;
    public ?string $dataAdjudicacaoHomologacao;

    public function __construct(
        int $demandaId,
        string $numeroProcesso,
        string $dataRecebimento,
        string $linkSigadoc = '',
        string $setorDemandante = '',
        string $objeto = '',
        ?int $servidorResponsavelId = null,
        string $editalLicitacao = '',
        ?string $realizacaoSessaoPublica = null,
        ?float $valorEstimado = null,
        ?float $valorAdjudicado = null,
        ?string $encaminhadoPactuacaoContrato = null,
        ?int $id = null,
        string $criadoEm = '',
        string $observacoesPropostaVencedora = '',
        ?string $dataAdjudicacaoHomologacao = null
    ) {
        $this->id = $id;
        $this->demandaId = $demandaId;
        $this->numeroProcesso = $numeroProcesso;
        $this->dataRecebimento = $dataRecebimento;
        $this->linkSigadoc = $linkSigadoc;
        $this->setorDemandante = $setorDemandante;
        $this->objeto = $objeto;
        $this->servidorResponsavelId = $servidorResponsavelId;
        $this->editalLicitacao = $editalLicitacao;
        $this->realizacaoSessaoPublica = $realizacaoSessaoPublica;
        $this->valorEstimado = $valorEstimado;
        $this->valorAdjudicado = $valorAdjudicado;
        $this->encaminhadoPactuacaoContrato = $encaminhadoPactuacaoContrato;
        $this->criadoEm = $criadoEm;
        $this->observacoesPropostaVencedora = $observacoesPropostaVencedora;
        $this->dataAdjudicacaoHomologacao = $dataAdjudicacaoHomologacao;
    }

    public static function criarApartirDeDemanda(Demanda $demanda): Licitacao
    {
        require_once __DIR__ . '/Cotacao.php';

        $cotacaoVinculada = Cotacao::buscarPorDemandaId($demanda->id);
        $valorEstimado = $cotacaoVinculada !== null ? $cotacaoVinculada->calcularValorTotal() : null;

        $licitacao = new Licitacao(
            $demanda->id,
            $demanda->numeroProcesso,
            $demanda->dataRecebimento,
            $demanda->linkSigadoc,
            $demanda->setorDemandante,
            $demanda->objeto,
            $demanda->servidorResponsavelId,
            '',
            null,
            $valorEstimado
        );
        $licitacao->salvar();

        return $licitacao;
    }

    public function salvar(): int
    {
        $pdo = Database::getConnection();

        if ($this->id === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO licitacoes (demanda_id, numero_processo, link_sigadoc, setor_demandante, data_recebimento,
                 objeto, servidor_responsavel_id, edital_licitacao, realizacao_sessao_publica, valor_estimado,
                 valor_adjudicado, encaminhado_pactuacao_contrato, observacoes_proposta_vencedora, data_adjudicacao_homologacao)
                 VALUES (:demanda_id, :numero_processo, :link_sigadoc, :setor_demandante, :data_recebimento,
                 :objeto, :servidor_responsavel_id, :edital_licitacao, :realizacao_sessao_publica, :valor_estimado,
                 :valor_adjudicado, :encaminhado_pactuacao_contrato, :observacoes_proposta_vencedora, :data_adjudicacao_homologacao)'
            );
            $stmt->execute($this->paramsParaSalvar());
            $this->id = (int) $pdo->lastInsertId();
        } else {
            $stmt = $pdo->prepare(
                'UPDATE licitacoes SET demanda_id = :demanda_id, numero_processo = :numero_processo, link_sigadoc = :link_sigadoc,
                 setor_demandante = :setor_demandante, data_recebimento = :data_recebimento, objeto = :objeto,
                 servidor_responsavel_id = :servidor_responsavel_id, edital_licitacao = :edital_licitacao,
                 realizacao_sessao_publica = :realizacao_sessao_publica, valor_estimado = :valor_estimado,
                 valor_adjudicado = :valor_adjudicado, encaminhado_pactuacao_contrato = :encaminhado_pactuacao_contrato,
                 observacoes_proposta_vencedora = :observacoes_proposta_vencedora,
                 data_adjudicacao_homologacao = :data_adjudicacao_homologacao
                 WHERE id = :id'
            );
            $stmt->execute(array_merge($this->paramsParaSalvar(), ['id' => $this->id]));
        }

        return $this->id;
    }

    private function paramsParaSalvar(): array
    {
        return [
            'demanda_id' => $this->demandaId,
            'numero_processo' => $this->numeroProcesso,
            'link_sigadoc' => $this->linkSigadoc,
            'setor_demandante' => $this->setorDemandante,
            'data_recebimento' => $this->dataRecebimento,
            'objeto' => $this->objeto,
            'servidor_responsavel_id' => $this->servidorResponsavelId,
            'edital_licitacao' => $this->editalLicitacao,
            'realizacao_sessao_publica' => $this->realizacaoSessaoPublica,
            'valor_estimado' => $this->valorEstimado,
            'valor_adjudicado' => $this->valorAdjudicado,
            'encaminhado_pactuacao_contrato' => $this->encaminhadoPactuacaoContrato,
            'observacoes_proposta_vencedora' => $this->observacoesPropostaVencedora,
            'data_adjudicacao_homologacao' => $this->dataAdjudicacaoHomologacao,
        ];
    }

    public function excluir(): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM licitacoes WHERE id = :id');
        $stmt->execute(['id' => $this->id]);
    }

    public function buscarServidorResponsavel(): ?Servidor
    {
        if ($this->servidorResponsavelId === null) {
            return null;
        }

        return Servidor::buscarPorId($this->servidorResponsavelId);
    }

    /**
     * O valor estimado nunca e digitado a mao: e sempre o total do mapa de
     * pesquisa de precos da Cotacao vinculada a demanda desta licitacao.
     * Recalcula e persiste sempre que a licitacao e carregada, para nunca
     * ficar dessincronizado (ou com um valor antigo/errado) do mapa.
     */
    private function sincronizarValorEstimado(): void
    {
        require_once __DIR__ . '/Cotacao.php';

        $cotacaoVinculada = Cotacao::buscarPorDemandaId($this->demandaId);

        if ($cotacaoVinculada === null) {
            return;
        }

        $valorAtual = $cotacaoVinculada->calcularValorTotal();

        if ($this->valorEstimado === null || abs($valorAtual - $this->valorEstimado) > 0.001) {
            $this->valorEstimado = $valorAtual;
            $this->salvar();
        }
    }

    public function estaFinalizada(): bool
    {
        return $this->dataAdjudicacaoHomologacao !== null;
    }

    public function calcularDiasNaLicitacao(): int
    {
        $dataInicio = new DateTime($this->dataRecebimento);
        $dataFim = $this->encaminhadoPactuacaoContrato !== null
            ? new DateTime($this->encaminhadoPactuacaoContrato)
            : new DateTime('today');

        $diferenca = $dataInicio->diff($dataFim);

        return (int) $diferenca->days;
    }

    public function estaEmAndamento(): bool
    {
        return $this->encaminhadoPactuacaoContrato === null;
    }

    public function foiHomologada(): bool
    {
        return $this->valorAdjudicado !== null;
    }

    /**
     * Nao existe coluna de status: e inferido a partir dos campos que ja
     * marcam o avanco da licitacao (edital, data de adjudicacao/homologacao,
     * encaminhamento). "Homologada" exige o ato formal de finalizar o
     * processo (estaFinalizada()) - so ter um valor_adjudicado digitado
     * nao basta, porque pode ser so um rascunho.
     */
    public function status(): StatusLicitacao
    {
        if ($this->encaminhadoPactuacaoContrato !== null) {
            return StatusLicitacao::EncaminhadaParaContratacao;
        }

        if ($this->estaFinalizada()) {
            return StatusLicitacao::Homologada;
        }

        if ($this->editalLicitacao !== '') {
            return StatusLicitacao::Publicada;
        }

        return StatusLicitacao::AguardandoPublicacao;
    }

    public function calcularEconomicidadeReais(): ?float
    {
        if ($this->valorEstimado === null || $this->valorAdjudicado === null) {
            return null;
        }

        return $this->valorEstimado - $this->valorAdjudicado;
    }

    public function calcularEconomicidadePercentual(): ?float
    {
        $economicidade = $this->calcularEconomicidadeReais();

        if ($economicidade === null || $this->valorEstimado == 0) {
            return null;
        }

        return ($economicidade / $this->valorEstimado) * 100;
    }

    public static function buscarPorId(int $id): ?Licitacao
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM licitacoes WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $linha = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$linha) {
            return null;
        }

        return self::fromArray($linha);
    }

    public static function buscarPorDemandaId(int $demandaId): ?Licitacao
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM licitacoes WHERE demanda_id = :demanda_id');
        $stmt->execute(['demanda_id' => $demandaId]);
        $linha = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$linha) {
            return null;
        }

        return self::fromArray($linha);
    }

    public static function buscarTodas(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM licitacoes ORDER BY data_recebimento DESC');

        $licitacoes = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
            $licitacoes[] = self::fromArray($linha);
        }

        return $licitacoes;
    }

    public static function contarPublicadas(): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT COUNT(*) FROM licitacoes WHERE edital_licitacao != ''");

        return (int) $stmt->fetchColumn();
    }

    public static function contarHomologadas(): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT COUNT(*) FROM licitacoes WHERE valor_adjudicado IS NOT NULL');

        return (int) $stmt->fetchColumn();
    }

    public static function somarValorAdjudicadoHomologadas(): float
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT SUM(valor_adjudicado) FROM licitacoes WHERE valor_adjudicado IS NOT NULL');

        return (float) ($stmt->fetchColumn() ?? 0);
    }

    private static function fromArray(array $linha): Licitacao
    {
        $licitacao = new Licitacao(
            (int) $linha['demanda_id'],
            $linha['numero_processo'],
            $linha['data_recebimento'],
            $linha['link_sigadoc'] ?? '',
            $linha['setor_demandante'] ?? '',
            $linha['objeto'] ?? '',
            $linha['servidor_responsavel_id'] !== null ? (int) $linha['servidor_responsavel_id'] : null,
            $linha['edital_licitacao'] ?? '',
            $linha['realizacao_sessao_publica'],
            $linha['valor_estimado'] !== null ? (float) $linha['valor_estimado'] : null,
            $linha['valor_adjudicado'] !== null ? (float) $linha['valor_adjudicado'] : null,
            $linha['encaminhado_pactuacao_contrato'],
            (int) $linha['id'],
            $linha['criado_em'] ?? '',
            $linha['observacoes_proposta_vencedora'] ?? '',
            $linha['data_adjudicacao_homologacao'] ?? null
        );

        $licitacao->sincronizarValorEstimado();

        return $licitacao;
    }
}