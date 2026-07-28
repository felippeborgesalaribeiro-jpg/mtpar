<?php

require_once __DIR__ . '/../app/models/Database.php';

$pdo = Database::getConnection();

// Vantajosidade passa a aceitar dois tipos: adesao a Ata de Registro de
// Precos (comportamento existente, tipo='ATA') ou comprovacao de
// vantajosidade de aditivo de contrato (tipo='CONTRATO_ADITIVO'), onde o
// indice do aditivo (%) e calculado a partir do valor total do objeto do
// contrato e do valor apurado no mapa comparativo - sem alterar em nada
// a logica de itens/precos/analise ja existente.

try {
    $pdo->exec("ALTER TABLE processos_vantajosidade ADD COLUMN tipo TEXT NOT NULL DEFAULT 'ATA'");
    echo 'Coluna tipo adicionada à tabela processos_vantajosidade.<br>';
} catch (PDOException $e) {
    echo 'Coluna tipo já existe em processos_vantajosidade ou erro: ' . $e->getMessage() . '<br>';
}

try {
    $pdo->exec("ALTER TABLE processos_vantajosidade ADD COLUMN numero_contrato TEXT NOT NULL DEFAULT ''");
    echo 'Coluna numero_contrato adicionada à tabela processos_vantajosidade.<br>';
} catch (PDOException $e) {
    echo 'Coluna numero_contrato já existe em processos_vantajosidade ou erro: ' . $e->getMessage() . '<br>';
}

try {
    $pdo->exec('ALTER TABLE processos_vantajosidade ADD COLUMN valor_total_objeto REAL');
    echo 'Coluna valor_total_objeto adicionada à tabela processos_vantajosidade.<br>';
} catch (PDOException $e) {
    echo 'Coluna valor_total_objeto já existe em processos_vantajosidade ou erro: ' . $e->getMessage() . '<br>';
}

echo '<br><b>Migração concluída.</b>';
