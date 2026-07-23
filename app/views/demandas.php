<?php
$titulo = 'Demandas - MT Par';
require __DIR__ . '/partials/header.php';

$coresStatus = [
    'EM ANDAMENTO'                    => 'bg-primary-subtle text-primary',
    'ELABORAÇÃO DE TR'                => 'bg-warning-subtle text-warning',
    'ELABORAÇÃO DE PESQUISA DE PREÇO' => 'bg-warning-subtle text-warning',
    'AVISO DE LICITAÇÃO'              => 'bg-info-subtle text-info',
    'AVISO DE DISPENSA DE LICITAÇÃO'  => 'bg-info-subtle text-info',
    'EMISSÃO DE PED RESERVA'          => 'bg-info-subtle text-info',
    'FASE DE HABILITAÇÃO'             => 'bg-danger-subtle text-danger',
    'ENVIADO PARA CONDES'             => 'bg-secondary-subtle text-secondary',
    'ENVIADO PARA PARECER JURÍDICO'   => 'bg-secondary-subtle text-secondary',
    'ENVIADO PARA PGE'                => 'bg-secondary-subtle text-secondary',
    'SANEAMENTO DE PROCESSO'          => 'bg-secondary-subtle text-secondary',
    'PUBLICADO'                       => 'bg-success-subtle text-success',
    'CONCLUÍDO'                       => 'bg-success-subtle text-success',
    'CANCELADO'                       => 'bg-dark-subtle text-dark',
];

$totalConcluidas = 0;
$totalTrEdital = 0;
$totalParecerJuridico = 0;
foreach ($demandas as $demanda) {
    if ($demanda->status === 'CONCLUÍDO') {
        $totalConcluidas++;
    } elseif ($demanda->status === 'ELABORAÇÃO DE TR') {
        $totalTrEdital++;
    } elseif ($demanda->status === 'ENVIADO PARA PARECER JURÍDICO') {
        $totalParecerJuridico++;
    }
}
$totalAtivas = count($demandas) - $totalConcluidas;
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="section-title">
        <i class="ti ti-folder" aria-hidden="true" style="font-size: 20px; vertical-align: -3px;"></i>
        Demandas
    </span>
    <a href="index.php?action=dashboard" class="btn btn-sm btn-secondary">
        <i class="ti ti-arrow-left" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
        Dashboard
    </a>
</div>

<p class="text-muted small mb-3">
    <i class="ti ti-info-circle" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
    Painel de acompanhamento das demandas do setor. Para cadastrar um novo processo, use o botão
    "Cadastrar Processo" no Dashboard.
</p>

<?php if (count($demandas) === 0): ?>
    <div class="card shadow-sm">
        <div class="empty-state">
            <i class="ti ti-folder-off" aria-hidden="true"></i>
            <p class="mb-0">Nenhuma demanda registrada ainda.</p>
        </div>
    </div>
<?php else: ?>

    <div class="d-flex flex-wrap gap-2 mb-3">
        <div class="card shadow-sm resumo-chip is-active" data-status-filtro="">
            <div class="card-body d-flex align-items-center gap-2 py-2 px-3">
                <span class="resumo-dot" style="background: var(--brand-deep);"></span>
                <div>
                    <p class="mb-0 fw-bold resumo-num"><?= $totalAtivas ?></p>
                    <p class="mb-0 text-muted resumo-lbl">Todas as demandas <span class="text-muted">· sem concluídas</span></p>
                </div>
            </div>
        </div>
        <div class="card shadow-sm resumo-chip" data-status-filtro="CONCLUÍDO">
            <div class="card-body d-flex align-items-center gap-2 py-2 px-3">
                <span class="resumo-dot" style="background: var(--brand-green-dark);"></span>
                <div>
                    <p class="mb-0 fw-bold resumo-num"><?= $totalConcluidas ?></p>
                    <p class="mb-0 text-muted resumo-lbl">Concluídas</p>
                </div>
            </div>
        </div>
        <div class="card shadow-sm resumo-chip" data-status-filtro="ELABORAÇÃO DE TR">
            <div class="card-body d-flex align-items-center gap-2 py-2 px-3">
                <span class="resumo-dot" style="background: #997404;"></span>
                <div>
                    <p class="mb-0 fw-bold resumo-num"><?= $totalTrEdital ?></p>
                    <p class="mb-0 text-muted resumo-lbl">Elaboração de TR/Edital</p>
                </div>
            </div>
        </div>
        <div class="card shadow-sm resumo-chip" data-status-filtro="ENVIADO PARA PARECER JURÍDICO">
            <div class="card-body d-flex align-items-center gap-2 py-2 px-3">
                <span class="resumo-dot" style="background: #6c757d;"></span>
                <div>
                    <p class="mb-0 fw-bold resumo-num"><?= $totalParecerJuridico ?></p>
                    <p class="mb-0 text-muted resumo-lbl">Emissão de Parecer Jurídico</p>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-2">
        <div class="input-group input-group-sm" style="max-width: 420px;">
            <span class="input-group-text bg-white">
                <i class="ti ti-search text-muted" aria-hidden="true" style="font-size: 13px;"></i>
            </span>
            <input type="text" id="buscaDemandas" class="form-control"
                   placeholder="Buscar por nº do processo, setor ou objeto...">
        </div>
    </div>
    <p class="text-muted small mb-2" id="contagemResultado"></p>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0 small">
                    <thead class="table-dark">
                        <tr>
                            <th>Status</th>
                            <th>Nº Processo</th>
                            <th>Setor</th>
                            <th>Recebimento</th>
                            <th>Objeto</th>
                            <th>Responsável</th>
                            <th>Vínculo</th>
                            <th>Dias em aberto</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($demandas as $demanda): ?>
                            <?php
                            $corStatus = $coresStatus[$demanda->status] ?? 'bg-secondary-subtle text-secondary';
                            $servidorResp = $demanda->buscarServidorResponsavel();
                            $cotacaoVinc = $demanda->buscarCotacaoVinculada();
                            $vantajVinc  = $demanda->buscarVantajosidadeVinculada();
                            $temVinculo  = $cotacaoVinc !== null || $vantajVinc !== null;
                            $dias = $demanda->calcularDiasEmAberto();
                            $corUrgencia = $dias >= 60 ? 'bg-danger-subtle text-danger'
                                : ($dias >= 25 ? 'bg-warning-subtle text-warning' : 'bg-secondary-subtle text-secondary');
                            $buscaTexto = mb_strtolower($demanda->numeroProcesso . ' ' . $demanda->setorDemandante . ' ' . $demanda->objeto);
                            $escondidaPorPadrao = $demanda->status === 'CONCLUÍDO';
                            ?>
                            <tr class="<?= $escondidaPorPadrao ? 'd-none' : '' ?>"
                                data-status="<?= htmlspecialchars($demanda->status) ?>"
                                data-busca="<?= htmlspecialchars($buscaTexto) ?>">
                                <td>
                                    <span class="badge <?= $corStatus ?>" style="font-size:10px;">
                                        <?= htmlspecialchars($demanda->status) ?>
                                    </span>
                                </td>
                                <td class="fw-semibold">
                                    <?php if ($demanda->linkSigadoc !== ''): ?>
                                        <a href="<?= htmlspecialchars($demanda->linkSigadoc) ?>" target="_blank">
                                            <?= htmlspecialchars($demanda->numeroProcesso) ?>
                                            <i class="ti ti-external-link" aria-hidden="true" style="font-size:10px;"></i>
                                        </a>
                                    <?php else: ?>
                                        <?= htmlspecialchars($demanda->numeroProcesso) ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($demanda->setorDemandante) ?></td>
                                <td><?= $demanda->dataRecebimento ? date('d/m/Y', strtotime($demanda->dataRecebimento)) : '—' ?></td>
                                <td><?= htmlspecialchars(mb_strimwidth($demanda->objeto, 0, 50, '...')) ?></td>
                                <td><?= $servidorResp ? htmlspecialchars($servidorResp->nome) : '<span class="text-muted">—</span>' ?></td>
                                <td class="text-center">
                                    <?php if ($temVinculo): ?>
                                        <i class="ti ti-link text-success" aria-hidden="true"
                                           style="font-size:14px;" title="Possui vínculo"></i>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $corUrgencia ?>" style="font-size:10px;">
                                        <?= $dias ?> dia<?= $dias === 1 ? '' : 's' ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="index.php?action=ver_demanda&id=<?= $demanda->id ?>"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="ti ti-eye" aria-hidden="true" style="font-size:13px; vertical-align:-1px;"></i>
                                        Ver Processo
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr id="linhaSemResultado" class="d-none">
                            <td colspan="9" class="text-center text-muted py-4">
                                Nenhuma demanda encontrada para esse filtro.
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
        var filtroAtivo = '';
        var chips = document.querySelectorAll('.resumo-chip');
        var linhas = document.querySelectorAll('tbody tr[data-status]');
        var linhaSemResultado = document.getElementById('linhaSemResultado');
        var campoBusca = document.getElementById('buscaDemandas');
        var contagem = document.getElementById('contagemResultado');

        function aplicarFiltros() {
            var query = campoBusca.value.trim().toLowerCase();
            var visiveis = 0;

            linhas.forEach(function (linha) {
                var visivel = filtroAtivo === ''
                    ? linha.dataset.status !== 'CONCLUÍDO'
                    : linha.dataset.status === filtroAtivo;

                if (visivel && query) {
                    visivel = linha.dataset.busca.indexOf(query) !== -1;
                }

                linha.classList.toggle('d-none', !visivel);
                if (visivel) visiveis++;
            });

            linhaSemResultado.classList.toggle('d-none', visiveis > 0);

            var sufixo = filtroAtivo === '' && !query ? ' (concluídas ocultas)' : '';
            contagem.textContent = visiveis + ' demanda' + (visiveis === 1 ? '' : 's') + sufixo;
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
