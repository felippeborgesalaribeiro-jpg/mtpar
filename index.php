<?php

session_start();

require_once __DIR__ . '/app/helpers/auth.php';
require_once __DIR__ . '/app/helpers/formatacao.php';
require_once __DIR__ . '/app/controllers/AuthController.php';
require_once __DIR__ . '/app/controllers/DashboardController.php';
require_once __DIR__ . '/app/controllers/DemandaController.php';
require_once __DIR__ . '/app/controllers/LicitacaoController.php';
require_once __DIR__ . '/app/controllers/OrcamentoController.php';
require_once __DIR__ . '/app/controllers/CotacaoController.php';
require_once __DIR__ . '/app/controllers/LoteController.php';
require_once __DIR__ . '/app/controllers/PrecoController.php';
require_once __DIR__ . '/app/controllers/MapaController.php';
require_once __DIR__ . '/app/controllers/RelatorioController.php';
require_once __DIR__ . '/app/controllers/RelatoriosLicitacaoController.php';
require_once __DIR__ . '/app/controllers/EmpresaController.php';
require_once __DIR__ . '/app/controllers/PropostaVencedoraController.php';
require_once __DIR__ . '/app/controllers/VantajosidadeController.php';
require_once __DIR__ . '/app/controllers/ParametroController.php';
require_once __DIR__ . '/app/controllers/ServidorController.php';
require_once __DIR__ . '/app/controllers/PerfilController.php';
require_once __DIR__ . '/app/controllers/AdminController.php';

$action = $_GET['action'] ?? 'login';

$rotasPublicas = ['login', 'fazer_login'];

if (usuarioLogado() === null && !in_array($action, $rotasPublicas)) {
    header('Location: index.php?action=login');
    exit;
}

if (usuarioLogado() !== null && $action === 'login') {
    header('Location: index.php?action=dashboard');
    exit;
}

switch ($action) {

    case 'login':
        (new AuthController())->mostrarLogin();
        break;

    case 'fazer_login':
        (new AuthController())->login();
        break;

    case 'logout':
        (new AuthController())->logout();
        break;

    case 'dashboard':
        (new DashboardController())->mostrar();
        break;

    case 'demandas':
        (new DemandaController())->listar();
        break;

    case 'ver_demanda':
        (new DemandaController())->mostrar();
        break;

    case 'criar_demanda':
        (new DemandaController())->criar();
        break;

    case 'editar_demanda':
        (new DemandaController())->editar();
        break;

    case 'editar_demanda_inline':
        (new DemandaController())->editarInline();
        break;

    case 'excluir_demanda':
        (new DemandaController())->excluir();
        break;

    case 'licitacoes':
        (new LicitacaoController())->listar();
        break;

    case 'editar_licitacao':
        (new LicitacaoController())->editar();
        break;

    case 'excluir_licitacao':
        (new LicitacaoController())->excluir();
        break;

    case 'orcamentos':
        (new OrcamentoController())->listar();
        break;

    case 'cotacoes':
        (new CotacaoController())->listar();
        break;

    case 'cotacao':
        $id = (int) ($_GET['id'] ?? 0);
        (new CotacaoController())->mostrar($id);
        break;

    case 'criar_cotacao':
        (new CotacaoController())->criar();
        break;

    case 'criar_cotacao_com_demanda':
        (new CotacaoController())->criarComDemandaNova();
        break;

    case 'finalizar_cotacao':
        (new CotacaoController())->finalizar();
        break;

    case 'excluir_cotacao':
        (new CotacaoController())->excluir();
        break;

    case 'criar_lote':
        (new LoteController())->criar();
        break;

    case 'excluir_lote':
        (new LoteController())->excluir();
        break;

    case 'adicionar_item':
        (new LoteController())->adicionarItem();
        break;

    case 'editar_item':
        (new LoteController())->editarItem();
        break;

    case 'excluir_item':
        (new LoteController())->excluirItem();
        break;

    case 'adicionar_preco':
        (new PrecoController())->adicionar();
        break;

    case 'editar_preco':
        (new PrecoController())->editar();
        break;

    case 'excluir_preco':
        (new PrecoController())->excluir();
        break;

    case 'mapa':
        $id = (int) ($_GET['id'] ?? 0);
        (new MapaController())->mostrar($id);
        break;

    case 'relatorio':
    case 'relatorio_formulario':
        (new RelatorioController())->formulario();
        break;

    case 'gerar_relatorio':
        (new RelatorioController())->gerar();
        break;

    case 'gerar_pesquisa':
    case 'gerar_relatorio_pesquisa':
        (new RelatorioController())->gerarPesquisa();
        break;

    case 'relatorios_licitacao':
        (new RelatoriosLicitacaoController())->mostrar();
        break;

    case 'buscar_empresas':
        (new EmpresaController())->buscar();
        break;

    case 'criar_empresa':
        (new EmpresaController())->criar();
        break;

    case 'proposta_vencedora':
        (new PropostaVencedoraController())->mostrar();
        break;

    case 'salvar_proposta_vencedora':
        (new PropostaVencedoraController())->salvar();
        break;

    case 'gerar_documento_proposta_vencedora':
        (new PropostaVencedoraController())->gerarDocumento();
        break;

    case 'vantajosidades':
        (new VantajosidadeController())->listar();
        break;

    case 'vantajosidade':
        $id = (int) ($_GET['id'] ?? 0);
        (new VantajosidadeController())->mostrar($id);
        break;

    case 'criar_vantajosidade':
        (new VantajosidadeController())->criar();
        break;

    case 'criar_vantajosidade_com_demanda':
        (new VantajosidadeController())->criarComDemandaNova();
        break;

    case 'finalizar_vantajosidade':
        (new VantajosidadeController())->finalizar();
        break;

    case 'excluir_vantajosidade':
        (new VantajosidadeController())->excluir();
        break;

    case 'adicionar_item_vantajosidade':
        (new VantajosidadeController())->adicionarItem();
        break;

    case 'editar_item_vantajosidade':
        (new VantajosidadeController())->editarItem();
        break;

    case 'excluir_item_vantajosidade':
        (new VantajosidadeController())->excluirItem();
        break;

    case 'adicionar_preco_vantajosidade':
        (new VantajosidadeController())->adicionarPreco();
        break;

    case 'editar_preco_vantajosidade':
        (new VantajosidadeController())->editarPreco();
        break;

    case 'excluir_preco_vantajosidade':
        (new VantajosidadeController())->excluirPreco();
        break;

    case 'mapa_vantajosidade':
        (new VantajosidadeController())->mapa();
        break;

    case 'parametros':
        (new ParametroController())->listar();
        break;

    case 'criar_parametro':
        (new ParametroController())->criar();
        break;

    case 'editar_parametro':
        (new ParametroController())->editar();
        break;

    case 'excluir_parametro':
        (new ParametroController())->excluir();
        break;

    case 'servidores':
        (new ServidorController())->listar();
        break;

    case 'criar_servidor':
        (new ServidorController())->criar();
        break;

    case 'editar_servidor':
        (new ServidorController())->editar();
        break;

    case 'resetar_senha':
    case 'resetar_senha_servidor':
        (new ServidorController())->resetarSenha();
        break;

    case 'excluir_servidor':
        (new ServidorController())->excluir();
        break;

    case 'perfil':
        (new PerfilController())->mostrar();
        break;

    case 'atualizar_perfil':
        (new PerfilController())->atualizar();
        break;

    case 'admin':
        (new AdminController())->index();
        break;

    case 'admin_lixeira':
        (new AdminController())->lixeira();
        break;

    case 'admin_restaurar_demanda':
        (new AdminController())->restaurarDemanda();
        break;

    case 'admin_restaurar_cotacao':
        (new AdminController())->restaurarCotacao();
        break;

    case 'admin_restaurar_vantajosidade':
        (new AdminController())->restaurarVantajosidade();
        break;

    case 'admin_excluir_definitivo_demanda':
        (new AdminController())->excluirDefinitivamenteDemanda();
        break;

    case 'admin_excluir_definitivo_cotacao':
        (new AdminController())->excluirDefinitivamenteCotacao();
        break;

    case 'admin_excluir_definitivo_vantajosidade':
        (new AdminController())->excluirDefinitivamenteVantajosidade();
        break;

    case 'admin_backup_criar':
        (new AdminController())->criarBackup();
        break;

    case 'admin_backup_excluir':
        (new AdminController())->excluirBackup();
        break;

    case 'editar_cotacao':
        (new CotacaoController())->editar();
        break;

    default:
        header('Location: index.php?action=dashboard');
        exit;
}