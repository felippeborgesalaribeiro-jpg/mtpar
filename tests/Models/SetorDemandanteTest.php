<?php

namespace Tests\Models;

use SetorDemandante;
use Tests\DatabaseTestCase;

require_once __DIR__ . '/../../app/models/SetorDemandante.php';

final class SetorDemandanteTest extends DatabaseTestCase
{
    public function testSalvarEBuscarPorNomeEPorId(): void
    {
        $setor = new SetorDemandante('Setor de TI');
        $setor->salvar();

        $this->assertNotNull($setor->id);
        $this->assertSame('Setor de TI', SetorDemandante::buscarPorId($setor->id)->nome);
        $this->assertSame($setor->id, SetorDemandante::buscarPorNome('Setor de TI')->id);
        $this->assertNull(SetorDemandante::buscarPorNome('Setor que não existe'));
    }

    public function testBuscarTodosOrdenaPorNome(): void
    {
        (new SetorDemandante('Setor de TI'))->salvar();
        (new SetorDemandante('Diretoria Financeira'))->salvar();

        $nomes = array_map(fn(SetorDemandante $s) => $s->nome, SetorDemandante::buscarTodos());

        $this->assertSame(['Diretoria Financeira', 'Setor de TI'], $nomes);
    }

    public function testExcluirRemoveDaLista(): void
    {
        $setor = new SetorDemandante('Setor de TI');
        $setor->salvar();

        $setor->excluir();

        $this->assertNull(SetorDemandante::buscarPorId($setor->id));
        $this->assertCount(0, SetorDemandante::buscarTodos());
    }
}
