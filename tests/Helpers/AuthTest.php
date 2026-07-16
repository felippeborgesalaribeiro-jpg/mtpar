<?php

namespace Tests\Helpers;

use Servidor;
use Tests\DatabaseTestCase;

require_once __DIR__ . '/../../app/helpers/auth.php';

final class AuthTest extends DatabaseTestCase
{
    protected function tearDown(): void
    {
        if (session_status() === \PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }

        parent::tearDown();
    }

    public function testCicloCompletoDeLoginELogout(): void
    {
        $servidor = new Servidor('Ana Souza');
        $servidor->salvar();

        // Ninguem logado no inicio.
        $this->assertNull(\usuarioLogado());

        // Apos login, usuarioLogado() e exigirLogin() devolvem o mesmo servidor.
        \efetuarLogin($servidor);

        $logado = \usuarioLogado();
        $this->assertNotNull($logado);
        $this->assertSame($servidor->id, $logado->id);

        $exigido = \exigirLogin();
        $this->assertSame($servidor->id, $exigido->id);

        // Apos logout, a sessao e limpa.
        \efetuarLogout();
        $this->assertNull(\usuarioLogado());
    }
}
