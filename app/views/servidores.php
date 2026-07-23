<?php
$titulo = 'Servidores - MT Par';
require __DIR__ . '/partials/header.php';

$senhaResetada = isset($_GET['senha_resetada']);
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

<?php if ($senhaResetada): ?>
    <div class="alert alert-success small">
        <i class="ti ti-check" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
        Senha resetada para o padrão (123).
    </div>
<?php endif; ?>

<?php if (!empty($_SESSION['sucesso'])): ?>
    <div class="alert alert-success small">
        <i class="ti ti-check" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
        <?= $_SESSION['sucesso'] ?>
    </div>
    <?php unset($_SESSION['sucesso']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['erro'])): ?>
    <div class="alert alert-danger small">
        <i class="ti ti-alert-triangle" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
        <?= $_SESSION['erro'] ?>
    </div>
    <?php unset($_SESSION['erro']); ?>
<?php endif; ?>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <h6 class="card-subtitle mb-3 text-muted">Cadastrar novo servidor</h6>
        <form method="post" action="index.php?action=criar_servidor" class="row g-2">
            <div class="col-md-3">
                <input type="text" name="nome" class="form-control" placeholder="Nome completo" required>
            </div>
            <div class="col-md-2">
                <input type="text" name="matricula" class="form-control" placeholder="Matrícula">
            </div>
            <div class="col-md-2">
                <input type="text" name="cargo" class="form-control" placeholder="Cargo">
            </div>
            <div class="col-md-2">
                <input type="text" name="usuario" class="form-control" placeholder="Usuário (login)" required>
            </div>
            <div class="col-md-2">
                <select name="nivel_acesso" class="form-select">
                    <option value="COMUM">Comum</option>
                    <option value="ADMIN">Administrador</option>
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="ti ti-plus" aria-hidden="true" style="font-size: 14px;"></i>
                </button>
            </div>
        </form>
        <p class="text-muted small mt-2 mb-0">
            <i class="ti ti-info-circle" aria-hidden="true" style="font-size: 12px; vertical-align: -1px;"></i>
            Novos servidores são criados com a senha padrão <b>123</b>.
        </p>
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
                        <th>Usuário</th>
                        <th>Nível</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($servidores as $servidor): ?>
                        <tr>
                            <td><?= htmlspecialchars($servidor->nome) ?></td>
                            <td><?= htmlspecialchars($servidor->matricula) ?></td>
                            <td><?= htmlspecialchars($servidor->cargo) ?></td>
                            <td><?= htmlspecialchars($servidor->usuario) ?></td>
                            <td>
                                <span class="badge <?= $servidor->ehAdmin() ? 'bg-warning text-dark' : 'bg-secondary' ?>">
                                    <?= $servidor->ehAdmin() ? 'Administrador' : 'Comum' ?>
                                </span>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                        data-bs-toggle="modal" data-bs-target="#modalEditarServidor<?= $servidor->id ?>">
                                    <i class="ti ti-edit" aria-hidden="true" style="font-size: 13px;"></i>
                                </button>
                                <a href="index.php?action=resetar_senha_servidor&id=<?= $servidor->id ?>"
                                   class="btn btn-sm btn-outline-warning"
                                   onclick="return confirm('Resetar a senha deste servidor para o padrão (123)?')">
                                    <i class="ti ti-key" aria-hidden="true" style="font-size: 13px;"></i>
                                </a>
                                <a href="index.php?action=excluir_servidor&id=<?= $servidor->id ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Excluir este servidor?')">
                                    <i class="ti ti-trash" aria-hidden="true" style="font-size: 13px;"></i>
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
                                            <div class="mb-3">
                                                <label class="form-label">Usuário</label>
                                                <input type="text" name="usuario" class="form-control"
                                                       value="<?= htmlspecialchars($servidor->usuario) ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Nível de acesso</label>
                                                <select name="nivel_acesso" class="form-select">
                                                    <option value="COMUM" <?= !$servidor->ehAdmin() ? 'selected' : '' ?>>Comum</option>
                                                    <option value="ADMIN" <?= $servidor->ehAdmin() ? 'selected' : '' ?>>Administrador</option>
                                                </select>
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