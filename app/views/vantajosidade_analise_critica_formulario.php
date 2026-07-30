<?php
$titulo = 'Gerar análise crítica - MT Par';
require __DIR__ . '/partials/header.php';
$identificador = $processo->ehContratoAditivo() ? 'Aditivo do Contrato ' . $processo->numeroContrato : 'Ata ' . $processo->numeroAta;
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="section-title">
        <i class="ti ti-file-report" aria-hidden="true" style="font-size: 20px; vertical-align: -3px;"></i>
        Gerar análise crítica de vantajosidade
    </span>
    <a href="index.php?action=vantajosidade&id=<?= $processo->id ?>" class="btn btn-sm btn-secondary">
        <i class="ti ti-arrow-left" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
        Voltar ao processo
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <p class="text-muted small mb-3">
            <i class="ti ti-info-circle" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
            <?= htmlspecialchars($identificador) ?>. Preencha os dados abaixo para gerar o documento Word.
            Algumas informações precisarão ser complementadas manualmente após o download.
        </p>

        <form method="post" action="index.php?action=gerar_analise_critica_vantajosidade">
            <input type="hidden" name="processo_id" value="<?= $processo->id ?>">

            <div class="mb-3">
                <label class="form-label">Elaborado por</label>
                <select name="elaborado_por_id" class="form-select" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($servidores as $servidor): ?>
                        <option value="<?= $servidor->id ?>">
                            <?= htmlspecialchars($servidor->nome) ?><?= $servidor->cargo ? ' — ' . htmlspecialchars($servidor->cargo) : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">
                    O servidor responsável pelo processo não aparece nesta lista (não pode elaborar a análise crítica do próprio processo).
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Validado por</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars(($validador->nome ?? '—') . ' — ' . ($validador->cargo ?? '')) ?>" disabled>
            </div>

            <div class="mb-3">
                <label class="form-label">Número do DFD</label>
                <input type="text" name="numero_dfd" class="form-control" placeholder="Ex: MTPAR-DIC-2026/02502-A">
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="ti ti-download" aria-hidden="true" style="font-size: 14px; vertical-align: -1px;"></i>
                Gerar documento Word
            </button>
        </form>
    </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
