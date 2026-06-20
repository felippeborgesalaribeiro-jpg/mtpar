<?php

require_once __DIR__ . '/../models/Cotacao.php';
require_once __DIR__ . '/../models/Servidor.php';
require_once __DIR__ . '/../helpers/auth.php';

class DashboardController
{
    public function mostrar(): void
    {
        $servidorLogado = exigirLogin();

        $cotacoes = Cotacao::buscarTodas();
        $servidores = Servidor::buscarTodos();

        require __DIR__ . '/../views/dashboard.php';
    }
}