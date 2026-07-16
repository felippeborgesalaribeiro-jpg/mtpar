<?php
$titulo = 'Administração — MT Par';
require __DIR__ . '/../partials/header.php';
?>

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="mb-0 fw-semibold">Administração do sistema</h4>
            <small class="text-muted">Ferramentas de manutenção — visível somente para administradores.</small>
        </div>
    </div>

    <?php if (!empty($_SESSION['sucesso'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ti ti-circle-check" aria-hidden="true" style="font-size:14px; vertical-align:-1px;"></i>
            <?= $_SESSION['sucesso'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['sucesso']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['erro'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ti ti-alert-triangle" aria-hidden="true" style="font-size:14px; vertical-align:-1px;"></i>
            <?= $_SESSION['erro'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['erro']); ?>
    <?php endif; ?>

    <!-- BANCO DE DADOS -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
            <i class="ti ti-database" aria-hidden="true" style="font-size:20px; color:#0d6efd; vertical-align:-3px;"></i>
            <div>
                <span class="fw-semibold">Banco de dados</span>
                <small class="text-muted ms-2">Backup e restauração do banco SQLite</small>
            </div>
        </div>
        <div class="card-body">

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="bg-light rounded p-3">
                        <div class="text-muted small mb-1">Último backup</div>
                        <div class="fw-semibold">
                            <?= $ultimoBackup
                                ? htmlspecialchars($ultimoBackup)
                                : '<span class="text-warning">Nenhum realizado</span>' ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="bg-light rounded p-3">
                        <div class="text-muted small mb-1">Tamanho atual do banco</div>
                        <div class="fw-semibold"><?= htmlspecialchars($dbSize) ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="bg-light rounded p-3">
                        <div class="text-muted small mb-1">Backups salvos</div>
                        <div class="fw-semibold">
                            <?= count($backups) ?> <?= count($backups) === 1 ? 'arquivo' : 'arquivos' ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mb-4">
                <form method="POST" action="index.php?action=admin_backup_criar">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-download" aria-hidden="true" style="font-size:14px; vertical-align:-1px;"></i>
                        Fazer backup agora
                    </button>
                </form>
                <button type="button" class="btn btn-outline-secondary"
                        data-bs-toggle="modal" data-bs-target="#modalPasta">
                    <i class="ti ti-folder-open" aria-hidden="true" style="font-size:14px; vertical-align:-1px;"></i>
                    Ver pasta de backups
                </button>
            </div>

            <div class="fw-semibold small text-muted mb-2">Backups disponíveis</div>

            <?php if (empty($backups)): ?>
                <div class="text-center text-muted py-4 border rounded">
                    <i class="ti ti-archive" aria-hidden="true" style="font-size:28px; display:block; margin-bottom:6px;"></i>
                    Nenhum backup encontrado. Clique em "Fazer backup agora" para criar o primeiro.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Arquivo</th>
                                <th class="text-center">Tamanho</th>
                                <th class="text-center">Data/hora</th>
                                <th class="text-end">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($backups as $bk): ?>
                            <tr>
                                <td>
                                    <i class="ti ti-file-zip" aria-hidden="true" style="font-size:14px; color:#0d6efd; vertical-align:-1px;"></i>
                                    <span class="font-monospace small ms-1"><?= htmlspecialchars($bk['nome']) ?></span>
                                </td>
                                <td class="text-center text-muted small"><?= htmlspecialchars($bk['tamanho']) ?></td>
                                <td class="text-center text-muted small"><?= htmlspecialchars($bk['data_formatada']) ?></td>
                                <td class="text-end">
                                    <form method="POST" action="index.php?action=admin_backup_excluir"
                                          onsubmit="return confirm('Excluir o backup \'<?= htmlspecialchars($bk['nome']) ?>\'?\nEsta ação não pode ser desfeita.')">
                                        <input type="hidden" name="arquivo" value="<?= htmlspecialchars($bk['nome']) ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="ti ti-trash" aria-hidden="true" style="font-size:13px;"></i>
                                            Excluir
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- CARDS: LIXEIRA + EM BREVE -->
    <div class="row g-4">

        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body d-flex flex-column align-items-center justify-content-center py-4 text-center">
                    <i class="ti ti-trash" aria-hidden="true" style="font-size:32px; color:#dc3545; margin-bottom:8px;"></i>
                    <div class="fw-semibold mb-1">Lixeira</div>
                    <div class="text-muted small mb-3">
                        <?php if ($totalLixeira > 0): ?>
                            <span class="badge bg-danger"><?= $totalLixeira ?></span>
                            <?= $totalLixeira === 1 ? 'item aguardando' : 'itens aguardando' ?>
                        <?php else: ?>
                            Nenhum item na lixeira
                        <?php endif; ?>
                    </div>
                    <a href="index.php?action=admin_lixeira" class="btn btn-sm btn-outline-danger">
                        <i class="ti ti-trash" aria-hidden="true" style="font-size:13px; vertical-align:-1px;"></i>
                        Abrir lixeira
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm h-100" style="opacity:.55; pointer-events:none;">
                <div class="card-body d-flex flex-column align-items-center justify-content-center py-4 text-muted">
                    <i class="ti ti-settings" aria-hidden="true" style="font-size:32px; margin-bottom:8px;"></i>
                    <div class="fw-semibold">Configurações gerais</div>
                    <small>Em breve</small>
                </div>
            </div>
        </div>

    </div>

<!-- Modal — Pasta de backups -->
<div class="modal fade" id="modalPasta" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">
                    <i class="ti ti-folder-open" aria-hidden="true" style="font-size:15px; vertical-align:-2px;"></i>
                    Pasta de backups
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-2">Os arquivos de backup ficam salvos no seguinte caminho no servidor:</p>
                <code class="d-block bg-light p-3 rounded small"><?= htmlspecialchars($pastaBackup) ?></code>
                <p class="text-muted small mt-3 mb-0">
                    <i class="ti ti-info-circle" aria-hidden="true" style="font-size:13px; vertical-align:-1px;"></i>
                    Acesse essa pasta pelo Windows Explorer para copiar os backups para um local seguro.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>