<?php
$titulo = 'Setores Demandantes - MT Par';
require __DIR__ . '/../partials/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="section-title">
        <i class="ti ti-building-community" aria-hidden="true" style="font-size: 20px; vertical-align: -3px;"></i>
        Setores Demandantes
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
    Essa é a lista que alimenta a busca de "Setor demandante" nas telas de Demanda. Só quem está aqui aparece pra quem for digitar — pra manter os nomes padronizados.
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <h6 class="card-subtitle mb-3 text-muted">Cadastrar novo setor</h6>
        <form method="post" action="index.php?action=criar_setor_demandante" class="row g-2 align-items-center">
            <div class="col-md-10">
                <input type="text" name="nome" class="form-control" placeholder="Ex: Setor de TI" required>
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
        <?php if (count($setores) === 0): ?>
            <div class="empty-state">
                <i class="ti ti-building-community" aria-hidden="true"></i>
                <p class="mb-0">Nenhum setor demandante cadastrado ainda.</p>
            </div>
        <?php else: ?>
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Nome</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($setores as $setor): ?>
                        <tr>
                            <td><?= htmlspecialchars($setor->nome) ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                        data-bs-toggle="modal" data-bs-target="#modalEditarSetor<?= $setor->id ?>">
                                    <i class="ti ti-edit" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
                                    Editar
                                </button>
                                <?= botao_excluir_form('excluir_setor_demandante', $setor->id, 'Excluir este setor demandante? Ele deixará de aparecer na busca (demandas já cadastradas com ele não são afetadas).') ?>
                            </td>
                        </tr>

                        <div class="modal fade" id="modalEditarSetor<?= $setor->id ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="post" action="index.php?action=editar_setor_demandante">
                                        <input type="hidden" name="setor_id" value="<?= $setor->id ?>">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Editar setor demandante</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <label class="form-label">Nome</label>
                                            <input type="text" name="nome" class="form-control"
                                                   value="<?= htmlspecialchars($setor->nome) ?>" required>
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
