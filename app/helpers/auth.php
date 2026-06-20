<?php

require_once __DIR__ . '/../models/Servidor.php';

function iniciarSessao(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 60 * 60 * 24 * 30,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function usuarioLogado(): ?Servidor
{
    iniciarSessao();

    if (!isset($_SESSION['servidor_id'])) {
        return null;
    }

    return Servidor::buscarPorId((int) $_SESSION['servidor_id']);
}

function efetuarLogin(Servidor $servidor): void
{
    iniciarSessao();
    $_SESSION['servidor_id'] = $servidor->id;
}

function efetuarLogout(): void
{
    iniciarSessao();
    session_unset();
    session_destroy();
}

function exigirLogin(): Servidor
{
    $servidor = usuarioLogado();

    if ($servidor === null) {
        header('Location: index.php?action=login');
        exit;
    }

    return $servidor;
}

function exigirAdmin(): Servidor
{
    $servidor = exigirLogin();

    if (!$servidor->ehAdmin()) {
        echo 'Acesso restrito ao administrador.';
        exit;
    }

    return $servidor;
}