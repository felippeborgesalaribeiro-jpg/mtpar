<?php
$titulo = 'Dashboard - MT Par';
require __DIR__ . '/partials/header.php';

$coresAvatar = ['bg-success-subtle text-success', 'bg-primary-subtle text-primary', 'bg-warning-subtle text-warning', 'bg-danger-subtle text-danger', 'bg-info-subtle text-info'];

$coresStatus = [
    'EM ANDAMENTO' => 'text-primary',
    'ELABORAÇÃO DE TR' => 'text-warning',
    'ELABORAÇÃO DE PESQUISA DE PREÇO' => 'text-warning',
    'AVISO DE LICITAÇÃO' => 'text-info',
    'AVISO DE DISPENSA DE LICITAÇÃO' => 'text-info',
    'EMISSÃO DE PED RESERVA' => 'text-info',
    'FASE DE HABILITAÇÃO' => 'text-danger',
    'ENVIADO PARA CONDES' => 'text-secondary',
    'ENVIADO PARA PARECER JURÍDICO' => 'text-secondary',
    'ENVIADO PARA PGE' => 'text-secondary',
    'Cotação em andamento' => 'text-primary',
];
?>

<div class="row g-3 mb-4">
    <div class="col">
        <div class="card shadow-sm h-100" style="border-left: 4px solid #1F3864;">
            <div class="card-body py-3">
                <p class="text-muted small mb-1">Processos em andamento</p>
                <p class="fs-4 fw-bold m-0"><?= $processosEmAndamento ?></p>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card shadow-sm h-100" style="border-left: 4px solid #1F3864;">
            <div class="card-body py-3">
                <p class="text-muted small mb-1">Cotações em andamento</p>
                <p class="fs-4 fw-bold m-0"><?= $cotacoesEmAndamento ?></p>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card shadow-sm h-100" style="border-left: 4px solid #1F3864;">
            <div class="card-body py-3">
                <p class="text-muted small mb-1">Licitações publicadas</p>
                <p class="fs-4 fw-bold m-0"><?= $licitacoesPublicadas ?></p>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card shadow-sm h-100" style="border-left: 4px solid #1F3864;">
            <div class="card-body py-3">
                <p class="text-muted small mb-1">Licitações homologadas</p>
                <p class="fs-4 fw-bold m-0"><?= $licitacoesHomologadas ?></p>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card shadow-sm h-100" style="background-color: #1F3864; border-left: 4px solid #FFC107;">
            <div class="card-body py-3">
                <p class="small mb-1" style="color: rgba(255,255,255,0.75);">Valor homologado</p>
                <p class="fs-5 fw-bold m-0" style="color: #FFFFFF;"><?= formatarMoeda($valorHomologadas) ?></p>
            </div>
        </div>
    </div>
</div>

<p class="fs-6 fw-semibold mb-3" style="color: #1F3864;">
    <i class="ti ti-apps" aria-hidden="true" style="font-size: 18px; vertical-align: -3px;"></i>
    Módulos
</p>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <a href="index.php?action=demandas" class="text-decoration-none">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="rounded-3 bg-primary-subtle d-inline-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="ti ti-folder text-primary" aria-hidden="true" style="font-size: 20px;"></i>
                    </div>
                    <p class="fw-semibold mb-1 mt-3 text-dark">Demandas</p>
                    <p class="text-muted small mb-0">Controle de processos do setor</p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="index.php?action=licitacoes" class="text-decoration-none">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="rounded-3 bg-success-subtle d-inline-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="ti ti-gavel text-success" aria-hidden="true" style="font-size: 20px;"></i>
                    </div>
                    <p class="fw-semibold mb-1 mt-3 text-dark">Licitações</p>
                    <p class="text-muted small mb-0">Acompanhamento e economicidade</p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="index.php?action=orcamentos" class="text-decoration-none">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="rounded-3 bg-info-subtle d-inline-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="ti ti-calculator text-info" aria-hidden="true" style="font-size: 20px;"></i>
                    </div>
                    <p class="fw-semibold mb-1 mt-3 text-dark">Orçamentos</p>
                    <p class="text-muted small mb-0">Pesquisa de preço e vantajosidade de ata</p>
                </div>
            </div>
        </a>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-7">
        <div class="card shadow-sm h-100" style="border-top: 3px solid #1F3864;">
            <div class="card-body">
                <p class="text-muted small mb-1 text-uppercase" style="letter-spacing: 0.05em; font-size: 11px;">MT Par</p>
                <h4 class="mb-1">Bem-vindo, <?= htmlspecialchars(explode(' ', $servidorLogado->nome)[0]) ?></h4>
                <p class="text-muted small mb-3">Suas pendências em aberto:</p>

                <?php if (count($minhasPendenciasExibidas) === 0): ?>
                    <p class="text-muted small mb-0">
                        <i class="ti ti-mood-smile" aria-hidden="true" style="font-size: 14px; vertical-align: -1px;"></i>
                        Nenhuma pendência em seu nome.
                    </p>
                <?php else: ?>
                    <?php foreach ($minhasPendenciasExibidas as $indice => $pendencia): ?>
                        <?php
                        $corStatus = $coresStatus[$pendencia['status']] ?? 'text-secondary';
                        $ehUltimo = $indice === count($minhasPendenciasExibidas) - 1;
                        $iconeTipo = $pendencia['tipo'] === 'cotacao' ? 'ti-clipboard-list' : 'ti-folder';
                        ?>
                        <a href="<?= $pendencia['link'] ?>" class="text-decoration-none d-block">
                            <div class="d-flex align-items-center gap-3 py-2 <?= $ehUltimo ? '' : 'border-bottom' ?>">
                                <i class="ti <?= $iconeTipo ?> text-muted flex-shrink-0" aria-hidden="true" style="font-size: 15px;"></i>
                                <span class="small fst-italic flex-shrink-0 <?= $corStatus ?>" style="width: 180px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?= htmlspecialchars($pendencia['status']) ?>
                                </span>
                                <span class="flex-grow-1 text-dark"><?= htmlspecialchars($pendencia['numero_processo']) ?></span>
                                <span class="small text-muted flex-shrink-0"><?= date('d/m', strtotime($pendencia['data'])) ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if ($totalMinhasPendencias > count($minhasPendenciasExibidas)): ?>
                    <div class="text-end border-top pt-2 mt-2">
                        <a href="index.php?action=demandas" class="small fw-semibold text-decoration-none">
                            Ver todas as pendências
                            <i class="ti ti-chevron-right" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <p class="fw-semibold mb-3">
                    <i class="ti ti-users" aria-hidden="true" style="font-size: 16px; vertical-align: -2px; color: #6c757d;"></i>
                    Servidores do setor
                </p>
                <?php if (count($servidores) === 0): ?>
                    <p class="text-muted small mb-0">Nenhum servidor cadastrado.</p>
                <?php else: ?>
                    <?php foreach ($servidores as $indice => $servidor): ?>
                        <?php
                        $iniciais = mb_strtoupper(mb_substr($servidor->nome, 0, 1));
                        $corAvatar = $coresAvatar[$indice % count($coresAvatar)];
                        $ehUltimo = $indice === count($servidores) - 1;
                        ?>
                        <div class="d-flex align-items-center gap-3 py-2 <?= $ehUltimo ? '' : 'border-bottom' ?>">
                            <span class="rounded-circle <?= $corAvatar ?> d-flex align-items-center justify-content-center fw-semibold flex-shrink-0"
                                  style="width: 30px; height: 30px; font-size: 12px;">
                                <?= htmlspecialchars($iniciais) ?>
                            </span>
                            <div>
                                <p class="mb-0 small fw-medium"><?= htmlspecialchars($servidor->nome) ?></p>
                                <p class="mb-0" style="font-size: 11px; color: #6c757d;"><?= $servidor->cargo !== '' ? htmlspecialchars($servidor->cargo) : '—' ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>