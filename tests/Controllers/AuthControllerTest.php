<?php

namespace Tests\Controllers;

use AuthController;
use NivelAcesso;
use Servidor;
use Tests\DatabaseTestCase;

require_once __DIR__ . '/../../app/models/Servidor.php';
require_once __DIR__ . '/../../app/models/NivelAcesso.php';
require_once __DIR__ . '/../../app/controllers/AuthController.php';

/**
 * A rotina de login usa header() + exit; então em vez de chamar o
 * AuthController::login() diretamente (que sairia do processo), o teste
 * exercita as três funções privadas de rate-limit por reflexão. Elas são o
 * coração da proteção contra brute-force.
 */
final class AuthControllerTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // O AuthController usa REMOTE_ADDR pra compor o identificador.
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    }

    private function identificadorPara(AuthController $ctrl, string $usuario): string
    {
        $ref = new \ReflectionMethod($ctrl, 'identificadorTentativa');
        $ref->setAccessible(true);
        return $ref->invoke($ctrl, $usuario);
    }

    private function invocar(AuthController $ctrl, string $metodo, mixed ...$args): mixed
    {
        $ref = new \ReflectionMethod($ctrl, $metodo);
        $ref->setAccessible(true);
        return $ref->invoke($ctrl, ...$args);
    }

    public function testIdentificadorCombinaUsuarioNormalizadoComIp(): void
    {
        $ctrl = new AuthController();
        $ident = $this->identificadorPara($ctrl, '  FELIPPE  ');
        $this->assertSame('felippe|127.0.0.1', $ident);
    }

    public function testBloqueiaAposMaxTentativasNaJanela(): void
    {
        $ctrl = new AuthController();
        $ident = $this->identificadorPara($ctrl, 'alvo');

        // Antes de qualquer falha, não está bloqueado.
        $this->assertFalse($this->invocar($ctrl, 'estaBloqueado', $ident));

        // Registra MAX_TENTATIVAS falhas seguidas.
        for ($i = 0; $i < AuthController::MAX_TENTATIVAS; $i++) {
            $this->invocar($ctrl, 'registrarTentativa', $ident);
        }

        $this->assertTrue($this->invocar($ctrl, 'estaBloqueado', $ident));
    }

    public function testLimparTentativasDesbloqueiaImediatamente(): void
    {
        $ctrl = new AuthController();
        $ident = $this->identificadorPara($ctrl, 'alvo');

        for ($i = 0; $i < AuthController::MAX_TENTATIVAS; $i++) {
            $this->invocar($ctrl, 'registrarTentativa', $ident);
        }
        $this->assertTrue($this->invocar($ctrl, 'estaBloqueado', $ident));

        // Depois do login bem-sucedido, o controller limpa o contador
        // (regressão: usuário certo depois de várias tentativas não pode
        // ficar bloqueado sozinho).
        $this->invocar($ctrl, 'limparTentativas', $ident);
        $this->assertFalse($this->invocar($ctrl, 'estaBloqueado', $ident));
    }

    public function testTentativasDeOutroUsuarioNaoInterferem(): void
    {
        $ctrl = new AuthController();
        $identAlice = $this->identificadorPara($ctrl, 'alice');
        $identBob = $this->identificadorPara($ctrl, 'bob');

        for ($i = 0; $i < AuthController::MAX_TENTATIVAS; $i++) {
            $this->invocar($ctrl, 'registrarTentativa', $identAlice);
        }

        $this->assertTrue($this->invocar($ctrl, 'estaBloqueado', $identAlice));
        $this->assertFalse($this->invocar($ctrl, 'estaBloqueado', $identBob));
    }
}
