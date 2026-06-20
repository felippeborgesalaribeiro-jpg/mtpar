<?php

require_once __DIR__ . '/app/helpers/formatacao.php';
require_once __DIR__ . '/app/controllers/DashboardController.php';
require_once __DIR__ . '/app/controllers/ServidorController.php';
require_once __DIR__ . '/app/controllers/CotacaoController.php';
require_once __DIR__ . '/app/controllers/LoteController.php';
require_once __DIR__ . '/app/controllers/PrecoController.php';
require_once __DIR__ . '/app/controllers/MapaController.php';
require_once __DIR__ . '/app/controllers/ParametroController.php';
require_once __DIR__ . '/app/controllers/RelatorioController.php';

$action = $_GET['action'] ?? 'dashboard';

switch ($action) {
    case 'dashboard':
        $controller = new DashboardController();
        $controller->mostrar();
        break;

    case 'servidores':
        $controller = new ServidorController();
        $controller->listar();
        break;

    case 'criar_servidor':
        $controller = new ServidorController();
        $controller->criar();
        break;

    case 'editar_servidor':
        $controller = new ServidorController();
        $controller->editar();
        break;

    case 'excluir_servidor':
        $controller = new ServidorController();
        $controller->excluir();
        break;

    case 'parametros':
        $controller = new ParametroController();
        $controller->listar();
        break;

    case 'criar_parametro':
        $controller = new ParametroController();
        $controller->criar();
        break;

    case 'editar_parametro':
        $controller = new ParametroController();
        $controller->editar();
        break;

    case 'excluir_parametro':
        $controller = new ParametroController();
        $controller->excluir();
        break;

    case 'criar_cotacao':
        $controller = new CotacaoController();
        $controller->criar();
        break;

    case 'cotacao':
        $id = (int) ($_GET['id'] ?? 0);
        $controller = new CotacaoController();
        $controller->mostrar($id);
        break;

    case 'finalizar_cotacao':
        $controller = new CotacaoController();
        $controller->finalizar();
        break;

    case 'excluir_cotacao':
        $controller = new CotacaoController();
        $controller->excluir();
        break;

    case 'criar_lote':
        $controller = new LoteController();
        $controller->criar();
        break;

    case 'excluir_lote':
        $controller = new LoteController();
        $controller->excluir();
        break;

    case 'adicionar_item':
        $controller = new LoteController();
        $controller->adicionarItem();
        break;

    case 'editar_item':
        $controller = new LoteController();
        $controller->editarItem();
        break;

    case 'excluir_item':
        $controller = new LoteController();
        $controller->excluirItem();
        break;

    case 'adicionar_preco':
        $controller = new PrecoController();
        $controller->adicionar();
        break;

    case 'editar_preco':
        $controller = new PrecoController();
        $controller->editar();
        break;

    case 'excluir_preco':
        $controller = new PrecoController();
        $controller->excluir();
        break;

    case 'mapa':
        $id = (int) ($_GET['id'] ?? 0);
        $controller = new MapaController();
        $controller->mostrar($id);
        break;

    case 'relatorio_formulario':
        $controller = new RelatorioController();
        $controller->formulario();
        break;

    case 'gerar_relatorio':
        $controller = new RelatorioController();
        $controller->gerar();
        break;

    case 'gerar_relatorio_pesquisa':
        $controller = new RelatorioController();
        $controller->gerarPesquisa();
        break;

    default:
        echo 'Página não encontrada.';
        break;
}