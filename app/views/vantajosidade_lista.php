<?php
$titulo = 'Comprovação de Vantajosidade - MT Par';
require __DIR__ . '/partials/header.php';

$statusLabel = [
    ProcessoVantajosidade::STATUS_EM_ANDAMENTO => ['Em andamento', 'bg-primary'],
    ProcessoVantajosidade::STATUS_FINALIZADO => ['Finalizado', 'bg-success'],
];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="fs-6 fw-semibold" style="color: var(--brand-blue-dark);">
        <i class="ti ti-scale" aria-hidden="true" style="font-size: 20px; vertical-align: -3px;"></i>
        Comprovação de Vantajosidade
    </span>
    <div>
        <a href="index.php?action=orcamentos" class="btn btn-sm btn-secondary">
            <i class="ti ti-arrow-left" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
            Orçamentos
        </a>
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalVinculoEscolhaVant">
            <i class="ti ti-plus" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
            Novo processo
        </button>
    </div>
</div>

<?php if (count($processos) === 0): ?>
    <div class="card shadow-sm">
        <div class="empty-state text-muted">
            <i class="ti ti-scale" aria-hidden="true"></i>
            <p class="mb-0">Nenhum processo de vantajosidade criado ainda.</p>
        </div>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($processos as $processo): ?>
            <?php
            $servidor = $processo->buscarServidor();
            [$label, $classeBadge] = $statusLabel[$processo->status] ?? ['Indefinido', 'bg-secondary'];
            ?>
            <div class="col-md-4">
                <a href="index.php?action=vantajosidade&id=<?= $processo->id ?>" class="text-decoration-none">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="card-title mb-0">
                                    <i class="ti ti-scale" aria-hidden="true" style="font-size: 16px; color: var(--brand-blue-dark); vertical-align: -2px;"></i>
                                    Ata <?= htmlspecialchars($processo->numeroAta) ?>
                                </h6>
                                <span class="badge <?= $classeBadge ?>"><?= $label ?></span>
                            </div>
                            <p class="card-text text-muted small mb-1"><?= htmlspecialchars($processo->orgaoGerenciador ?: '—') ?></p>
                            <?php if ($processo->demandaId !== null): ?>
                                <p class="card-text small mb-1 text-info">
                                    <i class="ti ti-link" aria-hidden="true" style="font-size: 12px; vertical-align: -1px;"></i>
                                    Vinculado a demanda
                                </p>
                            <?php endif; ?>
                            <p class="card-text small mb-0">
                                <i class="ti ti-user" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
                                <?= $servidor ? htmlspecialchars($servidor->nome) : '—' ?>
                            </p>
                            <p class="card-text small text-muted mb-0">
                                <i class="ti ti-list" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
                                <?= count($processo->buscarItens()) ?> item(ns)
                            </p>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- MODAL 1: Pergunta inicial sobre vinculo -->
<div class="modal fade" id="modalVinculoEscolhaVant" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Vincular processo a uma demanda</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Este processo de vantajosidade está relacionado a uma demanda já existente?</p>

                <button type="button" class="btn btn-outline-primary w-100 mb-2 text-start"
                        data-bs-toggle="modal" data-bs-target="#modalSelecionarDemandaVant" data-bs-dismiss="modal">
                    <i class="ti ti-circle-check" aria-hidden="true" style="font-size: 15px; vertical-align: -2px;"></i>
                    Sim, vincular a uma demanda existente
                </button>

                <button type="button" class="btn btn-outline-primary w-100 mb-3 text-start"
                        data-bs-toggle="modal" data-bs-target="#modalNovaDemandaRapidaVant" data-bs-dismiss="modal">
                    <i class="ti ti-plus" aria-hidden="true" style="font-size: 15px; vertical-align: -2px;"></i>
                    Não, criar a demanda agora
                </button>

                <div class="text-center">
                    <button type="button" class="btn btn-link btn-sm text-muted"
                            data-bs-toggle="modal" data-bs-target="#modalDadosVantajosidade" data-bs-dismiss="modal"
                            onclick="prepararModalVantajosidade('sem_vinculo')">
                        Continuar sem vínculo (não recomendado)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 2A: Selecionar demanda existente -->
<div class="modal fade" id="modalSelecionarDemandaVant" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Selecionar demanda</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">Demanda</label>
                <select id="selectDemandaExistenteVant" class="form-select">
                    <option value="">Selecione...</option>
                    <?php foreach ($demandasDisponiveis as $demanda): ?>
                        <option value="<?= $demanda->id ?>"
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
                <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#modalVinculoEscolhaVant" data-bs-dismiss="modal">
                    <i class="ti ti-arrow-left" aria-hidden="true" style="font-size: 13px;"></i>
                    Voltar
                </button>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalDadosVantajosidade" data-bs-dismiss="modal"
                        onclick="prepararModalVantajosidade('existente')">
                    Continuar
                    <i class="ti ti-arrow-right" aria-hidden="true" style="font-size: 13px;"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 2B: Criar nova demanda rapida -->
<div class="modal fade" id="modalNovaDemandaRapidaVant" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nova demanda</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Número do processo</label>
                    <input type="text" id="novaDemandaNumeroVant" class="form-control" placeholder="MTPAR-PRO-2026/00050">
                </div>
                <div class="mb-3">
                    <label class="form-label">Setor demandante</label>
                    <input type="text" id="novaDemandaSetorVant" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Data de recebimento</label>
                    <input type="date" id="novaDemandaDataVant" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Objeto</label>
                    <textarea id="novaDemandaObjetoVant" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#modalVinculoEscolhaVant" data-bs-dismiss="modal">
                    <i class="ti ti-arrow-left" aria-hidden="true" style="font-size: 13px;"></i>
                    Voltar
                </button>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalDadosVantajosidade" data-bs-dismiss="modal"
                        onclick="prepararModalVantajosidade('nova_demanda')">
                    Continuar
                    <i class="ti ti-arrow-right" aria-hidden="true" style="font-size: 13px;"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 3: Dados do processo de vantajosidade (sempre, etapa final) -->
<div class="modal fade" id="modalDadosVantajosidade" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" id="formDadosVantajosidade" action="index.php?action=criar_vantajosidade">
                <div class="modal-header">
                    <h5 class="modal-title">Novo processo de vantajosidade</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="avisoVinculoVant" class="alert alert-info small d-none">
                        <i class="ti ti-link" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
                        <span id="textoVinculoVant"></span>
                    </div>

                    <input type="hidden" name="demanda_id" id="inputDemandaIdVant" value="">
                    <input type="hidden" name="demanda_numero_processo" id="inputDemandaNumeroVant" value="">
                    <input type="hidden" name="demanda_setor_demandante" id="inputDemandaSetorVant" value="">
                    <input type="hidden" name="demanda_data_recebimento" id="inputDemandaDataVant" value="">
                    <input type="hidden" name="demanda_objeto" id="inputDemandaObjetoOcultoVant" value="">

                    <div class="mb-3">
                        <label class="form-label">Número da Ata</label>
                        <input type="text" name="numero_ata" class="form-control" placeholder="Ex: 009/2024/SEPLAG" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Órgão gerenciador</label>
                        <input type="text" name="orgao_gerenciador" id="inputOrgaoGerenciadorVant" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Objeto</label>
                        <textarea name="objeto" id="inputObjetoVant" class="form-control" rows="2"></textarea>
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
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Criar processo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function prepararModalVantajosidade(origem) {
    const form = document.getElementById('formDadosVantajosidade');
    const aviso = document.getElementById('avisoVinculoVant');
    const textoAviso = document.getElementById('textoVinculoVant');

    document.getElementById('inputDemandaIdVant').value = '';
    document.getElementById('inputDemandaNumeroVant').value = '';
    document.getElementById('inputDemandaSetorVant').value = '';
    document.getElementById('inputDemandaDataVant').value = '';
    document.getElementById('inputDemandaObjetoOcultoVant').value = '';
    aviso.classList.add('d-none');

    if (origem === 'sem_vinculo') {
        form.action = 'index.php?action=criar_vantajosidade';
        return;
    }

    if (origem === 'existente') {
        const select = document.getElementById('selectDemandaExistenteVant');
        const opcaoSelecionada = select.options[select.selectedIndex];

        if (!select.value) {
            return;
        }

        form.action = 'index.php?action=criar_vantajosidade';
        document.getElementById('inputDemandaIdVant').value = select.value;
        document.getElementById('inputOrgaoGerenciadorVant').value = opcaoSelecionada.dataset.setor || '';
        document.getElementById('inputObjetoVant').value = opcaoSelecionada.dataset.objeto || '';

        textoAviso.textContent = 'Vinculado à demanda: ' + opcaoSelecionada.text;
        aviso.classList.remove('d-none');
        return;
    }

    if (origem === 'nova_demanda') {
        const numero = document.getElementById('novaDemandaNumeroVant').value;
        const setor = document.getElementById('novaDemandaSetorVant').value;
        const data = document.getElementById('novaDemandaDataVant').value;
        const objeto = document.getElementById('novaDemandaObjetoVant').value;

        form.action = 'index.php?action=criar_vantajosidade_com_demanda_nova';
        document.getElementById('inputDemandaNumeroVant').value = numero;
        document.getElementById('inputDemandaSetorVant').value = setor;
        document.getElementById('inputDemandaDataVant').value = data;
        document.getElementById('inputDemandaObjetoOcultoVant').value = objeto;
        document.getElementById('inputOrgaoGerenciadorVant').value = setor;
        document.getElementById('inputObjetoVant').value = objeto;

        textoAviso.textContent = 'Será criada uma nova demanda: ' + numero;
        aviso.classList.remove('d-none');
        return;
    }
}
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>