<?php
$titulo = 'Cotação ' . $cotacao->numeroProcesso . ' - MT Par';
require __DIR__ . '/partials/header.php';

$statusLabel = [
    StatusCotacao::EmAndamento->value => ['Em andamento', 'bg-primary'],
    StatusCotacao::Finalizada->value  => ['Finalizada', 'bg-success'],
];
[$labelStatus, $classeBadgeStatus] = $statusLabel[$cotacao->status->value] ?? ['Indefinido', 'bg-secondary'];
?>

<div class="print-header">
    <img src="public/img/logo.png" alt="MT Par">
    <div class="print-header-info">
        Processo <?= htmlspecialchars($cotacao->numeroProcesso) ?> — Etapas de análise de preços (70/30)<br>
        Impresso em <?= date('d/m/Y H:i') ?>
    </div>
</div>

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="m-0">
            <i class="ti ti-file-text" aria-hidden="true" style="font-size: 22px; vertical-align: -3px; color: var(--brand-blue-dark);"></i>
            Processo <?= htmlspecialchars($cotacao->numeroProcesso) ?>
            <span class="badge <?= $classeBadgeStatus ?>"><?= $labelStatus ?></span>
        </h4>
        <p class="text-muted mb-0"><?= htmlspecialchars($cotacao->orgaoSetor) ?></p>
        <?php if ($demandaVinculada !== null): ?>
            <p class="small mb-0">
                <i class="ti ti-link" aria-hidden="true" style="font-size: 13px; vertical-align: -1px; color: var(--brand-blue-dark);"></i>
                Vinculada à Demanda nº <?= htmlspecialchars($demandaVinculada->numeroProcesso) ?>
                <a href="index.php?action=ver_demanda&id=<?= $demandaVinculada->id ?>" class="text-decoration-none">
                    (ver processo <i class="ti ti-arrow-right" aria-hidden="true" style="font-size: 11px;"></i>)
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
        <a href="index.php?action=cotacoes" class="btn btn-sm btn-secondary">
            <i class="ti ti-arrow-left" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
            Voltar às cotações
        </a>
        <button type="button" class="btn btn-sm btn-outline-primary"
                data-bs-toggle="modal" data-bs-target="#modalEditarCotacao">
            <i class="ti ti-edit" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
            Editar
        </button>
        <button onclick="window.print()" class="btn btn-sm btn-primary">
            <i class="ti ti-printer" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
            Imprimir
        </button>
        <a href="index.php?action=mapa&id=<?= $cotacao->id ?>" class="btn btn-sm btn-info text-white">
            <i class="ti ti-table" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
            Ver mapa comparativo
        </a>
        <?php if ($cotacao->status === StatusCotacao::Finalizada): ?>
            <a href="index.php?action=gerar_relatorio_pesquisa&id=<?= $cotacao->id ?>" class="btn btn-sm btn-info text-white">
                <i class="ti ti-clipboard-data" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
                Relatório de pesquisa de preços
            </a>
            <a href="index.php?action=relatorio_formulario&id=<?= $cotacao->id ?>" class="btn btn-sm btn-warning text-dark">
                <i class="ti ti-file-report" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
                Gerar análise crítica
            </a>
        <?php endif; ?>
        <?php if ($cotacao->status === StatusCotacao::EmAndamento): ?>
            <a href="index.php?action=finalizar_cotacao&id=<?= $cotacao->id ?>"
               class="btn btn-sm btn-success"
               onclick="return confirm('Finalizar esta cotação?')">
                <i class="ti ti-check" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
                Finalizar cotação
            </a>
        <?php endif; ?>
        <a href="index.php?action=excluir_cotacao&id=<?= $cotacao->id ?>"
           class="btn btn-sm btn-outline-danger"
           onclick="return confirm('Excluir toda a cotação, incluindo lotes, itens e preços? Ela vai para a lixeira do Administrador, que pode restaurá-la depois se precisar.')">
            <i class="ti ti-trash" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
            Excluir
        </a>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="row small">
            <div class="col-md-3">
                <i class="ti ti-clipboard-list" aria-hidden="true" style="font-size: 18px; color: var(--brand-blue-dark); opacity: 0.7;"></i>
                <b>Procedimento:</b> <?= htmlspecialchars($cotacao->procedimento) ?>
            </div>
            <div class="col-md-3">
                <i class="ti ti-gavel" aria-hidden="true" style="font-size: 18px; color: var(--brand-blue-dark); opacity: 0.7;"></i>
                <b>Tipo de julgamento:</b> <?= htmlspecialchars($cotacao->tipoJulgamento) ?>
            </div>
            <div class="col-md-3">
                <i class="ti ti-user" aria-hidden="true" style="font-size: 18px; color: var(--brand-blue-dark); opacity: 0.7;"></i>
                <b>Servidor:</b> <?= htmlspecialchars($servidor->nome ?? '—') ?>
            </div>
            <div class="col-md-3">
                <i class="ti ti-calculator" aria-hidden="true" style="font-size: 18px; color: var(--brand-blue-dark); opacity: 0.7;"></i>
                <b>Critério:</b> <?= $cotacao->criterioConsolidacao ?>
            </div>
        </div>
        <?php if ($cotacao->objeto !== ''): ?>
            <div class="small mt-2"><b>Objeto:</b> <?= htmlspecialchars($cotacao->objeto) ?></div>
        <?php endif; ?>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="fs-6 fw-semibold" style="color: var(--brand-blue-dark);">
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
        <div class="empty-state text-muted">
            <i class="ti ti-package-off" aria-hidden="true"></i>
            <p class="mb-0">Nenhum lote criado ainda.</p>
        </div>
    </div>
<?php endif; ?>

<?php foreach ($lotes as $lote): ?>
    <div class="card shadow-sm mb-3" id="lote-<?= $lote->id ?>">
        <div class="card-header d-flex justify-content-between align-items-center text-white" style="background-color: var(--brand-deep);">
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
                $precos    = $item->buscarPrecos();
                ?>

                <div class="border rounded p-3 mb-3" id="item-<?= $item->id ?>" style="background-color: #fafbfc;">
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
                                                                    <option value="<?= htmlspecialchars($parametroOpcao->nome) ?>"
                                                                        <?= $preco->parametro === $parametroOpcao->nome ? 'selected' : '' ?>>
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
                        <i class="ti ti-target-arrow" aria-hidden="true" style="font-size: 14px; vertical-align: -1px; color: var(--brand-blue-dark);"></i>
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

<!-- ================================================================ -->
<!--  MODAL — Editar cotação                                            -->
<!-- ================================================================ -->
<div class="modal fade" id="modalEditarCotacao" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" action="index.php?action=editar_cotacao">
                <input type="hidden" name="cotacao_id" value="<?= $cotacao->id ?>">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="ti ti-edit" aria-hidden="true" style="font-size: 16px; vertical-align: -2px;"></i>
                        Editar cotação
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nº processo</label>
                            <input type="text" name="numero_processo" class="form-control"
                                   value="<?= htmlspecialchars($cotacao->numeroProcesso) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Órgão / Setor</label>
                            <input type="text" name="orgao_setor" class="form-control"
                                   value="<?= htmlspecialchars($cotacao->orgaoSetor) ?>">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Procedimento</label>
                            <input type="text" name="procedimento" class="form-control"
                                   value="<?= htmlspecialchars($cotacao->procedimento) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tipo de julgamento</label>
                            <input type="text" name="tipo_julgamento" class="form-control"
                                   value="<?= htmlspecialchars($cotacao->tipoJulgamento) ?>">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Servidor responsável</label>
                            <select name="servidor_id" class="form-select">
                                <?php foreach ($servidores as $srv): ?>
                                    <option value="<?= $srv->id ?>"
                                        <?= $cotacao->servidorId === $srv->id ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($srv->nome) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Critério de consolidação</label>
                            <select name="criterio_consolidacao" class="form-select">
                                <option value="MEDIANA" <?= $cotacao->criterioConsolidacao === 'MEDIANA' ? 'selected' : '' ?>>Mediana</option>
                                <option value="MEDIA"   <?= $cotacao->criterioConsolidacao === 'MEDIA'   ? 'selected' : '' ?>>Média</option>
                                <option value="MENOR_PRECO" <?= $cotacao->criterioConsolidacao === 'MENOR_PRECO' ? 'selected' : '' ?>>Menor preço</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Objeto</label>
                        <textarea name="objeto" class="form-control" rows="3"><?= htmlspecialchars($cotacao->objeto) ?></textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Processo vinculado (Demanda)</label>
                        <select name="demanda_id" class="form-select">
                            <option value="">— Nenhum —</option>
                            <?php foreach ($demandasParaVincular as $demandaOpcao): ?>
                                <option value="<?= $demandaOpcao->id ?>"
                                    <?= $cotacao->demandaId === $demandaOpcao->id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($demandaOpcao->numeroProcesso) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Só aparecem aqui demandas em andamento que ainda não têm nenhum vínculo (ou a que já está vinculada a esta cotação).</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>