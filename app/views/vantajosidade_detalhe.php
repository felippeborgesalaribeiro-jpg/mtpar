<?php
$titulo = 'Vantajosidade - Ata ' . $processo->numeroAta . ' - MT Par';
require __DIR__ . '/partials/header.php';

$statusLabel = [
    ProcessoVantajosidade::STATUS_EM_ANDAMENTO => ['Em andamento', 'bg-primary'],
    ProcessoVantajosidade::STATUS_FINALIZADO => ['Finalizado', 'bg-success'],
];
[$labelStatus, $classeBadgeStatus] = $statusLabel[$processo->status] ?? ['Indefinido', 'bg-secondary'];
?>

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="m-0">
            <i class="ti ti-scale" aria-hidden="true" style="font-size: 22px; vertical-align: -3px; color: #1F3864;"></i>
            Ata <?= htmlspecialchars($processo->numeroAta) ?>
            <span class="badge <?= $classeBadgeStatus ?>"><?= $labelStatus ?></span>
        </h4>
        <p class="text-muted mb-0"><?= htmlspecialchars($processo->orgaoGerenciador) ?></p>
        <?php if ($demandaVinculada !== null): ?>
            <p class="small mb-0">
                <i class="ti ti-link" aria-hidden="true" style="font-size: 13px; vertical-align: -1px; color: #1F3864;"></i>
                Vinculado à Demanda nº <?= htmlspecialchars($demandaVinculada->numeroProcesso) ?>
                <a href="index.php?action=demandas" class="text-decoration-none">
                    (ver demandas <i class="ti ti-arrow-right" aria-hidden="true" style="font-size: 11px;"></i>)
                </a>
            </p>
        <?php else: ?>
            <p class="small mb-0 text-muted">
                <i class="ti ti-link-off" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
                Sem vínculo com demanda
            </p>
        <?php endif; ?>
    </div>
    <div>
        <a href="index.php?action=vantajosidades" class="btn btn-sm btn-secondary">
            <i class="ti ti-arrow-left" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
            Voltar
        </a>
        <a href="index.php?action=mapa_vantajosidade&id=<?= $processo->id ?>" class="btn btn-sm btn-info text-white">
            <i class="ti ti-table" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
            Ver mapa de vantajosidade
        </a>
        <?php if ($processo->status === ProcessoVantajosidade::STATUS_EM_ANDAMENTO): ?>
            <a href="index.php?action=finalizar_vantajosidade&id=<?= $processo->id ?>"
               class="btn btn-sm btn-success"
               onclick="return confirm('Finalizar este processo?')">
                <i class="ti ti-check" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
                Finalizar
            </a>
        <?php endif; ?>
        <a href="index.php?action=excluir_vantajosidade&id=<?= $processo->id ?>"
           class="btn btn-sm btn-outline-danger"
           onclick="return confirm('Excluir todo o processo, incluindo itens e preços?')">
            <i class="ti ti-trash" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
            Excluir
        </a>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <p class="small mb-0"><b>Objeto:</b> <?= htmlspecialchars($processo->objeto ?: '—') ?></p>
        <p class="small mb-0"><b>Servidor responsável:</b> <?= htmlspecialchars($servidor->nome ?? '—') ?></p>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="fs-6 fw-semibold" style="color: #1F3864;">
        <i class="ti ti-list" aria-hidden="true" style="font-size: 18px; vertical-align: -3px;"></i>
        Itens da Ata
    </span>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalNovoItem">
        <i class="ti ti-plus" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
        Novo item
    </button>
</div>

<?php if (count($itens) === 0): ?>
    <div class="card shadow-sm">
        <div class="empty-state text-muted">
            <i class="ti ti-list" aria-hidden="true"></i>
            <p class="mb-0">Nenhum item cadastrado ainda. Adicione o primeiro acima.</p>
        </div>
    </div>
<?php endif; ?>

<?php foreach ($itens as $item): ?>
    <?php
    $resultado = $item->analisar();
    $precos = $item->buscarPrecos();
    $analiseAux = new AnaliseVantajosidade($item->precoAta, []);
    ?>

    <div class="card shadow-sm mb-3">
        <div class="card-header d-flex justify-content-between align-items-center text-white" style="background-color: #1F3864;">
            <span class="fw-bold">
                <i class="ti ti-box" aria-hidden="true" style="font-size: 15px; vertical-align: -2px;"></i>
                Lote <?= htmlspecialchars($item->lote) ?> — Item <?= htmlspecialchars($item->item) ?>
            </span>
            <div>
                <button type="button" class="btn btn-sm btn-outline-light"
                        data-bs-toggle="modal" data-bs-target="#modalEditarItem<?= $item->id ?>">
                    <i class="ti ti-edit" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
                    Editar
                </button>
                <a href="index.php?action=excluir_item_vantajosidade&id=<?= $item->id ?>"
                   class="btn btn-sm btn-outline-light"
                   onclick="return confirm('Excluir este item e todos os preços dele?')">
                    <i class="ti ti-trash" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
                    Excluir
                </a>
            </div>
        </div>
        <div class="card-body">
            <p class="mb-3">
                <b><?= htmlspecialchars($item->descricao) ?></b>
                <span class="text-muted small">(<?= formatarNumero($item->quantidade) ?> <?= htmlspecialchars($item->unidade) ?>)</span>
                &nbsp;—&nbsp;
                <b>Preço da Ata:</b>
                <span class="badge bg-info text-white"><?= formatarMoeda($item->precoAta) ?></span>
            </p>

            <?php if (count($precos) === 0): ?>
                <p class="text-muted small">
                    <i class="ti ti-info-circle" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
                    Nenhum preço de mercado coletado ainda para este item.
                </p>
            <?php else: ?>
                <table class="table table-sm table-bordered table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Parâmetro</th>
                            <th>Fonte</th>
                            <th>Preço mercado</th>
                            <th>% em relação à Ata</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($precos as $preco): ?>
                            <?php $diferenca = $analiseAux->calcularDiferencaPorPreco($preco->valor); ?>
                            <tr>
                                <td><?= htmlspecialchars($preco->parametro) ?></td>
                                <td><?= htmlspecialchars($preco->fonte) ?></td>
                                <td><?= formatarMoeda($preco->valor) ?></td>
                                <td class="<?= $diferenca >= 0 ? 'text-danger' : 'text-success' ?>">
                                    <?= $diferenca >= 0 ? '+' : '' ?><?= formatarNumero($diferenca, 1) ?>%
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                            data-bs-toggle="modal" data-bs-target="#modalEditarPreco<?= $preco->id ?>">
                                        <i class="ti ti-edit" aria-hidden="true" style="font-size: 12px;"></i>
                                    </button>
                                    <a href="index.php?action=excluir_preco_vantajosidade&id=<?= $preco->id ?>"
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Excluir este preço?')">
                                        <i class="ti ti-trash" aria-hidden="true" style="font-size: 12px;"></i>
                                    </a>
                                </td>
                            </tr>

                            <div class="modal fade" id="modalEditarPreco<?= $preco->id ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="post" action="index.php?action=editar_preco_vantajosidade">
                                            <input type="hidden" name="preco_id" value="<?= $preco->id ?>">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Editar preço de mercado</h5>
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
                                                    <input type="text" name="fonte" class="form-control" value="<?= htmlspecialchars($preco->fonte) ?>">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Preço de mercado</label>
                                                    <input type="text" name="valor" class="form-control" value="<?= formatarNumero($preco->valor) ?>" required>
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

            <form method="post" action="index.php?action=adicionar_preco_vantajosidade" class="row g-2">
                <input type="hidden" name="item_id" value="<?= $item->id ?>">
                <div class="col-md-3">
                    <select name="parametro" class="form-select form-select-sm">
                        <option value="">Parâmetro</option>
                        <?php foreach ($parametros as $parametroOpcao): ?>
                            <option value="<?= htmlspecialchars($parametroOpcao->nome) ?>"><?= htmlspecialchars($parametroOpcao->nome) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <input type="text" name="fonte" class="form-control form-control-sm" placeholder="Fonte / fornecedor">
                </div>
                <div class="col-md-2">
                    <input type="text" name="valor" class="form-control form-control-sm" placeholder="Preço mercado" required>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="ti ti-plus" aria-hidden="true" style="font-size: 12px;"></i>
                        Preço
                    </button>
                </div>
            </form>

            <?php if ($resultado['resultado'] !== null): ?>
                <p class="mb-0 mt-3 small">
                    <i class="ti ti-target-arrow" aria-hidden="true" style="font-size: 14px; vertical-align: -1px; color: #1F3864;"></i>
                    <b>Média de mercado:</b> <?= formatarMoeda($resultado['media_mercado']) ?>
                    &nbsp;—&nbsp;
                    <b>Resultado:</b>
                    <span class="badge <?= $resultado['resultado'] === AnaliseVantajosidade::VANTAJOSA ? 'bg-success' : 'bg-danger' ?>">
                        <?= $resultado['resultado'] === AnaliseVantajosidade::VANTAJOSA ? 'Vantajoso aderir' : 'Mercado mais barato' ?>
                    </span>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <div class="modal fade" id="modalEditarItem<?= $item->id ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="index.php?action=editar_item_vantajosidade">
                    <input type="hidden" name="item_id" value="<?= $item->id ?>">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar item</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Lote</label>
                                <input type="text" name="lote" class="form-control" value="<?= htmlspecialchars($item->lote) ?>" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Item</label>
                                <input type="text" name="item" class="form-control" value="<?= htmlspecialchars($item->item) ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descrição</label>
                            <input type="text" name="descricao" class="form-control" value="<?= htmlspecialchars($item->descricao) ?>">
                        </div>
                        <div class="row">
                            <div class="col-4 mb-3">
                                <label class="form-label">Unidade</label>
                                <input type="text" name="unidade" class="form-control" value="<?= htmlspecialchars($item->unidade) ?>">
                            </div>
                            <div class="col-4 mb-3">
                                <label class="form-label">Quantidade</label>
                                <input type="text" name="quantidade" class="form-control" value="<?= formatarNumero($item->quantidade) ?>">
                            </div>
                            <div class="col-4 mb-3">
                                <label class="form-label">Preço da Ata</label>
                                <input type="text" name="preco_ata" class="form-control" value="<?= formatarNumero($item->precoAta) ?>" required>
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

<div class="modal fade" id="modalNovoItem" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="index.php?action=adicionar_item_vantajosidade">
                <input type="hidden" name="processo_id" value="<?= $processo->id ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Novo item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Lote</label>
                            <input type="text" name="lote" class="form-control" placeholder="Ex: 10" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Item</label>
                            <input type="text" name="item" class="form-control" placeholder="Ex: 20" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <input type="text" name="descricao" class="form-control" placeholder="Descrição do item">
                    </div>
                    <div class="row">
                        <div class="col-4 mb-3">
                            <label class="form-label">Unidade</label>
                            <input type="text" name="unidade" class="form-control" value="UN">
                        </div>
                        <div class="col-4 mb-3">
                            <label class="form-label">Quantidade</label>
                            <input type="text" name="quantidade" class="form-control" value="1">
                        </div>
                        <div class="col-4 mb-3">
                            <label class="form-label">Preço da Ata</label>
                            <input type="text" name="preco_ata" class="form-control" placeholder="Preço unitário" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Adicionar item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>