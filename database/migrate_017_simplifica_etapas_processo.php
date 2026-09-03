<?php

require_once __DIR__ . '/../app/models/Database.php';

// Etapas do Processo foi revertido depois desta migracao ser escrita
// (ver migrate_015 + reversao em app/models/EtapaProcesso.php). Se o
// arquivo do model nao existir mais, esta migracao virou no-op: nao
// tem nada pra ajustar.
if (!file_exists(__DIR__ . '/../app/models/EtapaProcesso.php')) {
    echo 'Migração 017 pulada: Etapas do Processo não é mais usada no sistema. Nada a fazer.';
    return;
}

require_once __DIR__ . '/../app/models/EtapaProcesso.php';

// Simplifica a lista de etapas do processo, a pedido do usuario: a lista
// original (11 etapas, herdada do dropdown antigo hardcoded) tinha etapas
// nunca usadas na pratica e sem sequencia real. Fica so o que e realmente
// usado, na ordem real do fluxo. As etapas removidas cujo nome nao aparece
// mais aqui NAO tem processo algum usando elas hoje (conferido antes de
// rodar) - as que tem processo em andamento (Habilitacao, Parecer Juridico)
// so mudam de ordem, o nome exato e mantido pra nao perder o vinculo com
// demandas ja cadastradas nesse status.

$ordemFinal = [
    'ELABORAÇÃO DE PESQUISA DE PREÇO',
    'ELABORAÇÃO DE TR',
    'ENVIADO PARA PARECER JURÍDICO',
    'PUBLICADO',
    'FASE DE HABILITAÇÃO',
];

$remover = [
    'AVISO DE LICITAÇÃO',
    'AVISO DE DISPENSA DE LICITAÇÃO',
    'EMISSÃO DE PED RESERVA',
    'ENVIADO PARA CONDES',
    'ENVIADO PARA PGE',
    'SANEAMENTO DE PROCESSO',
];

foreach ($remover as $nome) {
    $etapa = EtapaProcesso::buscarPorNome($nome);
    if ($etapa !== null) {
        $etapa->excluir();
        echo "Etapa removida: {$nome}<br>";
    }
}

foreach ($ordemFinal as $indice => $nome) {
    $etapa = EtapaProcesso::buscarPorNome($nome);
    if ($etapa === null) {
        echo "Aviso: etapa \"{$nome}\" não encontrada, pulando.<br>";
        continue;
    }
    $etapa->ordem = $indice + 1;
    $etapa->salvar();
}

echo '<br><b>Migração concluída.</b> Etapas do processo simplificadas para: ' . implode(' → ', $ordemFinal) . '.';
