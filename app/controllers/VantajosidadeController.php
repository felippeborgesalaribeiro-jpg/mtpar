<?php

require_once __DIR__ . '/../models/ProcessoVantajosidade.php';
require_once __DIR__ . '/../models/ItemVantajosidade.php';
require_once __DIR__ . '/../models/PrecoVantajosidade.php';
require_once __DIR__ . '/../models/Servidor.php';
require_once __DIR__ . '/../models/Parametro.php';
require_once __DIR__ . '/../models/Demanda.php';
require_once __DIR__ . '/../models/AnaliseVantajosidade.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/formatacao.php';

class VantajosidadeController
{
    public function listar(): void
    {
        exigirLogin();

        $processos = ProcessoVantajosidade::buscarTodos();
        $servidores = Servidor::buscarTodos();
        $demandasDisponiveis = Demanda::buscarEmAndamentoSemVinculo();

        require __DIR__ . '/../views/vantajosidade_lista.php';
    }

    public function criar(): void
    {
        exigirLogin();

        $numeroAta = trim($_POST['numero_ata'] ?? '');
        $orgaoGerenciador = trim($_POST['orgao_gerenciador'] ?? '');
        $objeto = trim($_POST['objeto'] ?? '');
        $servidorId = (int) ($_POST['servidor_id'] ?? 0);
        $demandaId = (int) ($_POST['demanda_id'] ?? 0) ?: null;

        if ($numeroAta === '' || $servidorId === 0) {
            echo 'Número da Ata e servidor responsável são obrigatórios.';
            return;
        }

        $processo = new ProcessoVantajosidade(
            $numeroAta,
            $orgaoGerenciador,
            $objeto,
            $servidorId,
            ProcessoVantajosidade::STATUS_EM_ANDAMENTO,
            null,
            $demandaId
        );
        $processo->salvar();

        header('Location: index.php?action=vantajosidade&id=' . $processo->id);
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

        $numeroAta = trim($_POST['numero_ata'] ?? '');
        $orgaoGerenciador = trim($_POST['orgao_gerenciador'] ?? '');
        $objeto = trim($_POST['objeto'] ?? '');
        $servidorId = (int) ($_POST['servidor_id'] ?? 0);

        if ($servidorId === 0) {
            echo 'Servidor responsável é obrigatório.';
            return;
        }

        $processo = new ProcessoVantajosidade(
            $numeroAta,
            $orgaoGerenciador !== '' ? $orgaoGerenciador : $setorDemandante,
            $objeto !== '' ? $objeto : $objetoDemanda,
            $servidorId,
            ProcessoVantajosidade::STATUS_EM_ANDAMENTO,
            null,
            $demanda->id
        );
        $processo->salvar();

        header('Location: index.php?action=vantajosidade&id=' . $processo->id);
        exit;
    }

    public function mostrar(int $id): void
    {
        exigirLogin();

        $processo = ProcessoVantajosidade::buscarPorId($id);

        if ($processo === null) {
            echo 'Processo não encontrado.';
            return;
        }

        $itens = $processo->buscarItens();
        $servidor = $processo->buscarServidor();
        $parametros = Parametro::buscarTodos();
        $demandaVinculada = $processo->buscarDemandaVinculada();

        require __DIR__ . '/../views/vantajosidade_detalhe.php';
    }

    public function finalizar(): void
    {
        exigirLogin();

        $id = (int) ($_GET['id'] ?? 0);
        $processo = ProcessoVantajosidade::buscarPorId($id);

        if ($processo === null) {
            echo 'Processo não encontrado.';
            return;
        }

        $processo->status = ProcessoVantajosidade::STATUS_FINALIZADO;
        $processo->salvar();

        header('Location: index.php?action=vantajosidade&id=' . $processo->id);
        exit;
    }

    public function excluir(): void
    {
        exigirLogin();

        $id = (int) ($_GET['id'] ?? 0);
        $processo = ProcessoVantajosidade::buscarPorId($id);

        if ($processo !== null) {
            $processo->excluir();
        }

        header('Location: index.php?action=vantajosidades');
        exit;
    }

    public function adicionarItem(): void
    {
        exigirLogin();

        $processoId = (int) ($_POST['processo_id'] ?? 0);
        $lote = trim($_POST['lote'] ?? '');
        $item = trim($_POST['item'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $unidade = trim($_POST['unidade'] ?? 'UN');
        $quantidade = converterMoedaBrParaFloat($_POST['quantidade'] ?? '1');
        $precoAta = converterMoedaBrParaFloat($_POST['preco_ata'] ?? '0');

        if ($lote === '' || $item === '') {
            echo 'Lote e Item são obrigatórios.';
            return;
        }

        $itemNovo = new ItemVantajosidade($processoId, $lote, $item, $precoAta, $descricao, $unidade, $quantidade);
        $itemNovo->salvar();

        header('Location: index.php?action=vantajosidade&id=' . $processoId);
        exit;
    }

    public function editarItem(): void
    {
        exigirLogin();

        $itemId = (int) ($_POST['item_id'] ?? 0);
        $item = ItemVantajosidade::buscarPorId($itemId);

        if ($item === null) {
            echo 'Item não encontrado.';
            return;
        }

        $item->lote = trim($_POST['lote'] ?? '');
        $item->item = trim($_POST['item'] ?? '');
        $item->descricao = trim($_POST['descricao'] ?? '');
        $item->unidade = trim($_POST['unidade'] ?? 'UN');
        $item->quantidade = converterMoedaBrParaFloat($_POST['quantidade'] ?? '1');
        $item->precoAta = converterMoedaBrParaFloat($_POST['preco_ata'] ?? '0');
        $item->salvar();

        header('Location: index.php?action=vantajosidade&id=' . $item->processoId);
        exit;
    }

    public function excluirItem(): void
    {
        exigirLogin();

        $itemId = (int) ($_GET['id'] ?? 0);
        $item = ItemVantajosidade::buscarPorId($itemId);

        if ($item === null) {
            echo 'Item não encontrado.';
            return;
        }

        $processoId = $item->processoId;
        $item->excluir();

        header('Location: index.php?action=vantajosidade&id=' . $processoId);
        exit;
    }

    public function adicionarPreco(): void
    {
        exigirLogin();

        $itemId = (int) ($_POST['item_id'] ?? 0);
        $valor = converterMoedaBrParaFloat($_POST['valor'] ?? '0');
        $parametro = trim($_POST['parametro'] ?? '');
        $fonte = trim($_POST['fonte'] ?? '');

        $item = ItemVantajosidade::buscarPorId($itemId);

        if ($item === null) {
            echo 'Item não encontrado.';
            return;
        }

        $preco = new PrecoVantajosidade($item->id, $valor, $parametro, $fonte);
        $preco->salvar();

        header('Location: index.php?action=vantajosidade&id=' . $item->processoId);
        exit;
    }

    public function editarPreco(): void
    {
        exigirLogin();

        $precoId = (int) ($_POST['preco_id'] ?? 0);
        $preco = PrecoVantajosidade::buscarPorId($precoId);

        if ($preco === null) {
            echo 'Preço não encontrado.';
            return;
        }

        $preco->valor = converterMoedaBrParaFloat($_POST['valor'] ?? '0');
        $preco->parametro = trim($_POST['parametro'] ?? '');
        $preco->fonte = trim($_POST['fonte'] ?? '');
        $preco->salvar();

        $item = ItemVantajosidade::buscarPorId($preco->itemId);

        header('Location: index.php?action=vantajosidade&id=' . $item->processoId);
        exit;
    }

    public function excluirPreco(): void
    {
        exigirLogin();

        $precoId = (int) ($_GET['id'] ?? 0);
        $preco = PrecoVantajosidade::buscarPorId($precoId);

        if ($preco === null) {
            echo 'Preço não encontrado.';
            return;
        }

        $item = ItemVantajosidade::buscarPorId($preco->itemId);
        $processoId = $item->processoId;
        $preco->excluir();

        header('Location: index.php?action=vantajosidade&id=' . $processoId);
        exit;
    }

    public function mapa(): void
    {
        exigirLogin();

        $id = (int) ($_GET['id'] ?? 0);
        $processo = ProcessoVantajosidade::buscarPorId($id);

        if ($processo === null) {
            echo 'Processo não encontrado.';
            return;
        }

        $servidor = $processo->buscarServidor();
        $itens = $processo->buscarItens();

        $lotesAgrupados = [];
        $totalItens = 0;
        $totalVantajosos = 0;
        $totalNaoVantajosos = 0;

        foreach ($itens as $item) {
            $resultado = $item->analisar();
            $precos = $item->buscarPrecos();

            if (!isset($lotesAgrupados[$item->lote])) {
                $lotesAgrupados[$item->lote] = [];
            }

            $lotesAgrupados[$item->lote][] = [
                'item' => $item,
                'precos' => $precos,
                'resultado' => $resultado,
            ];

            if ($resultado['resultado'] !== null) {
                $totalItens++;
                if ($resultado['resultado'] === AnaliseVantajosidade::VANTAJOSA) {
                    $totalVantajosos++;
                } else {
                    $totalNaoVantajosos++;
                }
            }
        }

        require __DIR__ . '/../views/vantajosidade_mapa.php';
    }
}