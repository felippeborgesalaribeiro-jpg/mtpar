<?php
/**
 * Datalist com os setores demandantes cadastrados (cadastro em
 * Administração → "Setores Demandantes"). Serve como sugestão pros
 * campos <input name="setor_demandante"> das telas de cadastro/edição.
 *
 * Espera $setoresDemandantes já disponível no escopo da view (uma lista
 * de SetorDemandante). Só emite o <datalist> uma vez por página - se
 * incluir duas vezes na mesma tela, use IDs distintos.
 */
?>
<datalist id="listaSetoresDemandantes">
    <?php foreach (($setoresDemandantes ?? []) as $setor): ?>
        <option value="<?= htmlspecialchars($setor->nome) ?>">
    <?php endforeach; ?>
</datalist>
