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
                            <th>Edital</th>
                            <th>Nº Processo</th>
                            <th>Setor Demandante</th>
                            <th>Servidor Responsável</th>
                            <th>Data Receb.</th>
                            <th>Objeto</th>
                            <th>Sessão Pública</th>
                            <th>Valor Estimado</th>
                            <th>Valor Adjudicado</th>
                            <th>Economicidade</th>
                            <th>Encaminhado Contrato</th>
                            <th>Dias na Licitação</th>
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
                                <td><?= $licitacao->editalLicitacao !== '' ? htmlspecialchars($licitacao->editalLicitacao) : '<span class="text-muted">—</span>' ?></td>
                                <td>
                                    <?php if ($licitacao->linkSigadoc !== ''): ?>
                                        <a href="<?= htmlspecialchars($licitacao->linkSigadoc) ?>" target="_blank">
                                            <?= htmlspecialchars($licitacao->numeroProcesso) ?>
                                            <i class="ti ti-external-link" aria-hidden="true" style="font-size: 10px;"></i>
                                        </a>
                                    <?php else: ?>
                                        <?= htmlspecialchars($licitacao->numeroProcesso) ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($licitacao->setorDemandante) ?></td>
                                <td><?= $servidorResponsavel !== null ? htmlspecialchars($servidorResponsavel->nome) : '<span class="text-muted">—</span>' ?></td>
                                <td><?= date('d/m/Y', strtotime($licitacao->dataRecebimento)) ?></td>
                                <td><?= htmlspecialchars(mb_strimwidth($licitacao->objeto, 0, 40, '...')) ?></td>
                                <td><?= $licitacao->realizacaoSessaoPublica ? date('d/m/Y', strtotime($licitacao->realizacaoSessaoPublica)) : '—' ?></td>
                                <td><?= $licitacao->valorEstimado !== null ? formatarMoeda($licitacao->valorEstimado) : '—' ?></td>
                                <td><?= $licitacao->valorAdjudicado !== null ? formatarMoeda($licitacao->valorAdjudicado) : '—' ?></td>
                                <td>
                                    <?php if ($economicidadeReais !== null): ?>
                                        <span class="<?= $economicidadeReais >= 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= formatarMoeda($economicidadeReais) ?>
                                            (<?= formatarNumero($economicidadePercentual, 1) ?>%)
                                        </span>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td><?= $licitacao->encaminhadoPactuacaoContrato ? date('d/m/Y', strtotime($licitacao->encaminhadoPactuacaoContrato)) : '—' ?></td>
                                <td>
                                    <span class="badge <?= $licitacao->estaEmAndamento() ? 'bg-warning text-dark' : 'bg-secondary' ?>">
                                        <?= $diasNaLicitacao ?> dia(s)
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="index.php?action=ver_demanda&id=<?= $licitacao->demandaId ?>"
                                           class="btn btn-sm btn-outline-primary"
                                           title="Ver processo">
                                            <i class="ti ti-eye" aria-hidden="true" style="font-size: 13px;"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                                data-bs-toggle="modal" data-bs-target="#modalEditarLicitacao<?= $licitacao->id ?>"
                                                title="Editar">
                                            <i class="ti ti-edit" aria-hidden="true" style="font-size: 13px;"></i>
                                        </button>
                                        <a href="index.php?action=excluir_licitacao&id=<?= $licitacao->id ?>"
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Excluir esta licitação?')"
                                           title="Excluir">
                                            <i class="ti ti-trash" aria-hidden="true" style="font-size: 13px;"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>

                            <div class="modal fade" id="modalEditarLicitacao<?= $licitacao->id ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <form method="post" action="index.php?action=editar_licitacao">
                                            <input type="hidden" name="licitacao_id" value="<?= $licitacao->id ?>">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Editar licitação — <?= htmlspecialchars($licitacao->numeroProcesso) ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">Servidor responsável</label>
                                                    <select name="servidor_responsavel_id" class="form-select">
                                                        <option value="">— Selecione —</option>
                                                        <?php foreach ($servidores as $servidor): ?>
                                                            <option value="<?= $servidor->id ?>" <?= $licitacao->servidorResponsavelId === $servidor->id ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($servidor->nome) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Edital de licitação</label>
                                                    <input type="text" name="edital_licitacao" class="form-control"
                                                           value="<?= htmlspecialchars($licitacao->editalLicitacao) ?>" placeholder="Nº do edital">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Realização da sessão pública</label>
                                                    <input type="date" name="realizacao_sessao_publica" class="form-control"
                                                           value="<?= htmlspecialchars($licitacao->realizacaoSessaoPublica ?? '') ?>">
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Valor estimado</label>
                                                        <input type="text" name="valor_estimado" class="form-control"
                                                               value="<?= $licitacao->valorEstimado !== null ? formatarNumero($licitacao->valorEstimado) : '' ?>">
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Valor adjudicado</label>
                                                        <input type="text" name="valor_adjudicado" class="form-control"
                                                               value="<?= $licitacao->valorAdjudicado !== null ? formatarNumero($licitacao->valorAdjudicado) : '' ?>">
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Encaminhado para pactuação do contrato</label>
                                                    <input type="date" name="encaminhado_pactuacao_contrato" class="form-control"
                                                           value="<?= htmlspecialchars($licitacao->encaminhadoPactuacaoContrato ?? '') ?>">
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

<?php require __DIR__ . '/partials/footer.php'; ?>