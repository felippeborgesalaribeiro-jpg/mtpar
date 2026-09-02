<?php

/**
 * Roda os cenários das últimas correções pelo código REAL do sistema e
 * mostra PASSOU/FALHOU pra cada verificação. Não deixa nada no banco: cria
 * dados temporários (prefixo VERIF-), checa e apaga tudo no final.
 *
 * Uso: abra no navegador
 *   http://localhost/mtpar-teste/database/verificar_correcoes.php
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
require_once __DIR__ . '/../app/models/ProcessoVantajosidade.php';
require_once __DIR__ . '/../app/models/SituacaoLote.php';
require_once __DIR__ . '/../app/models/RepublicacaoLote.php';

$pdo = Database::getConnection();
$pdo->exec('PRAGMA foreign_keys = ON');

echo '<style>body{font-family:sans-serif;max-width:820px;margin:30px auto;line-height:1.6;color:#222}'
   . 'h2{margin-top:26px}.ok{color:#127a20;font-weight:bold}.fail{color:#b02a37;font-weight:bold}'
   . '.item{margin:6px 0}small{color:#666}</style>';
echo '<h1>Verificação das correções</h1>';

/* ------------------------------------------------------------------ */
/*  Utilitários                                                         */
/* ------------------------------------------------------------------ */
$falhas = 0;
$checar = function (string $descricao, bool $condicao) use (&$falhas) {
    if ($condicao) {
        echo "<div class='item'><span class='ok'>✓ PASSOU</span> — {$descricao}</div>";
    } else {
        echo "<div class='item'><span class='fail'>✗ FALHOU</span> — {$descricao}</div>";
        $falhas++;
    }
};

// Limpa qualquer resíduo de uma execução anterior (só VERIF-).
$limpar = function () use ($pdo) {
    foreach ($pdo->query("SELECT id FROM cotacoes WHERE numero_processo LIKE 'VERIF-%'")->fetchAll(PDO::FETCH_COLUMN) as $cid) {
        $pdo->prepare('DELETE FROM cotacoes WHERE id = :id')->execute(['id' => $cid]);
    }
    $pdo->exec("DELETE FROM processos_vantajosidade WHERE numero_ata LIKE 'VERIF-%'");
    $pdo->exec("DELETE FROM demandas WHERE numero_processo LIKE 'VERIF-%'");
    $pdo->exec("DELETE FROM servidores WHERE nome = 'VERIF Servidor'");
};
$limpar();

$servidor = new Servidor('VERIF Servidor', '', '', '', '', NivelAcesso::Comum);
$servidor->salvar();

/* ================================================================== */
/*  #1 — Licitação fantasma em Vantajosidade                           */
/* ================================================================== */
echo '<h2>Correção #1 — Concluir processo não cria licitação fantasma</h2>';

// 1a) processo NORMAL concluído DEVE gerar licitação.
$dNormal = new Demanda('VERIF-PRO-0001', '2026-01-10', '', 'Setor A', 'Compra normal', $servidor->id, 'CONCLUÍDO');
$dNormal->salvar();
$licNormal = Licitacao::gerarAoConcluirDemanda($dNormal);
$checar('Processo normal concluído gera a licitação (comportamento esperado se mantém)', $licNormal !== null);

// 1b) processo de VANTAJOSIDADE concluído NÃO pode gerar licitação.
$dVant = new Demanda('VERIF-PRO-0002', '2026-01-10', '', 'Setor B', 'Adesão a ata', $servidor->id, 'CONCLUÍDO');
$dVant->salvar();
$vant = new ProcessoVantajosidade(
    'VERIF-ATA-0002', 'Órgão Gerenciador', 'Adesão a ata de registro de preços',
    $servidor->id, ProcessoVantajosidade::STATUS_EM_ANDAMENTO, demandaId: $dVant->id
);
$vant->salvar();
$licVant = Licitacao::gerarAoConcluirDemanda($dVant);
$checar('Processo de VANTAJOSIDADE concluído NÃO cria licitação fantasma', $licVant === null);
$checar('...e nada de licitação sobra no banco para esse processo', Licitacao::buscarPorDemandaId($dVant->id) === null);

/* ================================================================== */
/*  Demanda ↔ Licitação sempre em sincronia (fonte única de verdade)   */
/* ================================================================== */
echo '<h2>Demanda ↔ Licitação — editar a Demanda reflete na Licitação</h2>';

$dSync = new Demanda('VERIF-PRO-0003', '2026-01-10', '', 'Setor Original', 'Objeto original', $servidor->id, 'CONCLUÍDO');
$dSync->salvar();
$licSync = Licitacao::criarApartirDeDemanda($dSync);

// Edita só a Demanda depois que a Licitação já existe.
$dSync->setorDemandante = 'Setor Corrigido';
$dSync->objeto = 'Objeto corrigido';
$dSync->numeroProcesso = 'VERIF-PRO-0003-B';
$dSync->salvar();

$licRecarregada = Licitacao::buscarPorId($licSync->id);
$checar('Setor editado na Demanda aparece na Licitação', $licRecarregada->setorDemandante === 'Setor Corrigido');
$checar('Objeto editado na Demanda aparece na Licitação', $licRecarregada->objeto === 'Objeto corrigido');
$checar('Nº do processo editado na Demanda aparece na Licitação', $licRecarregada->numeroProcesso === 'VERIF-PRO-0003-B');

/* ================================================================== */
/*  #2 — Reenvio duplicado do lote fracassado não quebra nem duplica   */
/* ================================================================== */
echo '<h2>Correção #2 — Reenvio do "lote fracassado/deserto" não quebra</h2>';

$dLote = new Demanda('VERIF-PRO-0004', '2026-01-10', '', 'Setor C', 'Processo com lote', $servidor->id, 'CONCLUÍDO');
$dLote->salvar();
$cLote = new Cotacao(
    numeroProcesso: 'VERIF-PRO-0004', orgaoSetor: '', procedimento: '', tipoJulgamento: '',
    objeto: 'Processo com lote', servidorId: $servidor->id,
    criterioConsolidacao: AnalisePrecos::CRITERIO_MEDIANA,
    status: StatusCotacao::Finalizada, demandaId: $dLote->id
);
$cLote->salvar();
$loteLote = new Lote($cLote->id, '01');
$loteLote->salvar();
$itemLote = new Item($loteLote->id, 1, 'Item', 'UN', 1);
$itemLote->salvar();
(new Preco($itemLote->id, 100))->salvar();
$licLote = Licitacao::criarApartirDeDemanda($dLote);

// --- Parte A: marcar o lote como fracassado é seguro contra reenvio ---
(new SituacaoLote($licLote->id, $loteLote->id, SituacaoLote::FRACASSADO, 'Sem propostas', date('Y-m-d')))->salvar();
// 2ª marcação (reenvio): não pode quebrar nem duplicar.
$marcarDeNovoQuebrou = false;
try {
    (new SituacaoLote($licLote->id, $loteLote->id, SituacaoLote::FRACASSADO, 'Sem propostas', date('Y-m-d')))->salvar();
} catch (Throwable) {
    $marcarDeNovoQuebrou = true;
}
$qtdSituacoes = (int) $pdo->query(
    "SELECT COUNT(*) FROM situacoes_lote WHERE licitacao_id = {$licLote->id} AND lote_id = {$loteLote->id}"
)->fetchColumn();
$checar('Marcar o lote como fracassado de novo (reenvio) não quebra a página', !$marcarDeNovoQuebrou);
$checar('...e não duplica: continua com apenas 1 situação registrada', $qtdSituacoes === 1);

// --- Parte B: a REPUBLICAÇÃO (onde o crash acontecia de verdade) ---
// Cria a 1ª rodada de republicação, como o sistema faz ao republicar o lote.
$cRepub = new Cotacao(
    numeroProcesso: 'VERIF-PRO-0004-R2', orgaoSetor: '', procedimento: '', tipoJulgamento: '',
    objeto: 'Republicação', servidorId: $servidor->id,
    criterioConsolidacao: AnalisePrecos::CRITERIO_MEDIANA, ehRepublicacaoLote: true
);
$cRepub->salvar();
$loteNovo = new Lote($cRepub->id, '01');
$loteNovo->salvar();
(new RepublicacaoLote($licLote->id, $loteLote->id, $loteNovo->id, $cRepub->id, 2, SituacaoLote::FRACASSADO, 'Sem propostas'))->salvar();

// É esta checagem que o sistema passou a fazer antes de republicar de novo:
// como já existe uma republicação para este lote, o 2º envio é ignorado.
$jaRepublicado = RepublicacaoLote::buscarPorLoteAnterior($loteLote->id) !== null;
$checar('Após republicar uma vez, o sistema detecta que o lote já foi republicado (o 2º envio é ignorado)', $jaRepublicado);

// Prova de que a proteção é necessária: sem o guard, republicar de novo
// estouraria erro fatal (trava de unicidade em republicacoes_lote) — era
// exatamente este o crash que a correção evita.
$loteNovo2 = new Lote($cRepub->id, '02');
$loteNovo2->salvar();
$republicarDeNovoQuebra = false;
try {
    (new RepublicacaoLote($licLote->id, $loteLote->id, $loteNovo2->id, $cRepub->id, 3, SituacaoLote::FRACASSADO, 'Sem propostas'))->salvar();
} catch (Throwable) {
    $republicarDeNovoQuebra = true;
}
$checar('Confirmado: republicar de novo sem a proteção quebraria — é este o crash que a correção evita', $republicarDeNovoQuebra);

$qtdRepub = (int) $pdo->query(
    "SELECT COUNT(*) FROM republicacoes_lote WHERE lote_anterior_id = {$loteLote->id}"
)->fetchColumn();
$checar('A republicação continua com apenas 1 rodada registrada (não duplicou)', $qtdRepub === 1);

/* ------------------------------------------------------------------ */
/*  Limpeza + resumo                                                    */
/* ------------------------------------------------------------------ */
$limpar();

echo '<h2>Resultado</h2>';
if ($falhas === 0) {
    echo "<p class='ok' style='font-size:18px'>✓ Todas as verificações passaram. As correções estão funcionando.</p>";
} else {
    echo "<p class='fail' style='font-size:18px'>✗ {$falhas} verificação(ões) falharam — me avise para investigar.</p>";
}
echo '<p><small>Este script não deixa nada no banco: tudo que ele criou (prefixo VERIF-) foi apagado no final.</small></p>';
