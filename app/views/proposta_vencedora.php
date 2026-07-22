<?php
$titulo = 'Conferência de Proposta Vencedora - MT Par';
require __DIR__ . '/partials/header.php';
?>

<div class="d-flex flex-column mb-3">
    <a href="index.php?action=ver_demanda&id=<?= $licitacao->demandaId ?>" class="small text-muted text-decoration-none mb-1">
        <i class="ti ti-arrow-left" aria-hidden="true" style="font-size: 12px; vertical-align: -1px;"></i>
        Voltar para o Processo <?= htmlspecialchars($licitacao->numeroProcesso) ?>
    </a>
    <span class="section-title">
        <i class="ti ti-clipboard-check" aria-hidden="true" style="font-size: 20px; vertical-align: -3px;"></i>
        Conferência de Proposta Vencedora
    </span>
    <?php if ($cotacao !== null): ?>
        <span class="text-muted small">
            <i class="ti ti-info-circle" aria-hidden="true" style="font-size: 12px; vertical-align: -1px;"></i>
            Os valores de referência vêm direto do mapa comparativo da Cotação <?= htmlspecialchars($cotacao->numeroProcesso) ?> — nenhum item foi redigitado.
        </span>
    <?php endif; ?>
</div>

<?php if ($cotacao === null): ?>
    <div class="card shadow-sm">
        <div class="empty-state">
            <i class="ti ti-clipboard-x" aria-hidden="true"></i>
            <p class="mb-0">Este processo não tem uma pesquisa de preço (Cotação) vinculada.</p>
            <p class="mb-0 small">Sem o mapa comparativo não é possível conferir a proposta vencedora item a item.</p>
        </div>
    </div>
<?php else: ?>

<?php if (count($lotes) > 1): ?>
<div class="d-flex justify-content-end mb-2">
    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalSelecionarLotes">
        <i class="ti ti-filter" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
        Selecionar lotes para conferir agora
    </button>
</div>
<?php endif; ?>

<form method="post" action="index.php?action=salvar_proposta_vencedora" id="formProposta">
    <input type="hidden" name="licitacao_id" value="<?= $licitacao->id ?>">
    <input type="hidden" name="operacao" id="campoOperacao" value="salvar">
    <input type="hidden" name="ultimo_item_id" id="campoUltimoItem" value="">

    <!-- Resumo -->
    <div class="position-sticky mb-3" style="top: 10px; z-index: 5;">
        <div class="card shadow-sm" id="cardResumo" style="border-left: 4px solid #198754;">
            <div class="card-body d-flex flex-wrap align-items-center gap-3">
                <div class="d-flex align-items-center gap-2 flex-grow-1">
                    <span class="rounded-circle flex-shrink-0" id="verdictDot" style="width: 11px; height: 11px; background: #198754; box-shadow: 0 0 0 4px rgba(25,135,84,.16);"></span>
                    <div>
                        <p class="text-muted mb-0" style="font-size:10px; text-transform:uppercase; letter-spacing:.05em;">Resultado geral</p>
                        <p class="mb-0 small fw-semibold" id="verdictText">Aguardando propostas</p>
                    </div>
                </div>
                <div class="text-end">
                    <p class="text-muted mb-0" style="font-size:10px; text-transform:uppercase; letter-spacing:.05em;">Estimado (mapa)</p>
                    <p class="mb-0 small fw-semibold tabular-nums" id="totalEstimado">R$ 0,00</p>
                </div>
                <div class="text-end">
                    <p class="text-muted mb-0" style="font-size:10px; text-transform:uppercase; letter-spacing:.05em;">Proposta vencedora</p>
                    <p class="mb-0 small fw-semibold tabular-nums" id="totalProposto">R$ 0,00</p>
                </div>
                <div class="text-end">
                    <p class="text-muted mb-0" style="font-size:10px; text-transform:uppercase; letter-spacing:.05em;">Economicidade</p>
                    <p class="mb-0 small fw-semibold tabular-nums" id="totalEconomicidade">—</p>
                </div>
                <button type="submit" class="btn btn-sm btn-secondary" id="btnSalvar">
                    <i class="ti ti-device-floppy" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
                    Salvar
                </button>
                <button type="button" class="btn btn-sm" style="background: var(--brand-deep); color: #fff;" id="btnGerar">
                    <i class="ti ti-file-download" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
                    Gerar documento
                </button>
                <button type="button" class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#modalAdjudicacao">
                    <i class="ti ti-stamp" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
                    Gerar Adjudicação/Homologação
                </button>
            </div>
        </div>
    </div>

    <!-- Lotes -->
    <?php foreach ($lotes as $lote): ?>
        <?php
        $itens = $lote->buscarItens();
        $loteProposta = $lotesComEmpresa[$lote->id] ?? null;
        $empresaDoLote = $loteProposta !== null ? $loteProposta->buscarEmpresa() : null;
        ?>
        <div class="card shadow-sm mb-3" data-lote data-lote-numero="<?= htmlspecialchars($lote->numero) ?>">
            <div class="card-header bg-white d-flex align-items-center justify-content-between py-2">
                <span class="fw-semibold small">
                    <i class="ti ti-package" aria-hidden="true" style="font-size:15px; color: var(--brand-blue-dark); vertical-align: -2px;"></i>
                    Lote <?= htmlspecialchars($lote->numero) ?>
                </span>
                <span class="text-muted small"><?= count($itens) ?> ite<?= count($itens) === 1 ? 'm' : 'ns' ?></span>
            </div>

            <!-- Empresa vencedora deste lote -->
            <div class="card-body border-bottom empresa-lote-widget" data-lote-id="<?= $lote->id ?>">
                <input type="hidden" name="lote_empresa_vencedora_id[<?= $lote->id ?>]" class="campo-empresa-id" value="<?= $empresaDoLote !== null ? $empresaDoLote->id : '' ?>">

                <div class="empresa-search-wrap <?= $empresaDoLote !== null ? 'd-none' : '' ?>">
                    <label class="form-label small fw-semibold mb-1">Empresa vencedora deste lote</label>
                    <div class="position-relative">
                        <i class="ti ti-search text-muted position-absolute" aria-hidden="true" style="font-size: 14px; left: 12px; top: 10px;"></i>
                        <input type="text" class="form-control form-control-sm empresa-input" style="padding-left: 32px;"
                               placeholder="Buscar por nome, nome fantasia ou CNPJ..." autocomplete="off">
                    </div>
                    <div class="empresa-results list-group mt-2" style="display:none;"></div>
                    <div class="empresa-not-found border border-dashed rounded p-2 mt-2 d-none align-items-center justify-content-between gap-2 small">
                        <span>Nenhuma empresa encontrada para "<b class="empresa-query-echo"></b>".</span>
                        <button type="button" class="btn btn-sm btn-outline-primary btn-abrir-cadastro">+ Cadastrar nova empresa</button>
                    </div>
                    <div class="empresa-cadastro border rounded p-3 mt-2" style="display:none; background: var(--paper, #F5F7FA);">
                        <div class="row g-2">
                            <div class="col-md-5">
                                <label class="form-label small">Nome (razão social)</label>
                                <input type="text" class="form-control form-control-sm campo-nome">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Nome fantasia</label>
                                <input type="text" class="form-control form-control-sm campo-fantasia">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">CNPJ</label>
                                <input type="text" class="form-control form-control-sm campo-cnpj" inputmode="numeric" maxlength="18" placeholder="00.000.000/0000-00">
                            </div>
                        </div>
                        <div class="mt-2 d-flex gap-3 align-items-center">
                            <button type="button" class="btn btn-sm btn-primary btn-salvar-empresa">Cadastrar e usar neste lote</button>
                            <button type="button" class="btn btn-sm btn-link text-muted btn-cancelar-cadastro">Cancelar</button>
                        </div>
                    </div>
                </div>
                <div class="empresa-selected align-items-center gap-3 <?= $empresaDoLote !== null ? 'd-flex' : 'd-none' ?>">
                    <div class="rounded-3 bg-primary-subtle d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 34px; height: 34px;">
                        <i class="ti ti-building text-primary" aria-hidden="true" style="font-size: 16px;"></i>
                    </div>
                    <div class="flex-grow-1">
                        <p class="mb-0 fw-semibold small sel-nome"><?= $empresaDoLote !== null ? htmlspecialchars($empresaDoLote->nome) : '' ?></p>
                        <p class="mb-0 text-muted small sel-fantasia"><?= $empresaDoLote !== null ? htmlspecialchars($empresaDoLote->nomeFantasia) : '' ?></p>
                        <p class="mb-0 text-muted small sel-cnpj"><?= $empresaDoLote !== null ? 'CNPJ ' . htmlspecialchars($empresaDoLote->cnpj) : '' ?></p>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-trocar-empresa">Trocar empresa</button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 32%;">Item</th>
                            <th class="text-end">Qtd.</th>
                            <th class="text-end">Ref. unit.</th>
                            <th class="text-end" style="width: 130px;">Proposto unit.</th>
                            <th class="text-end">Total proposto</th>
                            <th>Situação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($itens as $item): ?>
                            <?php
                            $resultado = $item->analisar($cotacao->criterioConsolidacao);
                            $valorReferencia = $resultado['valor_referencia'] ?? 0;
                            $propostaExistente = $valoresPropostos[$item->id] ?? null;
                            ?>
                            <tr data-item data-qtd="<?= $item->quantidade ?>" data-ref="<?= $valorReferencia ?>" id="item-<?= $item->id ?>">
                                <td>
                                    <span class="text-muted fw-semibold"><?= $item->numero ?>.</span>
                                    <?= htmlspecialchars($item->descricao) ?>
                                    <span class="text-muted d-block" style="font-size: 11px;"><?= htmlspecialchars($item->unidade) ?></span>
                                </td>
                                <td class="text-end tabular-nums"><?= formatarNumero($item->quantidade) ?></td>
                                <td class="text-end tabular-nums"><?= formatarMoeda($valorReferencia) ?></td>
                                <td class="text-end">
                                    <input type="text" inputmode="decimal" class="form-control form-control-sm text-end input-proposto"
                                           name="valor_proposto[<?= $item->id ?>]" data-item-id="<?= $item->id ?>"
                                           value="<?= $propostaExistente !== null ? formatarNumero($propostaExistente->valorProposto) : '' ?>"
                                           placeholder="0,00">
                                </td>
                                <td class="text-end tabular-nums total">—</td>
                                <td class="status"><span class="badge bg-secondary">—</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="lote-subtotal fw-semibold">
                            <td colspan="2">Subtotal do lote</td>
                            <td class="text-end tabular-nums subtotal-ref">—</td>
                            <td></td>
                            <td class="text-end tabular-nums subtotal-proposto">—</td>
                            <td class="status"><span class="badge bg-secondary">—</span></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Observações -->
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <label class="form-label small fw-semibold" for="observacoes">Observações para o documento (opcional)</label>
            <textarea id="observacoes" name="observacoes" class="form-control form-control-sm" rows="3"
                      placeholder="Ex.: item sem proposta - empresa informou substituição por modelo equivalente, aguardando confirmação."><?= htmlspecialchars($licitacao->observacoesPropostaVencedora) ?></textarea>
        </div>
    </div>
</form>

<!-- Modal: selecionar lotes -->
<div class="modal fade" id="modalSelecionarLotes" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Quais lotes você quer conferir agora?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">Não precisa ser sequencial — marque só os lotes que você quer trabalhar agora. Os demais continuam guardados, só ficam ocultos por enquanto.</p>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($lotes as $lote): ?>
                        <div class="form-check form-check-inline border rounded px-2 py-1">
                            <input class="form-check-input checkbox-lote" type="checkbox" value="<?= $lote->id ?>" id="chkLote<?= $lote->id ?>" checked>
                            <label class="form-check-label small" for="chkLote<?= $lote->id ?>">Lote <?= htmlspecialchars($lote->numero) ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-link text-muted" id="btnMarcarTodosLotes">Marcar todos</button>
                <button type="button" class="btn btn-sm btn-primary" id="btnAplicarSelecaoLotes" data-bs-dismiss="modal">Aplicar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Adjudicação/Homologação -->
<div class="modal fade" id="modalAdjudicacao" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="get" action="index.php" id="formTermoAdjudicacao">
                <input type="hidden" name="action" value="gerar_termo_adjudicacao">
                <input type="hidden" name="id" value="<?= $licitacao->id ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Gerar Termo de Adjudicação e Homologação</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Data da adjudicação e homologação</label>
                        <input type="date" name="data" class="form-control form-control-sm" style="max-width: 220px;"
                               value="<?= $licitacao->dataAdjudicacaoHomologacao ?? date('Y-m-d') ?>">
                    </div>
                    <?php $temLoteComEmpresa = false; ?>
                    <?php foreach ($lotes as $lote): ?>
                        <?php $loteProposta = $lotesComEmpresa[$lote->id] ?? null; ?>
                        <?php if ($loteProposta !== null): ?>
                            <?php $temLoteComEmpresa = true; ?>
                            <div class="row g-2 align-items-center mb-2">
                                <div class="col-auto">
                                    <span class="badge bg-secondary">Lote <?= htmlspecialchars($lote->numero) ?></span>
                                </div>
                                <div class="col">
                                    <input type="text" name="categoria_lote[<?= $lote->id ?>]" class="form-control form-control-sm"
                                           placeholder="Categoria (opcional) — ex.: AMPLA CONCORRÊNCIA, EXCLUSIVO ME/EPP/MEI">
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <?php if (!$temLoteComEmpresa): ?>
                        <p class="text-muted small mb-0">
                            <i class="ti ti-alert-circle" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
                            Nenhum lote tem empresa vencedora definida ainda. Salve pelo menos um lote antes de gerar o termo.
                        </p>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary btn-sm" <?= !$temLoteComEmpresa ? 'disabled' : '' ?>>
                        <i class="ti ti-file-download" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
                        Gerar documento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php endif; ?>

<style>
.tabular-nums { font-variant-numeric: tabular-nums; }
tr.row-alert td.total { color: #dc3545; font-weight: 600; }
tr.lote-subtotal.alert td { background-color: #f8d7da; }
tr.lote-subtotal.ok td { background-color: #d1e7dd; }
.border-dashed { border-style: dashed !important; }
#cardResumo { transition: border-color .2s ease; }
</style>

<script>
(function () {
    function parseNum(str) {
        if (!str) return null;
        var n = parseFloat(String(str).replace(/\./g, '').replace(',', '.'));
        return isNaN(n) ? null : n;
    }
    function fmtMoeda(n) {
        return 'R$ ' + n.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function fmtPercent(n) {
        return n.toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + '%';
    }

    function recalcular() {
        var totalEstimadoGeral = 0;
        var totalPropostoGeral = 0;
        var algumAlerta = false;

        document.querySelectorAll('[data-lote]').forEach(function (lote) {
            if (lote.style.display === 'none') return;

            var subEstimado = 0, subProposto = 0, subTemPendente = false, subAlerta = false;

            lote.querySelectorAll('tr[data-item]').forEach(function (row) {
                var qtd = parseFloat(row.dataset.qtd);
                var ref = parseFloat(row.dataset.ref);
                var input = row.querySelector('.input-proposto');
                var propostoUnit = parseNum(input.value);
                var chip = row.querySelector('.status .badge');
                var totalCell = row.querySelector('td.total');

                subEstimado += qtd * ref;

                if (propostoUnit === null) {
                    chip.className = 'badge bg-secondary';
                    chip.innerHTML = '<i class="ti ti-clock" aria-hidden="true" style="font-size:11px; vertical-align:-1px;"></i> Aguardando';
                    totalCell.textContent = '—';
                    row.classList.remove('row-alert');
                    subTemPendente = true;
                    return;
                }

                var totalItem = qtd * propostoUnit;
                subProposto += totalItem;
                totalCell.textContent = fmtMoeda(totalItem);

                if (propostoUnit > ref) {
                    chip.className = 'badge bg-danger';
                    chip.innerHTML = '<i class="ti ti-alert-triangle" aria-hidden="true" style="font-size:11px; vertical-align:-1px;"></i> Acima da referência';
                    row.classList.add('row-alert');
                    subAlerta = true;
                } else {
                    chip.className = 'badge bg-success';
                    chip.innerHTML = '<i class="ti ti-check" aria-hidden="true" style="font-size:11px; vertical-align:-1px;"></i> Dentro do valor';
                    row.classList.remove('row-alert');
                }
            });

            var subtotalRow = lote.querySelector('.lote-subtotal');
            subtotalRow.querySelector('.subtotal-ref').textContent = fmtMoeda(subEstimado);
            var subtotalChip = subtotalRow.querySelector('.status .badge');

            if (subTemPendente && subProposto === 0) {
                subtotalRow.querySelector('.subtotal-proposto').textContent = '—';
                subtotalRow.className = 'lote-subtotal fw-semibold';
                subtotalChip.className = 'badge bg-secondary';
                subtotalChip.textContent = 'Aguardando propostas';
            } else {
                subtotalRow.querySelector('.subtotal-proposto').textContent = fmtMoeda(subProposto) + (subTemPendente ? ' *' : '');
                var subAcima = subProposto > subEstimado;
                subtotalRow.className = 'lote-subtotal fw-semibold ' + (subAcima ? 'alert' : 'ok');
                subtotalChip.className = 'badge ' + (subAcima ? 'bg-danger' : 'bg-success');
                subtotalChip.textContent = subAcima ? 'Lote acima do estimado' : 'Lote dentro do estimado';
                if (subAcima) algumAlerta = true;
            }

            totalEstimadoGeral += subEstimado;
            totalPropostoGeral += subProposto;
        });

        document.getElementById('totalEstimado').textContent = fmtMoeda(totalEstimadoGeral);
        document.getElementById('totalProposto').textContent = fmtMoeda(totalPropostoGeral);

        var economia = totalEstimadoGeral - totalPropostoGeral;
        var economiaPerc = totalEstimadoGeral > 0 ? (economia / totalEstimadoGeral) * 100 : 0;
        var ecoEl = document.getElementById('totalEconomicidade');
        ecoEl.textContent = fmtMoeda(Math.abs(economia)) + ' (' + fmtPercent(Math.abs(economiaPerc)) + ')';
        ecoEl.className = 'mb-0 small fw-semibold tabular-nums ' + (economia >= 0 ? 'text-success' : 'text-danger');

        var acimaGeral = totalPropostoGeral > totalEstimadoGeral;
        algumAlerta = algumAlerta || acimaGeral || document.querySelectorAll('.row-alert').length > 0;

        var cardResumo = document.getElementById('cardResumo');
        var verdictText = document.getElementById('verdictText');
        var verdictDot = document.getElementById('verdictDot');
        var corEstado = algumAlerta ? '#dc3545' : '#198754';
        cardResumo.style.borderLeftColor = corEstado;
        verdictDot.style.background = corEstado;
        verdictDot.style.boxShadow = '0 0 0 4px ' + (algumAlerta ? 'rgba(220,53,69,.16)' : 'rgba(25,135,84,.16)');
        verdictText.textContent = algumAlerta ? 'Existem itens acima do valor de referência' : 'Proposta dentro do estimado';
    }

    var campoUltimoItem = document.getElementById('campoUltimoItem');
    document.querySelectorAll('.input-proposto').forEach(function (input) {
        input.addEventListener('input', recalcular);
        input.addEventListener('focus', function () {
            campoUltimoItem.value = input.dataset.itemId;
        });
    });
    if (document.querySelectorAll('[data-lote]').length > 0) {
        recalcular();
    }

    /* ---------- selecionar lotes pra conferir agora (so filtro visual) ---------- */
    var btnAplicar = document.getElementById('btnAplicarSelecaoLotes');
    if (btnAplicar) {
        btnAplicar.addEventListener('click', function () {
            var marcados = Array.from(document.querySelectorAll('.checkbox-lote'))
                .filter(function (c) { return c.checked; })
                .map(function (c) { return c.value; });

            document.querySelectorAll('[data-lote]').forEach(function (lote) {
                var loteId = lote.querySelector('.campo-empresa-id') ? lote.querySelector('.campo-empresa-id').name.match(/\[(\d+)\]/)[1] : null;
                lote.style.display = marcados.indexOf(loteId) === -1 ? 'none' : '';
            });
        });
    }
    var btnMarcarTodos = document.getElementById('btnMarcarTodosLotes');
    if (btnMarcarTodos) {
        btnMarcarTodos.addEventListener('click', function () {
            document.querySelectorAll('.checkbox-lote').forEach(function (c) { c.checked = true; });
        });
    }

    /* ---------- empresa vencedora por lote: busca + cadastro ---------- */
    function maskCnpj(digits) {
        digits = digits.slice(0, 14);
        var out = digits;
        if (digits.length > 12) out = digits.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{0,2})/, '$1.$2.$3/$4-$5');
        else if (digits.length > 8) out = digits.replace(/^(\d{2})(\d{3})(\d{3})(\d{0,4})/, '$1.$2.$3/$4');
        else if (digits.length > 5) out = digits.replace(/^(\d{2})(\d{3})(\d{0,3})/, '$1.$2.$3');
        else if (digits.length > 2) out = digits.replace(/^(\d{2})(\d{0,3})/, '$1.$2');
        return out;
    }

    function inicializarWidgetEmpresa(root) {
        var hiddenInput = root.querySelector('.campo-empresa-id');
        var searchWrap = root.querySelector('.empresa-search-wrap');
        var selected = root.querySelector('.empresa-selected');
        var input = root.querySelector('.empresa-input');
        var results = root.querySelector('.empresa-results');
        var notFound = root.querySelector('.empresa-not-found');
        var cadastro = root.querySelector('.empresa-cadastro');
        var campoCnpj = root.querySelector('.campo-cnpj');
        var debounceTimer = null;

        function selecionar(empresa) {
            root.querySelector('.sel-nome').textContent = empresa.nome;
            root.querySelector('.sel-fantasia').textContent = empresa.nomeFantasia || '';
            root.querySelector('.sel-cnpj').textContent = 'CNPJ ' + maskCnpj(empresa.cnpj);
            hiddenInput.value = empresa.id;

            searchWrap.classList.add('d-none');
            selected.classList.remove('d-none');
            selected.classList.add('d-flex');
            input.value = '';
            results.style.display = 'none';
            notFound.classList.add('d-none');
            notFound.classList.remove('d-flex');
            cadastro.style.display = 'none';
        }

        function renderizarResultados(lista) {
            results.innerHTML = lista.map(function (e, i) {
                return '<button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2" data-idx="' + i + '">' +
                    '<span><span class="fw-semibold small d-block">' + e.nome + '</span>' +
                    (e.nomeFantasia ? '<span class="text-muted small d-block">' + e.nomeFantasia + '</span>' : '') + '</span>' +
                    '<span class="text-end"><span class="text-muted small d-block tabular-nums">' + maskCnpj(e.cnpj) + '</span>' +
                    '<span class="badge bg-primary-subtle text-primary" style="font-size:10px;">' + e.licitacoesHomologadas + ' licitações</span></span>' +
                    '</button>';
            }).join('');

            results.querySelectorAll('[data-idx]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    selecionar(lista[parseInt(btn.dataset.idx, 10)]);
                });
            });
        }

        input.addEventListener('input', function () {
            var query = input.value.trim();
            cadastro.style.display = 'none';
            clearTimeout(debounceTimer);

            if (query.length < 2) {
                results.style.display = 'none';
                notFound.classList.add('d-none');
                notFound.classList.remove('d-flex');
                return;
            }

            debounceTimer = setTimeout(function () {
                fetch('index.php?action=buscar_empresas&q=' + encodeURIComponent(query))
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        var achadas = data.empresas || [];
                        if (achadas.length > 0) {
                            renderizarResultados(achadas);
                            results.style.display = 'block';
                            notFound.classList.add('d-none');
                            notFound.classList.remove('d-flex');
                        } else {
                            results.style.display = 'none';
                            root.querySelector('.empresa-query-echo').textContent = query;
                            notFound.classList.remove('d-none');
                            notFound.classList.add('d-flex');
                        }
                    });
            }, 250);
        });

        root.querySelector('.btn-abrir-cadastro').addEventListener('click', function () {
            cadastro.style.display = 'block';
            notFound.classList.add('d-none');
            notFound.classList.remove('d-flex');
            root.querySelector('.campo-nome').value = input.value.trim();
            root.querySelector('.campo-nome').focus();
        });

        root.querySelector('.btn-cancelar-cadastro').addEventListener('click', function () {
            cadastro.style.display = 'none';
        });

        campoCnpj.addEventListener('input', function () {
            campoCnpj.value = maskCnpj(campoCnpj.value.replace(/\D/g, ''));
        });

        root.querySelector('.btn-salvar-empresa').addEventListener('click', function () {
            var nome = root.querySelector('.campo-nome').value.trim();
            var nomeFantasia = root.querySelector('.campo-fantasia').value.trim();
            var cnpj = campoCnpj.value;

            var form = new FormData();
            form.append('nome', nome);
            form.append('nome_fantasia', nomeFantasia);
            form.append('cnpj', cnpj);

            fetch('index.php?action=criar_empresa', { method: 'POST', body: form })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.erro) {
                        alert(data.erro);
                        return;
                    }
                    selecionar(data.empresa);
                });
        });

        root.querySelector('.btn-trocar-empresa').addEventListener('click', function () {
            selected.classList.remove('d-flex');
            selected.classList.add('d-none');
            searchWrap.classList.remove('d-none');
            input.focus();
        });
    }

    document.querySelectorAll('.empresa-lote-widget').forEach(inicializarWidgetEmpresa);

    /* ---------- botões salvar / gerar documento ---------- */
    var btnGerar = document.getElementById('btnGerar');
    if (btnGerar) {
        btnGerar.addEventListener('click', function () {
            document.getElementById('campoOperacao').value = 'gerar_documento';
            document.getElementById('formProposta').submit();
        });
    }
})();
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
