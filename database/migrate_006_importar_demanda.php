<?php

require_once __DIR__ . '/../app/models/Database.php';
require_once __DIR__ . '/../app/models/Demanda.php';

// Cada linha: [status, numero_processo, setor_demandante, data_recebimento, objeto, observacao_referencia, responsavel_original_referencia]
// As colunas "observacao_referencia" e "responsavel_original_referencia" sao SO PARA SUA CONSULTA visual abaixo,
// nao sao gravadas no banco (o sistema nao tem campo de observacao, e o servidor responsavel fica em branco
// ate voce cadastrar e vincular manualmente pelo sistema).
$linhasImportar = [
    ['EM ANDAMENTO', 'MTPAR-PRO-2026/00050', 'Núcleo Proj. Estratégicos', '2026-01-12', 'Aquisição de Móveis Soltos', 'cotação do lote fracassado - setor demandante', 'Marcos'],
    ['FASE DE HABILITAÇÃO', 'MTPAR-PRO-2026/00060', 'Núcleo Proj. Estratégicos', '2026-01-13', 'Splash do Parque Novo Mato Grosso', 'Habilitação', 'Tayná'],
    ['EM ANDAMENTO', 'MTPAR-PRO-2026/00551', 'Gabinete de Direção', '2026-03-13', 'Aquisição dos painéis de LED destinados à cenografia da Árvore da Vida do Parque Novo Mato Grosso, com fornecimento de todos os acessórios necessários à sua implantação.', 'publicado', 'Felippe'],
    ['FASE DE HABILITAÇÃO', 'MTPAR-PRO-2026/00569', 'Parque Novo MT', '2026-03-18', 'Aquisição Insumos para Execução da rede de esgoto e Estação Elevatória de Esgoto, que compõe o Sistema de Esgotamento Sanitário do Parque Novo Mato Grosso.', 'Sessão - 19/06/2026', 'Tayná'],
    ['EM ANDAMENTO', 'MTPAR-PRO-2026/00601', 'Núcleo Proj. Estratégicos', '2026-04-07', 'Aquisição de dois sistemas profissionais de largada Pro Gate para pista de BMX/Bicicross, a serem instalados na pista do Parque Novo Mato Grosso.', 'Aguardando resposta área demandante', 'Felippe'],
    ['EM ANDAMENTO', 'MTPAR-PRO-2026/00804', 'UTI', '2026-04-27', 'Aquisição de licenças de softwares corporativos', 'Encaminhado dia 22/05', 'Felippe'],
    ['FASE DE HABILITAÇÃO', 'MTPAR-PRO-2026/00812', 'Núcleo Proj. Engenharia', '2026-04-29', 'Aquisição de bebedouros para edificações do Parque Novo Mato Grosso', 'Habilitação', 'Marcos'],
    ['EM ANDAMENTO', 'MTPAR-PRO-2026/00896', 'Parque Novo MT', '2026-05-11', 'Aquisição de Peças de Desgaste Bomba e Canhão Para Caminhões Pipa', 'aguardando documentos da empresa', 'Ana'],
    ['EM ANDAMENTO', 'MTPAR-PRO-2026/00959', 'Núcleo Proj. Engenharia', '2026-05-18', 'Pintura da Pista de Bicricross, Complexo Extreme', 'Sessão dia 23/06', 'Tayná'],
    ['ENVIADO PARA PARECER JURÍDICO', 'MTPAR-PRO-2026/00904', 'Núcleo Proj. Engenharia', '2026-05-28', 'Contratação de empresa especializada para execução das obras da Cenografia da casa cuiabana, no Parque Novo Mato Grosso', 'Jurídico - 09/06/2026', 'Ana'],
    ['ELABORAÇÃO DE PESQUISA DE PREÇO', 'MTPAR-DIC-2026/04858', 'Núcleo Adm', '2026-05-15', 'Contratação de empresa especializada para prestação de serviços de manutenção preventiva e corretiva em sistemas de climatização', '', 'Marcos'],
    ['EM ANDAMENTO', 'MTPAR-PRO-2026/00968', 'Gabinete de Direção', '2026-05-27', 'Contratação de empresa especializada para prestação de serviços integrados de suporte em saúde, urgência e emergência no Parque Novo Mato Grosso, com cobertura permanente e cobertura eventual sob demanda.', 'Enviado p/ Condes dia 02/06', 'Felippe'],
    ['ELABORAÇÃO DE TR', 'MTPAR-DIC-2026/05484', 'Núcleo Proj. Engenharia', '2026-06-03', 'Aquisição de Geradores', 'Cotação', 'Ana'],
    ['EM ANDAMENTO', 'MTPAR-PRO-2026/01129', 'Núcleo Proj. Engenharia', '2026-06-08', 'CONTRATAÇÃO DE EMPRESA ESPECIALIZADA PARA EXECUÇÃO DOS SERVIÇOS DE INSTALAÇÃO DO SISTEMA DE CLIMATIZAÇÃO DA PRAÇA DA ORLA – TELÃO DO PARQUE NOVO MATO GROSSO.', '', 'Felippe'],
    ['ENVIADO PARA PARECER JURÍDICO', 'MTPAR-PRO-2026/01115', 'Núcleo Proj. Engenharia', '2026-06-08', 'Contratação de Empresa Especializada para Serviço de Instalação de Sistema de Telegestão de Iluminação do Parque Novo Mato Grosso.', 'Encaminhado dia 10/06', 'Ana'],
    ['EM ANDAMENTO', 'MTPAR-PRO-2026/01165', 'Núcleo Adm', '2026-06-11', 'Contratação de Serviço de Treinamento e Aperfeiçoamento de Pessoal, por Inexigibilidade de Licitação, consubstanciado na Contratação de Treinamento Técnico Especializado sobre o Sistema Domínio (Módulos Folha de Pagamento e Escrita Fiscal/Contabilidade), a ser Ministrado pela Empresa CS - The Best', 'Empresa não encaminha os documentos de habilitação', 'Ana'],
    ['ELABORAÇÃO DE PESQUISA DE PREÇO', 'MTPAR-PRO-2026/01173', 'Gabinete de Direção', '2026-06-11', 'Aquisição de 01 (uma) unidade de frigobar destinado a assessoria da presidência.', 'Dispensa', 'Tayná'],
    ['EM ANDAMENTO', 'MTPAR-PRO-2026/01224', 'Gabinete de Direção', '2026-06-17', 'Contratação de empresa especializada na prestação de serviços de apoio logístico e fornecimento de materiais para eventos, atos e solenidades via Ata de Registro de Preço 009/2024/SEPLAG (Pregão Eletrônico n° 008/2024/SEPLAG)', 'Adesão ATA', 'Felippe'],
    ['EM ANDAMENTO', 'MTPAR-PRO-2026/01234', 'Gabinete de Direção', '2026-06-18', 'Adesão à Ata de Registro de Preços nº 013/2025 do Pregão Eletrônico SRP nº 0010/2025/CIDES-VRC, para contratação de empresa especializada em prestação de serviços e fornecimento de infraestrutura logística para realização de eventos institucionais da MT Par.', 'Adesão ATA', 'Felippe'],
    ['ELABORAÇÃO DE TR', 'MTPAR-DIC-2026/06166', 'Gabinete de Direção', '2026-06-17', 'AQUISIÇÃO DE ESTRUTURAS METÁLICAS TIPO BOX TRUSS PARA IMPLANTAÇÃO, AMPLIAÇÃO E MELHORIA DOS SISTEMAS DE ILUMINAÇÃO CÊNICA, SONORIZAÇÃO E SUPORTE DE EQUIPAMENTOS PARA EVENTOS REALIZADOS NO PARQUE NOVO MATO GROSSO.', 'TAC', 'Viviann'],
    ['EM ANDAMENTO', 'MTPAR-PRO-2026/01227', 'Gabinete de Direção', '2026-06-18', 'Contratação de empresa especializada para prestação de serviços técnicos de Suporte em Saúde e Segurança Operacional.', '', 'Felippe'],
];

$totalImportadas = 0;

foreach ($linhasImportar as $linha) {
    [$status, $numeroProcesso, $setorDemandante, $dataRecebimento, $objeto, $observacaoReferencia, $responsavelReferencia] = $linha;

    $demanda = new Demanda(
        $numeroProcesso,
        $dataRecebimento,
        '',
        $setorDemandante,
        $objeto,
        null,
        $status
    );
    $demanda->salvar();

    echo "Importado: {$numeroProcesso} ({$status}) — responsável a vincular: <b>{$responsavelReferencia}</b>";
    if ($observacaoReferencia !== '') {
        echo " — observação original: <i>{$observacaoReferencia}</i>";
    }
    echo "<br>";

    $totalImportadas++;
}

echo "<br><b>Total importado: {$totalImportadas} demandas.</b>";
echo "<br><br>Próximos passos: cadastre os servidores que faltam (Marcos, Tayná, Ana, Viviann — se ainda não existirem) e edite cada demanda para vincular o responsável correto e ajustar o status, conforme necessário.";