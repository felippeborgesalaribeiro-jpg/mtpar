<?php

require_once __DIR__ . '/../helpers/auth.php';

class OrcamentoController
{
    public function listar(): void
    {
        exigirLogin();

        require __DIR__ . '/../views/orcamentos.php';
    }
}