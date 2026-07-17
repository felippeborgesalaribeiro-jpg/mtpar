<?php

require_once __DIR__ . '/../models/Demanda.php';
require_once __DIR__ . '/../models/Licitacao.php';
require_once __DIR__ . '/../models/Servidor.php';
require_once __DIR__ . '/../models/Cotacao.php';
require_once __DIR__ . '/../models/ProcessoVantajosidade.php';
require_once __DIR__ . '/../helpers/auth.php';

class DemandaController
{
    public function listar(): void
    {
        exigirLogin();

        $demandas = Demanda::buscarTodas();

        require __DIR__ . '/../views/demandas.php';
    }

    public function mostrar(): void
    {
        exigirLogin();

        $id = (int) ($_GET['id'] ?? 0);
        $demanda = Demanda::buscarPorId($id);

        if ($demanda === null) {
            echo 'Demanda não encontrada.';
            return;
        }

        $modo = $_GET['modo'] ?? 'ver';
        $servidores = Servidor::buscarTodos();
        $servidorResponsavel = $demanda->buscarServidorResponsavel();
        $licitacao = Licitacao::buscarPorDemandaId($demanda->id);
        $cotacao = $demanda->buscarCotacaoVinculada();
        $vantajosidade = $demanda->buscarVantajosidadeVinculada();

        require __DIR__ . '/../views/demanda_detalhe.php';
    }

    public function criar(): void
    {
        exigirLogin();

        $numeroProcesso = trim($_POST['numero_processo'] ?? '');
        $linkSigadoc = trim($_POST['link_sigadoc'] ?? '');
        $setorDemandante = trim($_POST['setor_demandante'] ?? '');
        $dataRecebimento = trim($_POST['data_recebimento'] ?? '');
        $objeto = trim($_POST['objeto'] ?? '');
        $servidorResponsavelId = (int) ($_POST['servidor_responsavel_id'] ?? 0) ?: null;
        $status = $_POST['status'] ?? Demanda::STATUS_EM_ANDAMENTO;

        if ($numeroProcesso === '' || $dataRecebimento === '') {
            echo 'Número do processo e data de recebimento são obrigatórios.';
            return;
        }

        $demanda = new Demanda($numeroProcesso, $dataRecebimento, $linkSigadoc, $setorDemandante, $objeto, $servidorResponsavelId, $status);
        $demanda->salvar();

        header('Location: index.php?action=ver_demanda&id=' . $demanda->id);
        exit;
    }

    public function editarInline(): void
    {
        exigirLogin();

        $id = (int) ($_POST['demanda_id'] ?? 0);
        $demanda = Demanda::buscarPorId($id);

        if ($demanda === null) {
            echo 'Demanda não encontrada.';
            return;
        }

        $statusAnterior = $demanda->status;

        $demanda->numeroProcesso = trim($_POST['numero_processo'] ?? '');
        $demanda->linkSigadoc = trim($_POST['link_sigadoc'] ?? '');
        $demanda->setorDemandante = trim($_POST['setor_demandante'] ?? '');
        $demanda->dataRecebimento = trim($_POST['data_recebimento'] ?? '');
        $demanda->objeto = trim($_POST['objeto'] ?? '');
        $demanda->servidorResponsavelId = (int) ($_POST['servidor_responsavel_id'] ?? 0) ?: null;
        $demanda->status = $_POST['status'] ?? Demanda::STATUS_EM_ANDAMENTO;

        $demanda->salvar();

        $mudouParaConcluido = $statusAnterior !== Demanda::STATUS_CONCLUIDO && $demanda->status === Demanda::STATUS_CONCLUIDO;

        if ($mudouParaConcluido) {
            $licitacaoExistente = Licitacao::buscarPorDemandaId($demanda->id);
            if ($licitacaoExistente === null) {
                Licitacao::criarApartirDeDemanda($demanda);
            }
        }

        header('Location: index.php?action=ver_demanda&id=' . $demanda->id);
        exit;
    }

    public function excluir(): void
    {
        exigirLogin();

        $id = (int) ($_GET['id'] ?? 0);
        $demanda = Demanda::buscarPorId($id);

        if ($demanda !== null) {
            $demanda->excluir();
        }

        header('Location: index.php?action=demandas');
        exit;
    }
}