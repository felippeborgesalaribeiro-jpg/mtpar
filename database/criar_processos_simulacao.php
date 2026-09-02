<?php

/**
 * Cria 12 processos de SIMULAÇÃO, variados, pra visualizar o comportamento do
 * sistema em muitos estados diferentes (demandas em várias fases, licitações
 * publicada/homologada/encaminhada, economicidade positiva e negativa,
 * processo cancelado, planilha orçamentária e vantajosidades vantajosa e não
 * vantajosa).
 *
 * Tudo que este script cria usa o prefixo "SIM-". Rodar de novo APAGA os
 * processos SIM- anteriores e recria do zero. Ele é INDEPENDENTE do script
 * criar_processos_demonstracao.php (que usa o prefixo DEMO-): rodar um não
 * mexe no outro. Nenhum dos dois toca em processos reais.
 *
 * Uso: abra no navegador
 *   http://localhost/mtpar-teste/database/criar_processos_simulacao.php
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

echo '<style>body{font-family:sans-serif;max-width:820px;margin:30px auto;line-height:1.5;color:#222}li{margin:4px 0}code{background:#eee;padding:1px 5px;border-radius:3px}</style>';
echo '<h2>Criando processos de simulação</h2>';

/* ------------------------------------------------------------------ */
/*  1. LIMPA a simulação anterior (só o que começa com SIM-)           */
/* ------------------------------------------------------------------ */
foreach ($pdo->query("SELECT id FROM cotacoes WHERE numero_processo LIKE 'SIM-%'")->fetchAll(PDO::FETCH_COLUMN) as $cotacaoId) {
    $pdo->prepare('DELETE FROM cotacoes WHERE id = :id')->execute(['id' => $cotacaoId]);
}
$pdo->exec("DELETE FROM processos_vantajosidade WHERE numero_ata LIKE 'SIM-%'");
$pdo->exec("DELETE FROM demandas WHERE numero_processo LIKE 'SIM-%'");

echo '<p style="color:#666">Simulação anterior (processos SIM-) removida. Recriando…</p>';

/* ------------------------------------------------------------------ */
/*  2. Dados de apoio: servidores, parâmetros e setores                */
/* ------------------------------------------------------------------ */
$servidores = Servidor::buscarTodos();
if (count($servidores) === 0) {
    $s = new Servidor('Administrador', '', '', 'felippe', '', NivelAcesso::Admin);
    $s->definirSenha('123');
    $s->salvar();
    $servidores = [$s];
}
$resp = fn(int $i) => $servidores[$i % count($servidores)]->id;

$nomesParametrosExistentes = array_map(fn($p) => $p->nome, Parametro::buscarTodos());
foreach ([
    ['MÍDIA ESPECIALIZADA', false],
    ['ORÇAMENTO PRIVADO', false],
    ['PAINEL DE PREÇOS', true],
    ['ATA/CONTRATO PÚBLICO', true],
] as [$nome, $publico]) {
    if (!in_array($nome, $nomesParametrosExistentes, true)) {
        (new Parametro($nome, $publico))->salvar();
    }
}

foreach (['Setor de RH', 'Setor Jurídico', 'Setor de TI', 'Setor de Compras',
          'Diretoria Administrativa', 'Setor de Engenharia', 'Setor de Frotas'] as $setor) {
    if (SetorDemandante::buscarPorNome($setor) === null) {
        (new SetorDemandante($setor))->salvar();
    }
}

$criados = [];

/* Helper: cria uma cotação já com 1 lote e alguns itens/preços. */
$criarCotacaoComItens = function (Demanda $d, string $criterio, StatusCotacao $status, array $itens) use ($resp): Cotacao {
    $c = new Cotacao(
        numeroProcesso: $d->numeroProcesso, orgaoSetor: $d->setorDemandante,
        procedimento: 'Pregão Eletrônico', tipoJulgamento: 'Menor Preço',
        objeto: $d->objeto, servidorId: $d->servidorResponsavelId ?? $resp(0),
        criterioConsolidacao: $criterio, status: $status, demandaId: $d->id
    );
    $c->salvar();
    $lote = new Lote($c->id, '01');
    $lote->salvar();
    $n = 1;
    foreach ($itens as $it) {
        // $it = ['desc','und',qtd, [ [valor,parametro,fonte], ... ] ]
        $item = new Item($lote->id, $n++, $it[0], $it[1], $it[2]);
        $item->salvar();
        foreach ($it[3] as $p) {
            (new Preco($item->id, $p[0], $p[1] ?? '', $p[2] ?? ''))->salvar();
        }
    }
    return $c;
};

$hoje = fn(int $diasAtras) => date('Y-m-d', strtotime("-{$diasAtras} days"));

/* ================================================================== */
/*  1 — EM ANDAMENTO, recém-chegada, sem cotação                       */
/* ================================================================== */
$d = new Demanda('SIM-PRO-2026/0101', $hoje(2), '', 'Setor de RH',
    'Aquisição de cadeiras ergonômicas', $resp(0), 'EM ANDAMENTO');
$d->salvar();
$criados[] = 'SIM-PRO-2026/0101 — demanda <b>Em andamento</b> (2 dias), sem pesquisa de preço.';

/* ================================================================== */
/*  2 — ELABORAÇÃO DE TR, sem cotação                                  */
/* ================================================================== */
$d = new Demanda('SIM-PRO-2026/0102', $hoje(8), 'https://sigadoc.exemplo/0102', 'Setor Jurídico',
    'Contratação de assinatura de base jurídica online', $resp(1), 'ELABORAÇÃO DE TR');
$d->salvar();
$criados[] = 'SIM-PRO-2026/0102 — demanda em <b>Elaboração de TR</b> (8 dias), com link SIGADOC.';

/* ================================================================== */
/*  3 — PESQUISA DE PREÇO, cotação em andamento (análise 70/30)        */
/* ================================================================== */
$d = new Demanda('SIM-PRO-2026/0103', $hoje(18), '', 'Setor de TI',
    'Aquisição de periféricos de informática', $resp(2), 'ELABORAÇÃO DE PESQUISA DE PREÇO');
$d->salvar();
$criarCotacaoComItens($d, AnalisePrecos::CRITERIO_MEDIANA, StatusCotacao::EmAndamento, [
    ['Mouse sem fio', 'UN', 30, [[45.90,'MÍDIA ESPECIALIZADA','AMAZON'],[120.00,'ORÇAMENTO PRIVADO','FORNEC. A'],[52.00,'MÍDIA ESPECIALIZADA','KABUM'],[49.50,'MÍDIA ESPECIALIZADA','MERCADO LIVRE']]],
    ['Teclado ABNT2', 'UN', 30, [[89.00,'MÍDIA ESPECIALIZADA','AMAZON'],[95.00,'ORÇAMENTO PRIVADO','FORNEC. B'],[91.50,'MÍDIA ESPECIALIZADA','KABUM']]],
    ['Webcam Full HD', 'UN', 15, [[210.00,'MÍDIA ESPECIALIZADA','AMAZON'],[60.00,'ORÇAMENTO PRIVADO','FORNEC. C'],[199.00,'MÍDIA ESPECIALIZADA','KABUM']]],
]);
$criados[] = 'SIM-PRO-2026/0103 — cotação <b>em andamento</b> (3 itens) com preços excessivos e inexequíveis pra ver a análise 70/30.';

/* ================================================================== */
/*  4 — PARECER JURÍDICO, cotação finalizada (amarelo: 35 dias)        */
/* ================================================================== */
$d = new Demanda('SIM-PRO-2026/0104', $hoje(35), '', 'Setor de Compras',
    'Aquisição de material de escritório', $resp(3), 'ENVIADO PARA PARECER JURÍDICO');
$d->salvar();
$criarCotacaoComItens($d, AnalisePrecos::CRITERIO_MEDIA, StatusCotacao::Finalizada, [
    ['Resma papel A4', 'RS', 200, [[24.90,'MÍDIA ESPECIALIZADA','AMAZON'],[25.50,'ORÇAMENTO PRIVADO','FORNEC. A'],[24.50,'PAINEL DE PREÇOS','PAINEL FEDERAL']]],
    ['Caneta esferográfica (cx)', 'CX', 100, [[38.00,'MÍDIA ESPECIALIZADA','AMAZON'],[41.00,'ORÇAMENTO PRIVADO','FORNEC. B'],[39.50,'MÍDIA ESPECIALIZADA','KALUNGA']]],
]);
$criados[] = 'SIM-PRO-2026/0104 — <b>Parecer Jurídico</b> (35 dias, urgência amarela), cotação finalizada.';

/* ================================================================== */
/*  5 — FASE DE HABILITAÇÃO, licitação PUBLICADA (edital, s/ homolog.)  */
/* ================================================================== */
$d = new Demanda('SIM-PRO-2026/0105', $hoje(52), '', 'Setor de Engenharia',
    'Contratação de serviço de dedetização', $resp(0), 'FASE DE HABILITAÇÃO');
$d->salvar();
$criarCotacaoComItens($d, AnalisePrecos::CRITERIO_MEDIANA, StatusCotacao::Finalizada, [
    ['Dedetização trimestral', 'SV', 4, [[1200.00,'MÍDIA ESPECIALIZADA','EMP. A'],[1350.00,'ORÇAMENTO PRIVADO','EMP. B'],[1280.00,'ORÇAMENTO PRIVADO','EMP. C']]],
]);
$licP = Licitacao::criarApartirDeDemanda($d);
$licP->editalLicitacao = 'Edital 021/2026';
$licP->realizacaoSessaoPublica = $hoje(10);
$licP->salvar();
$criados[] = 'SIM-PRO-2026/0105 — licitação <b>Publicada</b> (tem edital, ainda sem homologação; 52 dias).';

/* ================================================================== */
/*  6 — CONCLUÍDO, licitação HOMOLOGADA, economicidade POSITIVA         */
/* ================================================================== */
$d = new Demanda('SIM-PRO-2026/0106', $hoje(75), '', 'Setor de Frotas',
    'Contratação de manutenção de veículos', $resp(1), 'CONCLUÍDO');
$d->salvar();
$criarCotacaoComItens($d, AnalisePrecos::CRITERIO_MEDIANA, StatusCotacao::Finalizada, [
    ['Manutenção mensal da frota', 'MÊS', 12, [[2500.00,'MÍDIA ESPECIALIZADA','OFIC. A'],[2650.00,'ORÇAMENTO PRIVADO','OFIC. B'],[2580.00,'ORÇAMENTO PRIVADO','OFIC. C']]],
]);
$licH = Licitacao::criarApartirDeDemanda($d);
$licH->editalLicitacao = 'Edital 018/2026';
$licH->realizacaoSessaoPublica = $hoje(45);
$licH->valorAdjudicado = 28000.00; // abaixo do estimado (12 x 2580 = 30.960) => economia positiva
$licH->dataAdjudicacaoHomologacao = $hoje(30);
$licH->salvar();
$criados[] = 'SIM-PRO-2026/0106 — <b>Concluído</b> (75 dias, urgência vermelha) com licitação <b>Homologada</b> e economicidade positiva.';

/* ================================================================== */
/*  7 — CONCLUÍDO, ENCAMINHADA P/ CONTRATAÇÃO, multi-lote (2 lotes)     */
/* ================================================================== */
$d = new Demanda('SIM-PRO-2026/0107', $hoje(120), '', 'Diretoria Administrativa',
    'Aquisição de equipamentos de copa e mobiliário', $resp(2), 'CONCLUÍDO');
$d->salvar();
$c7 = new Cotacao(
    numeroProcesso: $d->numeroProcesso, orgaoSetor: $d->setorDemandante,
    procedimento: 'Pregão Eletrônico', tipoJulgamento: 'Menor Preço',
    objeto: $d->objeto, servidorId: $resp(2),
    criterioConsolidacao: AnalisePrecos::CRITERIO_MEDIANA,
    status: StatusCotacao::Finalizada, demandaId: $d->id
);
$c7->salvar();
$l7a = new Lote($c7->id, '01'); $l7a->salvar();
$i = new Item($l7a->id, 1, 'Geladeira frost free', 'UN', 3); $i->salvar();
foreach ([[2800.00,'MÍDIA ESPECIALIZADA','MAGALU'],[2950.00,'ORÇAMENTO PRIVADO','FORNEC. A'],[2870.00,'MÍDIA ESPECIALIZADA','CASAS BAHIA']] as $p) (new Preco($i->id, $p[0], $p[1], $p[2]))->salvar();
$l7b = new Lote($c7->id, '02'); $l7b->salvar();
$i = new Item($l7b->id, 1, 'Mesa de reunião', 'UN', 2); $i->salvar();
foreach ([[1900.00,'MÍDIA ESPECIALIZADA','MADEIRAMADEIRA'],[2100.00,'ORÇAMENTO PRIVADO','FORNEC. B'],[1980.00,'ORÇAMENTO PRIVADO','FORNEC. C']] as $p) (new Preco($i->id, $p[0], $p[1], $p[2]))->salvar();
$licE = Licitacao::criarApartirDeDemanda($d);
$licE->editalLicitacao = 'Edital 009/2026';
$licE->realizacaoSessaoPublica = $hoje(80);
$licE->valorAdjudicado = 12500.00;
$licE->dataAdjudicacaoHomologacao = $hoje(70);
$licE->encaminhadoPactuacaoContrato = $hoje(60);
$licE->salvar();
$criados[] = 'SIM-PRO-2026/0107 — <b>Encaminhada para contratação</b> (fase final completa), cotação com <b>2 lotes</b>.';

/* ================================================================== */
/*  8 — CANCELADO                                                       */
/* ================================================================== */
$d = new Demanda('SIM-PRO-2026/0108', $hoje(40), '', 'Setor de Compras',
    'Aquisição de brindes institucionais (cancelada)', $resp(3), 'CANCELADO');
$d->salvar();
$criados[] = 'SIM-PRO-2026/0108 — demanda <b>Cancelada</b> (aparece com destaque próprio).';

/* ================================================================== */
/*  9 — PLANILHA ORÇAMENTÁRIA (valor único por item)                   */
/* ================================================================== */
$d = new Demanda('SIM-PRO-2026/0109', $hoje(14), '', 'Setor de Engenharia',
    'Reforma do almoxarifado (planilha orçamentária)', $resp(0), 'ELABORAÇÃO DE PESQUISA DE PREÇO');
$d->salvar();
$criarCotacaoComItens($d, AnalisePrecos::CRITERIO_PLANILHA_ORCAMENTARIA, StatusCotacao::EmAndamento, [
    ['Serviço de alvenaria e pintura (conforme planilha)', 'VB', 1, [[42000.00]]],
    ['Instalação elétrica', 'VB', 1, [[18500.00]]],
    ['Estrutura metálica de prateleiras', 'VB', 1, [[26300.00]]],
]);
$criados[] = 'SIM-PRO-2026/0109 — cotação por <b>Planilha Orçamentária</b> (valor único, 3 itens).';

/* ================================================================== */
/*  10 — CONCLUÍDO, HOMOLOGADA, economicidade NEGATIVA (acima)          */
/* ================================================================== */
$d = new Demanda('SIM-PRO-2026/0110', $hoje(95), '', 'Setor de TI',
    'Aquisição de licenças de software', $resp(1), 'CONCLUÍDO');
$d->salvar();
$criarCotacaoComItens($d, AnalisePrecos::CRITERIO_MEDIANA, StatusCotacao::Finalizada, [
    ['Licença anual de antivírus corporativo', 'UN', 50, [[180.00,'MÍDIA ESPECIALIZADA','REVENDA A'],[195.00,'ORÇAMENTO PRIVADO','REVENDA B'],[188.00,'ORÇAMENTO PRIVADO','REVENDA C']]],
]);
$licN = Licitacao::criarApartirDeDemanda($d);
$licN->editalLicitacao = 'Edital 025/2026';
$licN->realizacaoSessaoPublica = $hoje(50);
$licN->valorAdjudicado = 10500.00; // acima do estimado (50 x 188 = 9.400) => economicidade negativa
$licN->dataAdjudicacaoHomologacao = $hoje(35);
$licN->salvar();
$criados[] = 'SIM-PRO-2026/0110 — licitação <b>Homologada</b> mas com valor adjudicado <b>acima</b> do estimado (economicidade negativa, em vermelho).';

/* ================================================================== */
/*  11 — VANTAJOSIDADE: adesão a ATA, resultado VANTAJOSA               */
/* ================================================================== */
$v = new ProcessoVantajosidade(
    'SIM-ATA-2026/0111', 'Secretaria de Planejamento',
    'Adesão a ata de registro de preços de material de copa',
    $resp(2), ProcessoVantajosidade::STATUS_EM_ANDAMENTO,
    tipo: ProcessoVantajosidade::TIPO_ATA
);
$v->salvar();
$iv = new ItemVantajosidade($v->id, '01', '1', 12.00, 'Café em pó 500g', 'UN', 200); $iv->salvar();
foreach ([[15.00,'MÍDIA ESPECIALIZADA','MERCADO A'],[16.50,'ORÇAMENTO PRIVADO','MERCADO B'],[14.80,'MÍDIA ESPECIALIZADA','MERCADO C']] as $p) (new PrecoVantajosidade($iv->id, $p[0], $p[1], $p[2]))->salvar();
$iv = new ItemVantajosidade($v->id, '01', '2', 8.50, 'Açúcar refinado 1kg', 'UN', 300); $iv->salvar();
foreach ([[9.90,'MÍDIA ESPECIALIZADA','MERCADO A'],[10.20,'ORÇAMENTO PRIVADO','MERCADO B'],[9.50,'MÍDIA ESPECIALIZADA','MERCADO C']] as $p) (new PrecoVantajosidade($iv->id, $p[0], $p[1], $p[2]))->salvar();
$criados[] = 'SIM-ATA-2026/0111 — <b>Vantajosidade (ATA)</b>: preço da ata abaixo do mercado ⇒ resultado <b>VANTAJOSA</b>.';

/* ================================================================== */
/*  12 — VANTAJOSIDADE: CONTRATO/aditivo, resultado NÃO VANTAJOSA       */
/* ================================================================== */
$v = new ProcessoVantajosidade(
    'SIM-ATA-2026/0112', 'Secretaria de Administração',
    'Aditivo de contrato de prestação de serviços de limpeza',
    $resp(3), ProcessoVantajosidade::STATUS_EM_ANDAMENTO,
    tipo: ProcessoVantajosidade::TIPO_CONTRATO_ADITIVO, numeroContrato: 'CT-2025/044'
);
$v->salvar();
$iv = new ItemVantajosidade($v->id, '01', '1', 5200.00, 'Posto de serviço mensal', 'MÊS', 12); $iv->salvar();
foreach ([[4600.00,'MÍDIA ESPECIALIZADA','EMP. A'],[4800.00,'ORÇAMENTO PRIVADO','EMP. B'],[4700.00,'ORÇAMENTO PRIVADO','EMP. C']] as $p) (new PrecoVantajosidade($iv->id, $p[0], $p[1], $p[2]))->salvar();
$criados[] = 'SIM-ATA-2026/0112 — <b>Vantajosidade (CONTRATO/aditivo)</b>: preço do contrato acima do mercado ⇒ resultado <b>NÃO VANTAJOSA</b>.';

/* ------------------------------------------------------------------ */
/*  Resumo                                                              */
/* ------------------------------------------------------------------ */
echo '<p><b>Pronto! ' . count($criados) . ' processos de simulação criados:</b></p><ul>';
foreach ($criados as $linha) {
    echo '<li>' . $linha . '</li>';
}
echo '</ul>';
echo '<p>Abra o sistema em <a href="../index.php">../index.php</a> e navegue por <b>Demandas</b>, <b>Cotações</b>, <b>Licitações</b>, <b>Painel Aplic</b> e <b>Vantajosidades</b>.</p>';
echo '<p style="color:#666;font-size:13px">Rodar este arquivo de novo apaga estes 12 e recria do zero. É independente do <code>criar_processos_demonstracao.php</code> (prefixo DEMO-) e nunca mexe em processos reais.</p>';
