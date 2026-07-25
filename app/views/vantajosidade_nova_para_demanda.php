<?php
$titulo = 'Nova comprovação de vantajosidade — ' . $demanda->numeroProcesso;
require __DIR__ . '/partials/header.php';
?>

<div class="d-flex flex-column mb-3">
    <a href="index.php?action=ver_demanda&id=<?= $demanda->id ?>" class="small text-muted text-decoration-none mb-1">
        <i class="ti ti-arrow-left" aria-hidden="true" style="font-size: 12px; vertical-align: -1px;"></i>
        Voltar para o Processo <?= htmlspecialchars($demanda->numeroProcesso) ?>
    </a>
    <span class="section-title">
        <i class="ti ti-scale" aria-hidden="true" style="font-size: 20px; vertical-align: -3px;"></i>
        Iniciar comprovação de vantajosidade
    </span>
    <span class="text-muted small">
        <i class="ti ti-link" aria-hidden="true" style="font-size: 12px; vertical-align: -1px;"></i>
        Já vinculada ao Processo <?= htmlspecialchars($demanda->numeroProcesso) ?> — não precisa procurar o processo de novo.
    </span>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="post" action="index.php?action=criar_vantajosidade">
            <input type="hidden" name="demanda_id" value="<?= $demanda->id ?>">

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Número da Ata</label>
                    <input type="text" name="numero_ata" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Órgão gerenciador</label>
                    <input type="text" name="orgao_gerenciador" class="form-control form-control-sm">
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-semibold">Objeto</label>
                <textarea name="objeto" class="form-control form-control-sm" rows="2"><?= htmlspecialchars($demanda->objeto) ?></textarea>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-semibold">Servidor responsável</label>
                <select name="servidor_id" class="form-select form-select-sm" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($servidores as $servidor): ?>
                        <option value="<?= $servidor->id ?>"><?= htmlspecialchars($servidor->nome) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-sm btn-success">
                <i class="ti ti-check" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
                Criar comprovação de vantajosidade
            </button>
        </form>
    </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
