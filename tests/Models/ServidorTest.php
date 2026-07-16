<?php

namespace Tests\Models;

use Servidor;
use Tests\DatabaseTestCase;

require_once __DIR__ . '/../../app/models/Servidor.php';

final class ServidorTest extends DatabaseTestCase
{
    public function testSalvarAtribuiIdEBuscarPorIdRetornaOMesmoRegistro(): void
    {
        $servidor = new Servidor('Ana Souza', '1234', 'Analista', 'ana', '', Servidor::NIVEL_COMUM);
        $id = $servidor->salvar();

        $this->assertGreaterThan(0, $id);

        $encontrado = Servidor::buscarPorId($id);

        $this->assertNotNull($encontrado);
        $this->assertSame('Ana Souza', $encontrado->nome);
        $this->assertSame('ana', $encontrado->usuario);
        $this->assertFalse($encontrado->ehAdmin());
    }

    public function testBuscarPorIdRetornaNullQuandoNaoExiste(): void
    {
        $this->assertNull(Servidor::buscarPorId(999));
    }

    public function testDefinirSenhaEVerificarSenha(): void
    {
        $servidor = new Servidor('Marcos Lima');
        $servidor->definirSenha('minhaSenha123');

        $this->assertTrue($servidor->verificarSenha('minhaSenha123'));
        $this->assertFalse($servidor->verificarSenha('senhaErrada'));
    }

    public function testResetarSenhaPadraoDefineComo123EPersiste(): void
    {
        $servidor = new Servidor('Tayná Ribeiro', usuario: 'tayna');
        $servidor->salvar();

        $servidor->resetarSenhaPadrao();

        $recarregado = Servidor::buscarPorId($servidor->id);
        $this->assertTrue($recarregado->verificarSenha('123'));
    }

    public function testEhAdminDistingueNivelDeAcesso(): void
    {
        $admin = new Servidor('Felippe Alaribeiro', nivelAcesso: Servidor::NIVEL_ADMIN);
        $comum = new Servidor('Vivianne Costa', nivelAcesso: Servidor::NIVEL_COMUM);

        $this->assertTrue($admin->ehAdmin());
        $this->assertFalse($comum->ehAdmin());
    }

    public function testBuscarPorUsuarioEncontraPeloCampoUsuario(): void
    {
        $servidor = new Servidor('Ana Souza', usuario: 'ana.souza');
        $servidor->salvar();

        $encontrado = Servidor::buscarPorUsuario('ana.souza');

        $this->assertNotNull($encontrado);
        $this->assertSame($servidor->id, $encontrado->id);
        $this->assertNull(Servidor::buscarPorUsuario('nao-existe'));
    }
}
