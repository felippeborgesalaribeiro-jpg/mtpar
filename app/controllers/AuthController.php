<?php

require_once __DIR__ . '/../models/Servidor.php';
require_once __DIR__ . '/../helpers/auth.php';

class AuthController
{
    public function mostrarLogin(): void
    {
        if (usuarioLogado() !== null) {
            header('Location: index.php?action=dashboard');
            exit;
        }

        $erro = $_GET['erro'] ?? null;

        require __DIR__ . '/../views/login.php';
    }

    public function login(): void
    {
        $usuario = trim($_POST['usuario'] ?? '');
        $senha = $_POST['senha'] ?? '';

        $servidor = Servidor::buscarPorUsuario($usuario);

        if ($servidor === null || !$servidor->verificarSenha($senha)) {
            header('Location: index.php?action=login&erro=1');
            exit;
        }

        efetuarLogin($servidor);

        header('Location: index.php?action=dashboard');
        exit;
    }

    public function logout(): void
    {
        efetuarLogout();
        header('Location: index.php?action=login');
        exit;
    }
}