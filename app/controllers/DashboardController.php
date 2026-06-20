<?php

require_once __DIR__ . '/../models/Cotacao.php';
require_once __DIR__ . '/../models/Servidor.php';

class DashboardController
{
    public function mostrar(): void
    {
        $cotacoes = Cotacao::buscarTodas();
        $servidores = Servidor::buscarTodos();

        require __DIR__ . '/../views/dashboard.php';
    }
}