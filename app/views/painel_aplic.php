<?php
$titulo = 'Aplic (TCE-MT) - MT Par';
require __DIR__ . '/partials/header.php';

$statusAplicLabel = [
    StatusAplic::NaoAplicavel->value => ['Ainda não aplicável', 'bg-secondary-subtle text-secondary'],
    StatusAplic::Pendente->value => ['Pendente de envio', 'bg-warning-subtle text-warning'],
    StatusAplic::Enviado->value => ['Enviado', 'bg-success-subtle text-success'],
];

$statusLicitacaoLabel = [
    StatusLicitacao::AguardandoPublicacao->value => ['Aguardando publicação', 'bg-secondary'],
    StatusLicitacao::Publicada->value => ['Publicada', 'bg-primary'],
    StatusLicitacao::Homologada->value => ['Homologada', 'bg-info text-dark'],
    StatusLicitacao::EncaminhadaParaContratacao->value => ['Encaminhada p/ contratação', 'bg-success'],
];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="section-title">
        <i class="ti ti-building-bank" aria-hidden="true" style="font-size: 20px; vertical-align: -3px;"></i>
        Aplic — TCE/MT
    </span>
    <a href="index.php?action=dashboard" class="btn btn-sm btn-secondary">
        <i class="ti ti-arrow-left" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
        Dashboard
    </a>
</div>

<div class="alert alert-info small">
    <i class="ti ti-info-circle" aria-hidden="true" style="font-size: 14px; vertical-align: -2px;"></i>
    A etapa de cada processo é preenchida automaticamente, do mesmo jeito que na tela de Licitações. Assim que um processo é <strong>Homologado</strong>, ele aparece aqui sozinho como pendente. O único clique manual é confirmar que o integrador já lançou aquele processo no Aplic de verdade.
</div>

<?php if (count($licitacoes) === 0): ?>
    <div class="card shadow-sm">
        <div class="empty-state">
            <i class="ti ti-building-bank" aria-hidden="true"></i>
            <p class="mb-0">Nenhum processo registrado ainda.</p>
        </div>
    </div>
<?php else: ?>

    <div class="d-flex flex-wrap gap-2 mb-3">
        <div class="card shadow-sm resumo-chip is-active" data-status-filtro="todos">
            <div class="card-body d-flex align-items-center gap-2 py-2 px-3">
                <span class="resumo-dot" style="background: var(--brand-deep);"></span>
                <div>
                    <p class="mb-0 fw-bold resumo-num"><?= count($licitacoes) ?></p>
                    <p class="mb-0 text-muted resumo-lbl">Todos</p>
                </div>
            </div>
        </div>
        <div class="card shadow-sm resumo-chip" data-status-filtro="<?= StatusAplic::Pendente->value ?>">
            <div class="card-body d-flex align-items-center gap-2 py-2 px-3">
                <span class="resumo-dot" style="background: #ffc107;"></span>
                <div>
                    <p class="mb-0 fw-bold resumo-num"><?= $totalPendentes ?></p>
                    <p class="mb-0 text-muted resumo-lbl">Pendentes de envio</p>
                </div>
            </div>
        </div>
        <div class="card shadow-sm resumo-chip" data-status-filtro="<?= StatusAplic::Enviado->value ?>">
            <div class="card-body d-flex align-items-center gap-2 py-2 px-3">
                <span class="resumo-dot" style="background: var(--brand-green-dark);"></span>
                <div>
                    <p class="mb-0 fw-bold resumo-num"><?= $totalEnviados ?></p>
                    <p class="mb-0 text-muted resumo-lbl">Enviados</p>
                </div>
            </div>
        </div>
        <div class="card shadow-sm resumo-chip" data-status-filtro="<?= StatusAplic::NaoAplicavel->value ?>">
            <div class="card-body d-flex align-items-center gap-2 py-2 px-3">
                <span class="resumo-dot" style="background: var(--ink-faint, #9498A0);"></span>
                <div>
                    <p class="mb-0 fw-bold resumo-num"><?= $totalNaoAplicavel ?></p>
                    <p class="mb-0 text-muted resumo-lbl">Ainda não aplicável</p>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-2">
        <div class="input-group input-group-sm" style="max-width: 420px;">
            <span class="input-group-text bg-white">
                <i class="ti ti-search text-muted" aria-hidden="true" style="font-size: 13px;"></i>
            </span>
            <input type="text" id="buscaAplic" class="form-control"
                   placeholder="Buscar por processo, setor ou objeto...">
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0 small">
                    <thead class="table-dark">
                        <tr>
                            <th>Situação no Aplic</th>
                            <th>Processo</th>
                            <th>Setor / Responsável</th>
                            <th>Objeto</th>
                            <th>Etapa do processo</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($licitacoes as $licitacao): ?>
                            <?php
                            $servidorResponsavel = $licitacao->servidorResponsavelId !== null
                                ? ($mapaServidores[$licitacao->servidorResponsavelId] ?? null) : null;
                            $statusAplic = $licitacao->statusAplic();
                            [$aplicTexto, $aplicClasse] = $statusAplicLabel[$statusAplic->value];
                            [$etapaTexto, $etapaClasse] = $statusLicitacaoLabel[$licitacao->status()->value];
                            $buscaTexto = mb_strtolower($licitacao->numeroProcesso . ' ' . $licitacao->setorDemandante . ' ' . $licitacao->objeto);
                            ?>
                            <tr data-status-aplic="<?= $statusAplic->value ?>" data-busca="<?= htmlspecialchars($buscaTexto) ?>">
                                <td>
                                    <span class="badge <?= $aplicClasse ?>"><?= $aplicTexto ?></span>
                                    <?php if ($licitacao->enviadoAplicEm !== null): ?>
                                        <span class="text-muted d-block" style="font-size: 11px;">
                                            em <?= date('d/m/Y', strtotime($licitacao->enviadoAplicEm)) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($licitacao->numeroProcesso) ?></td>
                                <td>
                                    <?= htmlspecialchars($licitacao->setorDemandante) ?>
                                    <span class="text-muted d-block" style="font-size: 11px;">
                                        <?= $servidorResponsavel !== null ? htmlspecialchars($servidorResponsavel->nome) : '—' ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars(mb_strimwidth($licitacao->objeto, 0, 40, '...')) ?></td>
                                <td><span class="badge <?= $etapaClasse ?>"><?= $etapaTexto ?></span></td>
                                <td>
                                    <?php if ($statusAplic === StatusAplic::Pendente): ?>
                                        <form method="post" action="index.php?action=marcar_enviado_aplic">
            <?= csrf_input() ?>
                                            <input type="hidden" name="licitacao_id" value="<?= $licitacao->id ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                                <i class="ti ti-send" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
                                                Marcar como enviado
                                            </button>
                                        </form>
                                    <?php elseif ($statusAplic === StatusAplic::Enviado): ?>
                                        <form method="post" action="index.php?action=desmarcar_enviado_aplic"
                                              onsubmit="return confirm('Desfazer a confirmação de envio deste processo ao Aplic?')">
            <?= csrf_input() ?>
                                            <input type="hidden" name="licitacao_id" value="<?= $licitacao->id ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                <i class="ti ti-arrow-back-up" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
                                                Desfazer
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
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
        var filtroAtivo = 'todos';
        var chips = document.querySelectorAll('.resumo-chip');
        var linhas = document.querySelectorAll('[data-status-aplic]');
        var campoBusca = document.getElementById('buscaAplic');

        function aplicarFiltros() {
            var query = campoBusca.value.trim().toLowerCase();

            linhas.forEach(function (linha) {
                var visivel = filtroAtivo === 'todos' || linha.dataset.statusAplic === filtroAtivo;

                if (visivel && query) {
                    visivel = linha.dataset.busca.indexOf(query) !== -1;
                }

                linha.classList.toggle('d-none', !visivel);
            });
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
    })();
    </script>

<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
