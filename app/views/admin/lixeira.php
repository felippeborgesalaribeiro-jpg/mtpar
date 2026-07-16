<?php
$titulo = 'Lixeira — MT Par';
require __DIR__ . '/../partials/header.php';
?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0 fw-semibold">Lixeira</h4>
            <small class="text-muted">Registros excluídos — restaure ou exclua permanentemente.</small>
        </div>
        <a href="index.php?action=admin" class="btn btn-sm btn-secondary">
            <i class="ti ti-arrow-left" aria-hidden="true" style="font-size:13px; vertical-align:-1px;"></i>
            Voltar
        </a>
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

    <?php
    $totalGeral = count($demandasExcluidas) + count($cotacoesExcluidas) + count($vantajosidadesExcluidas);
    if ($totalGeral === 0):
    ?>
        <div class="card shadow-sm">
            <div class="card-body text-center py-5 text-muted">
                <i class="ti ti-trash-off" aria-hidden="true" style="font-size:36px; display:block; margin-bottom:8px;"></i>
                <p class="mb-0">A lixeira está vazia.</p>
            </div>
        </div>
    <?php else: ?>

    <!-- DEMANDAS EXCLUÍDAS -->
    <?php if (count($demandasExcluidas) > 0): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white d-flex align-items-center gap-2 py-2">
            <i class="ti ti-folder" aria-hidden="true" style="font-size:16px; color:#1F3864;"></i>
            <span class="fw-semibold small">Demandas</span>
            <span class="badge bg-secondary ms-1"><?= count($demandasExcluidas) ?></span>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>Nº processo</th>
                        <th>Setor</th>
                        <th>Status</th>
                        <th>Excluído em</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($demandasExcluidas as $d): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($d->numeroProcesso) ?></td>
                        <td><?= htmlspecialchars($d->setorDemandante ?: '—') ?></td>
                        <td><span class="badge bg-secondary-subtle text-secondary" style="font-size:10px;"><?= htmlspecialchars($d->status) ?></span></td>
                        <td class="text-muted">
                            <?= $d->deletedAt ? date('d/m/Y H:i', strtotime($d->deletedAt)) : '—' ?>
                        </td>
                        <td class="text-end">
                            <form method="POST" action="index.php?action=admin_restaurar_demanda" class="d-inline">
                                <input type="hidden" name="id" value="<?= $d->id ?>">
                                <button type="submit" class="btn btn-sm btn-outline-success">
                                    <i class="ti ti-restore" aria-hidden="true" style="font-size:13px;"></i>
                                    Restaurar
                                </button>
                            </form>
                            <form method="POST" action="index.php?action=admin_excluir_definitivo_demanda" class="d-inline"
                                  onsubmit="return confirm('Excluir permanentemente? Esta ação não pode ser desfeita.')">
                                <input type="hidden" name="id" value="<?= $d->id ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="ti ti-trash" aria-hidden="true" style="font-size:13px;"></i>
                                    Excluir definitivo
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- COTAÇÕES EXCLUÍDAS -->
    <?php if (count($cotacoesExcluidas) > 0): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white d-flex align-items-center gap-2 py-2">
            <i class="ti ti-clipboard-list" aria-hidden="true" style="font-size:16px; color:#0891b2;"></i>
            <span class="fw-semibold small">Pesquisas de preço (Cotações)</span>
            <span class="badge bg-secondary ms-1"><?= count($cotacoesExcluidas) ?></span>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>Nº processo</th>
                        <th>Órgão/Setor</th>
                        <th>Status</th>
                        <th>Excluído em</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cotacoesExcluidas as $c): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($c->numeroProcesso) ?></td>
                        <td><?= htmlspecialchars($c->orgaoSetor ?: '—') ?></td>
                        <td>
                            <span class="badge <?= $c->status === 'FINALIZADA' ? 'bg-success' : 'bg-primary' ?>" style="font-size:10px;">
                                <?= $c->status === 'FINALIZADA' ? 'Finalizada' : 'Em andamento' ?>
                            </span>
                        </td>
                        <td class="text-muted">
                            <?= $c->deletedAt ? date('d/m/Y H:i', strtotime($c->deletedAt)) : '—' ?>
                        </td>
                        <td class="text-end">
                            <form method="POST" action="index.php?action=admin_restaurar_cotacao" class="d-inline">
                                <input type="hidden" name="id" value="<?= $c->id ?>">
                                <button type="submit" class="btn btn-sm btn-outline-success">
                                    <i class="ti ti-restore" aria-hidden="true" style="font-size:13px;"></i>
                                    Restaurar
                                </button>
                            </form>
                            <form method="POST" action="index.php?action=admin_excluir_definitivo_cotacao" class="d-inline"
                                  onsubmit="return confirm('Excluir permanentemente? Esta ação não pode ser desfeita.')">
                                <input type="hidden" name="id" value="<?= $c->id ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="ti ti-trash" aria-hidden="true" style="font-size:13px;"></i>
                                    Excluir definitivo
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- VANTAJOSIDADES EXCLUÍDAS -->
    <?php if (count($vantajosidadesExcluidas) > 0): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white d-flex align-items-center gap-2 py-2">
            <i class="ti ti-scale" aria-hidden="true" style="font-size:16px; color:#198754;"></i>
            <span class="fw-semibold small">Comprovações de vantajosidade</span>
            <span class="badge bg-secondary ms-1"><?= count($vantajosidadesExcluidas) ?></span>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>Nº da Ata</th>
                        <th>Órgão gerenciador</th>
                        <th>Status</th>
                        <th>Excluído em</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vantajosidadesExcluidas as $v): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($v->numeroAta) ?></td>
                        <td><?= htmlspecialchars($v->orgaoGerenciador ?: '—') ?></td>
                        <td>
                            <span class="badge <?= $v->status === 'FINALIZADO' ? 'bg-success' : 'bg-primary' ?>" style="font-size:10px;">
                                <?= $v->status === 'FINALIZADO' ? 'Finalizado' : 'Em andamento' ?>
                            </span>
                        </td>
                        <td class="text-muted">
                            <?= $v->deletedAt ? date('d/m/Y H:i', strtotime($v->deletedAt)) : '—' ?>
                        </td>
                        <td class="text-end">
                            <form method="POST" action="index.php?action=admin_restaurar_vantajosidade" class="d-inline">
                                <input type="hidden" name="id" value="<?= $v->id ?>">
                                <button type="submit" class="btn btn-sm btn-outline-success">
                                    <i class="ti ti-restore" aria-hidden="true" style="font-size:13px;"></i>
                                    Restaurar
                                </button>
                            </form>
                            <form method="POST" action="index.php?action=admin_excluir_definitivo_vantajosidade" class="d-inline"
                                  onsubmit="return confirm('Excluir permanentemente? Esta ação não pode ser desfeita.')">
                                <input type="hidden" name="id" value="<?= $v->id ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="ti ti-trash" aria-hidden="true" style="font-size:13px;"></i>
                                    Excluir definitivo
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>

<?php require __DIR__ . '/../partials/footer.php'; ?>