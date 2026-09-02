<?php

namespace Tests\Helpers;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/helpers/csrf.php';

final class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === \PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_SERVER['HTTP_X_CSRF_TOKEN']);
    }

    protected function tearDown(): void
    {
        if (session_status() === \PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
    }

    public function testCsrfTokenEhGeradoUmaVezEReaproveitadoNaMesmaSessao(): void
    {
        $t1 = \csrf_token();
        $t2 = \csrf_token();
        $this->assertSame($t1, $t2);
        // Tem que ser aleatório longo (hex de 32 bytes = 64 chars).
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $t1);
    }

    public function testCsrfInputEmbrulhaOTokenEscapadoNumInputHidden(): void
    {
        $html = \csrf_input();
        $this->assertStringContainsString('name="csrf_token"', $html);
        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringContainsString(\csrf_token(), $html);
    }

    public function testExigirCsrfDeixaPassarRequisicaoGet(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        \exigir_csrf(); // não deve fazer nada
        $this->addToAssertionCount(1);
    }

    public function testExigirCsrfAceitaPostComTokenValido(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['csrf_token'] = \csrf_token();
        \exigir_csrf();
        $this->addToAssertionCount(1);
    }

    public function testExigirCsrfRecusaPostComTokenErrado(): void
    {
        // Roda num subprocesso pq exigir_csrf() chama exit em caso de falha.
        // O echo antes do exit imprime a mensagem, e olhamos por ela na saida.
        $csrfPath = realpath(__DIR__ . '/../../app/helpers/csrf.php');
        $script = <<<PHP
<?php
\$_SERVER['REQUEST_METHOD'] = 'POST';
\$_POST['csrf_token'] = 'valor-invalido';
require '{$csrfPath}';
\$_SESSION['csrf_token'] = 'token-real';
exigir_csrf();
echo 'NAO_PROTEGIDO';
PHP;
        $tmp = tempnam(sys_get_temp_dir(), 'csrf');
        file_put_contents($tmp, $script);
        $resultado = shell_exec("php {$tmp} 2>&1");
        unlink($tmp);

        // Confirma que exigir_csrf abortou (nunca chegou no echo final) e
        // que a mensagem de recusa foi impressa antes do exit.
        $this->assertStringNotContainsString('NAO_PROTEGIDO', (string) $resultado);
        $this->assertStringContainsString('inválida', (string) $resultado);
    }
}
