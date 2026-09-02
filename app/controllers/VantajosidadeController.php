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
require_once __DIR__ . '/../helpers/config.php';

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

    /**
     * Formulario enxuto de vantajosidade nova ja vinculada a uma demanda
     * especifica - acessado a partir da tela do Processo, sem passar
     * pelo assistente de 3 modais da lista geral de Vantajosidades.
     */
    public function formularioParaDemanda(): void
    {
        exigirLogin();

        $demandaId = (int) ($_GET['demanda_id'] ?? 0);
        $demanda = Demanda::buscarPorId($demandaId);

        if ($demanda === null) {
            echo 'Processo não encontrado.';
            return;
        }

        $servidores = Servidor::buscarTodos();

        require __DIR__ . '/../views/vantajosidade_nova_para_demanda.php';
    }

    public function criar(): void
    {
        exigirLogin();

        $tipo = $_POST['tipo'] ?? ProcessoVantajosidade::TIPO_ATA;
        $numeroAta = trim($_POST['numero_ata'] ?? '');
        $orgaoGerenciador = trim($_POST['orgao_gerenciador'] ?? '');
        $numeroContrato = trim($_POST['numero_contrato'] ?? '');
        $valorTotalObjeto = $_POST['valor_total_objeto'] ?? '';
        $objeto = trim($_POST['objeto'] ?? '');
        $servidorId = (int) ($_POST['servidor_id'] ?? 0);
        $demandaId = (int) ($_POST['demanda_id'] ?? 0) ?: null;

        $erro = $this->validarDadosPorTipo($tipo, $numeroAta, $numeroContrato, $valorTotalObjeto, $servidorId);
        if ($erro !== null) {
            echo $erro;
            return;
        }

        $processo = new ProcessoVantajosidade(
            $tipo === ProcessoVantajosidade::TIPO_CONTRATO_ADITIVO ? '' : $numeroAta,
            $orgaoGerenciador,
            $objeto,
            $servidorId,
            ProcessoVantajosidade::STATUS_EM_ANDAMENTO,
            null,
            $demandaId,
            null,
            $tipo,
            $numeroContrato,
            $valorTotalObjeto !== '' ? converterMoedaBrParaFloat($valorTotalObjeto) : null
        );
        $processo->salvar();

        header('Location: index.php?action=vantajosidade&id=' . $processo->id);
        exit;
    }

    /**
     * @return string|null mensagem de erro, ou null se os dados forem validos
     */
    private function validarDadosPorTipo(string $tipo, string $numeroAta, string $numeroContrato, string $valorTotalObjeto, int $servidorId): ?string
    {
        if ($servidorId === 0) {
            return 'Servidor responsável é obrigatório.';
        }

        if ($tipo === ProcessoVantajosidade::TIPO_CONTRATO_ADITIVO) {
            if ($numeroContrato === '' || $valorTotalObjeto === '') {
                return 'Número do contrato e valor total do objeto são obrigatórios para aditivo de contrato.';
            }
            return null;
        }

        if ($numeroAta === '') {
            return 'Número da Ata é obrigatório.';
        }

        return null;
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

        $tipo = $_POST['tipo'] ?? ProcessoVantajosidade::TIPO_ATA;
        $numeroAta = trim($_POST['numero_ata'] ?? '');
        $orgaoGerenciador = trim($_POST['orgao_gerenciador'] ?? '');
        $numeroContrato = trim($_POST['numero_contrato'] ?? '');
        $valorTotalObjeto = $_POST['valor_total_objeto'] ?? '';
        $objeto = trim($_POST['objeto'] ?? '');
        $servidorId = (int) ($_POST['servidor_id'] ?? 0);

        $erro = $this->validarDadosPorTipo($tipo, $numeroAta, $numeroContrato, $valorTotalObjeto, $servidorId);
        if ($erro !== null) {
            echo $erro;
            return;
        }

        $processo = new ProcessoVantajosidade(
            $tipo === ProcessoVantajosidade::TIPO_CONTRATO_ADITIVO ? '' : $numeroAta,
            $orgaoGerenciador !== '' ? $orgaoGerenciador : $setorDemandante,
            $objeto !== '' ? $objeto : $objetoDemanda,
            $servidorId,
            ProcessoVantajosidade::STATUS_EM_ANDAMENTO,
            null,
            $demanda->id,
            null,
            $tipo,
            $numeroContrato,
            $valorTotalObjeto !== '' ? converterMoedaBrParaFloat($valorTotalObjeto) : null
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

        $id = (int) ($_POST['id'] ?? 0);
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

        $itemId = (int) ($_POST['id'] ?? 0);
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

        $precoId = (int) ($_POST['id'] ?? 0);
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

    public function formularioAnaliseCritica(): void
    {
        exigirLogin();

        $processoId = (int) ($_GET['id'] ?? 0);
        $processo = ProcessoVantajosidade::buscarPorId($processoId);

        if ($processo === null) {
            echo 'Processo não encontrado.';
            return;
        }

        if ($processo->status !== ProcessoVantajosidade::STATUS_FINALIZADO) {
            echo 'Este processo ainda não foi finalizado.';
            return;
        }

        $servidorResponsavelId = $processo->servidorId;

        $servidores = array_filter(
            Servidor::buscarTodos(),
            fn($servidor) => $servidor->id !== $servidorResponsavelId
        );

        $validador = Servidor::buscarPorId(SERVIDOR_VALIDADOR_PADRAO_ID);

        require __DIR__ . '/../views/vantajosidade_analise_critica_formulario.php';
    }

    public function gerarAnaliseCritica(): void
    {
        exigirLogin();

        $processoId    = (int) ($_POST['processo_id'] ?? 0);
        $elaboradoPorId = (int) ($_POST['elaborado_por_id'] ?? 0);
        $numeroDfd     = trim($_POST['numero_dfd'] ?? '');

        $processo     = ProcessoVantajosidade::buscarPorId($processoId);
        $elaboradoPor = Servidor::buscarPorId($elaboradoPorId);
        $validador    = Servidor::buscarPorId(SERVIDOR_VALIDADOR_PADRAO_ID);

        if ($processo === null || $elaboradoPor === null || $validador === null) {
            echo 'Dados insuficientes para gerar o documento.';
            return;
        }

        require_once __DIR__ . '/../models/GeradorAnaliseCriticaVantajosidade.php';

        $gerador        = new GeradorAnaliseCriticaVantajosidade($processo, $elaboradoPor, $validador, $numeroDfd);
        $caminhoArquivo = $gerador->gerar();
        $identificador  = $processo->ehContratoAditivo() ? $processo->numeroContrato : $processo->numeroAta;
        $nomeArquivo    = 'Analise_Critica_Vantajosidade_' . preg_replace('/[^A-Za-z0-9]/', '_', $identificador) . '.docx';

        session_write_close();
        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
        header('Content-Length: ' . filesize($caminhoArquivo));
        readfile($caminhoArquivo);
        unlink($caminhoArquivo);
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