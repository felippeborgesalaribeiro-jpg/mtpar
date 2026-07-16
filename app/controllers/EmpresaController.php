<?php

require_once __DIR__ . '/../models/Empresa.php';
require_once __DIR__ . '/../helpers/auth.php';

class EmpresaController
{
    public function buscar(): void
    {
        exigirLogin();

        $query = trim($_GET['q'] ?? '');
        $empresas = Empresa::buscar($query);

        $resultado = array_map(fn(Empresa $empresa) => [
            'id' => $empresa->id,
            'nome' => $empresa->nome,
            'nomeFantasia' => $empresa->nomeFantasia,
            'cnpj' => $empresa->cnpj,
            'licitacoesHomologadas' => $empresa->contarLicitacoesHomologadas(),
        ], $empresas);

        $this->responderJson(['empresas' => $resultado]);
    }

    public function criar(): void
    {
        exigirLogin();

        $nome = trim($_POST['nome'] ?? '');
        $nomeFantasia = trim($_POST['nome_fantasia'] ?? '');
        $cnpj = Empresa::normalizarCnpj($_POST['cnpj'] ?? '');

        if ($nome === '') {
            $this->responderJson(['erro' => 'Nome é obrigatório.'], 422);
            return;
        }

        if (strlen($cnpj) !== 14) {
            $this->responderJson(['erro' => 'CNPJ inválido.'], 422);
            return;
        }

        if (Empresa::buscarPorCnpj($cnpj) !== null) {
            $this->responderJson(['erro' => 'Já existe uma empresa cadastrada com esse CNPJ.'], 422);
            return;
        }

        $empresa = new Empresa($nome, $cnpj, $nomeFantasia);
        $empresa->salvar();

        $this->responderJson([
            'empresa' => [
                'id' => $empresa->id,
                'nome' => $empresa->nome,
                'nomeFantasia' => $empresa->nomeFantasia,
                'cnpj' => $empresa->cnpj,
                'licitacoesHomologadas' => 0,
            ],
        ]);
    }

    private function responderJson(array $dados, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($dados);
        exit;
    }
}
