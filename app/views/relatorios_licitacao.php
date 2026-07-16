<?php
$titulo = 'Relatórios de Licitações - MT Par';
require __DIR__ . '/partials/header.php';

$secoes = [
    ['titulo' => 'Por unidade demandante', 'icone' => 'ti-building', 'coluna' => 'Setor Demandante', 'linhas' => $porSetorDemandante],
    ['titulo' => 'Por servidor da licitação', 'icone' => 'ti-user', 'coluna' => 'Servidor Responsável', 'linhas' => $porServidorResponsavel],
    ['titulo' => 'Por ano', 'icone' => 'ti-calendar', 'coluna' => 'Ano', 'linhas' => $porAno],
];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="section-title">
        <i class="ti ti-chart-bar" aria-hidden="true" style="font-size: 20px; vertical-align: -3px;"></i>
        Relatórios de Licitações
    </span>
    <a href="index.php?action=dashboard" class="btn btn-sm btn-secondary">
        <i class="ti ti-arrow-left" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
        Dashboard
    </a>
</div>

<p class="text-muted small mb-4">
    <i class="ti ti-info-circle" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
    Gerado automaticamente a partir de todas as licitações cadastradas. "Ano" usa a data da sessão pública, ou a
    data de criação do registro quando a sessão ainda não ocorreu.
</p>

<?php foreach ($secoes as $secao): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <p class="fw-semibold mb-3">
                <i class="ti <?= $secao['icone'] ?>" aria-hidden="true" style="font-size: 16px; vertical-align: -2px; color: #6c757d;"></i>
                <?= htmlspecialchars($secao['titulo']) ?>
            </p>

            <?php if (count($secao['linhas']) === 0): ?>
                <p class="text-muted small mb-0">Nenhum dado disponível ainda.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0 small">
                        <thead class="table-dark">
                            <tr>
                                <th><?= htmlspecialchars($secao['coluna']) ?></th>
                                <th>Qtd.</th>
                                <th>Valor Estimado</th>
                                <th>Homologadas</th>
                                <th>Valor Adjudicado</th>
                                <th>Economicidade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($secao['linhas'] as $linha): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string) $linha['chave']) ?></td>
                                    <td><?= $linha['quantidade'] ?></td>
                                    <td><?= formatarMoeda($linha['valor_estimado']) ?></td>
                                    <td><?= $linha['homologadas'] ?></td>
                                    <td><?= formatarMoeda($linha['valor_adjudicado']) ?></td>
                                    <td>
                                        <?php if ($linha['economicidade'] !== null): ?>
                                            <span class="<?= $linha['economicidade'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                                <?= formatarMoeda($linha['economicidade']) ?>
                                            </span>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
