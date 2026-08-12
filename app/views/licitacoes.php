<?php
$titulo = 'Licitações - MT Par';
require __DIR__ . '/partials/header.php';

$statusLabel = [
    StatusLicitacao::AguardandoPublicacao->value => ['Aguardando publicação', 'bg-secondary'],
    StatusLicitacao::Publicada->value => ['Publicada', 'bg-primary'],
    StatusLicitacao::Homologada->value => ['Homologada', 'bg-info text-dark'],
    StatusLicitacao::EncaminhadaParaContratacao->value => ['Encaminhada p/ contratação', 'bg-success'],
];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="section-title">
        <i class="ti ti-gavel" aria-hidden="true" style="font-size: 20px; vertical-align: -3px;"></i>
        Licitações
    </span>
    <a href="index.php?action=dashboard" class="btn btn-sm btn-secondary">
        <i class="ti ti-arrow-left" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
        Dashboard
    </a>
</div>

<p class="text-muted small mb-3">
    <i class="ti ti-info-circle" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
    Este painel é alimentado automaticamente quando uma demanda é marcada como CONCLUÍDO.
</p>

<?php if (count($licitacoes) === 0): ?>
    <div class="card shadow-sm">
        <div class="empty-state">
            <i class="ti ti-gavel" aria-hidden="true"></i>
            <p class="mb-0">Nenhuma licitação registrada ainda.</p>
            <p class="mb-0 small">Conclua uma demanda para que ela apareça aqui.</p>
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
                            <th>Processo</th>
                            <th>Setor / Responsável</th>
                            <th>Objeto</th>
                            <th>Datas</th>
                            <th>Valores</th>
                            <th>Dias</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($licitacoes as $licitacao): ?>
                            <?php
                            $economicidadeReais = $licitacao->calcularEconomicidadeReais();
                            $economicidadePercentual = $licitacao->calcularEconomicidadePercentual();
                            $diasNaLicitacao = $licitacao->calcularDiasNaLicitacao();
                            $servidorResponsavel = $licitacao->buscarServidorResponsavel();
                            [$statusTexto, $statusClasse] = $statusLabel[$licitacao->status()->value];
                            ?>
                            <tr>
                                <td><span class="badge <?= $statusClasse ?>"><?= $statusTexto ?></span></td>
                                <td>
                                    <?php if ($licitacao->linkSigadoc !== ''): ?>
                                        <a href="<?= htmlspecialchars($licitacao->linkSigadoc) ?>" target="_blank">
                                            <?= htmlspecialchars($licitacao->numeroProcesso) ?>
                                            <i class="ti ti-external-link" aria-hidden="true" style="font-size: 10px;"></i>
                                        </a>
                                    <?php else: ?>
                                        <?= htmlspecialchars($licitacao->numeroProcesso) ?>
                                    <?php endif; ?>
                                    <?php if ($licitacao->editalLicitacao !== ''): ?>
                                        <span class="text-muted d-block" style="font-size: 11px;">Edital: <?= htmlspecialchars($licitacao->editalLicitacao) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($licitacao->setorDemandante) ?>
                                    <span class="text-muted d-block" style="font-size: 11px;">
                                        <?= $servidorResponsavel !== null ? htmlspecialchars($servidorResponsavel->nome) : '—' ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars(mb_strimwidth($licitacao->objeto, 0, 40, '...')) ?></td>
                                <td style="font-size: 11px;">
                                    <span class="d-block">Receb: <?= date('d/m/Y', strtotime($licitacao->dataRecebimento)) ?></span>
                                    <span class="d-block text-muted">Sessão: <?= $licitacao->realizacaoSessaoPublica ? date('d/m/Y', strtotime($licitacao->realizacaoSessaoPublica)) : '—' ?></span>
                                    <span class="d-block text-muted">Contrato: <?= $licitacao->encaminhadoPactuacaoContrato ? date('d/m/Y', strtotime($licitacao->encaminhadoPactuacaoContrato)) : '—' ?></span>
                                </td>
                                <td style="font-size: 11px;">
                                    <span class="d-block">Estimado: <?= $licitacao->valorEstimado !== null ? formatarMoeda($licitacao->valorEstimado) : '—' ?></span>
                                    <span class="d-block text-muted">Adjudicado: <?= $licitacao->valorAdjudicado !== null ? formatarMoeda($licitacao->valorAdjudicado) : '—' ?></span>
                                    <?php if ($economicidadeReais !== null): ?>
                                        <span class="d-block <?= $economicidadeReais >= 0 ? 'text-success' : 'text-danger' ?>">
                                            Econ.: <?= formatarMoeda($economicidadeReais) ?> (<?= formatarNumero($economicidadePercentual, 1) ?>%)
                                        </span>
                                    <?php else: ?>
                                        <span class="d-block text-muted">Econ.: —</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $licitacao->estaEmAndamento() ? 'bg-warning text-dark' : 'bg-secondary' ?>">
                                        <?= $diasNaLicitacao ?>d
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="index.php?action=ver_demanda&id=<?= $licitacao->demandaId ?>&origem=licitacoes"
                                           class="btn btn-sm btn-outline-primary"
                                           title="Ver processo">
                                            <i class="ti ti-eye" aria-hidden="true" style="font-size: 13px;"></i>
                                            Ver processo
                                        </a>
                                    </div>
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