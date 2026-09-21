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

        if (strlen($cnpj) !== 14) {
            $this->responderJson(['erro' => 'CNPJ inválido.'], 422);
            return;
        }

        // Paliativo pra desbloquear troca de empresa em Lote ja com Vencedor
        // definido: se o CNPJ ja existe, o botao "Cadastrar e usar neste lote"
        // devolve a empresa existente pra que o front selecione ela no lote,
        // em vez de recusar com "ja existe empresa com esse CNPJ" e travar o
        // usuario. Fluxo original de rejeitar duplicidade sera revisto depois
        // - por enquanto o efeito pratico e "cadastra se novo, seleciona se
        // ja existente".
        $empresaExistente = Empresa::buscarPorCnpj($cnpj);
        if ($empresaExistente !== null) {
            $this->responderJson([
                'empresa' => [
                    'id' => $empresaExistente->id,
                    'nome' => $empresaExistente->nome,
                    'nomeFantasia' => $empresaExistente->nomeFantasia,
                    'cnpj' => $empresaExistente->cnpj,
                    'licitacoesHomologadas' => $empresaExistente->contarLicitacoesHomologadas(),
                ],
            ]);
            return;
        }

        // Empresa realmente nova: aqui sim o nome e obrigatorio (razao social
        // fica no cadastro definitivo).
        if ($nome === '') {
            $this->responderJson(['erro' => 'Nome é obrigatório.'], 422);
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
