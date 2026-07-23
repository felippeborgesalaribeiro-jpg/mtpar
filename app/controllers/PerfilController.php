<?php

require_once __DIR__ . '/../models/Servidor.php';
require_once __DIR__ . '/../helpers/auth.php';

class PerfilController
{
    public function mostrar(): void
    {
        $servidorLogado = exigirLogin();

        require __DIR__ . '/../views/perfil.php';
    }

    public function atualizar(): void
    {
        $servidorLogado = exigirLogin();

        $nome = trim($_POST['nome'] ?? '');
        $matricula = trim($_POST['matricula'] ?? '');
        $cargo = trim($_POST['cargo'] ?? '');
        $usuario = trim($_POST['usuario'] ?? '');
        $novaSenha = $_POST['nova_senha'] ?? '';

        if ($nome === '' || $usuario === '') {
            echo 'Nome e usuário são obrigatórios.';
            return;
        }

        $existente = Servidor::buscarPorUsuario($usuario);
        if ($existente !== null && $existente->id !== $servidorLogado->id) {
            $_SESSION['erro'] = "Já existe outro servidor cadastrado com o usuário <strong>{$usuario}</strong>.";
            header('Location: index.php?action=perfil');
            exit;
        }

        $servidorLogado->nome = $nome;
        $servidorLogado->matricula = $matricula;
        $servidorLogado->cargo = $cargo;
        $servidorLogado->usuario = $usuario;

        if ($novaSenha !== '') {
            $servidorLogado->definirSenha($novaSenha);
            $servidorLogado->senhaProvisoria = false;
        }

        $servidorLogado->salvar();

        header('Location: index.php?action=perfil&sucesso=1');
        exit;
    }
}