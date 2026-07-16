<?php
$titulo = 'Mapa de Vantajosidade - Ata ' . $processo->numeroAta;
require __DIR__ . '/partials/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <span class="fs-6 fw-semibold" style="color: #1F3864;">
        <i class="ti ti-table" aria-hidden="true" style="font-size: 18px; vertical-align: -3px;"></i>
        Mapa de comprovação de vantajosidade
    </span>
    <div>
        <a href="index.php?action=vantajosidade&id=<?= $processo->id ?>" class="btn btn-sm btn-secondary">
            <i class="ti ti-arrow-left" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
            Voltar ao processo
        </a>
        <button onclick="window.print()" class="btn btn-sm btn-primary">
            <i class="ti ti-printer" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
            Imprimir / salvar PDF
        </button>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body small">
        <div class="row">
            <div class="col-md-3"><b>Ata:</b> <?= htmlspecialchars($processo->numeroAta) ?></div>
            <div class="col-md-3"><b>Órgão gerenciador:</b> <?= htmlspecialchars($processo->orgaoGerenciador) ?></div>
            <div class="col-md-3"><b>Servidor:</b> <?= htmlspecialchars($servidor->nome ?? '—') ?></div>
            <div class="col-md-3"><b>Data:</b> <?= date('d/m/Y') ?></div>
        </div>
        <?php if ($processo->objeto !== ''): ?>
            <div class="mt-2"><b>Objeto:</b> <?= htmlspecialchars($processo->objeto) ?></div>
        <?php endif; ?>
    </div>
</div>

<?php foreach ($lotesAgrupados as $numeroLote => $itensDoLote): ?>
    <?php
    $maxFontes = 0;
    foreach ($itensDoLote as $dadosItem) {
        $maxFontes = max($maxFontes, count($dadosItem['precos']));
    }
    $maxFontes = max($maxFontes, 1);
    ?>

    <div class="card shadow-sm mb-4">
        <div class="card-header text-white fw-bold" style="background-color: #1F3864;">
            Lote <?= htmlspecialchars($numeroLote) ?>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Item</th>
                            <th style="min-width: 220px;">Descrição</th>
                            <th>Preço Ata</th>
                            <?php for ($i = 1; $i <= $maxFontes; $i++): ?>
                                <th>Fonte <?= $i ?></th>
                            <?php endfor; ?>
                            <th>Média mercado</th>
                            <th>Resultado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($itensDoLote as $dadosItem): ?>
                            <?php
                            $item = $dadosItem['item'];
                            $precos = $dadosItem['precos'];
                            $resultado = $dadosItem['resultado'];
                            ?>
                            <tr>
                                <td class="text-center"><?= htmlspecialchars($item->item) ?></td>
                                <td><?= htmlspecialchars($item->descricao) ?></td>
                                <td class="fw-bold"><?= formatarMoeda($item->precoAta) ?></td>
                                <?php for ($i = 0; $i < $maxFontes; $i++): ?>
                                    <td>
                                        <?php if (isset($precos[$i])): ?>
                                            <div class="small text-muted"><?= htmlspecialchars($precos[$i]->fonte ?: '—') ?></div>
                                            <div><?= formatarMoeda($precos[$i]->valor) ?></div>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                <?php endfor; ?>
                                <td class="fw-bold">
                                    <?= $resultado['media_mercado'] !== null ? formatarMoeda($resultado['media_mercado']) : '—' ?>
                                </td>
                                <td>
                                    <?php if ($resultado['resultado'] === AnaliseVantajosidade::VANTAJOSA): ?>
                                        <span class="badge bg-success">Vantajosa</span>
                                    <?php elseif ($resultado['resultado'] === AnaliseVantajosidade::NAO_VANTAJOSA): ?>
                                        <span class="badge bg-danger">Não vantajosa</span>
                                    <?php else: ?>
                                        <span class="text-muted small">Sem preços coletados</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<div class="card shadow-sm mb-4" style="border: 2px solid #1F3864;">
    <div class="card-body">
        <p class="fw-bold mb-3">Resumo da vantajosidade</p>
        <div class="row text-center">
            <div class="col-md-4">
                <p class="text-muted small mb-1">Itens analisados</p>
                <p class="fs-4 fw-bold m-0"><?= $totalItens ?></p>
            </div>
            <div class="col-md-4">
                <p class="text-muted small mb-1">Itens vantajosos</p>
                <p class="fs-4 fw-bold m-0 text-success"><?= $totalVantajosos ?></p>
            </div>
            <div class="col-md-4">
                <p class="text-muted small mb-1">Itens não vantajosos</p>
                <p class="fs-4 fw-bold m-0 text-danger"><?= $totalNaoVantajosos ?></p>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <p class="mb-1"><b>Responsável pela elaboração:</b></p>
        <p class="mb-1">Nome: <?= htmlspecialchars($servidor->nome ?? '') ?></p>
        <p class="mb-1">Matrícula: <?= htmlspecialchars($servidor->matricula ?? '') ?></p>
        <p class="mb-0">Data: <?= date('d/m/Y') ?></p>
    </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>