<?php

require_once __DIR__ . '/../app/models/Database.php';

$pdo = Database::getConnection();

// As etapas intermediarias que ja existiam hardcoded em Demanda::STATUS_OPCOES,
// na mesma ordem, viram a lista inicial editavel pelo admin. EM ANDAMENTO,
// CONCLUÍDO e CANCELADO continuam fixas no codigo, entao nao entram aqui.
$etapasIniciais = [
    'ELABORAÇÃO DE TR',
    'ELABORAÇÃO DE PESQUISA DE PREÇO',
    'AVISO DE LICITAÇÃO',
    'AVISO DE DISPENSA DE LICITAÇÃO',
    'EMISSÃO DE PED RESERVA',
    'FASE DE HABILITAÇÃO',
    'ENVIADO PARA CONDES',
    'ENVIADO PARA PARECER JURÍDICO',
    'ENVIADO PARA PGE',
    'SANEAMENTO DE PROCESSO',
    'PUBLICADO',
];

$stmt = $pdo->prepare('INSERT OR IGNORE INTO etapas_processo (nome, ordem) VALUES (:nome, :ordem)');

foreach ($etapasIniciais as $indice => $nome) {
    $stmt->execute(['nome' => $nome, 'ordem' => $indice + 1]);
}

echo count($etapasIniciais) . ' etapa(s) do processo cadastradas.<br>';
echo '<br><b>Migração concluída.</b>';
