<?php

require_once __DIR__ . '/../models/Cotacao.php';
require_once __DIR__ . '/../models/StatusCotacao.php';
require_once __DIR__ . '/../models/Lote.php';
require_once __DIR__ . '/../models/Servidor.php';
require_once __DIR__ . '/../models/Demanda.php';
require_once __DIR__ . '/../models/AnalisePrecos.php';
require_once __DIR__ . '/../models/Parametro.php';
require_once __DIR__ . '/../helpers/auth.php';

class CotacaoController
{
    public function listar(): void
    {
        exigirLogin();

        $cotacoes = Cotacao::buscarTodas();
        $servidores = Servidor::buscarTodos();
        $demandasDisponiveis = Demanda::buscarEmAndamentoSemVinculo();

        require __DIR__ . '/../views/cotacoes.php';
    }

    public function criar(): void
    {
        exigirLogin();

        $numeroProcesso = trim($_POST['numero_processo'] ?? '');
        $orgaoSetor = trim($_POST['orgao_setor'] ?? '');
        $procedimento = trim($_POST['procedimento'] ?? '');
        $tipoJulgamento = trim($_POST['tipo_julgamento'] ?? '');
        $objeto = trim($_POST['objeto'] ?? '');
        $servidorId = (int) ($_POST['servidor_id'] ?? 0);
        $criterio = $_POST['criterio_consolidacao'] ?? AnalisePrecos::CRITERIO_MEDIANA;
        $demandaId = (int) ($_POST['demanda_id'] ?? 0) ?: null;

        if ($numeroProcesso === '' || $servidorId === 0) {
            echo 'Número do processo e servidor responsável são obrigatórios.';
            return;
        }

        $cotacao = new Cotacao(
            $numeroProcesso,
            $orgaoSetor,
            $procedimento,
            $tipoJulgamento,
            $objeto,
            $servidorId,
            $criterio,
            StatusCotacao::EmAndamento,
            null,
            '',
            $demandaId
        );
        $cotacao->salvar();

        header('Location: index.php?action=cotacao&id=' . $cotacao->id);
        exit;
    }

    public function criarComDemandaNova(): void
    {
        exigirLogin();

        $numeroProcessoDemanda = trim($_POST['demanda_numero_processo'] ?? '');
        $setorDemandante = trim($_POST['demanda_setor_demandante'] ?? '');
        $dataRecebimento = trim($_POST['demanda_data_recebimento'] ?? '');
        $objetoDemanda = trim($_POST['demanda_objeto'] ?? '');

        if ($numeroProcessoDemanda === '' || $dataRecebimento === '') {
            echo 'Número do processo e data de recebimento são obrigatórios.';
            return;
        }

        $demanda = new Demanda($numeroProcessoDemanda, $dataRecebimento, '', $setorDemandante, $objetoDemanda);
        $demanda->salvar();

        $numeroProcesso = trim($_POST['numero_processo'] ?? '');
        $orgaoSetor = trim($_POST['orgao_setor'] ?? '');
        $procedimento = trim($_POST['procedimento'] ?? '');
        $tipoJulgamento = trim($_POST['tipo_julgamento'] ?? '');
        $objeto = trim($_POST['objeto'] ?? '');
        $servidorId = (int) ($_POST['servidor_id'] ?? 0);
        $criterio = $_POST['criterio_consolidacao'] ?? AnalisePrecos::CRITERIO_MEDIANA;

        if ($servidorId === 0) {
            echo 'Servidor responsável é obrigatório.';
            return;
        }

        $cotacao = new Cotacao(
            $numeroProcesso !== '' ? $numeroProcesso : $numeroProcessoDemanda,
            $orgaoSetor !== '' ? $orgaoSetor : $setorDemandante,
            $procedimento,
            $tipoJulgamento,
            $objeto !== '' ? $objeto : $objetoDemanda,
            $servidorId,
            $criterio,
            StatusCotacao::EmAndamento,
            null,
            '',
            $demanda->id
        );
        $cotacao->salvar();

        header('Location: index.php?action=cotacao&id=' . $cotacao->id);
        exit;
    }

    public function mostrar(int $id): void
    {
        exigirLogin();

        $cotacao = Cotacao::buscarPorId($id);

        if ($cotacao === null) {
            echo 'Cotação não encontrada.';
            return;
        }

        $lotes           = $cotacao->buscarLotes();
        $servidor        = $cotacao->buscarServidor();
        $servidores      = Servidor::buscarTodos();
        $parametros      = Parametro::buscarTodos();
        $demandaVinculada = $cotacao->buscarDemandaVinculada();

        require __DIR__ . '/../views/cotacao.php';
    }

    public function editar(): void
    {
        exigirLogin();

        $id = (int) ($_POST['cotacao_id'] ?? 0);
        $cotacao = Cotacao::buscarPorId($id);

        if ($cotacao === null) {
            echo 'Cotação não encontrada.';
            return;
        }

        $cotacao->numeroProcesso     = trim($_POST['numero_processo'] ?? '');
        $cotacao->orgaoSetor         = trim($_POST['orgao_setor'] ?? '');
        $cotacao->procedimento       = trim($_POST['procedimento'] ?? '');
        $cotacao->tipoJulgamento     = trim($_POST['tipo_julgamento'] ?? '');
        $cotacao->objeto             = trim($_POST['objeto'] ?? '');
        $cotacao->servidorId         = (int) ($_POST['servidor_id'] ?? $cotacao->servidorId);
        $cotacao->criterioConsolidacao = trim($_POST['criterio_consolidacao'] ?? $cotacao->criterioConsolidacao);

        $cotacao->salvar();

        header('Location: index.php?action=cotacao&id=' . $cotacao->id);
        exit;
    }

    public function finalizar(): void
    {
        exigirLogin();

        $id = (int) ($_GET['id'] ?? 0);
        $cotacao = Cotacao::buscarPorId($id);

        if ($cotacao === null) {
            echo 'Cotação não encontrada.';
            return;
        }

        $cotacao->status = StatusCotacao::Finalizada;
        $cotacao->salvar();

        header('Location: index.php?action=cotacao&id=' . $cotacao->id);
        exit;
    }

    public function excluir(): void
    {
        exigirLogin();

        $id = (int) ($_GET['id'] ?? 0);
        $cotacao = Cotacao::buscarPorId($id);

        if ($cotacao !== null) {
            $cotacao->excluir();
        }

        header('Location: index.php?action=cotacoes');
        exit;
    }
}