<?php

require_once __DIR__ . '/csrf.php';

/**
 * Gera um <form> POST inline com o botão "Excluir" (ou equivalente), já
 * incluindo o token CSRF e o confirm() de segurança. Usado para substituir
 * os antigos <a href="index.php?action=excluir_*"> (que faziam exclusão
 * por GET, vulnerável a pré-fetch do navegador e a CSRF).
 *
 * Exemplo:
 *   <?= botao_excluir_form('excluir_demanda', $demanda->id, 'Excluir esta demanda?') ?>
 *
 * O botão fica com aparência de <a> outline-danger e um ícone de lixeira, pra
 * casar visualmente com o restante da UI, que já usa botões pequenos.
 */
function botao_excluir_form(
    string $action,
    int $id,
    string $mensagemConfirmacao,
    string $rotulo = 'Excluir',
    string $classeBotao = 'btn btn-sm btn-outline-danger',
    array $paramsExtras = []
): string {
    $actionHtml = htmlspecialchars($action);
    $mensagemJs = htmlspecialchars($mensagemConfirmacao, ENT_QUOTES);
    $rotuloHtml = htmlspecialchars($rotulo);
    $classeHtml = htmlspecialchars($classeBotao);
    $csrfToken = htmlspecialchars(csrf_token());

    $inputsExtras = '';
    foreach ($paramsExtras as $nome => $valor) {
        $inputsExtras .= '<input type="hidden" name="' . htmlspecialchars($nome)
            . '" value="' . htmlspecialchars((string) $valor) . '">';
    }

    $textoBotao = $rotulo === '' ? '' : ' ' . $rotuloHtml;

    return <<<HTML
<form method="post" action="index.php?action={$actionHtml}" style="display:inline"
      onsubmit="return confirm('{$mensagemJs}');">
    <input type="hidden" name="id" value="{$id}">
    {$inputsExtras}
    <input type="hidden" name="csrf_token" value="{$csrfToken}">
    <button type="submit" class="{$classeHtml}">
        <i class="ti ti-trash" aria-hidden="true" style="font-size: 13px; vertical-align: -1px;"></i>{$textoBotao}
    </button>
</form>
HTML;
}
