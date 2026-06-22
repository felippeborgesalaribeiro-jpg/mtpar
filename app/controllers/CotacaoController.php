<?php

require_once __DIR__ . '/../models/Cotacao.php';
require_once __DIR__ . '/../models/Lote.php';
require_once __DIR__ . '/../models/Servidor.php';
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
            $criterio
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

        $lotes = $cotacao->buscarLotes();
        $servidor = $cotacao->buscarServidor();
        $parametros = Parametro::buscarTodos();

        require __DIR__ . '/../views/cotacao.php';
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

        $cotacao->status = Cotacao::STATUS_FINALIZADA;
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