<?php
$titulo = 'Servidores - MT Par';
require __DIR__ . '/partials/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="section-title">
        <i class="ti ti-users" aria-hidden="true" style="font-size: 20px; vertical-align: -3px;"></i>
        Servidores
    </span>
    <a href="index.php?action=dashboard" class="btn btn-sm btn-secondary">
        <i class="ti ti-arrow-left" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
        Voltar ao dashboard
    </a>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <h6 class="card-subtitle mb-3 text-muted">Cadastrar novo servidor</h6>
        <form method="post" action="index.php?action=criar_servidor" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="nome" class="form-control" placeholder="Nome completo" required>
            </div>
            <div class="col-md-3">
                <input type="text" name="matricula" class="form-control" placeholder="Matrícula">
            </div>
            <div class="col-md-3">
                <input type="text" name="cargo" class="form-control" placeholder="Cargo (ex: Analista Administrativo)">
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
        <?php if (count($servidores) === 0): ?>
            <div class="empty-state">
                <i class="ti ti-user-off" aria-hidden="true"></i>
                <p class="mb-0">Nenhum servidor cadastrado ainda.</p>
            </div>
        <?php else: ?>
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Nome</th>
                        <th>Matrícula</th>
                        <th>Cargo</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($servidores as $servidor): ?>
                        <tr>
                            <td><?= htmlspecialchars($servidor->nome) ?></td>
                            <td><?= htmlspecialchars($servidor->matricula) ?></td>
                            <td><?= htmlspecialchars($servidor->cargo) ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                        data-bs-toggle="modal" data-bs-target="#modalEditarServidor<?= $servidor->id ?>">
                                    <i class="ti ti-edit" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
                                    Editar
                                </button>
                                <a href="index.php?action=excluir_servidor&id=<?= $servidor->id ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Excluir este servidor?')">
                                    <i class="ti ti-trash" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
                                    Excluir
                                </a>
                            </td>
                        </tr>

                        <div class="modal fade" id="modalEditarServidor<?= $servidor->id ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="post" action="index.php?action=editar_servidor">
                                        <input type="hidden" name="servidor_id" value="<?= $servidor->id ?>">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Editar servidor</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">Nome</label>
                                                <input type="text" name="nome" class="form-control"
                                                       value="<?= htmlspecialchars($servidor->nome) ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Matrícula</label>
                                                <input type="text" name="matricula" class="form-control"
                                                       value="<?= htmlspecialchars($servidor->matricula) ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Cargo</label>
                                                <input type="text" name="cargo" class="form-control"
                                                       value="<?= htmlspecialchars($servidor->cargo) ?>">
                                            </div>
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

<?php require __DIR__ . '/partials/footer.php'; ?>