<?php
$titulo = 'Nova pesquisa de preço — ' . $demanda->numeroProcesso;
require __DIR__ . '/partials/header.php';
?>

<div class="d-flex flex-column mb-3">
    <a href="index.php?action=ver_demanda&id=<?= $demanda->id ?>" class="small text-muted text-decoration-none mb-1">
        <i class="ti ti-arrow-left" aria-hidden="true" style="font-size: 12px; vertical-align: -1px;"></i>
        Voltar para o Processo <?= htmlspecialchars($demanda->numeroProcesso) ?>
    </a>
    <span class="section-title">
        <i class="ti ti-clipboard-list" aria-hidden="true" style="font-size: 20px; vertical-align: -3px;"></i>
        Iniciar pesquisa de preço
    </span>
    <span class="text-muted small">
        <i class="ti ti-link" aria-hidden="true" style="font-size: 12px; vertical-align: -1px;"></i>
        Já vinculada ao Processo <?= htmlspecialchars($demanda->numeroProcesso) ?> — não precisa procurar o processo de novo.
    </span>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="post" action="index.php?action=criar_cotacao">
            <input type="hidden" name="demanda_id" value="<?= $demanda->id ?>">

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Número do processo</label>
                    <input type="text" name="numero_processo" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($demanda->numeroProcesso) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Órgão / Setor</label>
                    <input type="text" name="orgao_setor" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($demanda->setorDemandante) ?>">
                </div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Procedimento</label>
                    <input type="text" name="procedimento" class="form-control form-control-sm" placeholder="Ex: Pregão Eletrônico">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Tipo de julgamento</label>
                    <input type="text" name="tipo_julgamento" class="form-control form-control-sm" placeholder="Ex: Menor Preço">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">Objeto</label>
                <textarea name="objeto" class="form-control form-control-sm" rows="2"><?= htmlspecialchars($demanda->objeto) ?></textarea>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Servidor responsável</label>
                    <select name="servidor_id" class="form-select form-select-sm" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($servidores as $servidor): ?>
                            <option value="<?= $servidor->id ?>"><?= htmlspecialchars($servidor->nome) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Critério de consolidação</label>
                    <select name="criterio_consolidacao" class="form-select form-select-sm">
                        <option value="MEDIANA">Mediana</option>
                        <option value="MEDIA">Média</option>
                        <option value="MENOR_PRECO">Menor preço</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-sm btn-primary">
                <i class="ti ti-check" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
                Criar cotação
            </button>
        </form>
    </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
