<?php
$titulo = 'Cotação ' . $cotacao->numeroProcesso . ' - MT Par';
require __DIR__ . '/partials/header.php';

$statusLabel = [
    Cotacao::STATUS_EM_ANDAMENTO => ['Em andamento', 'bg-primary'],
    Cotacao::STATUS_FINALIZADA => ['Finalizada', 'bg-success'],
];
[$labelStatus, $classeBadgeStatus] = $statusLabel[$cotacao->status] ?? ['Indefinido', 'bg-secondary'];
?>

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="m-0">
            <i class="ti ti-file-text" aria-hidden="true" style="font-size: 22px; vertical-align: -3px; color: #1F3864;"></i>
            Processo <?= htmlspecialchars($cotacao->numeroProcesso) ?>
            <span class="badge <?= $classeBadgeStatus ?>"><?= $labelStatus ?></span>
        </h4>
        <p class="text-muted mb-0"><?= htmlspecialchars($cotacao->orgaoSetor) ?></p>
    </div>
    <div>
        <a href="index.php?action=dashboard" class="btn btn-sm btn-secondary">
            <i class="ti ti-arrow-left" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
            Voltar ao dashboard
        </a>
        <a href="index.php?action=mapa&id=<?= $cotacao->id ?>" class="btn btn-sm btn-info text-white">
            <i class="ti ti-table" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
            Ver mapa comparativo
        </a>
        <?php if ($cotacao->status === Cotacao::STATUS_FINALIZADA): ?>
            <a href="index.php?action=gerar_relatorio_pesquisa&id=<?= $cotacao->id ?>" class="btn btn-sm btn-info text-white">
                <i class="ti ti-clipboard-data" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
                Relatório de pesquisa de preços
            </a>
            <a href="index.php?action=relatorio_formulario&id=<?= $cotacao->id ?>" class="btn btn-sm btn-warning text-dark">
                <i class="ti ti-file-report" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
                Gerar análise crítica
            </a>
        <?php endif; ?>
        <?php if ($cotacao->status === Cotacao::STATUS_EM_ANDAMENTO): ?>
            <a href="index.php?action=finalizar_cotacao&id=<?= $cotacao->id ?>"
               class="btn btn-sm btn-success"
               onclick="return confirm('Finalizar esta cotação?')">
                <i class="ti ti-check" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
                Finalizar cotação
            </a>
        <?php endif; ?>
        <a href="index.php?action=excluir_cotacao&id=<?= $cotacao->id ?>"
           class="btn btn-sm btn-outline-danger"
           onclick="return confirm('Excluir toda a cotação, incluindo lotes, itens e preços?')">
            <i class="ti ti-trash" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
            Excluir
        </a>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="row small">
            <div class="col-md-3">
                <i class="ti ti-clipboard-list icon-stat" aria-hidden="true"></i>
                <b>Procedimento:</b> <?= htmlspecialchars($cotacao->procedimento) ?>
            </div>
            <div class="col-md-3">
                <i class="ti ti-gavel icon-stat" aria-hidden="true"></i>
                <b>Tipo de julgamento:</b> <?= htmlspecialchars($cotacao->tipoJulgamento) ?>
            </div>
            <div class="col-md-3">
                <i class="ti ti-user icon-stat" aria-hidden="true"></i>
                <b>Servidor:</b> <?= htmlspecialchars($servidor->nome ?? '—') ?>
            </div>
            <div class="col-md-3">
                <i class="ti ti-calculator icon-stat" aria-hidden="true"></i>
                <b>Critério:</b> <?= $cotacao->criterioConsolidacao ?>
            </div>
        </div>
        <?php if ($cotacao->objeto !== ''): ?>
            <div class="small mt-2"><b>Objeto:</b> <?= htmlspecialchars($cotacao->objeto) ?></div>
        <?php endif; ?>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="section-title">
        <i class="ti ti-package" aria-hidden="true" style="font-size: 18px; vertical-align: -3px;"></i>
        Lotes
    </span>
    <form method="post" action="index.php?action=criar_lote">
        <input type="hidden" name="cotacao_id" value="<?= $cotacao->id ?>">
        <button type="submit" class="btn btn-primary btn-sm">
            <i class="ti ti-plus" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
            Novo lote
        </button>
    </form>
</div>

<?php if (count($lotes) === 0): ?>
    <div class="card shadow-sm">
        <div class="empty-state">
            <i class="ti ti-package-off" aria-hidden="true"></i>
            <p class="mb-0">Nenhum lote criado ainda.</p>
        </div>
    </div>
<?php endif; ?>

<?php foreach ($lotes as $lote): ?>
    <div class="card shadow-sm mb-3">
        <div class="card-header d-flex justify-content-between align-items-center text-white" style="background-color: #1F3864;">
            <span class="fw-bold">
                <i class="ti ti-box" aria-hidden="true" style="font-size: 15px; vertical-align: -2px;"></i>
                Lote <?= htmlspecialchars($lote->numero) ?>
            </span>
            <a href="index.php?action=excluir_lote&id=<?= $lote->id ?>"
               class="btn btn-sm btn-outline-light"
               onclick="return confirm('Excluir este lote e todos os itens/preços dele?')">
                <i class="ti ti-trash" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
                Excluir lote
            </a>
        </div>
        <div class="card-body">

            <?php $itens = $lote->buscarItens(); ?>

            <?php if (count($itens) === 0): ?>
                <p class="text-muted small mb-3">
                    <i class="ti ti-info-circle" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
                    Nenhum item neste lote ainda. Adicione o primeiro abaixo.
                </p>
            <?php endif; ?>

            <?php foreach ($itens as $item): ?>
                <?php
                $resultado = $item->analisar($cotacao->criterioConsolidacao);
                $precos = $item->buscarPrecos();
                ?>

                <div class="border rounded p-3 mb-3" style="background-color: #fafbfc;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="m-0">
                            <span class="badge bg-secondary"><?= $item->numero ?></span>
                            <?= htmlspecialchars($item->descricao) ?>
                            <span class="text-muted small">(<?= formatarNumero($item->quantidade) ?> <?= htmlspecialchars($item->unidade) ?>)</span>
                        </h6>
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                    data-bs-toggle="modal" data-bs-target="#modalEditarItem<?= $item->id ?>">
                                <i class="ti ti-edit" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
                                Editar
                            </button>
                            <a href="index.php?action=excluir_item&id=<?= $item->id ?>"
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('Excluir este item e todos os preços dele?')">
                                <i class="ti ti-trash" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
                                Excluir item
                            </a>
                        </div>
                    </div>

                    <?php if (count($precos) === 0): ?>
                        <p class="text-muted small">
                            <i class="ti ti-info-circle" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
                            Nenhum preço coletado ainda para este item.
                        </p>
                    <?php else: ?>
                        <table class="table table-sm table-bordered table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Parâmetro</th>
                                    <th>Fonte</th>
                                    <th>Preço</th>
                                    <th>Etapa 1 — Excessivo (&gt;30%)</th>
                                    <th>Etapa 2 — Inexequível (&lt;70%)</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($precos as $indice => $preco): ?>
                                    <?php
                                    $resultadoEtapa1 = $resultado['etapa1'][$indice]['resultado'];
                                    $resultadoEtapa2 = $resultado['etapa2'][$indice]['resultado'];
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($preco->parametro) ?></td>
                                        <td><?= htmlspecialchars($preco->fonte) ?></td>
                                        <td><?= formatarMoeda($preco->valor) ?></td>
                                        <td>
                                            <?php if ($resultadoEtapa1 === AnalisePrecos::EXCESSIVO): ?>
                                                <span class="badge bg-danger">Excessivo</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Aprovado</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($resultadoEtapa1 === AnalisePrecos::EXCESSIVO): ?>
                                                <span class="text-muted small">—</span>
                                            <?php elseif ($resultadoEtapa2 === AnalisePrecos::INEXEQUIVEL): ?>
                                                <span class="badge bg-danger">Inexequível</span>
                                            <?php elseif ($resultadoEtapa2 === AnalisePrecos::EXCECAO_PRECO_PUBLICO): ?>
                                                <span class="badge bg-warning text-dark">Exceção - preço público</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Aprovado</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                                    data-bs-toggle="modal" data-bs-target="#modalEditarPreco<?= $preco->id ?>">
                                                <i class="ti ti-edit" aria-hidden="true" style="font-size: 12px;"></i>
                                            </button>
                                            <a href="index.php?action=excluir_preco&id=<?= $preco->id ?>"
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Excluir este preço?')">
                                                <i class="ti ti-trash" aria-hidden="true" style="font-size: 12px;"></i>
                                            </a>
                                        </td>
                                    </tr>

                                    <div class="modal fade" id="modalEditarPreco<?= $preco->id ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="post" action="index.php?action=editar_preco">
                                                    <input type="hidden" name="preco_id" value="<?= $preco->id ?>">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Editar preço</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">Parâmetro</label>
                                                            <select name="parametro" class="form-select">
                                                                <option value="">Nenhum</option>
                                                                <?php foreach ($parametros as $parametroOpcao): ?>
                                                                    <option value="<?= htmlspecialchars($parametroOpcao->nome) ?>" <?= $preco->parametro === $parametroOpcao->nome ? 'selected' : '' ?>>
                                                                        <?= htmlspecialchars($parametroOpcao->nome) ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Fonte / fornecedor</label>
                                                            <input type="text" name="fonte" class="form-control"
                                                                   value="<?= htmlspecialchars($preco->fonte) ?>">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Preço</label>
                                                            <input type="text" name="valor" class="form-control"
                                                                   value="<?= formatarNumero($preco->valor) ?>" required>
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
                    <?php endif; ?>

                    <form method="post" action="index.php?action=adicionar_preco" class="row g-2">
                        <input type="hidden" name="item_id" value="<?= $item->id ?>">
                        <div class="col-md-3">
                            <select name="parametro" class="form-select form-select-sm">
                                <option value="">Parâmetro</option>
                                <?php foreach ($parametros as $parametroOpcao): ?>
                                    <option value="<?= htmlspecialchars($parametroOpcao->nome) ?>">
                                        <?= htmlspecialchars($parametroOpcao->nome) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <input type="text" name="fonte" class="form-control form-control-sm" placeholder="Fonte / fornecedor">
                        </div>
                        <div class="col-md-2">
                            <input type="text" name="valor" class="form-control form-control-sm" placeholder="Preço" required>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="ti ti-plus" aria-hidden="true" style="font-size: 12px;"></i>
                                Preço
                            </button>
                        </div>
                    </form>

                    <p class="mb-0 mt-3 small">
                        <i class="ti ti-target-arrow" aria-hidden="true" style="font-size: 14px; vertical-align: -1px; color: #1F3864;"></i>
                        <b>Valor de referência (<?= $cotacao->criterioConsolidacao ?>):</b>
                        <?= formatarMoeda($resultado['valor_referencia'] ?? 0) ?>
                        &nbsp;—&nbsp;
                        <b>Total (x <?= formatarNumero($item->quantidade) ?>):</b>
                        <span class="badge bg-success"><?= formatarMoeda(($resultado['valor_referencia'] ?? 0) * $item->quantidade) ?></span>
                    </p>
                </div>

                <div class="modal fade" id="modalEditarItem<?= $item->id ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="post" action="index.php?action=editar_item">
                                <input type="hidden" name="item_id" value="<?= $item->id ?>">
                                <div class="modal-header">
                                    <h5 class="modal-title">Editar item</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">Descrição</label>
                                        <input type="text" name="descricao" class="form-control"
                                               value="<?= htmlspecialchars($item->descricao) ?>" required>
                                    </div>
                                    <div class="row">
                                        <div class="col-6">
                                            <label class="form-label">Unidade</label>
                                            <input type="text" name="unidade" class="form-control"
                                                   value="<?= htmlspecialchars($item->unidade) ?>">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label">Quantidade</label>
                                            <input type="text" name="quantidade" class="form-control"
                                                   value="<?= formatarNumero($item->quantidade) ?>">
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

            <form method="post" action="index.php?action=adicionar_item" class="row g-2 mt-2">
                <input type="hidden" name="lote_id" value="<?= $lote->id ?>">
                <div class="col-md-6">
                    <input type="text" name="descricao" class="form-control" placeholder="Descrição do novo item" required>
                </div>
                <div class="col-md-2">
                    <input type="text" name="unidade" class="form-control" placeholder="Unidade (ex: UN)" value="UN">
                </div>
                <div class="col-md-2">
                    <input type="text" name="quantidade" class="form-control" placeholder="Quantidade" value="1">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="ti ti-plus" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
                        Item
                    </button>
                </div>
            </form>

        </div>
    </div>
<?php endforeach; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>