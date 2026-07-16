<?php
$titulo = 'Cotações - MT Par';
require __DIR__ . '/partials/header.php';

$statusLabel = [
    Cotacao::STATUS_EM_ANDAMENTO => ['Em andamento', 'bg-primary'],
    Cotacao::STATUS_FINALIZADA => ['Finalizada', 'bg-success'],
];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="fs-6 fw-semibold" style="color: #1F3864;">
        <i class="ti ti-clipboard-list" aria-hidden="true" style="font-size: 20px; vertical-align: -3px;"></i>
        Cotações
    </span>
    <div>
        <a href="index.php?action=orcamentos" class="btn btn-sm btn-secondary">
            <i class="ti ti-arrow-left" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
            Orçamentos
        </a>
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalVinculoEscolha">
            <i class="ti ti-plus" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
            Nova cotação
        </button>
    </div>
</div>

<?php if (count($cotacoes) === 0): ?>
    <div class="card shadow-sm">
        <div class="empty-state text-muted">
            <i class="ti ti-clipboard-off" aria-hidden="true"></i>
            <p class="mb-0">Nenhuma cotação criada ainda.</p>
        </div>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($cotacoes as $cotacao): ?>
            <?php
            $servidor = $cotacao->buscarServidor();
            [$label, $classeBadge] = $statusLabel[$cotacao->status] ?? ['Indefinido', 'bg-secondary'];
            ?>
            <div class="col-md-4">
                <a href="index.php?action=cotacao&id=<?= $cotacao->id ?>" class="text-decoration-none">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="card-title mb-0">
                                    <i class="ti ti-file-text" aria-hidden="true" style="font-size: 16px; color: #1F3864; vertical-align: -2px;"></i>
                                    Processo <?= htmlspecialchars($cotacao->numeroProcesso) ?>
                                </h6>
                                <span class="badge <?= $classeBadge ?>"><?= $label ?></span>
                            </div>
                            <p class="card-text text-muted small mb-1"><?= htmlspecialchars($cotacao->orgaoSetor ?: '—') ?></p>
                            <?php if ($cotacao->demandaId !== null): ?>
                                <p class="card-text small mb-1 text-info">
                                    <i class="ti ti-link" aria-hidden="true" style="font-size: 12px; vertical-align: -1px;"></i>
                                    Vinculada a demanda
                                </p>
                            <?php endif; ?>
                            <p class="card-text small mb-0">
                                <i class="ti ti-user" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
                                <?= $servidor ? htmlspecialchars($servidor->nome) : '—' ?>
                            </p>
                            <p class="card-text small text-muted mb-0">
                                <i class="ti ti-box" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
                                <?= count($cotacao->buscarLotes()) ?> lote(s)
                            </p>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- MODAL 1: Pergunta inicial sobre vinculo -->
<div class="modal fade" id="modalVinculoEscolha" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Vincular cotação a um processo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Esta cotação está relacionada a um processo (Demanda) já existente?</p>

                <button type="button" class="btn btn-outline-primary w-100 mb-2 text-start"
                        data-bs-toggle="modal" data-bs-target="#modalSelecionarDemanda" data-bs-dismiss="modal">
                    <i class="ti ti-circle-check" aria-hidden="true" style="font-size: 15px; vertical-align: -2px;"></i>
                    Sim, vincular a uma demanda existente
                </button>

                <button type="button" class="btn btn-outline-primary w-100 mb-3 text-start"
                        data-bs-toggle="modal" data-bs-target="#modalNovaDemandaRapida" data-bs-dismiss="modal">
                    <i class="ti ti-plus" aria-hidden="true" style="font-size: 15px; vertical-align: -2px;"></i>
                    Não, criar o processo agora
                </button>

                <div class="text-center">
                    <button type="button" class="btn btn-link btn-sm text-muted"
                            data-bs-toggle="modal" data-bs-target="#modalDadosCotacao" data-bs-dismiss="modal"
                            onclick="prepararModalCotacao('sem_vinculo')">
                        Continuar sem vínculo (não recomendado)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 2A: Selecionar demanda existente -->
<div class="modal fade" id="modalSelecionarDemanda" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Selecionar processo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">Demanda</label>
                <select id="selectDemandaExistente" class="form-select">
                    <option value="">Selecione...</option>
                    <?php foreach ($demandasDisponiveis as $demanda): ?>
                        <option value="<?= $demanda->id ?>"
                                data-numero="<?= htmlspecialchars($demanda->numeroProcesso) ?>"
                                data-setor="<?= htmlspecialchars($demanda->setorDemandante) ?>"
                                data-objeto="<?= htmlspecialchars($demanda->objeto) ?>">
                            <?= htmlspecialchars($demanda->numeroProcesso) ?> — <?= htmlspecialchars(mb_strimwidth($demanda->objeto, 0, 40, '...')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (count($demandasDisponiveis) === 0): ?>
                    <p class="text-muted small mt-2 mb-0">Nenhuma demanda disponível para vincular.</p>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#modalVinculoEscolha" data-bs-dismiss="modal">
                    <i class="ti ti-arrow-left" aria-hidden="true" style="font-size: 13px;"></i>
                    Voltar
                </button>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalDadosCotacao" data-bs-dismiss="modal"
                        onclick="prepararModalCotacao('existente')">
                    Continuar
                    <i class="ti ti-arrow-right" aria-hidden="true" style="font-size: 13px;"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 2B: Criar nova demanda rapida -->
<div class="modal fade" id="modalNovaDemandaRapida" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Novo processo (Demanda)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Número do processo</label>
                    <input type="text" id="novaDemandaNumero" class="form-control" placeholder="MTPAR-PRO-2026/00050">
                </div>
                <div class="mb-3">
                    <label class="form-label">Setor demandante</label>
                    <input type="text" id="novaDemandaSetor" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Data de recebimento</label>
                    <input type="date" id="novaDemandaData" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Objeto</label>
                    <textarea id="novaDemandaObjeto" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#modalVinculoEscolha" data-bs-dismiss="modal">
                    <i class="ti ti-arrow-left" aria-hidden="true" style="font-size: 13px;"></i>
                    Voltar
                </button>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalDadosCotacao" data-bs-dismiss="modal"
                        onclick="prepararModalCotacao('nova_demanda')">
                    Continuar
                    <i class="ti ti-arrow-right" aria-hidden="true" style="font-size: 13px;"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 3: Dados da cotacao (sempre, etapa final) -->
<div class="modal fade" id="modalDadosCotacao" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" id="formDadosCotacao" action="index.php?action=criar_cotacao">
                <div class="modal-header">
                    <h5 class="modal-title">Nova cotação</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="avisoVinculoCotacao" class="alert alert-info small d-none">
                        <i class="ti ti-link" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
                        <span id="textoVinculoCotacao"></span>
                    </div>

                    <input type="hidden" name="demanda_id" id="inputDemandaId" value="">
                    <input type="hidden" name="demanda_numero_processo" id="inputDemandaNumero" value="">
                    <input type="hidden" name="demanda_setor_demandante" id="inputDemandaSetor" value="">
                    <input type="hidden" name="demanda_data_recebimento" id="inputDemandaData" value="">
                    <input type="hidden" name="demanda_objeto" id="inputDemandaObjetoOculto" value="">

                    <div class="mb-3">
                        <label class="form-label">Número do processo</label>
                        <input type="text" name="numero_processo" id="inputNumeroProcesso" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Órgão / Setor</label>
                        <input type="text" name="orgao_setor" id="inputOrgaoSetor" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Procedimento</label>
                        <input type="text" name="procedimento" class="form-control" placeholder="Ex: Pregão Eletrônico">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo de julgamento</label>
                        <input type="text" name="tipo_julgamento" class="form-control" placeholder="Ex: Menor Preço">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Objeto</label>
                        <textarea name="objeto" id="inputObjeto" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Servidor responsável</label>
                        <select name="servidor_id" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($servidores as $servidor): ?>
                                <option value="<?= $servidor->id ?>"><?= htmlspecialchars($servidor->nome) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Critério de consolidação</label>
                        <select name="criterio_consolidacao" class="form-select">
                            <option value="MEDIANA">Mediana</option>
                            <option value="MEDIA">Média</option>
                            <option value="MENOR_PRECO">Menor preço</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-check" aria-hidden="true" style="font-size: 14px; vertical-align: -1px;"></i>
                        Criar cotação
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function prepararModalCotacao(origem) {
    const form = document.getElementById('formDadosCotacao');
    const aviso = document.getElementById('avisoVinculoCotacao');
    const textoAviso = document.getElementById('textoVinculoCotacao');

    document.getElementById('inputDemandaId').value = '';
    document.getElementById('inputDemandaNumero').value = '';
    document.getElementById('inputDemandaSetor').value = '';
    document.getElementById('inputDemandaData').value = '';
    document.getElementById('inputDemandaObjetoOculto').value = '';
    aviso.classList.add('d-none');

    if (origem === 'sem_vinculo') {
        form.action = 'index.php?action=criar_cotacao';
        return;
    }

    if (origem === 'existente') {
        const select = document.getElementById('selectDemandaExistente');
        const opcaoSelecionada = select.options[select.selectedIndex];

        if (!select.value) {
            return;
        }

        form.action = 'index.php?action=criar_cotacao';
        document.getElementById('inputDemandaId').value = select.value;
        document.getElementById('inputNumeroProcesso').value = opcaoSelecionada.dataset.numero || '';
        document.getElementById('inputOrgaoSetor').value = opcaoSelecionada.dataset.setor || '';
        document.getElementById('inputObjeto').value = opcaoSelecionada.dataset.objeto || '';

        textoAviso.textContent = 'Vinculada à demanda: ' + (opcaoSelecionada.dataset.numero || '');
        aviso.classList.remove('d-none');
        return;
    }

    if (origem === 'nova_demanda') {
        const numero = document.getElementById('novaDemandaNumero').value;
        const setor = document.getElementById('novaDemandaSetor').value;
        const data = document.getElementById('novaDemandaData').value;
        const objeto = document.getElementById('novaDemandaObjeto').value;

        form.action = 'index.php?action=criar_cotacao_com_demanda_nova';
        document.getElementById('inputDemandaNumero').value = numero;
        document.getElementById('inputDemandaSetor').value = setor;
        document.getElementById('inputDemandaData').value = data;
        document.getElementById('inputDemandaObjetoOculto').value = objeto;
        document.getElementById('inputNumeroProcesso').value = numero;
        document.getElementById('inputOrgaoSetor').value = setor;
        document.getElementById('inputObjeto').value = objeto;

        textoAviso.textContent = 'Será criado um novo processo: ' + numero;
        aviso.classList.remove('d-none');
        return;
    }
}
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>