<?php
$titulo = 'Cotações - MT Par';
require __DIR__ . '/partials/header.php';

$statusLabel = [
    Cotacao::STATUS_EM_ANDAMENTO => ['Em andamento', 'bg-primary'],
    Cotacao::STATUS_FINALIZADA => ['Finalizada', 'bg-success'],
];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="fs-6 fw-semibold" style="color: #1F3864;">
        <i class="ti ti-clipboard-list" aria-hidden="true" style="font-size: 20px; vertical-align: -3px;"></i>
        Cotações
    </span>
    <div>
        <a href="index.php?action=dashboard" class="btn btn-sm btn-secondary">
            <i class="ti ti-arrow-left" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
            Dashboard
        </a>
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovaCotacao">
            <i class="ti ti-plus" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
            Nova cotação
        </button>
    </div>
</div>

<?php if (count($cotacoes) === 0): ?>
    <div class="card shadow-sm">
        <div class="empty-state text-muted">
            <i class="ti ti-clipboard-off" aria-hidden="true"></i>
            <p class="mb-0">Nenhuma cotação criada ainda.</p>
        </div>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($cotacoes as $cotacao): ?>
            <?php
            $servidor = $cotacao->buscarServidor();
            [$label, $classeBadge] = $statusLabel[$cotacao->status] ?? ['Indefinido', 'bg-secondary'];
            ?>
            <div class="col-md-4">
                <a href="index.php?action=cotacao&id=<?= $cotacao->id ?>" class="text-decoration-none">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="card-title mb-0">
                                    <i class="ti ti-file-text" aria-hidden="true" style="font-size: 16px; color: #1F3864; vertical-align: -2px;"></i>
                                    Processo <?= htmlspecialchars($cotacao->numeroProcesso) ?>
                                </h6>
                                <span class="badge <?= $classeBadge ?>"><?= $label ?></span>
                            </div>
                            <p class="card-text text-muted small mb-1">
                                <?= htmlspecialchars($cotacao->orgaoSetor ?: '—') ?>
                            </p>
                            <p class="card-text small mb-0">
                                <i class="ti ti-user" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
                                <?= $servidor ? htmlspecialchars($servidor->nome) : '—' ?>
                            </p>
                            <p class="card-text small text-muted mb-0">
                                <i class="ti ti-box" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
                                <?= count($cotacao->buscarLotes()) ?> lote(s)
                            </p>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="modal fade" id="modalNovaCotacao" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="index.php?action=criar_cotacao">
                <div class="modal-header">
                    <h5 class="modal-title">Nova cotação</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Número do processo</label>
                        <input type="text" name="numero_processo" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Órgão / Setor</label>
                        <input type="text" name="orgao_setor" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Procedimento</label>
                        <input type="text" name="procedimento" class="form-control" placeholder="Ex: Pregão Eletrônico">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo de julgamento</label>
                        <input type="text" name="tipo_julgamento" class="form-control" placeholder="Ex: Menor Preço">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Objeto</label>
                        <textarea name="objeto" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Servidor responsável</label>
                        <select name="servidor_id" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($servidores as $servidor): ?>
                                <option value="<?= $servidor->id ?>"><?= htmlspecialchars($servidor->nome) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Critério de consolidação</label>
                        <select name="criterio_consolidacao" class="form-select">
                            <option value="MEDIANA">Mediana</option>
                            <option value="MEDIA">Média</option>
                            <option value="MENOR_PRECO">Menor preço</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-check" aria-hidden="true" style="font-size: 14px; vertical-align: -1px;"></i>
                        Criar cotação
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>