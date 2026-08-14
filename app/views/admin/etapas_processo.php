<?php
$titulo = 'Etapas do Processo - MT Par';
require __DIR__ . '/../partials/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="section-title">
        <i class="ti ti-route" aria-hidden="true" style="font-size: 20px; vertical-align: -3px;"></i>
        Etapas do Processo
    </span>
    <a href="index.php?action=admin" class="btn btn-sm btn-secondary">
        <i class="ti ti-arrow-left" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
        Voltar à Administração
    </a>
</div>

<?php if (!empty($_SESSION['sucesso'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="ti ti-circle-check" aria-hidden="true" style="font-size:14px; vertical-align:-1px;"></i>
        <?= htmlspecialchars($_SESSION['sucesso']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['sucesso']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['erro'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="ti ti-alert-triangle" aria-hidden="true" style="font-size:14px; vertical-align:-1px;"></i>
        <?= htmlspecialchars($_SESSION['erro']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['erro']); ?>
<?php endif; ?>

<div class="alert alert-info small">
    <i class="ti ti-info-circle" aria-hidden="true" style="font-size: 14px; vertical-align: -2px;"></i>
    Essa é a ordem que aparece no dropdown de status da Demanda e no andamento visual da tela do Processo.
    "EM ANDAMENTO" (início) e "CONCLUÍDO"/"CANCELADO" (fim) já existem sempre e não aparecem aqui.
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <h6 class="card-subtitle mb-3 text-muted">Cadastrar nova etapa</h6>
        <form method="post" action="index.php?action=criar_etapa_processo" class="row g-2 align-items-center">
            <div class="col-md-10">
                <input type="text" name="nome" class="form-control" placeholder="Ex: SANEAMENTO DE PROCESSO" required>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="ti ti-plus" aria-hidden="true" style="font-size: 14px; vertical-align: -1px;"></i>
                    Adicionar
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if (count($etapas) === 0): ?>
            <div class="empty-state">
                <i class="ti ti-route" aria-hidden="true"></i>
                <p class="mb-0">Nenhuma etapa cadastrada ainda.</p>
            </div>
        <?php else: ?>
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th style="width:70px;">Ordem</th>
                        <th>Nome</th>
                        <th class="text-end"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($etapas as $indice => $etapa): ?>
                        <tr>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <a href="index.php?action=mover_etapa_processo_cima&id=<?= $etapa->id ?>"
                                       class="btn btn-sm btn-outline-secondary py-0 <?= $indice === 0 ? 'disabled' : '' ?>" title="Mover pra cima">
                                        <i class="ti ti-chevron-up" aria-hidden="true" style="font-size: 13px;"></i>
                                    </a>
                                    <a href="index.php?action=mover_etapa_processo_baixo&id=<?= $etapa->id ?>"
                                       class="btn btn-sm btn-outline-secondary py-0 <?= $indice === count($etapas) - 1 ? 'disabled' : '' ?>" title="Mover pra baixo">
                                        <i class="ti ti-chevron-down" aria-hidden="true" style="font-size: 13px;"></i>
                                    </a>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($etapa->nome) ?></td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                        data-bs-toggle="modal" data-bs-target="#modalEditarEtapa<?= $etapa->id ?>">
                                    <i class="ti ti-edit" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
                                    Editar
                                </button>
                                <a href="index.php?action=excluir_etapa_processo&id=<?= $etapa->id ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Excluir esta etapa? Processos que já estão com esse status não serão alterados, mas ela deixa de aparecer no dropdown.')">
                                    <i class="ti ti-trash" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
                                    Excluir
                                </a>
                            </td>
                        </tr>

                        <div class="modal fade" id="modalEditarEtapa<?= $etapa->id ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="post" action="index.php?action=editar_etapa_processo">
                                        <input type="hidden" name="etapa_id" value="<?= $etapa->id ?>">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Editar etapa</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <label class="form-label">Nome</label>
                                            <input type="text" name="nome" class="form-control"
                                                   value="<?= htmlspecialchars($etapa->nome) ?>" required>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" class="btn btn-primary">Salvar</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
