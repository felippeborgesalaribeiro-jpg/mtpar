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
    <a href="index.php?action=dashboard" class="btn btn-sm btn-secondary">
        <i class="ti ti-arrow-left" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
        Dashboard
    </a>
</div>

<p class="text-muted small mb-3">
    <i class="ti ti-info-circle" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
    Painel de acompanhamento das demandas do setor. Para cadastrar um novo processo, use o botão
    "Cadastrar Processo" no Dashboard.
</p>

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
                                    <a href="index.php?action=ver_demanda&id=<?= $demanda->id ?>"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="ti ti-eye" aria-hidden="true" style="font-size:13px; vertical-align:-1px;"></i>
                                        Ver Processo
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
