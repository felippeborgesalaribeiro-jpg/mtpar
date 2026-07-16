<?php
$titulo = 'Demandas - MT Par';
require __DIR__ . '/partials/header.php';

$coresStatus = [
    'EM ANDAMENTO'                    => 'bg-primary-subtle text-primary',
    'ELABORAÇÃO DE TR'                => 'bg-warning-subtle text-warning',
    'ELABORAÇÃO DE PESQUISA DE PREÇO' => 'bg-warning-subtle text-warning',
    'AVISO DE LICITAÇÃO'              => 'bg-info-subtle text-info',
    'AVISO DE DISPENSA DE LICITAÇÃO'  => 'bg-info-subtle text-info',
    'EMISSÃO DE PED RESERVA'          => 'bg-info-subtle text-info',
    'FASE DE HABILITAÇÃO'             => 'bg-danger-subtle text-danger',
    'ENVIADO PARA CONDES'             => 'bg-secondary-subtle text-secondary',
    'ENVIADO PARA PARECER JURÍDICO'   => 'bg-secondary-subtle text-secondary',
    'ENVIADO PARA PGE'                => 'bg-secondary-subtle text-secondary',
    'SANEAMENTO DE PROCESSO'          => 'bg-secondary-subtle text-secondary',
    'PUBLICADO'                       => 'bg-success-subtle text-success',
    'CONCLUÍDO'                       => 'bg-success-subtle text-success',
    'CANCELADO'                       => 'bg-dark-subtle text-dark',
];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="section-title">
        <i class="ti ti-folder" aria-hidden="true" style="font-size: 20px; vertical-align: -3px;"></i>
        Demandas
    </span>
    <div class="d-flex gap-2">
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
            <p class="mb-0">Nenhuma demanda registrada ainda.</p>
        </div>
    </div>
<?php else: ?>
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0 small">
                    <thead class="table-dark">
                        <tr>
                            <th>Status</th>
                            <th>Nº Processo</th>
                            <th>Setor</th>
                            <th>Recebimento</th>
                            <th>Objeto</th>
                            <th>Responsável</th>
                            <th>Vínculo</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($demandas as $demanda): ?>
                            <?php
                            $corStatus = $coresStatus[$demanda->status] ?? 'bg-secondary-subtle text-secondary';
                            $servidorResp = $demanda->buscarServidorResponsavel();
                            $cotacaoVinc = $demanda->buscarCotacaoVinculada();
                            $vantajVinc  = $demanda->buscarVantajosidadeVinculada();
                            $temVinculo  = $cotacaoVinc !== null || $vantajVinc !== null;
                            ?>
                            <tr>
                                <td>
                                    <span class="badge <?= $corStatus ?>" style="font-size:10px;">
                                        <?= htmlspecialchars($demanda->status) ?>
                                    </span>
                                </td>
                                <td class="fw-semibold">
                                    <?php if ($demanda->linkSigadoc !== ''): ?>
                                        <a href="<?= htmlspecialchars($demanda->linkSigadoc) ?>" target="_blank">
                                            <?= htmlspecialchars($demanda->numeroProcesso) ?>
                                            <i class="ti ti-external-link" aria-hidden="true" style="font-size:10px;"></i>
                                        </a>
                                    <?php else: ?>
                                        <?= htmlspecialchars($demanda->numeroProcesso) ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($demanda->setorDemandante) ?></td>
                                <td><?= $demanda->dataRecebimento ? date('d/m/Y', strtotime($demanda->dataRecebimento)) : '—' ?></td>
                                <td><?= htmlspecialchars(mb_strimwidth($demanda->objeto, 0, 50, '...')) ?></td>
                                <td><?= $servidorResp ? htmlspecialchars($servidorResp->nome) : '<span class="text-muted">—</span>' ?></td>
                                <td class="text-center">
                                    <?php if ($temVinculo): ?>
                                        <i class="ti ti-link text-success" aria-hidden="true"
                                           style="font-size:14px;" title="Possui vínculo"></i>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="index.php?action=ver_demanda&id=<?= $demanda->id ?>"
                                           class="btn btn-sm btn-outline-primary"
                                           title="Ver detalhes">
                                            <i class="ti ti-eye" aria-hidden="true" style="font-size:13px;"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalEditarDemanda<?= $demanda->id ?>"
                                                title="Editar">
                                            <i class="ti ti-edit" aria-hidden="true" style="font-size:13px;"></i>
                                        </button>
                                        <a href="index.php?action=excluir_demanda&id=<?= $demanda->id ?>"
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Excluir esta demanda?')"
                                           title="Excluir">
                                            <i class="ti ti-trash" aria-hidden="true" style="font-size:13px;"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>

                            <!-- Modal editar demanda -->
                            <div class="modal fade" id="modalEditarDemanda<?= $demanda->id ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <form method="post" action="index.php?action=editar_demanda">
                                            <input type="hidden" name="demanda_id" value="<?= $demanda->id ?>">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Editar demanda — <?= htmlspecialchars($demanda->numeroProcesso) ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row g-3 mb-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Nº processo</label>
                                                        <input type="text" name="numero_processo" class="form-control"
                                                               value="<?= htmlspecialchars($demanda->numeroProcesso) ?>" required>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Data de recebimento</label>
                                                        <input type="date" name="data_recebimento" class="form-control"
                                                               value="<?= htmlspecialchars($demanda->dataRecebimento) ?>" required>
                                                    </div>
                                                </div>
                                                <div class="row g-3 mb-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Setor demandante</label>
                                                        <input type="text" name="setor_demandante" class="form-control"
                                                               value="<?= htmlspecialchars($demanda->setorDemandante) ?>">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Status</label>
                                                        <select name="status" class="form-select">
                                                            <?php foreach (Demanda::STATUS_OPCOES as $opcao): ?>
                                                                <option value="<?= $opcao ?>" <?= $demanda->status === $opcao ? 'selected' : '' ?>>
                                                                    <?= htmlspecialchars($opcao) ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="row g-3 mb-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Responsável</label>
                                                        <select name="servidor_responsavel_id" class="form-select">
                                                            <option value="">— Sem responsável —</option>
                                                            <?php foreach ($servidores as $servidor): ?>
                                                                <option value="<?= $servidor->id ?>"
                                                                    <?= $demanda->servidorResponsavelId === $servidor->id ? 'selected' : '' ?>>
                                                                    <?= htmlspecialchars($servidor->nome) ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Link SIGADOC</label>
                                                        <input type="text" name="link_sigadoc" class="form-control"
                                                               value="<?= htmlspecialchars($demanda->linkSigadoc) ?>"
                                                               placeholder="https://sigadoc.mt.gov.br/...">
                                                    </div>
                                                </div>
                                                <div class="mb-0">
                                                    <label class="form-label">Objeto</label>
                                                    <textarea name="objeto" class="form-control" rows="3"><?= htmlspecialchars($demanda->objeto) ?></textarea>
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

<!-- Modal nova demanda -->
<div class="modal fade" id="modalNovaDemanda" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" action="index.php?action=criar_demanda">
                <div class="modal-header">
                    <h5 class="modal-title">Nova demanda</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nº processo</label>
                            <input type="text" name="numero_processo" class="form-control"
                                   placeholder="MTPAR-PRO-2026/00001" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Data de recebimento</label>
                            <input type="date" name="data_recebimento" class="form-control" required>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Setor demandante</label>
                            <input type="text" name="setor_demandante" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <?php foreach (Demanda::STATUS_OPCOES as $opcao): ?>
                                    <option value="<?= $opcao ?>"><?= htmlspecialchars($opcao) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Responsável</label>
                            <select name="servidor_responsavel_id" class="form-select">
                                <option value="">— Sem responsável —</option>
                                <?php foreach ($servidores as $servidor): ?>
                                    <option value="<?= $servidor->id ?>"><?= htmlspecialchars($servidor->nome) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Link SIGADOC</label>
                            <input type="text" name="link_sigadoc" class="form-control"
                                   placeholder="https://sigadoc.mt.gov.br/...">
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Objeto</label>
                        <textarea name="objeto" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>