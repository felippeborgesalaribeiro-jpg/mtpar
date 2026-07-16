<?php
$titulo = 'Mapa Comparativo - ' . $cotacao->numeroProcesso;
require __DIR__ . '/partials/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <span class="fs-6 fw-semibold" style="color: #1F3864;">
        <i class="ti ti-table" aria-hidden="true" style="font-size: 18px; vertical-align: -3px;"></i>
        Mapa comparativo de preços (detalhado)
    </span>
    <div>
        <a href="index.php?action=cotacao&id=<?= $cotacao->id ?>" class="btn btn-sm btn-secondary">
            <i class="ti ti-arrow-left" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
            Voltar à cotação
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
            <div class="col-md-3"><b>Processo:</b> <?= htmlspecialchars($cotacao->numeroProcesso) ?></div>
            <div class="col-md-3"><b>Órgão/Setor:</b> <?= htmlspecialchars($cotacao->orgaoSetor) ?></div>
            <div class="col-md-3"><b>Servidor:</b> <?= htmlspecialchars($servidor->nome ?? '—') ?></div>
            <div class="col-md-3"><b>Critério:</b> <?= $cotacao->criterioConsolidacao ?></div>
        </div>
    </div>
</div>

<?php foreach ($mapaLotes as $dadosLote): ?>
    <?php
    $lote = $dadosLote['lote'];
    $maxFornecedores = 0;
    foreach ($dadosLote['itens'] as $dadosItem) {
        $maxFornecedores = max($maxFornecedores, count($dadosItem['fornecedores']));
    }
    $maxFornecedores = max($maxFornecedores, 1);
    ?>

    <div class="card shadow-sm mb-4">
        <div class="card-header text-white fw-bold" style="background-color: #1F3864;">
            Resumo: Lote <?= htmlspecialchars($lote->numero) ?>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Item</th>
                            <th style="min-width: 260px;">Especificação</th>
                            <?php for ($i = 1; $i <= $maxFornecedores; $i++): ?>
                                <th>Fonte <?= $i ?></th>
                            <?php endfor; ?>
                            <th>Média / critério</th>
                            <th>UND</th>
                            <th>QTD</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dadosLote['itens'] as $dadosItem): ?>
                            <?php $item = $dadosItem['item']; ?>
                            <tr>
                                <td class="text-center"><?= $item->numero ?></td>
                                <td><?= htmlspecialchars($item->descricao) ?></td>
                                <?php for ($i = 0; $i < $maxFornecedores; $i++): ?>
                                    <td>
                                        <?php if (isset($dadosItem['fornecedores'][$i])): ?>
                                            <div class="small text-muted"><?= htmlspecialchars($dadosItem['fornecedores'][$i]['fonte']) ?></div>
                                            <div><?= formatarMoeda($dadosItem['fornecedores'][$i]['valor']) ?></div>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                <?php endfor; ?>
                                <td class="fw-bold"><?= formatarMoeda($dadosItem['valor_referencia']) ?></td>
                                <td class="text-center"><?= htmlspecialchars($item->unidade) ?></td>
                                <td class="text-center"><?= formatarNumero($item->quantidade) ?></td>
                                <td class="fw-bold"><?= formatarMoeda($dadosItem['total']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-3 text-end">
                <span class="fs-5">
                    <b>Valor total do Lote <?= htmlspecialchars($lote->numero) ?>:</b>
                    <span class="badge bg-success fs-6">
                        <?= formatarMoeda($dadosLote['valor_total']) ?>
                    </span>
                </span>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<div class="card shadow-sm mb-4" style="border: 2px solid #1F3864;">
    <div class="card-body text-end">
        <span class="fs-4">
            <b>Valor global da cotação:</b>
            <span class="badge fs-5" style="background-color: #1F3864;">
                <?= formatarMoeda($valorGlobalCotacao) ?>
            </span>
        </span>
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