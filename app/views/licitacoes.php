<?php
$titulo = 'Licitações - MT Par';
require __DIR__ . '/partials/header.php';

$statusLabel = [
    StatusLicitacao::AguardandoPublicacao->value => ['Aguardando publicação', 'bg-secondary'],
    StatusLicitacao::Publicada->value => ['Publicada', 'bg-primary'],
    StatusLicitacao::Homologada->value => ['Homologada', 'bg-info text-dark'],
    StatusLicitacao::EncaminhadaParaContratacao->value => ['Encaminhada p/ contratação', 'bg-success'],
];

// Contagem por status pra alimentar os chips de filtro.
$totais = [
    StatusLicitacao::AguardandoPublicacao->value => 0,
    StatusLicitacao::Publicada->value => 0,
    StatusLicitacao::Homologada->value => 0,
    StatusLicitacao::EncaminhadaParaContratacao->value => 0,
];
foreach ($licitacoes as $lic) {
    $totais[$lic->status()->value]++;
}
$totalEmAberto = $totais[StatusLicitacao::AguardandoPublicacao->value]
    + $totais[StatusLicitacao::Publicada->value];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="section-title">
        <i class="ti ti-gavel" aria-hidden="true" style="font-size: 20px; vertical-align: -3px;"></i>
        Licitações
    </span>
    <a href="index.php?action=dashboard" class="btn btn-sm btn-secondary">
        <i class="ti ti-arrow-left" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
        Dashboard
    </a>
</div>

<p class="text-muted small mb-3">
    <i class="ti ti-info-circle" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
    Este painel é alimentado automaticamente quando uma demanda é marcada como CONCLUÍDO.
</p>

<?php if (count($licitacoes) === 0): ?>
    <div class="card shadow-sm">
        <div class="empty-state">
            <i class="ti ti-gavel" aria-hidden="true"></i>
            <p class="mb-0">Nenhuma licitação registrada ainda.</p>
            <p class="mb-0 small">Conclua uma demanda para que ela apareça aqui.</p>
        </div>
    </div>
<?php else: ?>

    <div class="d-flex flex-wrap gap-2 mb-3">
        <div class="card shadow-sm resumo-chip is-active" data-status-filtro="EM_ABERTO">
            <div class="card-body d-flex align-items-center gap-2 py-2 px-3">
                <span class="resumo-dot" style="background: var(--brand-deep);"></span>
                <div>
                    <p class="mb-0 fw-bold resumo-num"><?= $totalEmAberto ?></p>
                    <p class="mb-0 text-muted resumo-lbl">Em aberto <span class="text-muted">· aguardando + publicadas</span></p>
                </div>
            </div>
        </div>
        <div class="card shadow-sm resumo-chip" data-status-filtro="<?= StatusLicitacao::AguardandoPublicacao->value ?>">
            <div class="card-body d-flex align-items-center gap-2 py-2 px-3">
                <span class="resumo-dot" style="background: #6c757d;"></span>
                <div>
                    <p class="mb-0 fw-bold resumo-num"><?= $totais[StatusLicitacao::AguardandoPublicacao->value] ?></p>
                    <p class="mb-0 text-muted resumo-lbl">Aguardando publicação</p>
                </div>
            </div>
        </div>
        <div class="card shadow-sm resumo-chip" data-status-filtro="<?= StatusLicitacao::Publicada->value ?>">
            <div class="card-body d-flex align-items-center gap-2 py-2 px-3">
                <span class="resumo-dot" style="background: var(--brand-blue-dark);"></span>
                <div>
                    <p class="mb-0 fw-bold resumo-num"><?= $totais[StatusLicitacao::Publicada->value] ?></p>
                    <p class="mb-0 text-muted resumo-lbl">Publicadas</p>
                </div>
            </div>
        </div>
        <div class="card shadow-sm resumo-chip" data-status-filtro="<?= StatusLicitacao::Homologada->value ?>">
            <div class="card-body d-flex align-items-center gap-2 py-2 px-3">
                <span class="resumo-dot" style="background: #0891b2;"></span>
                <div>
                    <p class="mb-0 fw-bold resumo-num"><?= $totais[StatusLicitacao::Homologada->value] ?></p>
                    <p class="mb-0 text-muted resumo-lbl">Homologadas</p>
                </div>
            </div>
        </div>
        <div class="card shadow-sm resumo-chip" data-status-filtro="<?= StatusLicitacao::EncaminhadaParaContratacao->value ?>">
            <div class="card-body d-flex align-items-center gap-2 py-2 px-3">
                <span class="resumo-dot" style="background: var(--brand-green-dark);"></span>
                <div>
                    <p class="mb-0 fw-bold resumo-num"><?= $totais[StatusLicitacao::EncaminhadaParaContratacao->value] ?></p>
                    <p class="mb-0 text-muted resumo-lbl">Encaminhadas p/ contratação</p>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-2">
        <div class="input-group input-group-sm" style="max-width: 420px;">
            <span class="input-group-text bg-white">
                <i class="ti ti-search text-muted" aria-hidden="true" style="font-size: 13px;"></i>
            </span>
            <input type="text" id="buscaLicitacoes" class="form-control"
                   placeholder="Buscar por nº do processo, edital, setor ou objeto...">
        </div>
    </div>
    <p class="text-muted small mb-2" id="contagemResultadoLic"></p>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0 small">
                    <thead class="table-dark">
                        <tr>
                            <th>Status</th>
                            <th>Processo</th>
                            <th>Setor / Responsável</th>
                            <th>Objeto</th>
                            <th>Datas</th>
                            <th>Valores</th>
                            <th>Dias</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($licitacoes as $licitacao): ?>
                            <?php
                            $economicidadeReais = $licitacao->calcularEconomicidadeReais();
                            $economicidadePercentual = $licitacao->calcularEconomicidadePercentual();
                            $diasNaLicitacao = $licitacao->calcularDiasNaLicitacao();
                            $servidorResponsavel = $licitacao->servidorResponsavelId !== null
                                ? ($mapaServidores[$licitacao->servidorResponsavelId] ?? null) : null;
                            [$statusTexto, $statusClasse] = $statusLabel[$licitacao->status()->value];
                            $statusValor = $licitacao->status()->value;
                            $buscaTexto = mb_strtolower(
                                $licitacao->numeroProcesso . ' ' . $licitacao->editalLicitacao . ' '
                                . $licitacao->setorDemandante . ' ' . $licitacao->objeto
                            );
                            // O filtro padrao "EM_ABERTO" e satisfeito por Aguardando + Publicada.
                            $emAberto = $statusValor === StatusLicitacao::AguardandoPublicacao->value
                                     || $statusValor === StatusLicitacao::Publicada->value;
                            ?>
                            <tr class="<?= $emAberto ? '' : 'd-none' ?>"
                                data-status="<?= htmlspecialchars($statusValor) ?>"
                                data-em-aberto="<?= $emAberto ? '1' : '0' ?>"
                                data-busca="<?= htmlspecialchars($buscaTexto) ?>">
                                <td><span class="badge <?= $statusClasse ?>"><?= $statusTexto ?></span></td>
                                <td>
                                    <?php if ($licitacao->linkSigadoc !== ''): ?>
                                        <a href="<?= htmlspecialchars($licitacao->linkSigadoc) ?>" target="_blank">
                                            <?= htmlspecialchars($licitacao->numeroProcesso) ?>
                                            <i class="ti ti-external-link" aria-hidden="true" style="font-size: 10px;"></i>
                                        </a>
                                    <?php else: ?>
                                        <?= htmlspecialchars($licitacao->numeroProcesso) ?>
                                    <?php endif; ?>
                                    <?php if ($licitacao->editalLicitacao !== ''): ?>
                                        <span class="text-muted d-block" style="font-size: 11px;">Edital: <?= htmlspecialchars($licitacao->editalLicitacao) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($licitacao->setorDemandante) ?>
                                    <span class="text-muted d-block" style="font-size: 11px;">
                                        <?= $servidorResponsavel !== null ? htmlspecialchars($servidorResponsavel->nome) : '—' ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars(mb_strimwidth($licitacao->objeto, 0, 40, '...')) ?></td>
                                <td style="font-size: 11px;">
                                    <span class="d-block">Receb: <?= date('d/m/Y', strtotime($licitacao->dataRecebimento)) ?></span>
                                    <span class="d-block text-muted">Sessão: <?= $licitacao->realizacaoSessaoPublica ? date('d/m/Y', strtotime($licitacao->realizacaoSessaoPublica)) : '—' ?></span>
                                    <span class="d-block text-muted">Contrato: <?= $licitacao->encaminhadoPactuacaoContrato ? date('d/m/Y', strtotime($licitacao->encaminhadoPactuacaoContrato)) : '—' ?></span>
                                </td>
                                <td style="font-size: 11px;">
                                    <span class="d-block">Estimado: <?= $licitacao->valorEstimado !== null ? formatarMoeda($licitacao->valorEstimado) : '—' ?></span>
                                    <span class="d-block text-muted">Adjudicado: <?= $licitacao->valorAdjudicado !== null ? formatarMoeda($licitacao->valorAdjudicado) : '—' ?></span>
                                    <?php if ($economicidadeReais !== null): ?>
                                        <span class="d-block <?= $economicidadeReais >= 0 ? 'text-success' : 'text-danger' ?>">
                                            Econ.: <?= formatarMoeda($economicidadeReais) ?> (<?= formatarNumero($economicidadePercentual, 1) ?>%)
                                        </span>
                                    <?php else: ?>
                                        <span class="d-block text-muted">Econ.: —</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $licitacao->estaEmAndamento() ? 'bg-warning text-dark' : 'bg-secondary' ?>">
                                        <?= $diasNaLicitacao ?>d
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="index.php?action=ver_demanda&id=<?= $licitacao->demandaId ?>&origem=licitacoes"
                                           class="btn btn-sm btn-outline-primary"
                                           title="Ver processo">
                                            <i class="ti ti-eye" aria-hidden="true" style="font-size: 13px;"></i>
                                            Ver processo
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr id="linhaSemResultadoLic" class="d-none">
                            <td colspan="8" class="text-center text-muted py-4">
                                Nenhuma licitação encontrada para esse filtro.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
        .resumo-chip { cursor: pointer; min-width: 155px; transition: border-color .15s ease; }
        .resumo-chip:hover { border-color: var(--brand-blue); }
        .resumo-chip.is-active { border-color: var(--brand-blue-dark); background: var(--brand-blue-soft); }
        .resumo-dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; display: inline-block; }
        .resumo-num { font-size: 18px; line-height: 1; }
        .resumo-lbl { font-size: 10px; }
    </style>

    <script>
    (function () {
        var filtroAtivo = 'EM_ABERTO';
        var chips = document.querySelectorAll('.resumo-chip');
        var linhas = document.querySelectorAll('tbody tr[data-status]');
        var linhaSemResultado = document.getElementById('linhaSemResultadoLic');
        var campoBusca = document.getElementById('buscaLicitacoes');
        var contagem = document.getElementById('contagemResultadoLic');

        function aplicarFiltros() {
            var query = campoBusca.value.trim().toLowerCase();
            var visiveis = 0;

            linhas.forEach(function (linha) {
                var visivel = filtroAtivo === 'EM_ABERTO'
                    ? linha.dataset.emAberto === '1'
                    : linha.dataset.status === filtroAtivo;

                if (visivel && query) {
                    visivel = linha.dataset.busca.indexOf(query) !== -1;
                }

                linha.classList.toggle('d-none', !visivel);
                if (visivel) visiveis++;
            });

            linhaSemResultado.classList.toggle('d-none', visiveis > 0);
            contagem.textContent = visiveis + ' licitação' + (visiveis === 1 ? '' : 'ões');
        }

        chips.forEach(function (chip) {
            chip.addEventListener('click', function () {
                filtroAtivo = chip.dataset.statusFiltro;
                chips.forEach(function (c) { c.classList.remove('is-active'); });
                chip.classList.add('is-active');
                aplicarFiltros();
            });
        });

        campoBusca.addEventListener('input', aplicarFiltros);
        aplicarFiltros();
    })();
    </script>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>