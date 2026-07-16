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
        <span class="text-muted small">Referência: mapa comparativo da Cotação <?= htmlspecialchars($cotacao->numeroProcesso) ?></span>
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

<form method="post" action="index.php?action=salvar_proposta_vencedora" id="formProposta">
    <input type="hidden" name="licitacao_id" value="<?= $licitacao->id ?>">
    <input type="hidden" name="empresa_vencedora_id" id="campoEmpresaVencedoraId" value="<?= $licitacao->empresaVencedoraId ?? '' ?>">
    <input type="hidden" name="operacao" id="campoOperacao" value="salvar">

    <!-- Empresa vencedora -->
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div id="empresaSearchWrap" style="<?= $empresaVencedora !== null ? 'display:none;' : '' ?>">
                <label class="form-label small fw-semibold" for="empresaInput">Empresa vencedora</label>
                <div class="position-relative">
                    <i class="ti ti-search text-muted position-absolute" aria-hidden="true" style="font-size: 14px; left: 12px; top: 10px;"></i>
                    <input type="text" id="empresaInput" class="form-control form-control-sm" style="padding-left: 32px;"
                           placeholder="Buscar por nome, nome fantasia ou CNPJ..." autocomplete="off">
                </div>
                <div id="empresaResults" class="list-group mt-2" style="display:none;"></div>
                <div id="empresaNotFound" class="border border-dashed rounded p-2 mt-2 d-none align-items-center justify-content-between gap-2 small">
                    <span>Nenhuma empresa encontrada para "<b id="empresaQueryEcho"></b>".</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnAbrirCadastro">+ Cadastrar nova empresa</button>
                </div>
                <div id="empresaCadastro" class="border rounded p-3 mt-2" style="display:none; background: var(--paper, #F5F7FA);">
                    <div class="row g-2">
                        <div class="col-md-5">
                            <label class="form-label small">Nome (razão social)</label>
                            <input type="text" id="campoNome" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Nome fantasia</label>
                            <input type="text" id="campoFantasia" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">CNPJ</label>
                            <input type="text" id="campoCnpj" class="form-control form-control-sm" inputmode="numeric" maxlength="18" placeholder="00.000.000/0000-00">
                        </div>
                    </div>
                    <div class="mt-2 d-flex gap-3 align-items-center">
                        <button type="button" class="btn btn-sm btn-primary" id="btnSalvarEmpresa">Cadastrar e usar nesta licitação</button>
                        <button type="button" class="btn btn-sm btn-link text-muted" id="btnCancelarCadastro">Cancelar</button>
                    </div>
                </div>
            </div>
            <div id="empresaSelected" class="d-flex align-items-center gap-3" style="<?= $empresaVencedora === null ? 'display:none;' : '' ?>">
                <div class="rounded-3 bg-primary-subtle d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px;">
                    <i class="ti ti-building text-primary" aria-hidden="true" style="font-size: 18px;"></i>
                </div>
                <div class="flex-grow-1">
                    <p class="mb-0 fw-semibold small" id="selNome"><?= $empresaVencedora !== null ? htmlspecialchars($empresaVencedora->nome) : '' ?></p>
                    <p class="mb-0 text-muted small" id="selFantasia"><?= $empresaVencedora !== null ? htmlspecialchars($empresaVencedora->nomeFantasia) : '' ?></p>
                    <p class="mb-0 text-muted small" id="selCnpj"><?= $empresaVencedora !== null ? 'CNPJ ' . htmlspecialchars($empresaVencedora->cnpj) : '' ?></p>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnTrocarEmpresa">Trocar empresa</button>
            </div>
        </div>
    </div>

    <!-- Resumo -->
    <div class="position-sticky mb-3" style="top: 0; z-index: 5;">
        <div class="card shadow-sm" id="cardResumo">
            <div class="card-body d-flex flex-wrap align-items-center gap-3">
                <div class="d-flex align-items-center gap-2 flex-grow-1">
                    <span class="rounded-circle flex-shrink-0" id="verdictDot" style="width: 10px; height: 10px; background: #198754;"></span>
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
            </div>
        </div>
    </div>

    <!-- Lotes -->
    <?php foreach ($lotes as $lote): ?>
        <?php $itens = $lote->buscarItens(); ?>
        <div class="card shadow-sm mb-3" data-lote>
            <div class="card-header bg-white d-flex align-items-center justify-content-between py-2">
                <span class="fw-semibold small">Lote <?= htmlspecialchars($lote->numero) ?></span>
                <span class="text-muted small"><?= count($itens) ?> ite<?= count($itens) === 1 ? 'm' : 'ns' ?></span>
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
                            <tr data-item data-qtd="<?= $item->quantidade ?>" data-ref="<?= $valorReferencia ?>">
                                <td>
                                    <span class="text-muted fw-semibold"><?= $item->numero ?>.</span>
                                    <?= htmlspecialchars($item->descricao) ?>
                                    <span class="text-muted d-block" style="font-size: 11px;"><?= htmlspecialchars($item->unidade) ?></span>
                                </td>
                                <td class="text-end tabular-nums"><?= formatarNumero($item->quantidade) ?></td>
                                <td class="text-end tabular-nums"><?= formatarMoeda($valorReferencia) ?></td>
                                <td class="text-end">
                                    <input type="text" inputmode="decimal" class="form-control form-control-sm text-end input-proposto"
                                           name="valor_proposto[<?= $item->id ?>]"
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

<?php endif; ?>

<style>
.tabular-nums { font-variant-numeric: tabular-nums; }
tr.row-alert td.total { color: #dc3545; }
tr.lote-subtotal.alert td { background-color: #f8d7da; }
tr.lote-subtotal.ok td.subtotal-proposto { color: #198754; }
#cardResumo.status-alert { border: 1px solid #dc3545; }
#cardResumo.status-ok { border: 1px solid #198754; }
.border-dashed { border-style: dashed !important; }
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
                    chip.textContent = 'Aguardando';
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
                    chip.textContent = 'Acima da referência';
                    row.classList.add('row-alert');
                    subAlerta = true;
                } else {
                    chip.className = 'badge bg-success';
                    chip.textContent = 'Dentro do valor';
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
        cardResumo.className = 'card shadow-sm ' + (algumAlerta ? 'status-alert' : 'status-ok');
        verdictDot.style.background = algumAlerta ? '#dc3545' : '#198754';
        verdictText.textContent = algumAlerta ? 'Existem itens acima do valor de referência' : 'Proposta dentro do estimado';
    }

    document.querySelectorAll('.input-proposto').forEach(function (input) {
        input.addEventListener('input', recalcular);
    });
    if (document.querySelectorAll('[data-lote]').length > 0) {
        recalcular();
    }

    /* ---------- empresa vencedora: busca + cadastro ---------- */
    function maskCnpj(digits) {
        digits = digits.slice(0, 14);
        var out = digits;
        if (digits.length > 12) out = digits.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{0,2})/, '$1.$2.$3/$4-$5');
        else if (digits.length > 8) out = digits.replace(/^(\d{2})(\d{3})(\d{3})(\d{0,4})/, '$1.$2.$3/$4');
        else if (digits.length > 5) out = digits.replace(/^(\d{2})(\d{3})(\d{0,3})/, '$1.$2.$3');
        else if (digits.length > 2) out = digits.replace(/^(\d{2})(\d{0,3})/, '$1.$2');
        return out;
    }

    var empresaInput = document.getElementById('empresaInput');
    if (!empresaInput) return;

    var empresaResults = document.getElementById('empresaResults');
    var empresaNotFound = document.getElementById('empresaNotFound');
    var empresaCadastro = document.getElementById('empresaCadastro');
    var empresaSearchWrap = document.getElementById('empresaSearchWrap');
    var empresaSelected = document.getElementById('empresaSelected');
    var campoCnpj = document.getElementById('campoCnpj');
    var campoEmpresaVencedoraId = document.getElementById('campoEmpresaVencedoraId');
    var debounceTimer = null;

    function selecionarEmpresa(empresa) {
        document.getElementById('selNome').textContent = empresa.nome;
        document.getElementById('selFantasia').textContent = empresa.nomeFantasia || '';
        document.getElementById('selCnpj').textContent = 'CNPJ ' + maskCnpj(empresa.cnpj);
        campoEmpresaVencedoraId.value = empresa.id;

        empresaSearchWrap.style.display = 'none';
        empresaSelected.style.display = 'flex';
        empresaInput.value = '';
        empresaResults.style.display = 'none';
        empresaNotFound.classList.add('d-none');
        empresaNotFound.classList.remove('d-flex');
        empresaCadastro.style.display = 'none';
    }

    function renderizarResultados(lista) {
        empresaResults.innerHTML = lista.map(function (e, i) {
            return '<button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2" data-idx="' + i + '">' +
                '<span><span class="fw-semibold small d-block">' + e.nome + '</span>' +
                (e.nomeFantasia ? '<span class="text-muted small d-block">' + e.nomeFantasia + '</span>' : '') + '</span>' +
                '<span class="text-end"><span class="text-muted small d-block tabular-nums">' + maskCnpj(e.cnpj) + '</span>' +
                '<span class="badge bg-primary-subtle text-primary" style="font-size:10px;">' + e.licitacoesHomologadas + ' licitações</span></span>' +
                '</button>';
        }).join('');

        empresaResults.querySelectorAll('[data-idx]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                selecionarEmpresa(lista[parseInt(btn.dataset.idx, 10)]);
            });
        });
    }

    empresaInput.addEventListener('input', function () {
        var query = empresaInput.value.trim();
        empresaCadastro.style.display = 'none';
        clearTimeout(debounceTimer);

        if (query.length < 2) {
            empresaResults.style.display = 'none';
            empresaNotFound.classList.add('d-none');
            empresaNotFound.classList.remove('d-flex');
            return;
        }

        debounceTimer = setTimeout(function () {
            fetch('index.php?action=buscar_empresas&q=' + encodeURIComponent(query))
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    var achadas = data.empresas || [];
                    if (achadas.length > 0) {
                        renderizarResultados(achadas);
                        empresaResults.style.display = 'block';
                        empresaNotFound.classList.add('d-none');
                        empresaNotFound.classList.remove('d-flex');
                    } else {
                        empresaResults.style.display = 'none';
                        document.getElementById('empresaQueryEcho').textContent = query;
                        empresaNotFound.classList.remove('d-none');
                        empresaNotFound.classList.add('d-flex');
                    }
                });
        }, 250);
    });

    document.getElementById('btnAbrirCadastro').addEventListener('click', function () {
        empresaCadastro.style.display = 'block';
        empresaNotFound.classList.add('d-none');
        empresaNotFound.classList.remove('d-flex');
        document.getElementById('campoNome').value = empresaInput.value.trim();
        document.getElementById('campoNome').focus();
    });

    document.getElementById('btnCancelarCadastro').addEventListener('click', function () {
        empresaCadastro.style.display = 'none';
    });

    campoCnpj.addEventListener('input', function () {
        campoCnpj.value = maskCnpj(campoCnpj.value.replace(/\D/g, ''));
    });

    document.getElementById('btnSalvarEmpresa').addEventListener('click', function () {
        var nome = document.getElementById('campoNome').value.trim();
        var nomeFantasia = document.getElementById('campoFantasia').value.trim();
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
                selecionarEmpresa(data.empresa);
            });
    });

    document.getElementById('btnTrocarEmpresa').addEventListener('click', function () {
        empresaSelected.style.display = 'none';
        empresaSearchWrap.style.display = 'block';
        empresaInput.focus();
    });

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
