<?php
$titulo = 'Processo — ' . $demanda->numeroProcesso;
require __DIR__ . '/partials/header.php';

$coresStatus = [
    'EM ANDAMENTO'                    => 'bg-primary-subtle text-primary',
    'ELABORAÇÃO DE TR'                => 'bg-warning-subtle text-warning',
    'ELABORAÇÃO DE PESQUISA DE PREÇO' => 'bg-warning-subtle text-warning',
    'AVISO DE LICITAÇÃO'              => 'bg-info-subtle text-info',
    'AVISO DE DISPENSA DE LICITAÇÃO'  => 'bg-info-subtle text-info',
    'EMISSÃO DE PED RESERVA'          => 'bg-info-subtle text-info',
    'FASE DE HABILITAÇÃO'             => 'bg-danger-subtle text-danger',
    'ENVIADO PARA CONDES'             => 'bg-secondary-subtle text-secondary',
    'ENVIADO PARA PARECER JURÍDICO'   => 'bg-secondary-subtle text-secondary',
    'ENVIADO PARA PGE'                => 'bg-secondary-subtle text-secondary',
    'SANEAMENTO DE PROCESSO'          => 'bg-secondary-subtle text-secondary',
    'PUBLICADO'                       => 'bg-success-subtle text-success',
    'CONCLUÍDO'                       => 'bg-success-subtle text-success',
    'CANCELADO'                       => 'bg-dark-subtle text-dark',
];

$corStatus = $coresStatus[$demanda->status] ?? 'bg-secondary-subtle text-secondary';
$modoEdicao = ($modo === 'editar');
?>

<?php if ($modoEdicao): ?>
    <div class="alert alert-warning d-flex align-items-center gap-2 py-2 small mb-3">
        <i class="ti ti-pencil" aria-hidden="true" style="font-size:15px;"></i>
        Você está editando este processo. Salve ou cancele para voltar à visualização.
    </div>
<?php endif; ?>

<!-- Cabeçalho da página -->
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h5 class="mb-0 fw-semibold"><?= htmlspecialchars($demanda->numeroProcesso) ?></h5>
        <small class="text-muted">
            <?= htmlspecialchars($demanda->setorDemandante ?: '—') ?> ·
            Recebida em <?= $demanda->dataRecebimento ? date('d/m/Y', strtotime($demanda->dataRecebimento)) : '—' ?>
        </small>
    </div>
    <div class="d-flex gap-2">
        <?php if ($modoEdicao): ?>
            <a href="index.php?action=ver_demanda&id=<?= $demanda->id ?>" class="btn btn-sm btn-secondary">
                <i class="ti ti-x" aria-hidden="true" style="font-size:13px; vertical-align:-1px;"></i>
                Cancelar
            </a>
            <button type="submit" form="formEdicao" class="btn btn-sm btn-success">
                <i class="ti ti-check" aria-hidden="true" style="font-size:13px; vertical-align:-1px;"></i>
                Salvar alterações
            </button>
        <?php else: ?>
            <a href="index.php?action=demandas" class="btn btn-sm btn-secondary">
                <i class="ti ti-arrow-left" aria-hidden="true" style="font-size:13px; vertical-align:-1px;"></i>
                Voltar
            </a>
            <a href="index.php?action=ver_demanda&id=<?= $demanda->id ?>&modo=editar" class="btn btn-sm btn-outline-primary">
                <i class="ti ti-edit" aria-hidden="true" style="font-size:13px; vertical-align:-1px;"></i>
                Editar
            </a>
            <a href="index.php?action=excluir_demanda&id=<?= $demanda->id ?>"
               class="btn btn-sm btn-outline-danger"
               onclick="return confirm('Excluir este processo? Ele vai para a lixeira do Administrador, que pode restaurá-lo depois se precisar.')">
                <i class="ti ti-trash" aria-hidden="true" style="font-size:13px; vertical-align:-1px;"></i>
                Excluir
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($modoEdicao): ?>
<!-- ================================================================ -->
<!--  MODO EDIÇÃO                                                       -->
<!-- ================================================================ -->
<form id="formEdicao" method="POST" action="index.php?action=editar_demanda_inline">
    <input type="hidden" name="demanda_id" value="<?= $demanda->id ?>">

    <div class="card shadow-sm mb-3">
        <div class="card-header bg-white d-flex align-items-center gap-2 py-2">
            <i class="ti ti-folder" aria-hidden="true" style="font-size:16px; color: var(--brand-blue-dark);"></i>
            <span class="fw-semibold small">Dados do processo</span>
            <span class="badge bg-warning text-dark ms-auto" style="font-size:10px;">editando</span>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label small">Nº processo</label>
                    <input type="text" name="numero_processo" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($demanda->numeroProcesso) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Data de recebimento</label>
                    <input type="date" name="data_recebimento" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($demanda->dataRecebimento) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Setor demandante</label>
                    <input type="text" name="setor_demandante" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($demanda->setorDemandante) ?>">
                </div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <?php foreach (Demanda::STATUS_OPCOES as $opcao): ?>
                            <option value="<?= $opcao ?>" <?= $demanda->status === $opcao ? 'selected' : '' ?>>
                                <?= htmlspecialchars($opcao) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small">Responsável</label>
                    <select name="servidor_responsavel_id" class="form-select form-select-sm">
                        <option value="">— Sem responsável —</option>
                        <?php foreach ($servidores as $servidor): ?>
                            <option value="<?= $servidor->id ?>"
                                <?= $demanda->servidorResponsavelId === $servidor->id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($servidor->nome) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label small">Link SIGADOC</label>
                <input type="text" name="link_sigadoc" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($demanda->linkSigadoc) ?>"
                       placeholder="https://sigadoc.mt.gov.br/...">
            </div>
            <div class="mb-0">
                <label class="form-label small">Objeto</label>
                <textarea name="objeto" class="form-control form-control-sm" rows="3"><?= htmlspecialchars($demanda->objeto) ?></textarea>
            </div>
        </div>
    </div>
</form>

<?php else: ?>
<!-- ================================================================ -->
<!--  MODO VISUALIZAÇÃO                                                 -->
<!-- ================================================================ -->
<div class="card shadow-sm mb-3">
    <div class="card-header bg-white d-flex align-items-center gap-2 py-2">
        <i class="ti ti-folder" aria-hidden="true" style="font-size:16px; color: var(--brand-blue-dark);"></i>
        <span class="fw-semibold small">Dados do processo</span>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <p class="text-muted mb-1" style="font-size:10px; text-transform:uppercase; letter-spacing:.05em;">Status</p>
                <span class="badge <?= $corStatus ?>"><?= htmlspecialchars($demanda->status) ?></span>
            </div>
            <div class="col-md-4">
                <p class="text-muted mb-1" style="font-size:10px; text-transform:uppercase; letter-spacing:.05em;">Responsável</p>
                <p class="mb-0 small fw-semibold"><?= $servidorResponsavel ? htmlspecialchars($servidorResponsavel->nome) : '—' ?></p>
            </div>
            <div class="col-md-4">
                <p class="text-muted mb-1" style="font-size:10px; text-transform:uppercase; letter-spacing:.05em;">SIGADOC</p>
                <?php if ($demanda->linkSigadoc !== ''): ?>
                    <a href="<?= htmlspecialchars($demanda->linkSigadoc) ?>" target="_blank" class="small">
                        Abrir no SIGADOC
                        <i class="ti ti-external-link" aria-hidden="true" style="font-size:11px;"></i>
                    </a>
                <?php else: ?>
                    <p class="mb-0 small text-muted">—</p>
                <?php endif; ?>
            </div>
        </div>
        <hr class="my-2">
        <p class="text-muted mb-1" style="font-size:10px; text-transform:uppercase; letter-spacing:.05em;">Objeto</p>
        <p class="mb-0 small"><?= htmlspecialchars($demanda->objeto ?: '—') ?></p>
    </div>
</div>
<?php endif; ?>

<!-- ================================================================ -->
<!--  LICITAÇÃO VINCULADA (sempre somente leitura)                     -->
<!-- ================================================================ -->
<?php if ($licitacao !== null): ?>
<div class="card shadow-sm mb-3" style="border-left: 3px solid var(--brand-blue);">
    <div class="card-header bg-white d-flex align-items-center gap-2 py-2">
        <i class="ti ti-gavel" aria-hidden="true" style="font-size:16px; color: var(--brand-blue-dark);"></i>
        <span class="fw-semibold small">Licitação vinculada</span>
        <span class="text-muted small ms-1">— somente leitura</span>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-2">
            <div class="col-md-3">
                <p class="text-muted mb-1" style="font-size:10px; text-transform:uppercase; letter-spacing:.05em;">Edital</p>
                <p class="mb-0 small fw-semibold"><?= $licitacao->editalLicitacao !== '' ? htmlspecialchars($licitacao->editalLicitacao) : '—' ?></p>
            </div>
            <div class="col-md-3">
                <p class="text-muted mb-1" style="font-size:10px; text-transform:uppercase; letter-spacing:.05em;">Sessão pública</p>
                <p class="mb-0 small fw-semibold"><?= $licitacao->realizacaoSessaoPublica ? date('d/m/Y', strtotime($licitacao->realizacaoSessaoPublica)) : '—' ?></p>
            </div>
            <div class="col-md-3">
                <p class="text-muted mb-1" style="font-size:10px; text-transform:uppercase; letter-spacing:.05em;">Valor estimado</p>
                <p class="mb-0 small fw-semibold"><?= $licitacao->valorEstimado !== null ? formatarMoeda($licitacao->valorEstimado) : '—' ?></p>
            </div>
            <div class="col-md-3">
                <p class="text-muted mb-1" style="font-size:10px; text-transform:uppercase; letter-spacing:.05em;">Valor adjudicado</p>
                <p class="mb-0 small fw-semibold"><?= $licitacao->valorAdjudicado !== null ? formatarMoeda($licitacao->valorAdjudicado) : '—' ?></p>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-md-3">
                <p class="text-muted mb-1" style="font-size:10px; text-transform:uppercase; letter-spacing:.05em;">Economicidade</p>
                <?php
                $eco = $licitacao->calcularEconomicidadeReais();
                $ecoPerc = $licitacao->calcularEconomicidadePercentual();
                ?>
                <?php if ($eco !== null): ?>
                    <p class="mb-0 small fw-semibold <?= $eco >= 0 ? 'text-success' : 'text-danger' ?>">
                        <?= formatarMoeda($eco) ?> (<?= formatarNumero($ecoPerc, 1) ?>%)
                    </p>
                <?php else: ?>
                    <p class="mb-0 small text-muted">—</p>
                <?php endif; ?>
            </div>
            <div class="col-md-3">
                <p class="text-muted mb-1" style="font-size:10px; text-transform:uppercase; letter-spacing:.05em;">Encaminhado contrato</p>
                <p class="mb-0 small fw-semibold"><?= $licitacao->encaminhadoPactuacaoContrato ? date('d/m/Y', strtotime($licitacao->encaminhadoPactuacaoContrato)) : '—' ?></p>
            </div>
            <div class="col-md-3">
                <p class="text-muted mb-1" style="font-size:10px; text-transform:uppercase; letter-spacing:.05em;">Dias na licitação</p>
                <span class="badge <?= $licitacao->estaEmAndamento() ? 'bg-warning text-dark' : 'bg-secondary' ?>">
                    <?= $licitacao->calcularDiasNaLicitacao() ?> dia(s)
                </span>
            </div>
        </div>
        <div class="mt-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <small class="text-muted">
                <i class="ti ti-info-circle" aria-hidden="true" style="font-size:12px; vertical-align:-1px;"></i>
                Para editar os dados da licitação, acesse o módulo
                <a href="index.php?action=licitacoes">Licitações</a>.
            </small>
            <div class="d-flex gap-2">
                <a href="index.php?action=proposta_vencedora&id=<?= $licitacao->id ?>" class="btn btn-sm btn-outline-primary">
                    <i class="ti ti-clipboard-check" aria-hidden="true" style="font-size:13px; vertical-align:-1px;"></i>
                    Conferir proposta vencedora
                </a>
                <?php if ($licitacao->estaFinalizada()): ?>
                    <span class="badge bg-success-subtle text-success d-flex align-items-center px-2">
                        <i class="ti ti-circle-check" aria-hidden="true" style="font-size:13px; vertical-align:-1px;"></i>
                        &nbsp;Processo finalizado em <?= date('d/m/Y', strtotime($licitacao->dataAdjudicacaoHomologacao)) ?>
                    </span>
                <?php else: ?>
                    <button type="button" class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#modalFinalizarProcesso">
                        <i class="ti ti-stamp" aria-hidden="true" style="font-size:13px; vertical-align:-1px;"></i>
                        Finalizar processo
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (!$licitacao->estaFinalizada()): ?>
<div class="modal fade" id="modalFinalizarProcesso" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="index.php?action=finalizar_licitacao" onsubmit="return confirm('Confirma que este processo foi de fato adjudicado e homologado? Depois de finalizado, isso marca oficialmente o encerramento no setor de licitação.');">
                <input type="hidden" name="licitacao_id" value="<?= $licitacao->id ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Finalizar processo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">
                        Isso marca que o processo foi adjudicado e homologado, encerrando-o no setor de licitação.
                        Essa ação não pode ser desfeita pela tela — se precisar corrigir a data depois, avise o administrador.
                    </p>
                    <label class="form-label small fw-semibold">Data da adjudicação e homologação</label>
                    <input type="date" name="data" class="form-control form-control-sm" style="max-width: 220px;" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-dark btn-sm">Confirmar finalização</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php endif; ?>

<!-- ================================================================ -->
<!--  PESQUISA DE PREÇO VINCULADA (sempre somente leitura)             -->
<!-- ================================================================ -->
<?php if ($cotacao !== null): ?>
<div class="card shadow-sm mb-3" style="border-left: 3px solid #0891b2;">
    <div class="card-header bg-white d-flex align-items-center gap-2 py-2">
        <i class="ti ti-clipboard-list" aria-hidden="true" style="font-size:16px; color:#0891b2;"></i>
        <span class="fw-semibold small">Pesquisa de preço vinculada</span>
        <span class="text-muted small ms-1">— somente leitura</span>
    </div>
    <div class="card-body d-flex align-items-center justify-content-between">
        <div class="row g-3 flex-grow-1">
            <div class="col-md-4">
                <p class="text-muted mb-1" style="font-size:10px; text-transform:uppercase; letter-spacing:.05em;">Nº processo</p>
                <p class="mb-0 small fw-semibold"><?= htmlspecialchars($cotacao->numeroProcesso) ?></p>
            </div>
            <div class="col-md-4">
                <p class="text-muted mb-1" style="font-size:10px; text-transform:uppercase; letter-spacing:.05em;">Status</p>
                <span class="badge <?= $cotacao->status === StatusCotacao::Finalizada ? 'bg-success' : 'bg-primary' ?>">
                    <?= $cotacao->status === StatusCotacao::Finalizada ? 'Finalizada' : 'Em andamento' ?>
                </span>
            </div>
            <div class="col-md-4">
                <p class="text-muted mb-1" style="font-size:10px; text-transform:uppercase; letter-spacing:.05em;">Responsável</p>
                <?php $servidorCotacao = $cotacao->buscarServidor(); ?>
                <p class="mb-0 small fw-semibold"><?= $servidorCotacao ? htmlspecialchars($servidorCotacao->nome) : '—' ?></p>
            </div>
        </div>
        <div class="ms-3">
            <a href="index.php?action=cotacao&id=<?= $cotacao->id ?>" class="btn btn-sm btn-primary">
                <i class="ti ti-clipboard-list" aria-hidden="true" style="font-size:13px; vertical-align:-1px;"></i>
                Abrir pesquisa
            </a>
        </div>
    </div>
</div>
<?php else: ?>
<div class="card shadow-sm mb-3" style="border: 0.5px dashed #ced4da;">
    <div class="card-body d-flex align-items-center gap-3 py-3">
        <i class="ti ti-clipboard-x text-muted" aria-hidden="true" style="font-size:22px;"></i>
        <div>
            <p class="mb-0 small fw-semibold text-muted">Nenhuma pesquisa de preço vinculada</p>
            <a href="index.php?action=cotacoes" class="small">
                Ir para pesquisas de preço
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ================================================================ -->
<!--  VANTAJOSIDADE VINCULADA (sempre somente leitura)                 -->
<!-- ================================================================ -->
<?php if ($vantajosidade !== null): ?>
<div class="card shadow-sm mb-3" style="border-left: 3px solid #198754;">
    <div class="card-header bg-white d-flex align-items-center gap-2 py-2">
        <i class="ti ti-scale" aria-hidden="true" style="font-size:16px; color:#198754;"></i>
        <span class="fw-semibold small">Comprovação de vantajosidade vinculada</span>
        <span class="text-muted small ms-1">— somente leitura</span>
    </div>
    <div class="card-body d-flex align-items-center justify-content-between">
        <div class="row g-3 flex-grow-1">
            <div class="col-md-4">
                <p class="text-muted mb-1" style="font-size:10px; text-transform:uppercase; letter-spacing:.05em;">Nº da Ata</p>
                <p class="mb-0 small fw-semibold"><?= htmlspecialchars($vantajosidade->numeroAta) ?></p>
            </div>
            <div class="col-md-4">
                <p class="text-muted mb-1" style="font-size:10px; text-transform:uppercase; letter-spacing:.05em;">Status</p>
                <span class="badge <?= $vantajosidade->status === 'FINALIZADO' ? 'bg-success' : 'bg-primary' ?>">
                    <?= $vantajosidade->status === 'FINALIZADO' ? 'Finalizado' : 'Em andamento' ?>
                </span>
            </div>
            <div class="col-md-4">
                <p class="text-muted mb-1" style="font-size:10px; text-transform:uppercase; letter-spacing:.05em;">Órgão gerenciador</p>
                <p class="mb-0 small fw-semibold"><?= htmlspecialchars($vantajosidade->orgaoGerenciador ?: '—') ?></p>
            </div>
        </div>
        <div class="ms-3">
            <a href="index.php?action=vantajosidade&id=<?= $vantajosidade->id ?>" class="btn btn-sm btn-success">
                <i class="ti ti-scale" aria-hidden="true" style="font-size:13px; vertical-align:-1px;"></i>
                Abrir vantajosidade
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>