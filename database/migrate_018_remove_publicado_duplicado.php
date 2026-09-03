<?php

require_once __DIR__ . '/../app/models/Database.php';

// Etapas do Processo foi revertido depois desta migracao ser escrita
// (ver migrate_015 + reversao em app/models/EtapaProcesso.php). Se o
// arquivo do model nao existir mais, esta migracao virou no-op: nao
// tem nada pra ajustar.
if (!file_exists(__DIR__ . '/../app/models/EtapaProcesso.php')) {
    echo 'Migração 018 pulada: Etapas do Processo não é mais usada no sistema. Nada a fazer.';
    return;
}

require_once __DIR__ . '/../app/models/EtapaProcesso.php';

// "PUBLICADO" como etapa manual da Demanda ficava fora de ordem e duplicado
// com o status automático da Licitação (StatusLicitacao::Publicada, ligado
// ao campo "Edital" na tela do Processo) - que so existe DEPOIS que a
// Demanda vira Licitacao, entao sempre aparecia depois de Habilitacao na
// barra, mesmo publicado ser antes na vida real. Como nenhuma demanda esta
// parada nesse status hoje, remove com seguranca e deixa so o automatico.

$etapa = EtapaProcesso::buscarPorNome('PUBLICADO');

if ($etapa !== null) {
    $etapa->excluir();
    echo 'Etapa "PUBLICADO" removida (agora só existe o "Edital Publicado" automático da Licitação).<br>';
} else {
    echo 'Etapa "PUBLICADO" já não existia.<br>';
}

// Reordena o que sobrou pra fechar o buraco na sequencia.
$restantes = EtapaProcesso::buscarTodas();
foreach ($restantes as $indice => $etapaRestante) {
    $etapaRestante->ordem = $indice + 1;
    $etapaRestante->salvar();
}

echo '<br><b>Migração concluída.</b> Etapas do processo: ' . implode(' → ', array_map(fn($e) => $e->nome, $restantes)) . '.';
