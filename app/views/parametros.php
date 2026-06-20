<?php
$titulo = 'Parâmetros - MT Par';
require __DIR__ . '/partials/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="section-title">
        <i class="ti ti-list-details" aria-hidden="true" style="font-size: 20px; vertical-align: -3px;"></i>
        Parâmetros
    </span>
    <a href="index.php?action=dashboard" class="btn btn-sm btn-secondary">
        <i class="ti ti-arrow-left" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
        Voltar ao dashboard
    </a>
</div>

<div class="alert alert-info small">
    <i class="ti ti-info-circle" aria-hidden="true" style="font-size: 14px; vertical-align: -2px;"></i>
    Parâmetros marcados como "Preço público" ficam isentos da regra de inexequibilidade (Etapa 2 da análise 30%/70%).
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <h6 class="card-subtitle mb-3 text-muted">Cadastrar novo parâmetro</h6>
        <form method="post" action="index.php?action=criar_parametro" class="row g-2 align-items-center">
            <div class="col-md-7">
                <input type="text" name="nome" class="form-control" placeholder="Ex: Art. 46, I" required>
            </div>
            <div class="col-md-3">
                <div class="form-check">
                    <input type="checkbox" name="preco_publico" value="1" class="form-check-input" id="novoPrecoPublico">
                    <label class="form-check-label small" for="novoPrecoPublico">
                        Conta como preço público
                    </label>
                </div>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="ti ti-plus" aria-hidden="true" style="font-size: 14px; vertical-align: -1px;"></i>
                    Adicionar
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if (count($parametros) === 0): ?>
            <div class="empty-state">
                <i class="ti ti-list-details" aria-hidden="true"></i>
                <p class="mb-0">Nenhum parâmetro cadastrado ainda.</p>
            </div>
        <?php else: ?>
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Nome</th>
                        <th class="text-center">Preço público</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($parametros as $parametro): ?>
                        <tr>
                            <td><?= htmlspecialchars($parametro->nome) ?></td>
                            <td class="text-center">
                                <?php if ($parametro->precoPublico): ?>
                                    <span class="badge bg-warning text-dark">
                                        <i class="ti ti-shield-check" aria-hidden="true" style="font-size: 12px;"></i>
                                        Sim
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted small">Não</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                        data-bs-toggle="modal" data-bs-target="#modalEditarParametro<?= $parametro->id ?>">
                                    <i class="ti ti-edit" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
                                    Editar
                                </button>
                                <a href="index.php?action=excluir_parametro&id=<?= $parametro->id ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Excluir este parâmetro?')">
                                    <i class="ti ti-trash" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>
                                    Excluir
                                </a>
                            </td>
                        </tr>

                        <div class="modal fade" id="modalEditarParametro<?= $parametro->id ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="post" action="index.php?action=editar_parametro">
                                        <input type="hidden" name="parametro_id" value="<?= $parametro->id ?>">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Editar parâmetro</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">Nome</label>
                                                <input type="text" name="nome" class="form-control"
                                                       value="<?= htmlspecialchars($parametro->nome) ?>" required>
                                            </div>
                                            <div class="form-check">
                                                <input type="checkbox" name="preco_publico" value="1" class="form-check-input"
                                                       id="editPrecoPublico<?= $parametro->id ?>"
                                                       <?= $parametro->precoPublico ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="editPrecoPublico<?= $parametro->id ?>">
                                                    Conta como preço público (isenta de inexequibilidade)
                                                </label>
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
    </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>