<?php
$titulo = 'Demandas - MT Par';
require __DIR__ . '/partials/header.php';

$classesBadgeStatus = [
    Demanda::STATUS_CONCLUIDO => 'bg-success',
    Demanda::STATUS_CANCELADO => 'bg-secondary',
];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="section-title">
        <i class="ti ti-folder" aria-hidden="true" style="font-size: 20px; vertical-align: -3px;"></i>
        Demandas
    </span>
    <div>
        <a href="index.php?action=dashboard" class="btn btn-sm btn-secondary">
            <i class="ti ti-arrow-left" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
            Dashboard
        </a>
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovaDemanda">
            <i class="ti ti-plus" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
            Nova demanda
        </button>
    </div>
</div>

<?php if (count($demandas) === 0): ?>
    <div class="card shadow-sm">
        <div class="empty-state">
            <i class="ti ti-folder-off" aria-hidden="true"></i>
            <p class="mb-0">Nenhuma demanda cadastrada ainda.</p>
        </div>
    </div>
<?php else: ?>
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Status</th>
                            <th>Nº Processo</th>
                            <th>Setor Demandante</th>
                            <th>Data Recebimento</th>
                            <th>Objeto</th>
                            <th>Servidor Responsável</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($demandas as $demanda): ?>
                            <?php $servidorResp = $demanda->buscarServidorResponsavel(); ?>
                            <tr>
                                <td>
                                    <span class="badge <?= $classesBadgeStatus[$demanda->status] ?? 'bg-primary' ?> small">
                                        <?= htmlspecialchars($demanda->status) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($demanda->linkSigadoc !== ''): ?>
                                        <a href="<?= htmlspecialchars($demanda->linkSigadoc) ?>" target="_blank">
                                            <?= htmlspecialchars($demanda->numeroProcesso) ?>
                                            <i class="ti ti-external-link" aria-hidden="true" style="font-size: 11px;"></i>
                                        </a>
                                    <?php else: ?>
                                        <?= htmlspecialchars($demanda->numeroProcesso) ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($demanda->setorDemandante) ?></td>
                                <td><?= date('d/m/Y', strtotime($demanda->dataRecebimento)) ?></td>
                                <td class="small"><?= htmlspecialchars(mb_strimwidth($demanda->objeto, 0, 60, '...')) ?></td>
                                <td><?= $servidorResp ? htmlspecialchars($servidorResp->nome) : '—' ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                            data-bs-toggle="modal" data-bs-target="#modalEditarDemanda<?= $demanda->id ?>">
                                        <i class="ti ti-edit" aria-hidden="true" style="font-size: 13px;"></i>
                                    </button>
                                    <a href="index.php?action=excluir_demanda&id=<?= $demanda->id ?>"
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Excluir esta demanda?')">
                                        <i class="ti ti-trash" aria-hidden="true" style="font-size: 13px;"></i>
                                    </a>
                                </td>
                            </tr>

                            <div class="modal fade" id="modalEditarDemanda<?= $demanda->id ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <form method="post" action="index.php?action=editar_demanda">
                                            <input type="hidden" name="demanda_id" value="<?= $demanda->id ?>">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Editar demanda</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Número do processo</label>
                                                        <input type="text" name="numero_processo" class="form-control"
                                                               value="<?= htmlspecialchars($demanda->numeroProcesso) ?>" required>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Link Sigadoc</label>
                                                        <input type="url" name="link_sigadoc" class="form-control"
                                                               value="<?= htmlspecialchars($demanda->linkSigadoc) ?>" placeholder="https://...">
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Setor demandante</label>
                                                        <input type="text" name="setor_demandante" class="form-control"
                                                               value="<?= htmlspecialchars($demanda->setorDemandante) ?>">
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Data de recebimento</label>
                                                        <input type="date" name="data_recebimento" class="form-control"
                                                               value="<?= htmlspecialchars($demanda->dataRecebimento) ?>" required>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Objeto</label>
                                                    <textarea name="objeto" class="form-control" rows="2"><?= htmlspecialchars($demanda->objeto) ?></textarea>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Servidor responsável</label>
                                                        <select name="servidor_responsavel_id" class="form-select">
                                                            <option value="">—</option>
                                                            <?php foreach ($servidores as $servidor): ?>
                                                                <option value="<?= $servidor->id ?>" <?= $demanda->servidorResponsavelId === $servidor->id ? 'selected' : '' ?>>
                                                                    <?= htmlspecialchars($servidor->nome) ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Status</label>
                                                        <select name="status" class="form-select">
                                                            <?php foreach (Demanda::STATUS_OPCOES as $opcao): ?>
                                                                <option value="<?= $opcao ?>" <?= $demanda->status === $opcao ? 'selected' : '' ?>>
                                                                    <?= $opcao ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
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
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="modal fade" id="modalNovaDemanda" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" action="index.php?action=criar_demanda">
                <div class="modal-header">
                    <h5 class="modal-title">Nova demanda</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Número do processo</label>
                            <input type="text" name="numero_processo" class="form-control" placeholder="MTPAR-PRO-2026/00050" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Link Sigadoc</label>
                            <input type="url" name="link_sigadoc" class="form-control" placeholder="https://...">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Setor demandante</label>
                            <input type="text" name="setor_demandante" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Data de recebimento</label>
                            <input type="date" name="data_recebimento" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Objeto</label>
                        <textarea name="objeto" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Servidor responsável</label>
                            <select name="servidor_responsavel_id" class="form-select">
                                <option value="">—</option>
                                <?php foreach ($servidores as $servidor): ?>
                                    <option value="<?= $servidor->id ?>"><?= htmlspecialchars($servidor->nome) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <?php foreach (Demanda::STATUS_OPCOES as $opcao): ?>
                                    <option value="<?= $opcao ?>"><?= $opcao ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Criar demanda</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>