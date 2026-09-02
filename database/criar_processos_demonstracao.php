<?php

/**
 * Cria 5 processos de DEMONSTRAÇÃO, variados, pra visualizar o comportamento
 * do sistema (demandas em fases diferentes, cotação com análise 70/30,
 * licitação homologada, planilha orçamentária e vantajosidade de ata).
 *
 * Tudo que este script cria usa o prefixo "DEMO-" no número do processo/ata.
 * Rodar de novo APAGA os processos DEMO- anteriores e recria do zero - então
 * dá pra rodar quantas vezes quiser pra "resetar" a demonstração. Ele NUNCA
 * toca em nenhum processo real (que não comece com DEMO-).
 *
 * Uso: abra no navegador
 *   http://localhost/mtpar-teste/database/criar_processos_demonstracao.php
 */

require_once __DIR__ . '/../app/models/Database.php';
require_once __DIR__ . '/../app/models/Servidor.php';
require_once __DIR__ . '/../app/models/NivelAcesso.php';
require_once __DIR__ . '/../app/models/Demanda.php';
require_once __DIR__ . '/../app/models/Cotacao.php';
require_once __DIR__ . '/../app/models/StatusCotacao.php';
require_once __DIR__ . '/../app/models/AnalisePrecos.php';
require_once __DIR__ . '/../app/models/Lote.php';
require_once __DIR__ . '/../app/models/Item.php';
require_once __DIR__ . '/../app/models/Preco.php';
require_once __DIR__ . '/../app/models/Licitacao.php';
require_once __DIR__ . '/../app/models/Parametro.php';
require_once __DIR__ . '/../app/models/SetorDemandante.php';
require_once __DIR__ . '/../app/models/ProcessoVantajosidade.php';
require_once __DIR__ . '/../app/models/ItemVantajosidade.php';
require_once __DIR__ . '/../app/models/PrecoVantajosidade.php';

$pdo = Database::getConnection();
$pdo->exec('PRAGMA foreign_keys = ON');

echo '<style>body{font-family:sans-serif;max-width:800px;margin:30px auto;line-height:1.5;color:#222}li{margin:4px 0}code{background:#eee;padding:1px 5px;border-radius:3px}</style>';
echo '<h2>Criando processos de demonstração</h2>';

/* ------------------------------------------------------------------ */
/*  1. LIMPA a demonstração anterior (só o que começa com DEMO-)       */
/* ------------------------------------------------------------------ */
// Cotações primeiro: a FK demanda_id nao tem cascade, entao a demanda nao
// pode ser apagada enquanto uma cotacao apontar pra ela. Apagar a cotacao
// leva junto lotes/itens/precos por cascata.
foreach ($pdo->query("SELECT id FROM cotacoes WHERE numero_processo LIKE 'DEMO-%'")->fetchAll(PDO::FETCH_COLUMN) as $cotacaoId) {
    $pdo->prepare('DELETE FROM cotacoes WHERE id = :id')->execute(['id' => $cotacaoId]);
}
// Vantajosidades (cascateia itens e precos de vantajosidade).
$pdo->exec("DELETE FROM processos_vantajosidade WHERE numero_ata LIKE 'DEMO-%'");
// Demandas por ultimo (cascateia licitacoes e tudo ligado a elas).
$pdo->exec("DELETE FROM demandas WHERE numero_processo LIKE 'DEMO-%'");

echo '<p style="color:#666">Demonstração anterior (processos DEMO-) removida. Recriando…</p>';

/* ------------------------------------------------------------------ */
/*  2. Dados de apoio: servidores, parâmetros e setores                */
/* ------------------------------------------------------------------ */
$servidores = Servidor::buscarTodos();
if (count($servidores) === 0) {
    // Nao deveria acontecer (criar_primeiro_admin ja cria um), mas garante.
    $s = new Servidor('Administrador', '', '', 'felippe', '', NivelAcesso::Admin);
    $s->definirSenha('123');
    $s->salvar();
    $servidores = [$s];
}
$resp = fn(int $i) => $servidores[$i % count($servidores)]->id;

// Parâmetros de pesquisa (só cria os que ainda não existem).
$nomesParametrosExistentes = array_map(fn($p) => $p->nome, Parametro::buscarTodos());
$parametrosDesejados = [
    ['MÍDIA ESPECIALIZADA', false],
    ['ORÇAMENTO PRIVADO', false],
    ['PAINEL DE PREÇOS', true],
    ['ATA/CONTRATO PÚBLICO', true],
];
foreach ($parametrosDesejados as [$nome, $publico]) {
    if (!in_array($nome, $nomesParametrosExistentes, true)) {
        (new Parametro($nome, $publico))->salvar();
    }
}

// Setores demandantes (só cria os que ainda não existem).
foreach (['Setor de TI', 'Setor de Compras', 'Diretoria Administrativa', 'Setor de Engenharia'] as $setor) {
    if (SetorDemandante::buscarPorNome($setor) === null) {
        (new SetorDemandante($setor))->salvar();
    }
}

$criados = [];

/* ------------------------------------------------------------------ */
/*  PROCESSO 1 — Demanda recém-chegada, fase inicial, sem cotação      */
/* ------------------------------------------------------------------ */
$d1 = new Demanda(
    'DEMO-PRO-2026/0001', date('Y-m-d', strtotime('-5 days')), '',
    'Setor de TI', 'Aquisição de notebooks para a equipe técnica',
    $resp(0), 'ELABORAÇÃO DE TR'
);
$d1->salvar();
$criados[] = 'Processo 1 — <b>' . $d1->numeroProcesso . '</b>: demanda em <i>Elaboração de TR</i>, ainda sem pesquisa de preço.';

/* ------------------------------------------------------------------ */
/*  PROCESSO 2 — Demanda com Cotação EM ANDAMENTO (análise 70/30)      */
/* ------------------------------------------------------------------ */
$d2 = new Demanda(
    'DEMO-PRO-2026/0002', date('Y-m-d', strtotime('-20 days')), '',
    'Setor de Compras', 'Aquisição de material de limpeza',
    $resp(1), 'ELABORAÇÃO DE PESQUISA DE PREÇO'
);
$d2->salvar();

$c2 = new Cotacao(
    numeroProcesso: $d2->numeroProcesso, orgaoSetor: $d2->setorDemandante,
    procedimento: 'Pregão Eletrônico', tipoJulgamento: 'Menor Preço',
    objeto: $d2->objeto, servidorId: $resp(1),
    criterioConsolidacao: AnalisePrecos::CRITERIO_MEDIANA,
    status: StatusCotacao::EmAndamento, demandaId: $d2->id
);
$c2->salvar();

$l2 = new Lote($c2->id, '01');
$l2->salvar();

// Item com preços que disparam a análise: 19,69 e 18,24 excessivos; 5,79
// inexequível; o resto aprovado. (mesmos valores do exemplo real do setor)
$i2a = new Item($l2->id, 1, 'Detergente neutro 5L', 'GL', 100);
$i2a->salvar();
foreach ([
    [19.69, 'ORÇAMENTO PRIVADO', 'ASTER SOLLO'],
    [5.79,  'ORÇAMENTO PRIVADO', '7HUB'],
    [18.24, 'MÍDIA ESPECIALIZADA', 'MAGAZINE LUIZA'],
    [12.50, 'MÍDIA ESPECIALIZADA', 'AMAZON'],
    [13.40, 'MÍDIA ESPECIALIZADA', 'LIMPPANO'],
    [14.90, 'MÍDIA ESPECIALIZADA', 'COBASI'],
] as [$v, $p, $f]) {
    (new Preco($i2a->id, $v, $p, $f))->salvar();
}

$i2b = new Item($l2->id, 2, 'Papel toalha interfolha (fardo)', 'FD', 50);
$i2b->salvar();
foreach ([
    [22.00, 'MÍDIA ESPECIALIZADA', 'AMAZON'],
    [24.50, 'MÍDIA ESPECIALIZADA', 'MERCADO LIVRE'],
    [23.10, 'ORÇAMENTO PRIVADO', 'DISTRIBUIDORA X'],
] as [$v, $p, $f]) {
    (new Preco($i2b->id, $v, $p, $f))->salvar();
}
$criados[] = 'Processo 2 — <b>' . $d2->numeroProcesso . '</b>: cotação <i>em andamento</i> (1 lote, 2 itens) com preços excessivos e inexequíveis pra ver a análise 70/30 e o mapa.';

/* ------------------------------------------------------------------ */
/*  PROCESSO 3 — Concluído: Cotação finalizada + Licitação homologada  */
/* ------------------------------------------------------------------ */
$d3 = new Demanda(
    'DEMO-PRO-2026/0003', date('Y-m-d', strtotime('-90 days')), '',
    'Setor de Engenharia', 'Contratação de serviço de manutenção predial',
    $resp(0), 'CONCLUÍDO'
);
$d3->salvar();

$c3 = new Cotacao(
    numeroProcesso: $d3->numeroProcesso, orgaoSetor: $d3->setorDemandante,
    procedimento: 'Pregão Eletrônico', tipoJulgamento: 'Menor Preço',
    objeto: $d3->objeto, servidorId: $resp(0),
    criterioConsolidacao: AnalisePrecos::CRITERIO_MEDIANA,
    status: StatusCotacao::Finalizada, demandaId: $d3->id
);
$c3->salvar();

$l3 = new Lote($c3->id, '01');
$l3->salvar();
$i3 = new Item($l3->id, 1, 'Serviço de manutenção mensal', 'MÊS', 12);
$i3->salvar();
foreach ([
    [1500.00, 'MÍDIA ESPECIALIZADA', 'EMPRESA A'],
    [1620.00, 'ORÇAMENTO PRIVADO', 'EMPRESA B'],
    [1580.00, 'ORÇAMENTO PRIVADO', 'EMPRESA C'],
] as [$v, $p, $f]) {
    (new Preco($i3->id, $v, $p, $f))->salvar();
}

// Licitação nasce da demanda concluída, e a gente avança até homologada.
$lic3 = Licitacao::criarApartirDeDemanda($d3);
$lic3->editalLicitacao = 'Edital 015/2026';
$lic3->realizacaoSessaoPublica = date('Y-m-d', strtotime('-40 days'));
$lic3->valorAdjudicado = 17400.00; // abaixo do estimado (12 x 1580 = 18.960) => economicidade positiva
$lic3->dataAdjudicacaoHomologacao = date('Y-m-d', strtotime('-30 days'));
$lic3->salvar();
$criados[] = 'Processo 3 — <b>' . $d3->numeroProcesso . '</b>: demanda <i>Concluída</i> com cotação finalizada e <b>licitação homologada</b> (mostra economicidade e o painel Aplic pendente).';

/* ------------------------------------------------------------------ */
/*  PROCESSO 4 — Cotação por PLANILHA ORÇAMENTÁRIA (valor único)       */
/* ------------------------------------------------------------------ */
$d4 = new Demanda(
    'DEMO-PRO-2026/0004', date('Y-m-d', strtotime('-12 days')), '',
    'Diretoria Administrativa', 'Reforma da sala de reuniões (rito de planilha orçamentária)',
    $resp(1), 'ELABORAÇÃO DE PESQUISA DE PREÇO'
);
$d4->salvar();

$c4 = new Cotacao(
    numeroProcesso: $d4->numeroProcesso, orgaoSetor: $d4->setorDemandante,
    procedimento: 'Dispensa', tipoJulgamento: 'Menor Preço',
    objeto: $d4->objeto, servidorId: $resp(1),
    criterioConsolidacao: AnalisePrecos::CRITERIO_PLANILHA_ORCAMENTARIA,
    status: StatusCotacao::EmAndamento, demandaId: $d4->id
);
$c4->salvar();

$l4 = new Lote($c4->id, '01');
$l4->salvar();
$i4a = new Item($l4->id, 1, 'Mão de obra e material (conforme planilha)', 'VB', 1);
$i4a->salvar();
(new Preco($i4a->id, 28500.00))->salvar(); // valor único, sem comparação
$i4b = new Item($l4->id, 2, 'Mobiliário sob medida', 'VB', 1);
$i4b->salvar();
(new Preco($i4b->id, 15750.00))->salvar();
$criados[] = 'Processo 4 — <b>' . $d4->numeroProcesso . '</b>: cotação por <b>Planilha Orçamentária</b> (valor único por item, sem comparação de preços).';

/* ------------------------------------------------------------------ */
/*  PROCESSO 5 — Vantajosidade de Ata de Registro de Preços            */
/* ------------------------------------------------------------------ */
$v5 = new ProcessoVantajosidade(
    'DEMO-ATA-2026/0005', 'Secretaria de Estado de Planejamento',
    'Adesão a ata de registro de preços de equipamentos de informática',
    $resp(0), ProcessoVantajosidade::STATUS_EM_ANDAMENTO
);
$v5->salvar();

$iv1 = new ItemVantajosidade($v5->id, '01', '1', 3200.00, 'Computador desktop i5', 'UN', 10);
$iv1->salvar();
foreach ([
    [3450.00, 'MÍDIA ESPECIALIZADA', 'AMAZON'],
    [3600.00, 'ORÇAMENTO PRIVADO', 'FORNECEDOR X'],
    [3390.00, 'MÍDIA ESPECIALIZADA', 'KABUM'],
] as [$v, $p, $f]) {
    (new PrecoVantajosidade($iv1->id, $v, $p, $f))->salvar();
}

$iv2 = new ItemVantajosidade($v5->id, '01', '2', 890.00, 'Monitor 24"', 'UN', 10);
$iv2->salvar();
foreach ([
    [950.00, 'MÍDIA ESPECIALIZADA', 'AMAZON'],
    [1020.00, 'ORÇAMENTO PRIVADO', 'FORNECEDOR Y'],
    [910.00, 'MÍDIA ESPECIALIZADA', 'KABUM'],
] as [$v, $p, $f]) {
    (new PrecoVantajosidade($iv2->id, $v, $p, $f))->salvar();
}
$criados[] = 'Processo 5 — <b>' . $v5->numeroAta . '</b>: <b>Vantajosidade</b> de ata (preço da ata x preços de mercado, pra ver se a adesão é vantajosa).';

/* ------------------------------------------------------------------ */
/*  Resumo                                                              */
/* ------------------------------------------------------------------ */
echo '<p><b>Pronto! 5 processos de demonstração criados:</b></p><ul>';
foreach ($criados as $linha) {
    echo '<li>' . $linha . '</li>';
}
echo '</ul>';
echo '<p>Abra o sistema em <a href="../index.php">../index.php</a> e navegue por <b>Demandas</b>, <b>Cotações</b>, <b>Licitações</b> e <b>Vantajosidades</b> pra ver cada um.</p>';
echo '<p style="color:#666;font-size:13px">Rodar este arquivo de novo apaga estes 5 e recria tudo do zero. Ele nunca mexe em processos reais (só nos que começam com <code>DEMO-</code>).</p>';
