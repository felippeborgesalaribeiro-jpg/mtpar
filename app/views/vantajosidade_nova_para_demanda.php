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

            <div class="mb-3">
                <label class="form-label small fw-semibold">Este processo é referente a:</label>
                <div class="d-flex gap-2">
                    <label class="border rounded p-2 flex-fill text-center small" style="cursor:pointer;">
                        <input type="radio" name="tipo" value="ATA" class="form-check-input me-1 campo-tipo-vant" checked>
                        Ata de Registro de Preços<br><span class="text-muted">adesão/carona</span>
                    </label>
                    <label class="border rounded p-2 flex-fill text-center small" style="cursor:pointer;">
                        <input type="radio" name="tipo" value="CONTRATO_ADITIVO" class="form-check-input me-1 campo-tipo-vant">
                        Aditivo de Contrato<br><span class="text-muted">acréscimo de até 25%</span>
                    </label>
                </div>
            </div>

            <div class="grupo-tipo-ata row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Número da Ata</label>
                    <input type="text" name="numero_ata" class="form-control form-control-sm campo-numero-ata">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Órgão gerenciador</label>
                    <input type="text" name="orgao_gerenciador" class="form-control form-control-sm">
                </div>
            </div>
            <div class="grupo-tipo-contrato row g-3 mb-3 d-none">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Número do contrato</label>
                    <input type="text" name="numero_contrato" class="form-control form-control-sm campo-numero-contrato" placeholder="Ex: 012/2026">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Valor total do objeto (contrato original)</label>
                    <input type="text" name="valor_total_objeto" class="form-control form-control-sm campo-valor-total-objeto" placeholder="0,00">
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

<script>
(function () {
    var gruposAta = document.querySelectorAll('.grupo-tipo-ata');
    var gruposContrato = document.querySelectorAll('.grupo-tipo-contrato');
    var camposTipo = document.querySelectorAll('.campo-tipo-vant');

    function aplicarTipoVantajosidade() {
        var ehContrato = document.querySelector('.campo-tipo-vant:checked').value === 'CONTRATO_ADITIVO';

        gruposAta.forEach(function (g) { g.classList.toggle('d-none', ehContrato); });
        gruposContrato.forEach(function (g) { g.classList.toggle('d-none', !ehContrato); });

        document.querySelectorAll('.campo-numero-ata').forEach(function (c) { c.required = !ehContrato; });
        document.querySelectorAll('.campo-numero-contrato, .campo-valor-total-objeto').forEach(function (c) { c.required = ehContrato; });
    }

    camposTipo.forEach(function (campo) {
        campo.addEventListener('change', aplicarTipoVantajosidade);
    });

    aplicarTipoVantajosidade();
})();
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
