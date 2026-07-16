<?php
require_once __DIR__ . '/../../helpers/auth.php';
$servidorLogado = usuarioLogado();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $titulo ?? 'MT Par' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark" style="background-color: #1F3864;">
    <div class="container-fluid">
        <a class="navbar-brand mb-0 h1" href="index.php?action=dashboard" style="color: white; text-decoration: none;">
            <i class="ti ti-building-bank" aria-hidden="true" style="font-size: 18px; vertical-align: -2px; margin-right: 6px;"></i>
            MT Participações e Projetos S.A. — MT Par
        </a>
        <div class="d-flex align-items-center gap-2">
            <a href="index.php?action=parametros" class="btn btn-sm btn-outline-light">
                <i class="ti ti-list-details" aria-hidden="true" style="font-size: 14px; vertical-align: -1px;"></i>
                Parâmetros
            </a>
            <?php if ($servidorLogado !== null && $servidorLogado->ehAdmin()): ?>
                <a href="index.php?action=servidores" class="btn btn-sm btn-outline-light">
                    <i class="ti ti-users" aria-hidden="true" style="font-size: 14px; vertical-align: -1px;"></i>
                    Servidores
                </a>
                <a href="index.php?action=admin" class="btn btn-sm btn-outline-light">
                    <i class="ti ti-shield-lock" aria-hidden="true" style="font-size: 14px; vertical-align: -1px;"></i>
                    Administração
                </a>
            <?php endif; ?>
            <?php if ($servidorLogado !== null): ?>
                <a href="index.php?action=perfil" class="btn btn-sm btn-outline-light">
                    <i class="ti ti-user-circle" aria-hidden="true" style="font-size: 14px; vertical-align: -1px;"></i>
                    <?= htmlspecialchars($servidorLogado->nome) ?>
                </a>
                <a href="index.php?action=logout" class="btn btn-sm btn-outline-light">
                    <i class="ti ti-logout" aria-hidden="true" style="font-size: 14px; vertical-align: -1px;"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container py-4">