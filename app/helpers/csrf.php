<?php

require_once __DIR__ . '/auth.php';

/**
 * Proteção contra CSRF (Cross-Site Request Forgery).
 *
 * Como funciona:
 *  - Cada sessão de usuário guarda um token aleatório (gerado uma única vez
 *    e reutilizado até o logout). O mesmo token vale para todos os formulários
 *    daquela sessão.
 *  - Todo formulário que escreve/altera dados (POST) precisa incluir esse
 *    token num campo escondido, usando csrf_input().
 *  - O controller que recebe o POST chama exigir_csrf() bem no início. Se o
 *    token não bater com o da sessão, a requisição é recusada com 403.
 *
 * Isso impede que uma página externa (ou uma aba maliciosa aberta em paralelo)
 * consiga forçar um POST em nome de um usuário já logado, porque essa página
 * não tem como saber qual é o token da sessão dele.
 *
 * NOTA sobre a Etapa 3: enquanto o CSRF ainda estiver sendo aplicado
 * gradualmente pelos formulários, exigir_csrf() ACEITA POSTs sem token e
 * apenas deixa passar em modo de compatibilidade. Uma vez que todos os
 * formulários tenham o campo csrf_input() incluído, basta trocar a linha
 * marcada abaixo pra recusar POSTs sem token de vez.
 */

function csrf_token(): string
{
    iniciarSessao();

    if (empty($_SESSION['csrf_token'])) {
        // 32 bytes = 256 bits de entropia, mais que suficiente.
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * HTML pronto pra colar dentro de um <form>. Ex.:
 *   <form method="post" action="...">
 *       <?= csrf_input() ?>
 *       ...
 *   </form>
 */
function csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

/**
 * Chame no COMEÇO de todo endpoint que processa POST/PUT/DELETE. Se o token
 * enviado (via _POST ou header X-CSRF-Token) não bater com o da sessão,
 * devolve 403 e termina a requisição.
 */
function exigir_csrf(): void
{
    // So exige em requisicoes que modificam estado.
    $metodo = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    if (!in_array($metodo, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        return;
    }

    iniciarSessao();

    $enviado = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $esperado = $_SESSION['csrf_token'] ?? '';

    // Modo compatibilidade: enquanto ha formularios sem o campo, deixa
    // passar POSTs sem token. Assim que todo formulario POST for atualizado,
    // remover este bloco (o proximo `if` ja cobre a checagem real).
    if ($enviado === '') {
        return;
    }

    if ($esperado === '' || !hash_equals($esperado, (string) $enviado)) {
        http_response_code(403);
        echo 'Sessão inválida ou expirada. Volte, atualize a página e envie o formulário de novo.';
        exit;
    }
}
