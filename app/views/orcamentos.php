<?php
$titulo = 'Orçamentos - MT Par';
require __DIR__ . '/partials/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="fs-6 fw-semibold" style="color: var(--brand-blue-dark);">
        <i class="ti ti-calculator" aria-hidden="true" style="font-size: 20px; vertical-align: -3px;"></i>
        Orçamentos
    </span>
    <a href="index.php?action=dashboard" class="btn btn-sm btn-secondary">
        <i class="ti ti-arrow-left" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
        Dashboard
    </a>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <a href="index.php?action=cotacoes" class="text-decoration-none">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="rounded-3 bg-info-subtle d-inline-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="ti ti-clipboard-list text-info" aria-hidden="true" style="font-size: 20px;"></i>
                    </div>
                    <p class="fw-semibold mb-1 mt-3 text-dark">Pesquisa de preço</p>
                    <p class="text-muted small mb-0">Estimativa de preço de referência para licitação</p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-6">
        <a href="index.php?action=vantajosidades" class="text-decoration-none">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="rounded-3 bg-success-subtle d-inline-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="ti ti-scale text-success" aria-hidden="true" style="font-size: 20px;"></i>
                    </div>
                    <p class="fw-semibold mb-1 mt-3 text-dark">Comprovação de vantajosidade</p>
                    <p class="text-muted small mb-0">Adesão a Ata de Registro de Preços</p>
                </div>
            </div>
        </a>
    </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>