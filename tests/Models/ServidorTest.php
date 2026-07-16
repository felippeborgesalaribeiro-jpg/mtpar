<?php

namespace Tests\Models;

use NivelAcesso;
use Servidor;
use Tests\DatabaseTestCase;

require_once __DIR__ . '/../../app/models/Servidor.php';
require_once __DIR__ . '/../../app/models/NivelAcesso.php';

final class ServidorTest extends DatabaseTestCase
{
    public function testSalvarAtribuiIdEBuscarPorIdRetornaOMesmoRegistro(): void
    {
        $servidor = new Servidor('Ana Souza', '1234', 'Analista', 'ana', '', NivelAcesso::Comum);
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
        $admin = new Servidor('Felippe Alaribeiro', nivelAcesso: NivelAcesso::Admin);
        $comum = new Servidor('Vivianne Costa', nivelAcesso: NivelAcesso::Comum);

        $this->assertTrue($admin->ehAdmin());
        $this->assertFalse($comum->ehAdmin());
    }

    public function testFromArrayIgnoraValorInvalidoDeNivelAcesso(): void
    {
        // Simula uma linha do banco com um nivel_acesso corrompido/desconhecido:
        // o model deve cair para NivelAcesso::Comum em vez de quebrar.
        $servidor = new Servidor('Usuário sem nível', usuario: 'sem-nivel');
        $servidor->salvar();

        \Database::getConnection()
            ->prepare('UPDATE servidores SET nivel_acesso = :valor WHERE id = :id')
            ->execute(['valor' => 'NAO_EXISTE', 'id' => $servidor->id]);

        $recarregado = Servidor::buscarPorId($servidor->id);

        $this->assertSame(NivelAcesso::Comum, $recarregado->nivelAcesso);
        $this->assertFalse($recarregado->ehAdmin());
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
