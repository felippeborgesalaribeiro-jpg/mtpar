<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $titulo ?? 'MT Par' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark" style="background-color: #1F3864;">
    <div class="container-fluid">
        <a class="navbar-brand mb-0 h1" href="index.php?action=dashboard" style="color: white; text-decoration: none;">
            <i class="ti ti-building-bank" aria-hidden="true" style="font-size: 18px; vertical-align: -2px; margin-right: 6px;"></i>
            MT Participações e Projetos S.A. — MT Par
        </a>
        <div>
            <a href="index.php?action=parametros" class="btn btn-sm btn-outline-light">
                <i class="ti ti-list-details" aria-hidden="true" style="font-size: 14px; vertical-align: -1px;"></i>
                Parâmetros
            </a>
            <a href="index.php?action=servidores" class="btn btn-sm btn-outline-light">
                <i class="ti ti-users" aria-hidden="true" style="font-size: 14px; vertical-align: -1px;"></i>
                Servidores
            </a>
        </div>
    </div>
</nav>

<div class="container py-4">