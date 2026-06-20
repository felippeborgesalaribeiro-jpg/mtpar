<?php
$titulo = 'MT Par - Capa';
require __DIR__ . '/partials/header.php';
?>

<div class="card shadow-sm">
    <div class="card-body">
        <h5 class="card-title">Lotes do processo</h5>

        <table class="table table-bordered align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Lote</th>
                    <th>Itens</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lotes as $lote): ?>
                    <tr>
                        <td><?= htmlspecialchars($lote->numero) ?></td>
                        <td><?= count($lote->buscarItens()) ?></td>
                        <td>
                            <a class="btn btn-sm btn-primary" href="index.php?action=lote&id=<?= $lote->id ?>">
                                Abrir
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <form method="post" action="index.php?action=criar_lote">
            <button type="submit" class="btn btn-primary">+ Novo lote</button>
        </form>
    </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>