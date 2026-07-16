<?php
$titulo = 'Meu Perfil - MT Par';
require __DIR__ . '/partials/header.php';

$sucesso = isset($_GET['sucesso']);
$iniciais = mb_strtoupper(mb_substr($servidorLogado->nome, 0, 1));
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="section-title">
        <i class="ti ti-user-circle" aria-hidden="true" style="font-size: 20px; vertical-align: -3px;"></i>
        Meu perfil
    </span>
    <a href="index.php?action=dashboard" class="btn btn-sm btn-secondary">
        <i class="ti ti-arrow-left" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
        Voltar ao dashboard
    </a>
</div>

<?php if ($sucesso): ?>
    <div class="alert alert-success small">
        <i class="ti ti-check" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
        Perfil atualizado com sucesso.
    </div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="d-flex align-items-center mb-4">
            <div style="width: 64px; height: 64px; border-radius: 50%; background: var(--brand-deep); color: white; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: 600;">
                <?= htmlspecialchars($iniciais) ?>
            </div>
            <div class="ms-3">
                <h5 class="m-0"><?= htmlspecialchars($servidorLogado->nome) ?></h5>
                <p class="text-muted small m-0"><?= htmlspecialchars($servidorLogado->cargo) ?></p>
            </div>
        </div>

        <form method="post" action="index.php?action=atualizar_perfil">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nome completo</label>
                    <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($servidorLogado->nome) ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Cargo</label>
                    <input type="text" name="cargo" class="form-control" value="<?= htmlspecialchars($servidorLogado->cargo) ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Matrícula</label>
                    <input type="text" name="matricula" class="form-control" value="<?= htmlspecialchars($servidorLogado->matricula) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Usuário (login)</label>
                    <input type="text" name="usuario" class="form-control" value="<?= htmlspecialchars($servidorLogado->usuario) ?>" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Nova senha</label>
                <input type="password" name="nova_senha" class="form-control" placeholder="Deixe em branco para não alterar">
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="ti ti-check" aria-hidden="true" style="font-size: 14px; vertical-align: -1px;"></i>
                Salvar alterações
            </button>
        </form>
    </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>