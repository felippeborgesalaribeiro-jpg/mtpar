<?php

require_once __DIR__ . '/../models/Licitacao.php';
require_once __DIR__ . '/../models/RelatorioLicitacao.php';
require_once __DIR__ . '/../helpers/auth.php';

class RelatoriosLicitacaoController
{
    public function mostrar(): void
    {
        exigirLogin();

        $licitacoes = Licitacao::buscarTodas();

        $porSetorDemandante = RelatorioLicitacao::porSetorDemandante($licitacoes);
        $porServidorResponsavel = RelatorioLicitacao::porServidorResponsavel($licitacoes);
        $porAno = RelatorioLicitacao::porAno($licitacoes);

        require __DIR__ . '/../views/relatorios_licitacao.php';
    }
}
